<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PhotoController extends Controller
{
    /**
     * List every website photo, grouped by section, plus which
     * storage is active (cloudinary when keys exist, local otherwise).
     */
    public function index(CloudinaryService $cloudinary): JsonResponse
    {
        // Ordered hero -> service -> gallery -> feature, then sort_order.
        // Sorted in PHP (not FIELD()) so it works on TiDB and SQLite
        // alike — the gy5x host still runs SQLite.
        $sectionOrder = array_flip(Photo::SECTIONS);
        $photos = Photo::orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->sortBy(function (Photo $photo) use ($sectionOrder) {
                return [$sectionOrder[$photo->section] ?? 99, $photo->sort_order, $photo->id];
            })
            ->values()
            ->map(function (Photo $photo) {
                return $this->present($photo);
            });

        return response()->json([
            'success' => true,
            'cloudinary_configured' => $cloudinary->configured(),
            'storage' => $cloudinary->configured() ? 'cloudinary' : 'local',
            'photos' => $photos,
        ]);
    }

    /**
     * Add a photo. Uploads the file to Cloudinary when configured,
     * otherwise saves it on this server (test mode — see
     * storeLocally). A plain image URL may be given instead of a file.
     */
    public function store(Request $request, CloudinaryService $cloudinary): JsonResponse
    {
        $validated = $request->validate([
            'title_en' => 'nullable|string|max:255',
            'title_am' => 'nullable|string|max:255',
            'section' => ['required', 'string', Rule::in(Photo::SECTIONS)],
            'service_code' => 'nullable|string|max:64',
            'status' => ['nullable', 'string', Rule::in([Photo::STATUS_ADDED, Photo::STATUS_NOT_ADDED])],
            'sort_order' => 'nullable|integer|min:0|max:1000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'url' => 'nullable|url|max:1024',
        ]);

        if ($validated['section'] === 'service' && empty($validated['service_code'])) {
            return response()->json([
                'success' => false,
                'message' => 'Service card photos need a service (service_code).',
            ], 422);
        }

        $url = $validated['url'] ?? null;
        if ($request->hasFile('photo')) {
            if ($cloudinary->configured()) {
                try {
                    $url = $cloudinary->upload($request->file('photo'));
                } catch (\RuntimeException $e) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ], 422);
                }
            } else {
                $url = $this->storeLocally($request->file('photo'));
            }
        }

        if (empty($url)) {
            return response()->json([
                'success' => false,
                'message' => 'Choose a photo file or paste an image URL.',
            ], 422);
        }

        $photo = Photo::create([
            'url' => $url,
            'title_en' => $validated['title_en'] ?? null,
            'title_am' => $validated['title_am'] ?? null,
            'section' => $validated['section'],
            'service_code' => $validated['service_code'] ?? null,
            'status' => $validated['status'] ?? Photo::STATUS_NOT_ADDED,
            'sort_order' => $validated['sort_order'] ?? (int) (Photo::where('section', $validated['section'])->max('sort_order') ?? -1) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo added as Not added. Use “Add to site” to publish it.',
            'photo' => $this->present($photo),
        ], 201);
    }

    /**
     * Edit captions / section / service / status / order, and optionally
     * replace the image file (Cloudinary when configured, local save
     * otherwise).
     */
    public function update(Request $request, Photo $photo, CloudinaryService $cloudinary): JsonResponse
    {
        $validated = $request->validate([
            'title_en' => 'nullable|string|max:255',
            'title_am' => 'nullable|string|max:255',
            'section' => ['nullable', 'string', Rule::in(Photo::SECTIONS)],
            'service_code' => 'nullable|string|max:64',
            'status' => ['nullable', 'string', Rule::in([Photo::STATUS_ADDED, Photo::STATUS_NOT_ADDED])],
            'sort_order' => 'nullable|integer|min:0|max:1000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'url' => 'nullable|url|max:1024',
        ]);

        $section = $validated['section'] ?? $photo->section;
        // Service code only matters for service-card rows; any other
        // section clears it so stale codes never leak into lookups.
        $serviceCode = $section === 'service'
            ? (array_key_exists('service_code', $validated) ? $validated['service_code'] : $photo->service_code)
            : null;
        if ($section === 'service' && empty($serviceCode)) {
            return response()->json([
                'success' => false,
                'message' => 'Service card photos need a service (service_code).',
            ], 422);
        }

        // Only keys actually sent are changed — captions are never
        // wiped by an update that did not include them.
        $data = [];
        foreach (['title_en', 'title_am', 'section', 'status', 'sort_order'] as $key) {
            if (array_key_exists($key, $validated)) {
                $data[$key] = $validated[$key];
            }
        }
        $data['service_code'] = $serviceCode;

        if ($request->hasFile('photo')) {
            if ($cloudinary->configured()) {
                try {
                    $data['url'] = $cloudinary->upload($request->file('photo'));
                } catch (\RuntimeException $e) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                    ], 422);
                }
            } else {
                $data['url'] = $this->storeLocally($request->file('photo'));
            }
        } elseif (!empty($validated['url'])) {
            $data['url'] = $validated['url'];
        }

        $oldUrl = $photo->url;
        $photo->update($data);

        // If the image was replaced, remove the old file best-effort
        // (Cloudinary and/or local gallery file).
        if (!empty($data['url']) && $data['url'] !== $oldUrl) {
            $cloudinary->deleteByUrl($oldUrl);
            $this->deleteLocalFile($oldUrl);
        }

        return response()->json([
            'success' => true,
            'message' => 'Photo updated.',
            'photo' => $this->present($photo->fresh()),
        ]);
    }

    /**
     * Flip Added <-> Not added (publish / hide from the website).
     */
    public function toggleStatus(Photo $photo): JsonResponse
    {
        $photo->status = $photo->isAdded() ? Photo::STATUS_NOT_ADDED : Photo::STATUS_ADDED;
        $photo->save();

        return response()->json([
            'success' => true,
            'message' => $photo->isAdded() ? 'Photo is now Added — it shows on the website.' : 'Photo is now Not added — hidden from the website.',
            'photo' => $this->present($photo),
        ]);
    }

    /**
     * Delete a photo row (two-tap confirm in the admin UI). The
     * Cloudinary file is removed best-effort; the row delete always wins.
     */
    public function destroy(Photo $photo, CloudinaryService $cloudinary): JsonResponse
    {
        $cloudinary->deleteByUrl($photo->url);
        $this->deleteLocalFile($photo->url);
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted.',
        ]);
    }

    /**
     * Test-mode storage: save an uploaded image on this server under
     * storage/app/public/gallery (served as /storage/gallery/... via
     * the public/storage symlink the entrypoint creates on every
     * start), and return its root-relative URL. Used only while
     * Cloudinary is not configured.
     *
     * Why storage, not public/images/gallery: the container runs PHP
     * as www-data and only storage/ + bootstrap/cache are chowned to
     * www-data (see docker/entrypoint.sh) — public/ stays root-owned,
     * so moving an upload there throws and the API returns a 500.
     * On Render the file is wiped on the next deploy/restart; on
     * Plesk (real domain, later) it persists.
     * The file is already validated as an image (jpg/png/webp, max
     * 5 MB) by the request rules, and gets a unique name so an
     * upload can never overwrite a seeded photo.
     */
    protected function storeLocally(UploadedFile $file): string
    {
        $dir = storage_path('app/public/gallery');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $extension = strtolower($file->getClientOriginalExtension());
        $name = 'upload-' . time() . '-' . Str::random(8) . '.' . $extension;
        $file->move($dir, $name);

        return '/storage/gallery/' . $name;
    }

    /**
     * Best-effort delete of a locally stored upload. Only touches
     * paths under /storage/gallery (files this system saved); the
     * seeded repo photos under /images/gallery are never deleted
     * from disk. Never throws.
     */
    protected function deleteLocalFile(?string $url): void
    {
        try {
            if (empty($url) || !str_starts_with($url, '/storage/gallery/')) {
                return;
            }
            $path = storage_path('app/public/gallery/' . basename($url));
            if (is_file($path)) {
                @unlink($path);
            }
        } catch (\Throwable) {
            // Best effort only — the row delete always wins.
        }
    }

    protected function present(Photo $photo): array
    {
        return [
            'id' => $photo->id,
            'url' => $photo->url,
            'src' => $photo->src,
            'title_en' => $photo->title_en,
            'title_am' => $photo->title_am,
            'section' => $photo->section,
            'service_code' => $photo->service_code,
            'status' => $photo->status,
            'sort_order' => $photo->sort_order,
            'created_at' => $photo->created_at?->toIso8601String(),
            'updated_at' => $photo->updated_at?->toIso8601String(),
        ];
    }
}
