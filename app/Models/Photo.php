<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    public const SECTIONS = ['hero', 'service', 'gallery', 'feature'];

    public const SECTION_LABELS = [
        'hero' => 'Hero slider',
        'service' => 'Service cards',
        'gallery' => 'Gallery',
        'feature' => 'Feature strip',
    ];

    public const STATUS_ADDED = 'added';
    public const STATUS_NOT_ADDED = 'not_added';

    protected $fillable = [
        'url',
        'title_en',
        'title_am',
        'section',
        'service_code',
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
