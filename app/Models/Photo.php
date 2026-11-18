<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Photo extends Model
{
    use HasFactory;

    /**
     * F5: the public homepage reads the added photos three times per
     * visit (logo, gallery, before/after pairs). One cached query
     * serves all three — the blade filters the collection in PHP.
     * Cached for 5 minutes; any save/delete clears it (see booted) so
     * admin edits show immediately.
     */
    public const HOMEPAGE_CACHE_KEY = 'meash:photos:added';

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

    /** All added photos for the public homepage, in display order. */
    public static function homepagePhotos(): Collection
    {
        return Cache::remember(
            self::HOMEPAGE_CACHE_KEY,
            self::HOMEPAGE_CACHE_TTL_SECONDS,
            fn () => static::where('status', self::STATUS_ADDED)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public const SECTIONS = ['hero', 'service', 'gallery', 'feature', 'logo'];

    public const SECTION_LABELS = [
        'hero' => 'Hero slider',
        'service' => 'Service cards',
        'gallery' => 'Gallery',
        'feature' => 'Feature strip',
        'logo' => 'Website logo',
    ];

    public const STATUS_ADDED = 'added';
    public const STATUS_NOT_ADDED = 'not_added';

    // R6: before/after pairs. Two rows share one pair_id; one is
    // role 'before', the other 'after'. Null pair_id = not in a pair.
    public const PAIR_ROLES = ['before', 'after'];

    protected $fillable = [
        'url',
        'title_en',
        'title_am',
        'section',
        'service_code',
        'pair_id',
        'role',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function isAdded(): bool
    {
        return $this->status === self::STATUS_ADDED;
    }

    /**
     * Resolve the stored url to a browser-usable src. Cloudinary URLs
     * are absolute already; local gallery files are stored as a
     * root-relative path or a bare filename.
     */
    public static function resolveUrl(?string $url): string
    {
        $url = (string) $url;
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
            return $url;
        }

        return '/images/gallery/' . ltrim($url, '/');
    }

    public function getSrcAttribute(): string
    {
        return self::resolveUrl($this->url);
    }
}
