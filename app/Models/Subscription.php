<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'plan_type',
        'discount_percent',
        'service_summary',
        'base_price',
        'discounted_price',
        'preferred_day',
        'preferred_time_slot',
        'start_date',
        'next_service_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_service_date' => 'date',
        'base_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
