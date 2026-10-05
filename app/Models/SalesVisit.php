<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_code',
        'organization_id',
        'contact_person',
        'contact_position',
        'phone',
        'address',
        'services_introduced',
        'visit_purpose',
        'interest_level',
        'salesperson_user_id',
        'visit_date',
        'notes',
        'next_followup_date',
        'stage',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'next_followup_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_user_id');
    }

    public function proformas(): HasMany
    {
        return $this->hasMany(Proforma::class);
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByDesc('id')->first();
        $nextId = $last ? ($last->id + 1) : 1;
        return 'VST-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }
}
