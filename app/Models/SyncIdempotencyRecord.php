<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncIdempotencyRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'user_id',
        'entity_type',
        'action',
        'status',
        'entity_id',
        'response_payload',
        'lock_token',
    ];

    protected $casts = [
        'response_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
