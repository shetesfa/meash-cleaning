<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'full_name',
        'phone',
        'alt_phone',
        'address',
        'subcity',
        'woreda',
        'house_no',
        'landmark',
        'latitude',
        'longitude',
        'customer_type',
        'notes',
        'preferred_contact_method',
        'telegram_user_id',
        'marketing_consent',
        'consent_timestamp',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'marketing_consent' => 'boolean',
        'consent_timestamp' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = static::generateNextCode();
            }
        });
    }

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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->latest('appointment_date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class)->latest('due_date');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class)->latest();
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class)->latest();
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByDesc('id')->first();
        $nextId = $last ? ($last->id + 1) : 1;
        return 'MEASH-C' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }
}
