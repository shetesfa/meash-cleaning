<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'order_id',
        'customer_id',
        'amount',
        'payment_method',
        'reference_number',
        'payment_date',
        'recorded_by_user_id',
        'receipt_path',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public static function generateNextNumber(): string
    {
        $last = static::orderByDesc('id')->first();
        $nextId = $last ? ($last->id + 1) : 1;
        return 'PAY-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected static function booted()
    {
        static::saved(function (Payment $payment) {
            $order = $payment->order;
            if ($order) {
                $totalPaid = $order->payments()->sum('amount');
                if ($totalPaid >= (float)$order->total && (float)$order->total > 0) {
                    $order->payment_status = 'paid';
                } elseif ($totalPaid > 0) {
                    $order->payment_status = 'partially_paid';
                } else {
                    $order->payment_status = 'unpaid';
                }
                $order->save();
            }
        });
    }
}
