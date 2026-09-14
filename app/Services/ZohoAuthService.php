<?php

namespace App\Services;

use App\Exceptions\ZohoApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates and caches the Zoho OAuth access token (via the refresh-token
 * grant) so every other Zoho service can just ask for a valid token without
 * knowing anything about how/when it was issued.
 */
class ZohoAuthService
{
    private const CACHE_KEY = 'zoho_access_token';

    public function getAccessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(55), function (): string {
            return $this->requestNewAccessToken();
        });
    }

    /**
     * Drop the cached token (e.g. after Zoho rejects it as expired/invalid)
     * so the next getAccessToken() call fetches a fresh one.
     */
    public function forgetToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function requestNewAccessToken(): string
    {
        $config = config('services.zoho');

        $response = Http::asForm()->post(rtrim($config['accounts_url'], '/').'/oauth/v2/token', [
            'refresh_token' => $config['refresh_token'],
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'refresh_token',
        ]);

        $accessToken = $response->json('access_token');

        if (! $response->successful() || ! $accessToken) {
            // Never log the request payload (client secret/refresh token) or
            // the response body (may echo credentials back) — only enough to
            // diagnose that the token exchange failed.
            Log::error('Zoho OAuth token request failed.', [
                'http_status' => $response->status(),
                'has_access_token' => (bool) $accessToken,
            ]);

            throw new ZohoApiException('Unable to obtain a Zoho access token.');
        }

        return $accessToken;
    }
}
