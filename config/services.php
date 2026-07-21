<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

    'clubman' => [
        'token_url' => env('CLUBMAN_TOKEN_URL', 'https://ccfcmemberdata.in/token'),
        'member_lookup_url' => env(
            'CLUBMAN_MEMBER_LOOKUP_URL',
            'https://ccfcmemberdata.in/api/MemberLookup'
        ),
        'member_profile_url' => env(
            'CLUBMAN_MEMBER_PROFILE_URL',
            'https://ccfcmemberdata.in/Api/MemberProfile'
        ),
        'username' => env('CLUBMAN_USERNAME', 'CCFC'),
        'password' => env('CLUBMAN_PASSWORD'),
        'token' => env('CLUBMAN_API_TOKEN'),
        // Clubman's server currently omits the issuer chain required by PHP/cURL.
        // Keep this configurable so verification can be re-enabled once fixed upstream.
        'verify_ssl' => env('CLUBMAN_VERIFY_SSL', false),
        'timeout' => env('CLUBMAN_TIMEOUT', 8),
        'connect_timeout' => env('CLUBMAN_CONNECT_TIMEOUT', 3),
    ],

];
