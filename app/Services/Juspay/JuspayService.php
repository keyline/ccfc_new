<?php

namespace App\Services\Juspay;

use Illuminate\Support\Arr;
use Juspay\JuspayEnvironment;
use Juspay\Model\JuspayJWT;
use Juspay\Model\Order;
use Juspay\Model\OrderSession;
use Juspay\RequestOptions;

class JuspayService
{
    public function initialize(): void
    {
        $config = $this->config();

        JuspayEnvironment::init()
            ->withBaseUrl($config['base_url'])
            ->withMerchantId($config['merchant_id'])
            ->withJuspayJWT(new JuspayJWT(
                $config['key_uuid'],
                $this->readKeyFile($config['public_key_file']),
                $this->readKeyFile($config['private_key_file']),
            ));
    }

    public function createPaymentSession(string $orderId, string $returnUrl, string $amount = '120.30'): array
    {
        $this->initialize();

        $config = $this->config();
        $requestOptions = (new RequestOptions())->withCustomerId($config['customer_id']);

        $session = OrderSession::create([
            'amount' => $amount,
            'order_id' => $orderId,
            'merchant_id' => $config['merchant_id'],
            'customer_id' => $config['customer_id'],
            'payment_page_client_id' => $config['payment_page_client_id'],
            'action' => 'paymentPage',
            'return_url' => $returnUrl,
        ], $requestOptions);

        $status = Order::status([
            'order_id' => $orderId,
        ], $requestOptions);

        return [
            'session' => $session,
            'status' => $status,
            'payment_link' => Arr::get($session->paymentLinks, 'web'),
            'sdk_payload' => $session->sdkPayload ?? null,
            'meta' => [
                'customer_id' => $config['customer_id'],
                'merchant_id' => $config['merchant_id'],
                'base_url' => $config['base_url'],
                'service' => $config['service'],
                'environment' => $config['environment'],
                'product_name' => $config['product_name'],
            ],
        ];
    }

    public function config(): array
    {
        return [
            'base_url' => $this->resolveConfigValue('base_url', 'JUSPAY_BASE_URL', 'BASE_URL'),
            'merchant_id' => $this->resolveConfigValue('merchant_id', 'JUSPAY_MERCHANT_ID', 'MERCHANT_ID'),
            'key_uuid' => $this->resolveConfigValue('key_uuid', 'JUSPAY_KEY_UUID', 'KEY_UUID'),
            'public_key_file' => $this->resolveConfigValue('public_key_file', 'JUSPAY_PUBLIC_KEY_FILE', 'PUBLIC_KEY_PATH'),
            'private_key_file' => $this->resolveConfigValue('private_key_file', 'JUSPAY_PRIVATE_KEY_FILE', 'PRIVATE_KEY_PATH'),
            'payment_page_client_id' => $this->resolveConfigValue('payment_page_client_id', 'JUSPAY_PAYMENT_PAGE_CLIENT_ID', 'PAYMENT_PAGE_CLIENT_ID'),
            'customer_id' => config('juspay.customer_id'),
            'service' => config('juspay.service'),
            'environment' => config('juspay.environment'),
            'product_name' => config('juspay.product_name'),
        ];
    }

    private function readKeyFile(string $fileName): string
    {
        $path = storage_path('app/juspay/'.$fileName);

        if (! is_file($path)) {
            throw new \RuntimeException("Juspay key file not found: {$fileName}");
        }

        $contents = file_get_contents($path);

        if (! $contents) {
            throw new \RuntimeException("Unable to read Juspay key file: {$fileName}");
        }

        return $contents;
    }

    private function resolveConfigValue(string $configKey, string $envKey, string $jsonKey): string
    {
        $value = config("juspay.{$configKey}");

        if ($value) {
            return $value;
        }

        $jsonConfig = $this->jsonConfig();
        $fallback = $jsonConfig[$jsonKey] ?? null;

        if (! $fallback) {
            throw new \RuntimeException("Missing {$envKey} configuration.");
        }

        return $fallback;
    }

    private function jsonConfig(): array
    {
        $path = storage_path('app/juspay/config.json');

        if (! is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if (! $contents) {
            return [];
        }

        return json_decode($contents, true) ?: [];
    }
}
