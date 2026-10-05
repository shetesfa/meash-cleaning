<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proforma extends Model
{
    use HasFactory;

    protected $fillable = [
        'proforma_number',
        'organization_id',
        'sales_visit_id',
        'prepared_by_user_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'validity_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'validity_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function salesVisit(): BelongsTo
    {
        return $this->belongsTo(SalesVisit::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaItem::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total = max(0, ($subtotal - (float)$this->discount) + (float)$this->tax);
        $this->save();
    }

    public static function generateNextNumber(): string
    {
        $last = static::orderByDesc('id')->first();
        $nextId = $last ? ($last->id + 1) : 1;
        return 'PRF-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }
}
