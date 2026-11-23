<?php

return [
    'default' => 'local',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'throw' => false,
        ],
        // User avatars and other browser-visible uploads belong here. Keeping
        // this separate from the private local disk prevents accidental public
        // exposure of CRM attachments.
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
