<?php

namespace App\Http\Controllers\Api;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use GeneratesUniqueSlug;

    public function register(RegisterRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $result = DB::transaction(function () use ($payload) {
            $user = User::query()->create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => $payload['password'],
            ]);

            $tenant = new Tenant([
                'name' => $payload['organization_name'],
            ]);
            $tenant->slug = $this->uniqueSlugFor($tenant, $payload['organization_name']);
            $tenant->save();

            $tenant->users()->attach($user->id, [
                'role' => TenantRole::Owner->value,
            ]);

            $token = $user->createToken('api')->plainTextToken;

            return [$user, $tenant, $token];
        });

        [$user, $tenant, $token] = $result;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tenant' => new TenantResource(
                $user->tenants()->where('tenants.id', $tenant->id)->first() ?? $tenant
            ),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('api')->plainTextToken;

        $tenant = $user->tenants()->orderBy('tenant_user.created_at')->first();

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'tenant' => $tenant ? new TenantResource($tenant) : null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenants = $user->tenants()->orderBy('tenant_user.created_at')->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
            ],
            'tenants' => TenantResource::collection($tenants),
        ]);
    }
}