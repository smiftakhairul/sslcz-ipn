<?php

namespace App\Traits;

use App\Jobs\SendIpnMultipleSms;
use App\Jobs\SendIpnSms;
use App\Jobs\SendIpnStackholderSms;
use App\Jobs\SendPushNotification;
use App\Models\PushNotification;
use App\Models\PushNotificationLog;
use App\Models\Sms;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;

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
                if(! isset($smsData['content']) || $smsData['content'] == '') {
                    throw new \Exception('Not found the  body data');
                }
                $smsInput['recipient'] = $smsData['recipient'] ?? '';
                $smsInput['content'] = $smsData['content'] ?? '';
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

    protected function processMultipleSmsData(array $sms_data)
    {
        try {
            $sms_flag = false;
            $sms_inputs = [];
            $sms_log = SmsLog::create([
                'request' => json_encode($sms_data)
            ]);

            if ($sms_log) {
                if (isset($sms_data) && !empty($sms_data)) {
                    if (count($sms_data) <= config('misc.sms.multiple.limit')) {
                        foreach ($sms_data as $single_sms_data) {
                            if (!isset($single_sms_data['recipient']) || empty($single_sms_data['recipient'])) {
                                throw new \Exception('Not found the recipient data');
                            }
                            if (!isset($single_sms_data['body']) || empty($single_sms_data['body'])) {
                                throw new \Exception('Not found the  body data');
                            }
                        }
                        foreach ($sms_data as $single_sms_data) {
                            $sms_input['recipient'] = $single_sms_data['recipient'] ?? '';
                            $sms_input['content'] = $single_sms_data['body'] ?? '';
                            $sms_input['sms_log_id'] = $sms_log->id;
                            $saved_sms = Sms::create($sms_input);
                            $sms_flag = $saved_sms ? true : false;
                            array_push($sms_inputs, $sms_input);
                        }
                    } else {
                        throw new \Exception('Maximum limit of sms exceeded');
                    }
                } else {
                    throw new \Exception('Not found sms data');
                }
            }

            if ($sms_flag && !empty($sms_inputs)) {
                dispatch(new SendIpnMultipleSms($sms_inputs))->delay(
                    now()->addSeconds(config('misc.notification.multiple.delay_in_second'))
                );
            }
        } catch (\Exception $exception) {
            throw new \Exception('Something went wrong. Please try again.');
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

    protected function processStackholderSmsData($stackholderData, $smsData) {
        try {
            $smsLog = SmsLog::create([
                'request' => json_encode($smsData)
            ]);

            if($smsLog) {
                $smsLogId = $smsLog->id;
                if(! isset($smsData['recipient']) || $smsData['recipient'] == '') {
                    throw new \Exception('Not found the recipient data');
                }
                if(! isset($smsData['content']) || $smsData['content'] == '') {
                    throw new \Exception('Not found the  body data');
                }
                $smsInput['recipient'] = $smsData['recipient'] ?? '';
                $smsInput['content'] = $smsData['content'] ?? '';
                $smsInput['sms_log_id'] = $smsLogId;
                $saved_sms = Sms::create($smsInput);

                $smsInput['sid'] = $stackholderData['sid'];
                $smsInput['user'] = $stackholderData['user'];
                $smsInput['pass'] = $stackholderData['pass'];
            }
            if($saved_sms) {
                dispatch(new SendIpnStackholderSms($smsInput))->delay(now()->addSeconds(config('misc.notification.delay_in_second')));
            }

        } catch (\Exception $exception) {
            writeToLog('processStackholderSmsData method  response ;' . $exception->getMessage() , 'error');
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'Process Sms Data Error';
            if(isset($smsLogId) && $smsLogId) {
                SmsLog::firstWhere('id', $smsLogId)->update(['response' => $message]);
            }
        }

    }
}
