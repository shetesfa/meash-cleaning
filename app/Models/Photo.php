<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

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
