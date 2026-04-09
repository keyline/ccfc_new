<?php

return [
    // 'base_url' => env('JUSPAY_BASE_URL', 'https://smartgateway.hdfcuat.bank.in'), //uat
    'base_url' => env('JUSPAY_BASE_URL', 'https://smartgateway.hdfc.bank.in'), //live
    'merchant_id' => env('JUSPAY_MERCHANT_ID'),
    'key_uuid' => env('JUSPAY_KEY_UUID'),
    'public_key_file' => env('JUSPAY_PUBLIC_KEY_FILE'),
    'private_key_file' => env('JUSPAY_PRIVATE_KEY_FILE', 'privateKey.pem'),
    'payment_page_client_id' => env('JUSPAY_PAYMENT_PAGE_CLIENT_ID'),
    'customer_id' => env('JUSPAY_CUSTOMER_ID', 'testing-customer-one'),
    'service' => env('JUSPAY_SERVICE', 'in.juspay.hyperpay'),
    'environment' => env('JUSPAY_ENVIRONMENT', 'sandbox'),
    'product_name' => env('JUSPAY_PRODUCT_NAME', 'CALCUTTA CRICKET AND FOOTBALL CLUB'),
];