<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_id',
        'item_name',
        'quantity',
        'unit_price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    protected static function booted()
    {
        static::saving(function (OrderItem $item) {
            $item->subtotal = (float)$item->quantity * (float)$item->unit_price;
        });

        static::saved(function (OrderItem $item) {
            $item->order?->recalculateTotals();
        });

        static::deleted(function (OrderItem $item) {
            $item->order?->recalculateTotals();
        });
    }
}
