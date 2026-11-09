<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeedProvenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'seed_key',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Atomically tag an Eloquent model as development seed provenance.
     */
    public static function tag(Model $model, string $seedKey = 'DatabaseSeeder', array $metadata = []): self
    {
        return static::updateOrCreate(
            [
                'entity_type' => class_basename($model),
                'entity_id' => $model->getKey(),
            ],
            [
                'seed_key' => $seedKey,
                'metadata' => $metadata,
            ]
        );
    }
}
