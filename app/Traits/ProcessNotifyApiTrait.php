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
        try {
            $smsLog = SmsLog::create([
                'request' => json_encode($smsData)
            ]);

            if($smsLog) {
                $smsLogId = $smsLog->id;
                if(! isset($smsData['recipient']) || $smsData['recipient'] == '') {
                    throw new \Exception('Not found the recipient data');
                }
                if(! isset($smsData['body']) || $smsData['body'] == '') {
                    throw new \Exception('Not found the  body data');
                }
                $smsInput['recipient'] = $smsData['recipient'] ?? '';
                $smsInput['content'] = $smsData['body'] ?? '';
                $smsInput['sms_log_id'] = $smsLogId;
                $saved_sms = Sms::create($smsInput);
            }
            if($saved_sms) {
                dispatch(new SendIpnSms($smsInput))->delay(now()->addSeconds(config('misc.notification.delay_in_second')));
            }

        } catch (\Exception $exception) {
            writeToLog('processSmsData method  response ;' . $exception->getMessage() , 'error');
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'Process Sms Data Error';
            if(isset($smsLogId) && $smsLogId) {
                SmsLog::firstWhere('id', $smsLogId)->update(['response' => $message]);
            }
        }

    }

    protected function processPushNotifyData($pushData) {
        try {
            $notificationLog = PushNotificationLog::create([
                'request' => json_encode($pushData)
            ]);

            if($notificationLog) {
                if(! isset($pushData['recipient']) || $pushData['recipient'] == '') {
                    throw new \Exception('Not found the recipient data');
                }
                if(! isset($pushData['body']) || $pushData['body'] == '') {
                    throw new \Exception('Not found the  body data');
                }
                $pushInput['recipient'] = $pushData['recipient'] ?? '';
                $pushInput['content'] = $pushData['body'] ?? '';
                $pushInput['title'] = $pushData['title'] ?? '';
                $pushInput['notify_log_id'] = $notificationLog->id;
                $saved_notification = PushNotification::create($pushInput);

                if ($saved_notification) {
                    dispatch(new SendPushNotification(['title' => $pushInput['title'], 'body' => $pushInput['content']],$pushInput['recipient'], $notificationLog->id))->delay(now()->addSeconds(config('misc.notification.delay_in_second')));;
                }
            }

        }catch (\Exception $exception) {

            writeToLog('processPushNotifyData method  response ;' . $exception->getMessage() , 'error');
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'Process Sms Data Error';

            if(isset($notificationLog) && $notificationLog->id) {
                PushNotificationLog::firstWhere('id', $notificationLog->id)->update(['response' => $message]);
            }

        }

    }
}
