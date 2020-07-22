<?php

namespace App\Services;

use App\Enums\NotifyType;

class PushNotificationService
{
    public function sendPushNotification($stakeholder, $push_data, $notify_type, $firebase_url)
    {
        $fields = [
            'priority' => 'high',
            'data' => [
                'title' => $push_data['title'], 'content' => $push_data['content']
            ]
        ];

        if ($notify_type == NotifyType::$_MULTIPLE && is_array($push_data['recipient'])) {
            $fields['registration_ids'] = $push_data['recipient'];
        } else {
            $fields['to'] = $push_data['recipient'];
        }

        $headers = [
            'Authorization: key=' . $stakeholder['fcm_authorize_key'],
            'Content-Type: application/json'
        ];

        $result = callToApi($firebase_url, json_encode($fields), $headers, 'POST');
        $response = json_decode($result, true);

        return $response;
    }
}
