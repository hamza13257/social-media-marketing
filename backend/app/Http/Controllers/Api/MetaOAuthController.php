<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\SocialAccount;
use App\Services\Meta\MetaOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MetaOAuthController extends Controller
{
    public function __construct(private MetaOAuthService $meta) {}

    public function url(Request $request): JsonResponse
    {
        $this->authorize('connect', SocialAccount::class);

        if (! $this->meta->isConfigured()) {
            return response()->json([
                'message' => 'Meta OAuth is not configured. Set META_APP_ID and META_APP_SECRET on the server.',
            ], 503);
        }

        $state = $this->meta->generateState();

        $brandId = $request->integer('brand_id') ?: null;
        if ($brandId) {
            $brand = Brand::query()->find($brandId);
            if ($brand === null || (int) $brand->tenant_id !== (int) $request->attributes->get('tenant')->id) {
                return response()->json(['message' => 'Invalid brand for this organization.'], 422);
            }
        }

        Cache::put($this->stateCacheKey($state), [
            'tenant_id' => $request->attributes->get('tenant')->id,
            'user_id' => $request->user()->id,
            'brand_id' => $brandId,
        ], now()->addMinutes(15));

        return response()->json([
            'authorize_url' => $this->meta->authorizeUrl($state),
            'state' => $state,
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $frontendBase = config('meta.frontend_callback');

        if ($request->filled('error')) {
            return redirect()->away($frontendBase.'?error='.urlencode((string) $request->input('error_description', $request->input('error'))));
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($state === '' || $code === '') {
            return redirect()->away($frontendBase.'?error='.urlencode('Invalid OAuth response.'));
        }

        $payload = Cache::pull($this->stateCacheKey($state));

        if ($payload === null) {
            return redirect()->away($frontendBase.'?error='.urlencode('OAuth state expired or invalid.'));
        }

        if (! $this->meta->isConfigured()) {
            return redirect()->away($frontendBase.'?error='.urlencode('Meta OAuth is not configured.'));
        }

        try {
            $short = $this->meta->exchangeCodeForToken($code);
            $shortToken = $short['access_token'];
            $long = $this->meta->exchangeForLongLivedToken($shortToken);
            $accessToken = $long['access_token'] ?? $shortToken;
            $expiresIn = $long['expires_in'] ?? $short['expires_in'] ?? null;
            $expiresAt = $expiresIn ? now()->addSeconds((int) $expiresIn) : null;

            $count = $this->meta->syncDiscoveredAccounts(
                (int) $payload['tenant_id'],
                $payload['brand_id'] ?? null,
                $accessToken,
                $expiresAt,
            );

            if ($count === 0) {
                return redirect()->away($frontendBase.'?error='.urlencode('No Facebook Pages or Instagram accounts were found.'));
            }

            return redirect()->away($frontendBase.'?connected='.$count);
        } catch (\Throwable $exception) {
            Log::error('Meta OAuth callback failed', [
                'message' => $exception->getMessage(),
                'tenant_id' => $payload['tenant_id'] ?? null,
            ]);

            return redirect()->away($frontendBase.'?error='.urlencode('Meta connection failed. Check logs and app permissions.'));
        }
    }

    protected function stateCacheKey(string $state): string
    {
        return 'meta_oauth_state:'.$state;
    }
}