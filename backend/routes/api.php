<?php

use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\Admin\AdminTenantController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\WeekPlanController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MetaOAuthController;
use App\Http\Controllers\Api\PublishingLogController;
use App\Http\Controllers\Api\SocialAccountController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Middleware\EnsureTenantContext;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class);
Route::get('meta/oauth/callback', [MetaOAuthController::class, 'callback']);
Route::get('public/bootstrap', [PublicController::class, 'bootstrap']);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('google/url', [GoogleAuthController::class, 'url']);
    Route::get('google/callback', [GoogleAuthController::class, 'callback']);
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

        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('week-plans', [WeekPlanController::class, 'index']);
        Route::post('week-plans', [WeekPlanController::class, 'store']);
        Route::get('week-plans/{weekPlan}', [WeekPlanController::class, 'show']);
        Route::post('week-plans/{weekPlan}/generate', [WeekPlanController::class, 'generate']);
        Route::post('week-plans/{weekPlan}/approve-all', [WeekPlanController::class, 'approveAll']);

        Route::get('billing', [BillingController::class, 'subscription']);
        Route::get('billing/plans', [BillingController::class, 'plans']);
        Route::post('billing/request-plan', [BillingController::class, 'requestPlan']);
    });

    Route::middleware('super_admin')->prefix('admin')->group(function () {
        Route::get('dashboard', AdminDashboardController::class);
        Route::get('tenants', [AdminTenantController::class, 'index']);
        Route::get('tenants/{tenant}', [AdminTenantController::class, 'show']);
        Route::get('users', [AdminUserController::class, 'index']);
        Route::patch('users/{user}/super-admin', [AdminUserController::class, 'updateSuperAdmin']);
        Route::get('plans', [\App\Http\Controllers\Api\Admin\AdminPlanController::class, 'index']);
        Route::patch('plans/{plan}', [\App\Http\Controllers\Api\Admin\AdminPlanController::class, 'update']);
        Route::put('tenants/{tenant}/subscription', [AdminSubscriptionController::class, 'upsertForTenant']);
        Route::get('subscriptions/pending', [AdminSubscriptionController::class, 'pending']);
        Route::post('subscriptions/{subscription}/activate', [AdminSubscriptionController::class, 'activate']);
        Route::post('subscriptions/{subscription}/suspend', [AdminSubscriptionController::class, 'suspend']);
    });
});