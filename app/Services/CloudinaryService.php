<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Cloudinary client using the plain REST upload API — no SDK
 * dependency. Credentials come ONLY from environment variables
 * (CLOUDINARY_URL, or CLOUDINARY_CLOUD_NAME / CLOUDINARY_API_KEY /
 * CLOUDINARY_API_SECRET via config/services.php). Nothing is hardcoded.
 * When nothing is configured, configured() is false and uploads are
 * refused with a friendly message instead of crashing.
 */
class CloudinaryService
{
    public const FOLDER = 'meash-photos';

    protected ?string $cloudName;
    protected ?string $apiKey;
    protected ?string $apiSecret;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');

        // All-in-one URL form: cloudinary://<api_key>:<api_secret>@<cloud_name>
        $url = (string) config('services.cloudinary.url');
        if ($url !== '' && str_starts_with($url, 'cloudinary://')) {
            $parts = parse_url($url);
            if (!empty($parts['host'])) {
                $this->cloudName = $parts['host'];
            }
            if (!empty($parts['user'])) {
                $this->apiKey = $parts['user'];
            }
            if (!empty($parts['pass'])) {
                $this->apiSecret = $parts['pass'];
            }
        }
    }

    public function configured(): bool
    {
        return !empty($this->cloudName) && !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Upload an image file to Cloudinary. Returns the secure URL.
     * Throws \RuntimeException when not configured or the upload fails.
     */
    public function upload(UploadedFile $file): string
    {
        if (!$this->configured()) {
            throw new \RuntimeException('Cloudinary is not configured. Add CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY and CLOUDINARY_API_SECRET to the Render environment variables.');
        }

        $timestamp = time();
        $params = [
            'folder' => self::FOLDER,
            'timestamp' => $timestamp,
        ];
        $signature = $this->sign($params);

        $response = Http::timeout(60)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload", array_merge($params, [
                'api_key' => $this->apiKey,
                'signature' => $signature,
            ]));

        if (!$response->successful()) {
            Log::warning('Cloudinary upload failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Photo upload to Cloudinary failed. Please try again.');
        }

        $secureUrl = (string) $response->json('secure_url', '');
        if ($secureUrl === '') {
            throw new \RuntimeException('Photo upload to Cloudinary failed (no URL returned). Please try again.');
        }

        return $secureUrl;
    }

    /**
     * Best-effort delete of a Cloudinary image by its URL. Never throws —
     * the database row delete must still succeed even if this fails.
     */
    public function deleteByUrl(?string $url): void
    {
        try {
            if (!$this->configured() || empty($url)) {
                return;
            }
            $publicId = $this->publicIdFromUrl($url);
            if ($publicId === null) {
                return; // Not a Cloudinary URL (e.g. local gallery file).
            }

            $timestamp = time();
            $params = ['public_id' => $publicId, 'timestamp' => $timestamp];
            Http::timeout(30)->asForm()->post(
                "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy",
                array_merge($params, [
                    'api_key' => $this->apiKey,
                    'signature' => $this->sign($params),
                ])
            );
        } catch (\Throwable $e) {
            Log::warning('Cloudinary delete failed (ignored)', ['url' => $url, 'error' => $e->getMessage()]);
        }
    }

    protected function sign(array $params): string
    {
        ksort($params);
        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        return sha1(implode('&', $parts) . $this->apiSecret);
    }

    /**
     * Extract the public_id from a Cloudinary delivery URL, e.g.
     * https://res.cloudinary.com/<cloud>/image/upload/v123/meash-photos/abc.jpg
     * -> meash-photos/abc
     */
    protected function publicIdFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }
        $marker = '/image/upload/';
        $pos = strpos($path, $marker);
        if ($pos === false) {
            return null;
        }
        $rest = substr($path, $pos + strlen($marker));
        // Strip the version segment (v1234567890/) when present.
        $rest = preg_replace('#^v\d+/#', '', $rest);
        // Strip the file extension.
        $rest = preg_replace('#\.[a-zA-Z0-9]+$#', '', $rest);

        return $rest !== '' ? $rest : null;
    }
}
