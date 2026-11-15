<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function __construct(private CurrentTenant $currentTenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-Id');

        if ($tenantId === null || $tenantId === '') {
            return response()->json([
                'message' => 'The X-Tenant-Id header is required.',
            ], 400);
        }

        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        $user = $request->user();
        $role = $user->roleOnTenant($tenant);

        if ($role === null) {
            return response()->json([
                'message' => 'You are not a member of this organization.',
            ], 403);
        }

        $this->currentTenant->set($tenant, $role);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_role', $role);

        try {
            return $next($request);
        } finally {
            $this->currentTenant->clear();
        }
    }
}