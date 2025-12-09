<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Order;
use App\Services\OrderMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $symbol = $request->query('symbol');
        $query = Order::query()->where('status', Order::STATUS_OPEN);

        if ($symbol) {
            $query->where('symbol', $symbol);
        }

        $orders = $query->orderBy('price', 'desc')->get();

        return $orders;
    }

    public function store(Request $request, OrderMatcher $matcher)
    {
        $data = $request->validate([
            'symbol' => 'required|string|in:BTC,ETH',
            'side'   => 'required|string|in:buy,sell',
            'price'  => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($data, $user, $matcher) {
            $price  = $data['price'];
            $amount = $data['amount'];

            if ($data['side'] === 'buy') {
                $feeRate = OrderMatcher::FEE_RATE;
                $required = $amount * $price * (1 + $feeRate);

                if ($user->balance < $required) {
                    return response()->json(['message' => 'Insufficient USD balance'], 422);
                }

                $user->balance -= $required;
                $user->save();

                $order = Order::create([
                    'user_id'    => $user->id,
                    'symbol'     => $data['symbol'],
                    'side'       => 'buy',
                    'price'      => $price,
                    'amount'     => $amount,
                    'status'     => Order::STATUS_OPEN,
                    'locked_usd' => $required,
                ]);

            } else { // sell
                $asset = Asset::firstOrCreate([
                    'user_id' => $user->id,
                    'symbol'  => $data['symbol'],
                ]);

                if ($asset->amount < $amount) {
                    return response()->json(['message' => 'Insufficient asset balance'], 422);
                }

                $asset->amount        -= $amount;
                $asset->locked_amount += $amount;
                $asset->save();

                $order = Order::create([
                    'user_id'    => $user->id,
                    'symbol'     => $data['symbol'],
                    'side'       => 'sell',
                    'price'      => $price,
                    'amount'     => $amount,
                    'status'     => Order::STATUS_OPEN,
                    'locked_usd' => 0,
                ]);
            }

            // Try to match immediately
            $trade = $matcher->match($order);

            return response()->json([
                'order' => $order->fresh(),
                'trade' => $trade,
            ], 201);
        });
    }

    public function cancel(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id || $order->status !== Order::STATUS_OPEN) {
            return response()->json(['message' => 'Cannot cancel this order'], 403);
        }

        return DB::transaction(function () use ($order, $user) {
            if ($order->side === 'buy') {
                // Refund locked USD
                $user->balance += $order->locked_usd;
                $user->save();

                $order->status    = Order::STATUS_CANCELLED;
                $order->locked_usd = 0;
                $order->save();
            } else { // sell
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->first();

                if ($asset) {
                    $asset->amount        += $order->amount;
                    $asset->locked_amount -= $order->amount;
                    $asset->save();
                }

                $order->status = Order::STATUS_CANCELLED;
                $order->save();
            }

            return $order;
        });
    }

    public function userOrders(Request $request)
    {
        $user = $request->user();

        $orders = $user->orders()
            ->orderByDesc('created_at')
            ->get();

        return $orders;
    }
}
