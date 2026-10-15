<?php

return [
    'provider' => env('WHATSAPP_PROVIDER', 'fonnte'),

    'token' => env('WHATSAPP_TOKEN'),

    'device_id' => env('WHATSAPP_DEVICE_ID'),

    'custom_url' => env('WHATSAPP_CUSTOM_URL'),

    'custom_headers' => [
        'Authorization' => env('WHATSAPP_CUSTOM_AUTH'),
        'Content-Type' => 'application/json',
    ],
];
