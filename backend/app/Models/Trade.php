<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = [
        'buy_order_id', 'sell_order_id', 'symbol', 'price', 'amount',
        'volume_usd', 'fee_usd',
    ];

    protected $casts = [
        'price'      => 'decimal:8',
        'amount'     => 'decimal:8',
        'volume_usd' => 'decimal:8',
        'fee_usd'    => 'decimal:8',
    ];

    public function buyOrder()
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    public function sellOrder()
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }
}
