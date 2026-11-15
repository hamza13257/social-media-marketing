<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\Ai\OpenAiCompatibleClient;
use App\Support\CurrentTenant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class);

        $this->app->singleton(AiClient::class, function () {
            return new OpenAiCompatibleClient;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->is_super_admin) {
                return true;
            }

            return null;
        });
    }
}