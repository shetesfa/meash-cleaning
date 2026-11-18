<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Service extends Model
{
    use HasFactory;

    /**
     * F5: the public homepage reads the active services on every visit.
     * Cached for 5 minutes; any save/delete clears it (see booted) so
     * admin edits show immediately.
     */
    public const HOMEPAGE_CACHE_KEY = 'meash:services:active';

    public const HOMEPAGE_CACHE_TTL_SECONDS = 300;

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetHomepageCache());
        static::deleted(fn () => static::forgetHomepageCache());
    }

    public static function forgetHomepageCache(): void
    {
        Cache::forget(self::HOMEPAGE_CACHE_KEY);
    }

    /** Active services for the public homepage, in display order. */
    public static function activeForHomepage(): Collection
    {
        return Cache::remember(
            self::HOMEPAGE_CACHE_KEY,
            self::HOMEPAGE_CACHE_TTL_SECONDS,
            fn () => static::where('is_active', true)->orderBy('sort_order')->get()
        );
    }

    protected $fillable = [
        'code',
        'name_en',
        'name_am',
        'description_en',
        'description_am',
        'icon',
        'base_price',
        'unit',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function proformaItems(): HasMany
    {
        return $this->hasMany(ProformaItem::class);
    }
}
