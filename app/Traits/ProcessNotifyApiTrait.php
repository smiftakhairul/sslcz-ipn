<?php

namespace App\Traits;

use App\Jobs\SendIpnMultipleSms;
use App\Jobs\SendIpnMultipleStakeholderSms;
use App\Jobs\SendIpnSms;
use App\Jobs\SendIpnStakeholderSms;
use App\Jobs\SendPushNotification;
use App\Models\PushNotification;
use App\Models\PushNotificationLog;
use App\Models\Sms;
use App\Models\SmsLog;
use App\Models\Stakeholder;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;

trait ProcessNotifyApiTrait
{
    use StakeholderTrait;

    protected function processSmsData(array $data) {
        try {
            $stakeholder_uid = $data['stakeholder_uid'];
            $smsData = $data['sms_data'];

            $stakeholder = $this->checkStakeholder($stakeholder_uid);
            if (!$stakeholder) {
                throw new \Exception('Invalid stakeholder! No stakeholder found.');
            }

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

                $smsInput['stakeholder_url'] = $stakeholder->url;
                $smsInput['stakeholder_uid'] = $stakeholder->stakeholder_uid;
                $smsInput['stakeholder_user'] = $stakeholder->user;
                $smsInput['stakeholder_pass'] = decryptString($stakeholder->pass);
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

    protected function processMultipleSmsData(array $data)
    {
        try {
            $stakeholder_uid = $data['stakeholder_uid'];
            $sms_data = $data['sms_data'];

            $stakeholder = $this->checkStakeholder($stakeholder_uid);
            if (!$stakeholder) {
                throw new \Exception('Invalid stakeholder! No stakeholder found.');
            }

            $sms_flag = false;
            $sms_inputs = [];
            $sms_collections = [];
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
                            if (!isset($single_sms_data['content']) || empty($single_sms_data['content'])) {
                                throw new \Exception('Not found the body data');
                            }
                        }
                        foreach ($sms_data as $single_sms_data) {
                            $sms_input['recipient'] = $single_sms_data['recipient'] ?? '';
                            $sms_input['content'] = $single_sms_data['content'] ?? '';
                            $sms_input['sms_log_id'] = $sms_log->id;
                            $saved_sms = Sms::create($sms_input);
                            $sms_flag = $saved_sms ? true : false;
                            array_push($sms_collections, $sms_input);
                        }
                    } else {
                        throw new \Exception('Maximum limit of sms exceeded');
                    }
                } else {
                    throw new \Exception('Not found sms data');
                }

                $sms_inputs['stakeholder_url'] = $stakeholder->url;
                $sms_inputs['stakeholder_uid'] = $stakeholder->stakeholder_uid;
                $sms_inputs['stakeholder_user'] = $stakeholder->user;
                $sms_inputs['stakeholder_pass'] = decryptString($stakeholder->pass);
                $sms_inputs['sms_collections'] = $sms_collections;
            }

            if ($sms_flag && !empty($sms_inputs) && isset($sms_inputs['sms_collections']) && !empty($sms_inputs['sms_collections'])) {
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
                if(! isset($pushData['content']) || $pushData['content'] == '') {
                    throw new \Exception('Not found the body data');
                }
                $pushInput['recipient'] = $pushData['recipient'] ?? '';
                $pushInput['content'] = $pushData['content'] ?? '';
                $pushInput['title'] = $pushData['title'] ?? '';
                $pushInput['notify_log_id'] = $notificationLog->id;
                $saved_notification = PushNotification::create($pushInput);

                if ($saved_notification) {
                    dispatch(new SendPushNotification(['title' => $pushInput['title'], 'content' => $pushInput['content']],
                        $pushInput['recipient'], $notificationLog->id))->delay(now()->addSeconds(config('misc.notification.delay_in_second')));;
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

    protected function processStakeholderSmsData(array $data) {
        try {
            $stakeholderData = [
                'stakeholder_uid' => $data['stakeholder_uid'],
                'stakeholder_user' => $data['stakeholder_user'],
                'stakeholder_pass' => $data['stakeholder_pass'],
            ];
            $smsData = $data['sms_data'];

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

                $smsInput['sid'] = $stakeholderData['stakeholder_uid'];
                $smsInput['user'] = $stakeholderData['stakeholder_user'];
                $smsInput['pass'] = $stakeholderData['stakeholder_pass'];
            }
            if($saved_sms) {
                dispatch(new SendIpnStakeholderSms($smsInput))->delay(now()->addSeconds(config('misc.notification.delay_in_second')));
            }

        } catch (\Exception $exception) {
            writeToLog('processStakeholderSmsData method  response ;' . $exception->getMessage() , 'error');
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'Process Sms Data Error';
            if(isset($smsLogId) && $smsLogId) {
                SmsLog::firstWhere('id', $smsLogId)->update(['response' => $message]);
            }
        }

    }

    protected function processMultipleStakeholderSmsData(array $data)
    {
        try {
            $stakeholderData = [
                'stakeholder_uid' => $data['stakeholder_uid'],
                'stakeholder_user' => $data['stakeholder_user'],
                'stakeholder_pass' => $data['stakeholder_pass'],
            ];
            $sms_data = $data['sms_data'];

            $sms_flag = false;
            $sms_inputs = [];
            $sms_collections = [];
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
                            if (!isset($single_sms_data['content']) || empty($single_sms_data['content'])) {
                                throw new \Exception('Not found the body data');
                            }
                        }
                        foreach ($sms_data as $single_sms_data) {
                            $sms_input['recipient'] = $single_sms_data['recipient'] ?? '';
                            $sms_input['content'] = $single_sms_data['content'] ?? '';
                            $sms_input['sms_log_id'] = $sms_log->id;
                            $saved_sms = Sms::create($sms_input);
                            $sms_flag = $saved_sms ? true : false;
                            array_push($sms_collections, $sms_input);
                        }
                    } else {
                        throw new \Exception('Maximum limit of sms exceeded');
                    }
                } else {
                    throw new \Exception('Not found sms data');
                }

                $sms_inputs['sid'] = $stakeholderData['stakeholder_uid'];
                $sms_inputs['user'] = $stakeholderData['stakeholder_user'];
                $sms_inputs['pass'] = $stakeholderData['stakeholder_pass'];
                $sms_inputs['sms_collections'] = $sms_collections;
            }

            if ($sms_flag && !empty($sms_inputs) && isset($sms_inputs['sms_collections']) && !empty($sms_inputs['sms_collections'])) {
                dispatch(new SendIpnMultipleStakeholderSms($sms_inputs))->delay(
                    now()->addSeconds(config('misc.notification.multiple.delay_in_second'))
                );
            }
        } catch (\Exception $exception) {
            throw new \Exception('Something went wrong. Please try again.');
        }
    }
}
