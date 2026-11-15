<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MetaOAuthController;
use App\Http\Controllers\Api\SocialAccountController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Middleware\EnsureTenantContext;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class);
Route::get('meta/oauth/callback', [MetaOAuthController::class, 'callback']);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::get('tenants', [TenantController::class, 'index']);
    Route::post('tenants', [TenantController::class, 'store']);
    Route::get('tenants/{tenant}', [TenantController::class, 'show']);

    Route::middleware(EnsureTenantContext::class)->group(function () {
        Route::apiResource('brands', BrandController::class);
        Route::get('meta/oauth/url', [MetaOAuthController::class, 'url']);
        Route::apiResource('social-accounts', SocialAccountController::class)
            ->only(['index', 'show', 'update', 'destroy']);
    });
});