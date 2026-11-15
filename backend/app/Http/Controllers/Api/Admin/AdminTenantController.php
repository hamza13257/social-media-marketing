<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminTenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class AdminTenantController extends Controller
{
    public function index(): JsonResponse
    {
        $tenants = Tenant::query()
            ->with(['subscription.plan'])
            ->withCount(['users', 'brands'])
            ->latest('id')
            ->paginate(25);

        return response()->json([
            'data' => AdminTenantResource::collection($tenants),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['subscription.plan'])->loadCount(['users', 'brands']);

        return response()->json(['data' => new AdminTenantResource($tenant)]);
    }
}