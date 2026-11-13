<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhotoController extends Controller
{
    /**
     * List every website photo, grouped by section, plus whether
     * Cloudinary uploads are configured yet.
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
            'photos' => $photos,
        ]);
    }

    /**
     * Add a photo. Uploads the file to Cloudinary when configured;
     * otherwise returns a friendly 422 explaining the keys are missing.
     * A plain image URL may be given instead of a file.
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
            if (!$cloudinary->configured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cloudinary is not configured yet — add CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY and CLOUDINARY_API_SECRET in the Render environment variables, then redeploy. Until then you can still add a photo by pasting its image URL.',
                    'cloudinary_configured' => false,
                ], 422);
            }
            try {
                $url = $cloudinary->upload($request->file('photo'));
            } catch (\RuntimeException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
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
     * replace the image file (Cloudinary upload when configured).
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
            if (!$cloudinary->configured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cloudinary is not configured yet — add the keys in the Render environment variables first.',
                    'cloudinary_configured' => false,
                ], 422);
            }
            try {
                $data['url'] = $cloudinary->upload($request->file('photo'));
            } catch (\RuntimeException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        } elseif (!empty($validated['url'])) {
            $data['url'] = $validated['url'];
        }

        // Changing a photo keeps its old file out of the live site only
        // when the row itself is deleted; old Cloudinary files are
        // removed best-effort on delete (see destroy()).
        $photo->update($data);

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
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted.',
        ]);
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
