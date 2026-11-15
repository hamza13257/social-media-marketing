<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app_key' => config('app.key') !== '' && config('app.key') !== null,
            'database' => false,
            'migrations' => false,
        ];

        try {
            DB::connection()->getPdo();
            $checks['database'] = true;
            $checks['migrations'] = Schema::hasTable('users')
                && Schema::hasTable('tenants')
                && Schema::hasTable('personal_access_tokens');
        } catch (\Throwable) {
            $checks['database'] = false;
        }

        $ok = $checks['app_key'] && $checks['database'] && $checks['migrations'];

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
            'hint' => $ok
                ? null
                : 'Run: cd backend && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate',
        ], $ok ? 200 : 503);
    }
}