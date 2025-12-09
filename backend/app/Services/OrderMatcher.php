<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderMatcher
{
    const FEE_RATE = 0.015; // 1.5%

    public function match(Order $order): ?Trade
    {
        if ($order->status !== Order::STATUS_OPEN) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            if ($order->side === 'buy') {
                $counter = Order::where('symbol', $order->symbol)
                    ->where('side', 'sell')
                    ->where('status', Order::STATUS_OPEN)
                    ->where('price', '<=', $order->price)
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->first();
            } else { // sell
                $counter = Order::where('symbol', $order->symbol)
                    ->where('side', 'buy')
                    ->where('status', Order::STATUS_OPEN)
                    ->where('price', '>=', $order->price)
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $counter) {
                return null; // no match
            }

            // FULL MATCH ONLY: amounts must be equal
            if ((float) $order->amount !== (float) $counter->amount) {
                // in a real exchange we'd do partial, but spec says "no partial"
                return null;
            }

            // Determine roles
            $buyOrder  = $order->side === 'buy' ? $order : $counter;
            $sellOrder = $order->side === 'sell' ? $order : $counter;

            /** @var User $buyer */
            $buyer = $buyOrder->user()->lockForUpdate()->first();
            /** @var User $seller */
            $seller = $sellOrder->user()->lockForUpdate()->first();

            $amount = $buyOrder->amount;
            // Trade price – we use SELL order price here
            $price  = $sellOrder->price;
            $volume = bcmul($amount, $price, 8);
            $fee    = bcmul($volume, self::FEE_RATE, 8);
            $totalCost = bcadd($volume, $fee, 8);

            // Buyer had locked_usd at creation; here we adjust and refund leftover
            if (bccomp($buyOrder->locked_usd, $totalCost, 8) < 0) {
                // Should not happen if we validated properly on create
                throw new \RuntimeException('Insufficient locked USD for buyer.');
            }

            $refund = bcsub($buyOrder->locked_usd, $totalCost, 8);
            $buyer->balance = bcadd($buyer->balance, $refund, 8); // refund leftover
            $buyer->save();

            // Credit seller with trade volume
            $seller->balance = bcadd($seller->balance, $volume, 8);
            $seller->save();

            // Update buyer asset balance
            $buyerAsset = Asset::firstOrCreate([
                'user_id' => $buyer->id,
                'symbol'  => $buyOrder->symbol,
            ]);

            $buyerAsset->amount = bcadd($buyerAsset->amount, $amount, 8);
            $buyerAsset->save();

            // Update seller asset (release locked_amount)
            $sellerAsset = Asset::where('user_id', $seller->id)
                ->where('symbol', $sellOrder->symbol)
                ->lockForUpdate()
                ->firstOrFail();

            $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, 8);
            $sellerAsset->save();

            // Set orders to filled
            $buyOrder->status  = Order::STATUS_FILLED;
            $sellOrder->status = Order::STATUS_FILLED;
            $buyOrder->save();
            $sellOrder->save();

            // Record a trade
            $trade = Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id'=> $sellOrder->id,
                'symbol'       => $buyOrder->symbol,
                'price'        => $price,
                'amount'       => $amount,
                'volume_usd'   => $volume,
                'fee_usd'      => $fee,
            ]);

            // Broadcast to both users
            broadcast(new OrderMatched($trade))->toOthers();

            return $trade;
        });
    }
}
