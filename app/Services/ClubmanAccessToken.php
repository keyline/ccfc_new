<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ClubmanAccessToken
{
    private const CACHE_KEY = 'clubman:access_token';

    public function get(bool $forceRefresh = false): string
    {
        $configuredToken = $this->configuredToken();

        if (! $this->canRefresh()) {
            if ($configuredToken !== '') {
                return $configuredToken;
            }

            throw new RuntimeException('Clubman API credentials are not configured.');
        }

        if (! $forceRefresh) {
            $cachedToken = $this->cachedToken();

            if ($cachedToken !== '') {
                return $cachedToken;
            }
        }

        try {
            $response = $this->request()
                ->asForm()
                ->post(trim((string) config('services.clubman.token_url')), [
                    'grant_type' => 'password',
                    'UserName' => trim((string) config('services.clubman.username')),
                    'Password' => (string) config('services.clubman.password'),
                ]);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'Clubman rejected the token request (HTTP ' . $response->status() . ').'
                );
            }

            $payload = $response->json();
            $token = is_array($payload)
                ? trim((string) ($payload['access_token'] ?? $payload['AccessToken'] ?? ''))
                : '';

            if ($token === '') {
                throw new RuntimeException('Clubman returned no access token.');
            }

            $expiresIn = is_array($payload) ? (int) ($payload['expires_in'] ?? 3600) : 3600;
            $this->cacheToken($token, min(86400, max(300, $expiresIn - 300)));

            return $token;
        } catch (Throwable $exception) {
            if ($configuredToken !== '') {
                return $configuredToken;
            }

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Clubman token generation failed.', 0, $exception);
        }
    }

    public function canRefresh(): bool
    {
        return trim((string) config('services.clubman.token_url')) !== ''
            && trim((string) config('services.clubman.username')) !== ''
            && trim((string) config('services.clubman.password')) !== '';
    }

    public function forget(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable $exception) {
            // A fresh request still works when the cache is unavailable.
        }
    }

    private function configuredToken(): string
    {
        // Environment configuration is authoritative over a possibly stale
        // token saved in the legacy settings table.
        $token = trim((string) config('services.clubman.token'));

        if ($token !== '') {
            return $token;
        }

        try {
            $setting = GeneralSetting::find(1);

            return $setting ? trim((string) $setting->clubman_api_token) : '';
        } catch (Throwable $exception) {
            return '';
        }
    }

    private function request()
    {
        $request = Http::acceptJson()
            ->timeout((int) config('services.clubman.timeout', 15))
            ->withOptions([
                'connect_timeout' => (int) config('services.clubman.connect_timeout', 5),
            ]);

        if (! filter_var(config('services.clubman.verify_ssl', false), FILTER_VALIDATE_BOOLEAN)) {
            $request->withoutVerifying();
        }

        return $request;
    }

    private function cachedToken(): string
    {
        try {
            return trim((string) Cache::get(self::CACHE_KEY, ''));
        } catch (Throwable $exception) {
            return '';
        }
    }

    private function cacheToken(string $token, int $seconds): void
    {
        try {
            Cache::put(self::CACHE_KEY, $token, now()->addSeconds($seconds));
        } catch (Throwable $exception) {
            // Token use must not depend on the cache backend being available.
        }
    }
}
