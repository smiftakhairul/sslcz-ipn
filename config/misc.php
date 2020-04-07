<?php

return [

    'firebase' => [
        'authorization_key' => env('FIREBASE_AUTH_KEY', ''),
        'url' => env('FIREBASE_URL', 'https://fcm.googleapis.com/fcm/send'),
    ],
    'sms' => [
        'url' => env('SMS_API_URL','https://sms.sslwireless.com/pushapi/dynamic/server.php'),
        'user' => env('SMS_API_USER','easychkout'),
        'password' => env('SMS_API_PASSWD','50?8D82e'),
        'sid' => env('SMS_API_SID','EasyChkOut'),
    ],
    'notification' => [
        'delay_in_second' => env('NOTIFICATION_DELAY_SECOND',0)
    ]

];
