<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'phone_number',
        'language_code',
        'bot_state',
        'payload_cache',
        'is_excluded',
        'is_active',
        'source',
        'tags',
        'lifecycle_stage',
        'marketing_consent',
        'consent_timestamp',
        'last_interaction_at',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'payload_cache' => 'array',
        'is_excluded' => 'boolean',
        'is_active' => 'boolean',
        'marketing_consent' => 'boolean',
        'consent_timestamp' => 'datetime',
        'last_interaction_at' => 'datetime',
    ];

    protected $appends = [
        'name',
        'phone',
    ];

    public function getNameAttribute(): string
    {
        $fullName = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        if (!empty($fullName)) {
            return $fullName;
        }
        if (!empty($this->username)) {
            return '@' . ltrim($this->username, '@');
        }
        return 'ተጠቃሚ #' . $this->id;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->phone_number;
    }
}
