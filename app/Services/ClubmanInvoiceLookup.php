<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ClubmanInvoiceLookup
{
    private $accessToken;

    public function __construct(ClubmanAccessToken $accessToken = null)
    {
        $this->accessToken = $accessToken ?: new ClubmanAccessToken();
    }

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
            $response = $this->sendRequest($url, $this->accessToken->get());

            if (in_array($response->status(), [401, 403], true)
                && $this->accessToken->canRefresh()) {
                $this->accessToken->forget();
                $response = $this->sendRequest($url, $this->accessToken->get(true));
            }
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

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
        $result = is_array($payload)
            ? strtolower(trim((string) $this->value($payload, ['Result'], '')))
            : '';

        if ($result !== '' && $result !== 'success') {
            $message = trim((string) $this->value($payload, ['ErrorMsg', 'Message'], ''));
            throw new RuntimeException($message ?: 'Clubman could not return invoice data.');
        }

        $data = is_array($payload) ? $this->value($payload, ['data']) : null;

        if (is_string($data)) {
            $decodedData = json_decode($data, true);
            $data = is_array($decodedData) ? $decodedData : null;
        }

        if (! is_array($data)) {
            throw new RuntimeException('Clubman returned an invalid invoice response.');
        }

        if ($this->looksLikeTransaction($data)) {
            $data = [$data];
        }

        return array_values(array_filter($data, 'is_array'));
    }

    private function sendRequest(string $url, string $token)
    {
        $request = Http::acceptJson()
            ->withToken($token)
            ->withHeaders(['Cache-Control' => 'no-cache'])
            ->timeout((int) config('services.clubman.timeout', 15))
            ->withOptions([
                'connect_timeout' => (int) config('services.clubman.connect_timeout', 5),
            ]);

        if (! filter_var(config('services.clubman.verify_ssl', false), FILTER_VALIDATE_BOOLEAN)) {
            $request->withoutVerifying();
        }

        return $request->post($url);
    }

    private function value(array $data, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            foreach ($data as $actualKey => $value) {
                if (strcasecmp((string) $actualKey, $key) === 0) {
                    return $value;
                }
            }
        }

        return $default;
    }

    private function looksLikeTransaction(array $data): bool
    {
        foreach (array_keys($data) as $key) {
            if (strcasecmp((string) $key, 'Month') === 0) {
                return true;
            }
        }

        return false;
    }
}
