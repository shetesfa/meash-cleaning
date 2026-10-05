<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CleaningTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_name',
        'team_leader_id',
        'phone',
        'vehicle_plate',
        'status',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
        'notes',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'assigned_team_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
