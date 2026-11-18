<?php

namespace App\Http\Controllers\Api;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(Request $request): JsonResponse
    {
        $tenants = $request->user()->tenants()->orderByPivot('created_at')->get();

        return response()->json([
            'data' => TenantResource::collection($tenants),
        ]);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = new Tenant([
            'name' => $request->validated('name'),
        ]);
        $tenant->slug = $this->uniqueSlugFor($tenant, $request->validated('name'));
        $tenant->save();

        $request->user()->tenants()->attach($tenant->id, [
            'role' => TenantRole::Owner->value,
        ]);

        return response()->json([
            'data' => new TenantResource($tenant),
        ], 201);
    }

    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        return response()->json([
            'data' => new TenantResource($tenant),
        ]);
    }
}