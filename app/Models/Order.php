<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'created_by_user_id',
        'assigned_team_id',
        'source',
        'order_status',
        'payment_status',
        'subtotal',
        'discount',
        'tax',
        'total',
        'appointment_date',
        'appointment_time_slot',
        'address',
        'subcity',
        'woreda',
        'house_no',
        'landmark',
        'latitude',
        'longitude',
        'notes',
        'completion_notes',
        'cancellation_reason',
        'completed_at',
        'version',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'appointment_date' => 'date',
        'completed_at' => 'datetime',
        'version' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function getGoogleMapsUrlAttribute(): ?string
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        if ($this->address) {
            $encoded = urlencode("Addis Ababa, " . $this->address . ($this->subcity ? ", " . $this->subcity : ''));
            return "https://www.google.com/maps/search/?api=1&query={$encoded}";
        }
        return null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(CleaningTeam::class, 'assigned_team_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
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
        return 'MEASH-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}
