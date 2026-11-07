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

    protected $appends = [
        'name',
        'google_maps_url',
    ];

    public function getNameAttribute(): string
    {
        return $this->full_name ?? '';
    }

    protected static function booted(): void
    {
        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = static::generateNextCode();
            }
        });

        static::saved(function ($customer) {
            static::syncToBroadcastList($customer);
        });
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) return null;
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '251')) {
            return '0' . substr($digits, 3);
        }
        if (str_starts_with($digits, '09') || str_starts_with($digits, '07')) {
            return $digits;
        }
        if (strlen($digits) === 9 && (str_starts_with($digits, '9') || str_starts_with($digits, '7'))) {
            return '0' . $digits;
        }
        return $digits;
    }

    public static function syncToBroadcastList(Customer $customer, ?string $source = null, ?int $telegramChatId = null): ?TelegramUser
    {
        try {
            $cleanPhone = self::normalizePhone($customer->phone);
            $altPhone = self::normalizePhone($customer->alt_phone);
            $chatId = $telegramChatId ?: (!empty($customer->telegram_user_id) && is_numeric($customer->telegram_user_id) ? (int)$customer->telegram_user_id : null);

            $tgUser = null;
            if ($chatId) {
                $tgUser = TelegramUser::where('telegram_id', $chatId)->first();
            }

            if (!$tgUser && !empty($cleanPhone)) {
                $suffix = substr($cleanPhone, -9);
                $tgUser = TelegramUser::where(function ($q) use ($cleanPhone, $suffix) {
                    $q->where('phone_number', $cleanPhone)
                      ->orWhere('phone_number', 'like', "%{$suffix}");
                })->first();
            }

            $determinedSource = $source ?: ($tgUser?->source ?: 'website_customer');

            if ($tgUser) {
                // Update while strictly preserving friend is_excluded status!
                $tgUser->first_name = $customer->full_name ?: $tgUser->first_name;
                if (!empty($cleanPhone)) {
                    $tgUser->phone_number = $cleanPhone;
                }
                if ($chatId && empty($tgUser->telegram_id)) {
                    $tgUser->telegram_id = $chatId;
                }
                $tgUser->is_active = true;
                $tgUser->source = $determinedSource;
                $tgUser->marketing_consent = true;
                $tgUser->last_interaction_at = now();
                $tgUser->save();
                return $tgUser;
            }

            // Create new record with real numeric telegram_id or NULL (never fake numeric ID!)
            return TelegramUser::create([
                'telegram_id' => $chatId ?: null,
                'first_name' => $customer->full_name ?: 'ክቡር ደንበኛ',
                'phone_number' => $cleanPhone ?: $altPhone,
                'is_active' => true,
                'is_excluded' => false,
                'source' => $determinedSource,
                'marketing_consent' => true,
                'last_interaction_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Customer::syncToBroadcastList failed: ' . $e->getMessage());
            return null;
        }
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
