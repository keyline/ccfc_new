<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ClubmanPaymentPosting
{
    public function post(
        string $memberCode,
        string $voucherNo,
        float $amount,
        string $instrumentNo,
        string $description = 'Online payment against outstanding',
        string $paymentGateway = ''
    ): array {
        $memberCode = trim($memberCode);

        if ($memberCode === '') {
            throw new RuntimeException('This member does not have a Clubman membership ID.');
        }

        $endpoint = trim((string) config('services.clubman.payment_posting_url'));

        if ($endpoint === '') {
            throw new RuntimeException('The Clubman Payment Posting URL is not configured.');
        }

        $payload = [
            'VoucherNo' => $voucherNo,
            'MemberId' => $memberCode,
            'VoucherDate' => Carbon::now('Asia/Kolkata')->format('d M Y'),
            'Amount' => round($amount, 2),
            'InstrumentNo' => $instrumentNo,
            'paymentgateway' => $paymentGateway,
            'Description' => $description,
        ];

        try {
            $response = Http::withoutVerifying()
                ->acceptJson()
                ->withToken($this->apiToken())
                ->withHeaders(['Cache-Control' => 'no-cache'])
                ->timeout((int) config('services.clubman.timeout', 15))
                ->withOptions([
                    'connect_timeout' => (int) config('services.clubman.connect_timeout', 5),
                ])
                ->post($endpoint . '?json=' . json_encode($payload));
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Clubman could not be reached while posting the payment.',
                0,
                $exception
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Clubman rejected the payment posting (HTTP ' . $response->status() . ').'
            );
        }

        $result = $response->json();

        if (! is_array($result)) {
            throw new RuntimeException('Clubman returned an invalid payment posting response.');
        }

        $status = strtolower(trim((string) ($result['Status'] ?? '')));

        if ($status !== 'success') {
            $message = trim((string) ($result['StatusMessage'] ?? ''));

            throw new RuntimeException($message ?: 'Clubman rejected the payment posting.');
        }

        return $result;
    }

    private function apiToken(): string
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
