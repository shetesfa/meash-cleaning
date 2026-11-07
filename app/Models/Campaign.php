<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'channel',
        'audience_filter',
        'message_text',
        'status',
        'session_token',
        'notes',
        'total_targets',
        'sent_count',
        'failed_count',
        'created_by_user_id',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function getSuccessfulCountAttribute(): int
    {
        return (int)$this->sent_count;
    }
}
