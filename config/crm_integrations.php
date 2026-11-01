<?php

return [
    // Generic HTTPS adapters. Point these at your provider or a small adapter
    // service. If left blank, CRM keeps using the existing device links.
    'sms' => [
        'endpoint' => env('CRM_SMS_API_URL'),
        'token' => env('CRM_SMS_API_TOKEN'),
        'sender' => env('CRM_SMS_SENDER'),
    ],
    'calling' => [
        'endpoint' => env('CRM_CALL_API_URL'),
        'token' => env('CRM_CALL_API_TOKEN'),
        'caller_id' => env('CRM_CALLER_ID'),
    ],
];
