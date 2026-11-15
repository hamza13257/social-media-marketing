<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\RegistersMarketingWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    use RegistersMarketingWorkspace;

    public function url(): JsonResponse
    {
        if (! config('services.google.client_id')) {
            return response()->json(['message' => 'Google sign-in is not configured.'], 503);
        }

        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $redirectUrl]);
    }

    public function callback(): RedirectResponse
    {
        $frontend = rtrim(config('app.frontend_url'), '/');
        $errorRedirect = $frontend.'/login?error=google_auth';

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $exception) {
            Log::warning('Google OAuth failed', ['error' => $exception->getMessage()]);

            return redirect()->away($errorRedirect);
        }

        $email = $googleUser->getEmail();
        if ($email === null) {
            return redirect()->away($errorRedirect);
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if ($user === null) {
            $user = User::query()->where('email', $email)->first();
        }

        if ($user === null) {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?? strstr($email, '@', true),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => null,
            ]);

            $this->provisionDefaultTenant($user);
        } else {
            $user->fill([
                'google_id' => $user->google_id ?? $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar() ?? $user->avatar_url,
            ]);
            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }
            $user->save();

            if ($user->tenants()->count() === 0) {
                $this->provisionDefaultTenant($user);
            }
        }

        $user->tokens()->where('name', 'api')->delete();
        $token = $user->createToken('api')->plainTextToken;
        $tenant = $user->tenants()->orderBy('tenant_user.created_at')->first();

        $query = http_build_query([
            'token' => $token,
            'tenant_id' => $tenant?->getKey(),
        ]);

        return redirect()->away($frontend.'/auth/google/callback?'.$query);
    }
}