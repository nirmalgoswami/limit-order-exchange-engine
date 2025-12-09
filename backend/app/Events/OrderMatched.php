<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use SerializesModels;

    public Trade $trade;

    public function __construct(Trade $trade)
    {
        $this->trade = $trade->load('buyOrder.user', 'sellOrder.user');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->trade->buyOrder->user_id),
            new PrivateChannel('user.' . $this->trade->sellOrder->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderMatched';
    }

    public function broadcastWith(): array
    {
        return [
            'trade' => [
                'id'          => $this->trade->id,
                'symbol'      => $this->trade->symbol,
                'price'       => $this->trade->price,
                'amount'      => $this->trade->amount,
                'volume_usd'  => $this->trade->volume_usd,
                'fee_usd'     => $this->trade->fee_usd,
                'created_at'  => $this->trade->created_at->toIso8601String(),
            ],
            'buy_order' => [
                'id'     => $this->trade->buyOrder->id,
                'status' => $this->trade->buyOrder->status,
            ],
            'sell_order' => [
                'id'     => $this->trade->sellOrder->id,
                'status' => $this->trade->sellOrder->status,
            ],
        ];
    }
}
