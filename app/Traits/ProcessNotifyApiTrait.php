<?php

namespace App\Traits;

use App\Jobs\SendIpnSms;
use App\Jobs\SendPushNotification;
use App\Models\PushNotification;
use App\Models\PushNotificationLog;
use App\Models\Sms;
use App\Models\SmsLog;

trait ProcessNotifyApiTrait
{
    protected function processSmsData($smsData) {
        $smsInput['recipient'] = $smsData['recipient'] ?? '';
        $smsInput['content'] = $smsData['body'] ?? '';
        $saved_sms = Sms::create($smsInput);
        $smsInput['sms_id'] = $saved_sms->id;

        if ($saved_sms) {
            SmsLog::create([
                'sms_id' => $saved_sms->id,
                'request' => json_encode($smsData)
            ]);
        }

        /*dispatch(new SendIpnSms($smsInput));*/
        dispatch(new SendIpnSms($smsInput))->delay(now()->addSeconds(config('misc.notification.delay_in_second')));

    }

    protected function processPushNotifyData($pushData) {
        $pushInput['recipient'] = $pushData['recipient'] ?? '';
        $pushInput['content'] = $pushData['body'] ?? '';
        $pushInput['title'] = $pushData['title'] ?? '';
        $saved_notification = PushNotification::create($pushInput);
        $pushInput['notify_id'] = $saved_notification->id;

        if ($saved_notification) {
            PushNotificationLog::create([
                'notification_id' => $saved_notification->id,
                'request' => json_encode($pushData)
            ]);
        }

        dispatch(new SendPushNotification(['title' => $pushInput['title'], 'body' => $pushInput['content']],$pushInput['recipient'], $saved_notification->id))->delay(now()->addSeconds(config('misc.notification.delay_in_second')));;
    }
}
