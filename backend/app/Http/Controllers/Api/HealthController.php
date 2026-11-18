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
        $connection = (string) config('database.default');
        $pdoDrivers = \PDO::getAvailableDrivers();

        $checks = [
            'app_key' => config('app.key') !== '' && config('app.key') !== null,
            'db_connection' => $connection,
            'pdo_drivers' => $pdoDrivers,
            'pdo_sqlite' => in_array('sqlite', $pdoDrivers, true),
            'pdo_mysql' => in_array('mysql', $pdoDrivers, true),
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

        $hint = null;
        if (! $ok) {
            if ($connection === 'mysql' && ! $checks['pdo_mysql']) {
                $hint = 'MySQL is configured but PHP has no pdo_mysql driver (common on Windows App Control). '
                    .'Use SQLite locally: set DB_CONNECTION=sqlite in .env, create database/database.sqlite, then php artisan migrate. '
                    .'Or allow php_pdo_mysql.dll / use Laragon.';
            } else {
                $hint = 'Run: cd backend && cp .env.example .env && php artisan key:generate && touch database/database.sqlite && php artisan migrate';
            }
        }

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
            'hint' => $hint,
        ], $ok ? 200 : 503);
    }
}