<?php

return [
    'firebase' => [
        'authorization_key' => env('FIREBASE_AUTH_KEY', ''),
        'url' => env('FIREBASE_URL', ''),
    ],
    'sms' => [
        'url' => env('SMS_API_URL',''),
        'user' => env('SMS_API_USER',''),
        'password' => env('SMS_API_PASSWD',''),
        'sid' => env('SMS_API_SID',''),
        'multiple' => [
            'limit' => 50,
        ]
    ],
    'notification' => [
        'delay_in_second' => env('NOTIFICATION_DELAY_SECOND',0),
        'multiple' => [
            'delay_in_second' => env('NOTIFICATION_DELAY_SECOND',0),
            'limit' => 50
        ]
    ],
    'security' => [
        'enc_salt' => env('ENC_SECURITY_SALT', 'u1rAi'),
    ]
];
