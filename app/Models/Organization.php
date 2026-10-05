<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_code',
        'name',
        'industry',
        'address',
        'phone',
        'email',
        'website',
        'notes',
    ];

    public function visits(): HasMany
    {
        return $this->hasMany(SalesVisit::class)->latest('visit_date');
    }

    public function proformas(): HasMany
    {
        return $this->hasMany(Proforma::class)->latest();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class)->latest('start_date');
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByDesc('id')->first();
        $nextId = $last ? ($last->id + 1) : 1;
        return 'ORG-' . str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }
}
