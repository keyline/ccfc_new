<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ClubmanMemberLookup
{
    private const TOKEN_CACHE_KEY = 'clubman:member_lookup:access_token';

    public function cached(User $user): ?array
    {
        foreach ($this->financialCacheKeys($user) as $key) {
            $financials = Cache::get($key);

            if (is_array($financials)) {
                return $financials;
            }
        }

        return null;
    }

    public function lookup(User $user, bool $allowStale = true): array
    {
        $memberCode = trim((string) $user->user_code);

        if ($memberCode === '') {
            throw new RuntimeException('This member does not have a Clubman membership ID.');
        }

        [$cacheKey, $staleCacheKey] = $this->financialCacheKeys($user);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $financials = $this->fetch($memberCode);
            Cache::put($cacheKey, $financials, now()->addMinutes(5));
            Cache::put($staleCacheKey, $financials, now()->addDay());

            return $financials;
        } catch (Throwable $exception) {
            $stale = Cache::get($staleCacheKey);

            if ($allowStale && is_array($stale)) {
                return $stale;
            }

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException(
                'Clubman member balances are temporarily unavailable.',
                0,
                $exception
            );
        }
    }

    public function minimumPaymentAmount(User $user): float
    {
        // A payment may use the five-minute fresh cache, but must never fall
        // back to an older balance if Clubman is unavailable.
        $financials = $this->lookup($user, false);

        // Payment gateways do not accept a zero-value transaction.
        return max(1.0, (float) $financials['minimum_due_amount']);
    }

    private function fetch(string $memberCode): array
    {
        $endpoint = trim((string) config('services.clubman.member_lookup_url'));

        if ($endpoint === '') {
            throw new RuntimeException('The Clubman Member Lookup URL is not configured.');
        }

        try {
            $response = $this->sendLookupRequest($endpoint, $memberCode);

            if (in_array($response->status(), [401, 403], true)) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                $response = $this->sendLookupRequest($endpoint, $memberCode);
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Clubman member balances are temporarily unavailable.',
                0,
                $exception
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Clubman rejected the member lookup (HTTP ' . $response->status() . ').'
            );
        }

        $payload = $response->json();
        $result = is_array($payload) ? strtolower(trim((string) ($payload['Result'] ?? ''))) : '';
        $data = is_array($payload) ? ($payload['data'] ?? null) : null;

        if (isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        if ($result !== 'success' || ! is_array($data)) {
            $message = is_array($payload) ? trim((string) ($payload['ErrorMsg'] ?? '')) : '';

            throw new RuntimeException($message ?: 'Clubman returned an invalid member lookup response.');
        }

        $returnedMemberCode = trim((string) ($data['MembershipID'] ?? ''));

        if ($returnedMemberCode !== '' && strcasecmp($returnedMemberCode, $memberCode) !== 0) {
            throw new RuntimeException('Clubman returned balances for a different member.');
        }

        return [
            'membership_id' => $returnedMemberCode ?: $memberCode,
            'member_name' => trim((string) ($data['MemberName'] ?? '')),
            'status' => trim((string) ($data['Status'] ?? '')),
            'mobile_no' => trim((string) ($data['MobileNo'] ?? '')),
            'outstanding' => $this->amount($data['Outstanding'] ?? null, 'Outstanding'),
            'minimum_due_amount' => max(
                0.0,
                $this->amount($data['MinimumDueAmount'] ?? null, 'MinimumDueAmount')
            ),
        ];
    }

    private function accessToken(): string
    {
        $cachedToken = trim((string) Cache::get(self::TOKEN_CACHE_KEY, ''));

        if ($cachedToken !== '') {
            return $cachedToken;
        }

        $username = trim((string) config('services.clubman.username'));
        $password = (string) config('services.clubman.password');
        $fallbackToken = $this->configuredToken();

        if ($username === '' || $password === '') {
            if ($fallbackToken !== '') {
                return $fallbackToken;
            }

            throw new RuntimeException('Clubman API credentials are not configured.');
        }

        $endpoint = trim((string) config('services.clubman.token_url'));

        if ($endpoint === '') {
            throw new RuntimeException('The Clubman token URL is not configured.');
        }

        try {
            $response = $this->request()
                ->asForm()
                ->post($endpoint, [
                    'grant_type' => 'password',
                    'UserName' => $username,
                    'Password' => $password,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Clubman rejected the token request (HTTP ' . $response->status() . ').');
            }

            $payload = $response->json();
            $token = is_array($payload) ? trim((string) ($payload['access_token'] ?? '')) : '';

            if ($token === '') {
                throw new RuntimeException('Clubman returned no access token.');
            }

            $expiresIn = is_array($payload) ? (int) ($payload['expires_in'] ?? 3600) : 3600;
            $cacheSeconds = min(86400, max(300, $expiresIn - 300));
            Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($cacheSeconds));

            return $token;
        } catch (Throwable $exception) {
            if ($fallbackToken !== '') {
                return $fallbackToken;
            }

            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Clubman token generation failed.', 0, $exception);
        }
    }

    private function sendLookupRequest(string $endpoint, string $memberCode)
    {
        return $this->request()
            ->withToken($this->accessToken())
            ->withHeaders(['Cache-Control' => 'no-cache'])
            ->asMultipart()
            ->post($endpoint . '?' . http_build_query(['MCODE' => $memberCode]), [
                'MCODE' => $memberCode,
            ]);
    }

    private function request()
    {
        $request = Http::acceptJson()
            ->timeout((int) config('services.clubman.timeout', 8))
            ->withOptions([
                'connect_timeout' => (int) config('services.clubman.connect_timeout', 3),
            ]);

        if (! filter_var(config('services.clubman.verify_ssl', true), FILTER_VALIDATE_BOOLEAN)) {
            $request->withoutVerifying();
        }

        return $request;
    }

    private function configuredToken(): string
    {
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

    private function amount($value, string $field): float
    {
        $normalized = is_string($value) ? str_replace(',', '', trim($value)) : $value;

        if (! is_numeric($normalized)) {
            throw new RuntimeException('Clubman returned an invalid ' . $field . ' amount.');
        }

        return round((float) $normalized, 2);
    }

    private function financialCacheKeys(User $user): array
    {
        $memberCode = strtoupper(trim((string) $user->user_code));
        $cacheKey = 'clubman:member_lookup:' . sha1($memberCode);

        return [$cacheKey, $cacheKey . ':stale'];
    }
}
