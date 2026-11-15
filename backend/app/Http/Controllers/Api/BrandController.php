<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Support\CurrentTenant;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use GeneratesUniqueSlug;

    public function __construct(private CurrentTenant $currentTenant) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        $brands = Brand::query()
            ->where('tenant_id', $this->currentTenant->id())
            ->when(! $request->boolean('include_archived'), fn ($q) => $q->whereNull('archived_at'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => BrandResource::collection($brands),
        ]);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $this->authorize('create', Brand::class);

        $data = $request->validated();
        $slugSource = $data['slug'] ?? $data['name'];

        $brand = new Brand($data);
        $brand->tenant_id = $this->currentTenant->id();
        $brand->slug = $this->uniqueSlugFor($brand, $slugSource, 'slug', [
            'tenant_id' => $this->currentTenant->id(),
        ]);
        $brand->save();

        return response()->json([
            'data' => new BrandResource($brand),
        ], 201);
    }

    public function show(Brand $brand): JsonResponse
    {
        $this->authorize('view', $brand);

        return response()->json([
            'data' => new BrandResource($brand),
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $this->authorize('update', $brand);

        $data = $request->validated();

        if (array_key_exists('archived', $data)) {
            $brand->archived_at = $data['archived'] ? now() : null;
            unset($data['archived']);
        }

        if (isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlugFor($brand, $data['slug'], 'slug', [
                'tenant_id' => $brand->tenant_id,
            ]);
        }

        $brand->fill($data);
        $brand->save();

        return response()->json([
            'data' => new BrandResource($brand->fresh()),
        ]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $this->authorize('delete', $brand);

        $brand->delete();

        return response()->json(null, 204);
    }
}