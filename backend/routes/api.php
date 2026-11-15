<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MetaOAuthController;
use App\Http\Controllers\Api\PublishingLogController;
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

        Route::apiResource('contents', ContentController::class);
        Route::post('contents/{content}/submit', [ContentController::class, 'submit']);
        Route::post('contents/{content}/approve', [ContentController::class, 'approve']);
        Route::post('contents/{content}/reject', [ContentController::class, 'reject']);
        Route::post('contents/{content}/request-changes', [ContentController::class, 'requestChanges']);
        Route::post('contents/{content}/schedule', [ContentController::class, 'schedule']);
        Route::post('contents/{content}/generate-caption', [ContentController::class, 'generateCaption']);
        Route::post('contents/{content}/publish', [ContentController::class, 'publish']);

        Route::get('publishing-logs', [PublishingLogController::class, 'index']);
    });
});