<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncConflict extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'client_uuid',
        'client_state',
        'server_state',
        'resolved_by_user_id',
        'resolution',
        'resolved_at',
    ];

    protected $casts = [
        'client_state' => 'array',
        'server_state' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
