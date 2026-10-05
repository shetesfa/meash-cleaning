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
        'marketing_consent',
        'consent_timestamp',
        'last_interaction_at',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'payload_cache' => 'array',
        'marketing_consent' => 'boolean',
        'consent_timestamp' => 'datetime',
        'last_interaction_at' => 'datetime',
    ];
}
