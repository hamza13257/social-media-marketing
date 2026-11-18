<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\TenantResource;
use App\Models\User;
use App\Services\Auth\RegistersMarketingWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use RegistersMarketingWorkspace;

    public function register(RegisterRequest $request): JsonResponse
    {
        $payload = $request->validated();

        [$user, $tenant, $token] = DB::transaction(function () use ($payload) {
            $user = User::query()->create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => $payload['password'],
            ]);

            $tenant = $this->provisionDefaultTenant($user, $payload['organization_name']);
            $token = $user->createToken('api')->plainTextToken;

            return [$user, $tenant, $token];
        });

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

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->password === null) {
            throw ValidationException::withMessages([
                'email' => ['Use Google sign-in for this account.'],
            ]);
        }

        if (! Hash::check($request->validated('password'), $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        try {
            $user->tokens()->delete();
            $token = $user->createToken('api')->plainTextToken;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Login failed while creating an API token.',
                'hint' => 'Run: php artisan key:generate && php artisan migrate (needs personal_access_tokens). '
                    .'Check backend/storage/logs/laravel.log for the exact error.',
            ], 503);
        }

        $tenant = $user->tenants()->orderByPivot('created_at')->first();

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
        $tenants = $user->tenants()->orderByPivot('created_at')->get();

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