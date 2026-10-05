<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proforma_id',
        'service_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    protected static function booted()
    {
        static::saving(function (ProformaItem $item) {
            $item->subtotal = (float)$item->quantity * (float)$item->unit_price;
        });

        static::saved(function (ProformaItem $item) {
            $item->proforma?->recalculateTotals();
        });

        static::deleted(function (ProformaItem $item) {
            $item->proforma?->recalculateTotals();
        });
    }
}
