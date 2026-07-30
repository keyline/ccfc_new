<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ClubmanInvoiceLookup
{
    public function lookup(User $user): array
    {
        $memberCode = trim((string) $user->user_code);

        if ($memberCode === '') {
            throw new RuntimeException('This member does not have a Clubman membership ID.');
        }

        $endpoint = trim((string) config('services.clubman.monthly_balance_url'));

        if ($endpoint === '') {
            throw new RuntimeException('The Clubman monthly balance URL is not configured.');
        }

        $fromDate = trim((string) config(
            'services.clubman.monthly_balance_from_date',
            '01-apr-2020'
        ));
        $toDate = strtolower(Carbon::now()->format('d-M-Y'));
        $url = rtrim($endpoint, '/') . '/?' . http_build_query([
            'MCODE' => $memberCode,
            'FromDate' => $fromDate,
            'ToDate' => $toDate,
        ]);

        try {
            $response = Http::withoutVerifying()
                ->acceptJson()
                ->withToken($this->apiToken())
                ->withHeaders(['Cache-Control' => 'no-cache'])
                ->timeout((int) config('services.clubman.timeout', 15))
                ->withOptions([
                    'connect_timeout' => (int) config('services.clubman.connect_timeout', 5),
                ])
                ->post($url);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Clubman could not be reached while loading invoices.',
                0,
                $exception
            );
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException(
                'Clubman rejected the invoice API token (HTTP ' . $response->status() . ').'
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Clubman rejected the invoice request (HTTP ' . $response->status() . ').'
            );
        }

        $payload = $response->json();
        $result = is_array($payload) ? strtolower(trim((string) ($payload['Result'] ?? ''))) : '';

        if ($result !== '' && $result !== 'success') {
            $message = trim((string) ($payload['ErrorMsg'] ?? ''));
            throw new RuntimeException($message ?: 'Clubman could not return invoice data.');
        }

        if (! is_array($payload) || ! array_key_exists('data', $payload) || ! is_array($payload['data'])) {
            throw new RuntimeException('Clubman returned an invalid invoice response.');
        }

        return array_values(array_filter($payload['data'], 'is_array'));
    }

    protected function apiToken(): string
    {
        $settingToken = '';

        try {
            $setting = GeneralSetting::find(1);
            $settingToken = $setting ? trim((string) $setting->clubman_api_token) : '';
        } catch (Throwable $exception) {
            // The environment token remains available during setup or DB maintenance.
        }

        $token = $settingToken ?: trim((string) config('services.clubman.token'));

        if ($token === '') {
            throw new RuntimeException(
                'The Clubman API token is missing from Admin Settings and the environment.'
            );
        }

        return $token;
    }
}
