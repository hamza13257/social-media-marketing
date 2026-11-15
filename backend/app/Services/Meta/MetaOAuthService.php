<?php

namespace App\Services\Meta;

use App\Enums\SocialConnectionStatus;
use App\Enums\SocialPlatform;
use App\Models\SocialAccount;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaOAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('meta.app_id')) && filled(config('meta.app_secret'));
    }

    public function authorizeUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => config('meta.app_id'),
            'redirect_uri' => config('meta.redirect_uri'),
            'state' => $state,
            'scope' => implode(',', config('meta.scopes')),
            'response_type' => 'code',
        ]);

        return 'https://www.facebook.com/'.config('meta.graph_version').'/dialog/oauth?'.$params;
    }

  /**
     * @return array{access_token: string, expires_in?: int}
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::get($this->graphUrl('/oauth/access_token'), [
            'client_id' => config('meta.app_id'),
            'client_secret' => config('meta.app_secret'),
            'redirect_uri' => config('meta.redirect_uri'),
            'code' => $code,
        ])->throw();

        return $response->json();
    }

    /**
     * @return array{access_token: string, expires_in?: int}
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get($this->graphUrl('/oauth/access_token'), [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('meta.app_id'),
            'client_secret' => config('meta.app_secret'),
            'fb_exchange_token' => $shortLivedToken,
        ])->throw();

        return $response->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchPages(string $userAccessToken): array
    {
        $response = Http::get($this->graphUrl('/me/accounts'), [
            'fields' => 'id,name,access_token,instagram_business_account',
            'access_token' => $userAccessToken,
        ])->throw();

        return $response->json('data', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchInstagramProfile(string $instagramAccountId, string $accessToken): array
    {
        $response = Http::get($this->graphUrl('/'.$instagramAccountId), [
            'fields' => 'id,username,name,profile_picture_url',
            'access_token' => $accessToken,
        ])->throw();

        return $response->json();
    }

    /**
     * @return int Number of accounts created/updated
     */
    public function syncDiscoveredAccounts(
        int $tenantId,
        ?int $brandId,
        string $userAccessToken,
        ?Carbon $tokenExpiresAt = null,
    ): int {
        $pages = $this->fetchPages($userAccessToken);
        $count = 0;
        $scopes = config('meta.scopes');

        foreach ($pages as $page) {
            $pageToken = $page['access_token'] ?? $userAccessToken;
            $pageId = (string) $page['id'];

            SocialAccount::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'platform' => SocialPlatform::FacebookPage,
                    'external_id' => $pageId,
                ],
                [
                    'brand_id' => $brandId,
                    'name' => (string) ($page['name'] ?? 'Facebook Page'),
                    'access_token' => $pageToken,
                    'token_expires_at' => $tokenExpiresAt,
                    'scopes' => $scopes,
                    'connection_status' => SocialConnectionStatus::Connected,
                    'metadata' => ['page_id' => $pageId],
                    'last_error' => null,
                    'connected_at' => now(),
                ]
            );
            $count++;

            $igRef = $page['instagram_business_account']['id'] ?? null;
            if ($igRef === null) {
                continue;
            }

            try {
                $ig = $this->fetchInstagramProfile((string) $igRef, $pageToken);
            } catch (RequestException $exception) {
                SocialAccount::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'platform' => SocialPlatform::InstagramProfessional,
                        'external_id' => (string) $igRef,
                    ],
                    [
                        'brand_id' => $brandId,
                        'name' => 'Instagram',
                        'access_token' => $pageToken,
                        'token_expires_at' => $tokenExpiresAt,
                        'scopes' => $scopes,
                        'connection_status' => SocialConnectionStatus::Error,
                        'metadata' => ['instagram_business_account_id' => $igRef, 'page_id' => $pageId],
                        'last_error' => 'Unable to load Instagram profile metadata.',
                        'connected_at' => now(),
                    ]
                );
                $count++;

                continue;
            }

            SocialAccount::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'platform' => SocialPlatform::InstagramProfessional,
                    'external_id' => (string) $ig['id'],
                ],
                [
                    'brand_id' => $brandId,
                    'name' => (string) ($ig['name'] ?? $ig['username'] ?? 'Instagram'),
                    'username' => $ig['username'] ?? null,
                    'profile_picture_url' => $ig['profile_picture_url'] ?? null,
                    'access_token' => $pageToken,
                    'token_expires_at' => $tokenExpiresAt,
                    'scopes' => $scopes,
                    'connection_status' => SocialConnectionStatus::Connected,
                    'metadata' => [
                        'instagram_business_account_id' => $ig['id'],
                        'page_id' => $pageId,
                    ],
                    'last_error' => null,
                    'connected_at' => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    public function generateState(): string
    {
        return Str::random(64);
    }

    protected function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/'.config('meta.graph_version').$path;
    }
}