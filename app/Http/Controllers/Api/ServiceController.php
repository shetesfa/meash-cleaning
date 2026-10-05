<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        return response()->json($services);
    }

    public function store(Request $request): JsonResponse
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
        ]);

        $service = Service::create($validated);

        return response()->json([
            'message' => 'Service created successfully',
            'service' => $service,
        ], 201);
    }

    public function update(Request $request, Service $service): JsonResponse
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
        ]);

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated successfully',
            'service' => $service,
        ]);
    }
}
