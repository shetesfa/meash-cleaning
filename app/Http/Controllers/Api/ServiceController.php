<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Service;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($services);
    }

    public function all(): JsonResponse
    {
        $services = Service::orderBy('sort_order')->get();

        // Card photo per service, from the admin-managed `photos` table:
        // the Added service-section row for the service's code. This is
        // the same source of truth as the public service cards
        // (website.blade.php builds the same map, last row per code
        // wins).
        $servicePhoto = [];
        $rows = Photo::where('section', 'service')
            ->where('status', Photo::STATUS_ADDED)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        foreach ($rows as $row) {
            if ($row->service_code) {
                $servicePhoto[$row->service_code] = Photo::resolveUrl($row->url);
            }
        }

        $services = $services->map(function (Service $service) use ($servicePhoto) {
            $service->photo_url = $servicePhoto[$service->code] ?? null;
            return $service;
        });

        return response()->json($services);
    }

    public function store(Request $request, CloudinaryService $cloudinary): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:services,code',
            'name_en' => 'required|string',
            'name_am' => 'required|string',
            'description_en' => 'nullable|string',
            'description_am' => 'nullable|string',
            'icon' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'photo_url' => 'nullable|string|max:1024',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $serviceData = collect($validated)->except(['photo_url', 'photo'])->all();
        $service = Service::create($serviceData);

        // Handle photo if supplied
        $photoUrl = $validated['photo_url'] ?? null;
        if ($request->hasFile('photo')) {
            if ($cloudinary->configured()) {
                try {
                    $photoUrl = $cloudinary->upload($request->file('photo'));
                } catch (\RuntimeException $e) {
                    return response()->json(['message' => $e->getMessage()], 422);
                }
            } else {
                $photoUrl = $this->storeLocally($request->file('photo'));
            }
        }

        if (!empty($photoUrl)) {
            Photo::updateOrCreate(
                ['section' => 'service', 'service_code' => $service->code],
                [
                    'url' => $photoUrl,
                    'title_en' => $service->name_en,
                    'title_am' => $service->name_am,
                    'status' => Photo::STATUS_ADDED,
                    'sort_order' => (int) (Photo::where('section', 'service')->max('sort_order') ?? -1) + 1,
                ]
            );
            Photo::forgetHomepageCache();
            Service::forgetHomepageCache();
        }

        $service->photo_url = $photoUrl ? Photo::resolveUrl($photoUrl) : null;

        return response()->json([
            'message' => 'Service created successfully / አገልግሎቱ በተሳካ ሁኔታ ተመዝግቧል',
            'service' => $service,
        ], 201);
    }

    public function update(Request $request, Service $service, CloudinaryService $cloudinary): JsonResponse
    {
        $validated = $request->validate([
            'name_en' => 'sometimes|required|string',
            'name_am' => 'sometimes|required|string',
            'description_en' => 'nullable|string',
            'description_am' => 'nullable|string',
            'icon' => 'nullable|string',
            'base_price' => 'sometimes|required|numeric|min:0',
            'unit' => 'sometimes|required|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'photo_url' => 'nullable|string|max:1024',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $serviceData = collect($validated)->except(['photo_url', 'photo'])->all();
        $service->update($serviceData);

        // Handle photo if supplied
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            if ($cloudinary->configured()) {
                try {
                    $photoUrl = $cloudinary->upload($request->file('photo'));
                } catch (\RuntimeException $e) {
                    return response()->json(['message' => $e->getMessage()], 422);
                }
            } else {
                $photoUrl = $this->storeLocally($request->file('photo'));
            }
        } elseif (array_key_exists('photo_url', $validated)) {
            $photoUrl = $validated['photo_url'];
        }

        if ($photoUrl !== null) {
            if ($photoUrl === '') {
                // User explicitly cleared photo
                Photo::where('section', 'service')->where('service_code', $service->code)->delete();
            } else {
                Photo::updateOrCreate(
                    ['section' => 'service', 'service_code' => $service->code],
                    [
                        'url' => $photoUrl,
                        'title_en' => $service->name_en,
                        'title_am' => $service->name_am,
                        'status' => Photo::STATUS_ADDED,
                    ]
                );
            }
            Photo::forgetHomepageCache();
            Service::forgetHomepageCache();
        }

        $row = Photo::where('section', 'service')
            ->where('service_code', $service->code)
            ->where('status', Photo::STATUS_ADDED)
            ->first();
        $service->photo_url = $row ? Photo::resolveUrl($row->url) : null;

        return response()->json([
            'message' => 'Service updated successfully / የአገልግሎት መረጃው ተዘምኗል',
            'service' => $service,
        ]);
    }

    protected function storeLocally(UploadedFile $file): string
    {
        $dir = storage_path('app/public/gallery');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = 'upload-' . time() . '-' . Str::random(8) . '.' . $extension;
        $file->move($dir, $name);

        try {
            $symlink = public_path('storage');
            if (!file_exists($symlink)) {
                @symlink(storage_path('app/public'), $symlink);
            }
        } catch (\Throwable) {}

        return '/storage/gallery/' . $name;
    }
}
