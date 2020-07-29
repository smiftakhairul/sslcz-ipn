<?php

namespace App\Traits;

use App\Enums\NotifyType;
use App\Jobs\SendIpnEmail;
use App\Jobs\SendIpnMultipleSms;
use App\Jobs\SendIpnMultipleStakeholderSms;
use App\Jobs\SendIpnSms;
use App\Jobs\SendIpnStakeholderSms;
use App\Jobs\SendPushNotification;
use App\Models\Email;
use App\Models\EmailLog;
use App\Models\PushNotification;
use App\Models\PushNotificationLog;
use App\Models\Sms;
use App\Models\SmsLog;
use App\Models\Stakeholder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\Matcher\Not;
use mysql_xdevapi\Exception;

trait ProcessNotifyApiTrait
{
    use StakeholderTrait;

    protected function processEmailData(array $emailData)
    {
        try {
            $emailLog = EmailLog::create([
                'request' => json_encode($emailData)
            ]);

            if ($emailLog) {
                $emailLogId = $emailLog->id;
                $emailInput = [
                    'email_log_id' => $emailLogId,
                    'sender' => json_encode($emailData['sender']),
                    'recipient' => json_encode($emailData['recipient']),
                    'cc' => json_encode($emailData['cc']),
                    'bcc' => json_encode($emailData['bcc']),
                    'subject' => $emailData['subject'],
                    'content' => $emailData['content'],
                ];

                $email = Email::create($emailInput);

                if ($email) {
                    $emailInput['sender'] = json_decode($emailInput['sender'], true);
                    $emailInput['recipient'] = json_decode($emailInput['recipient'], true);
                    $emailInput['cc'] = json_decode($emailInput['cc'], true);
                    $emailInput['bcc'] = json_decode($emailInput['bcc'], true);

                    $email_attachments = [];
                    $emailInput['attachments'] = $this->uploadEmailAttachments($emailData['attachments']);
                    $emailInput['attachment_url'] = $this->uploadEmailAttachmentUrl($emailData['attachment_url']);
                    if (!empty($emailInput['attachments'])) {
                        $email_attachments = array_merge($email_attachments, $emailInput['attachments']);
                    }
                    if (!empty($emailInput['attachment_url'])) {
                        $email_attachments = array_merge($email_attachments, $emailInput['attachment_url']);
                    }

                    if (!empty($email_attachments)) {
                        $email->attachments()->sync($email_attachments);
                    }

                    $emailInput['attachments'] = $email_attachments;

                    dispatch(new SendIpnEmail($emailInput))
                        ->delay(now()->addSeconds(config('misc.notification.delay_in_second')));
                }
            }
        } catch (\Exception $exception) {
            writeToLog('processEmailData method  response ;' . $exception->getMessage() , 'error');
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'Process Email Data Error';
            if(isset($emailLogId) && $emailLogId) {
                EmailLog::firstWhere('id', $emailLogId)->update(['response' => $message]);
            }
        }
    }

    protected function uploadEmailAttachments($attachments)
    {
        if (empty($attachments)) {
            return [];
        }

        $output = [];
        try {
            $count = 0;
            foreach ($attachments as $file) {
                $count++;
                $time = $count . time();
                $mime = $file->getClientOriginalExtension();
                $file_name = uniqid($time) . '.' . $mime;
                $storagePath = Storage::disk('commonStorage')->getDriver()->getAdapter()->getPathPrefix();
                $subDirePath = 'uploads/email-attachments/';
                $directoryPath = $storagePath . $subDirePath;
                \File::isDirectory($directoryPath) or \File::makeDirectory($directoryPath, 0775, true, true);
                $path = Storage::disk('commonStorage')->putFileAs($subDirePath, $file, $file_name);
                $exists = Storage::disk('commonStorage')->exists($path);
                if ($exists) {
                    $output[] = $file_name;
                }
            }
        } catch (\Exception $exception) {
            writeToLog('processEmailData method - attachments store error: ' . $exception->getMessage() , 'error');
        }

        return $output;
    }

    protected function uploadEmailAttachmentUrl($attachments)
    {
        if (empty($attachments)) {
            return [];
        }

        $output = [];
        try {
            $count = 0;
            foreach ($attachments as $url) {
                $count++;
                $time = $count . time();
                $file = file_get_contents($url);
                $file_name = substr($url, strrpos($url, '/') + 1);
                $mime = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_name = uniqid($time) . '.' . $mime;

                $storagePath = Storage::disk('commonStorage')->getDriver()->getAdapter()->getPathPrefix();
                $subDirePath = 'uploads/email-attachments/';
                $directoryPath = $storagePath . $subDirePath;
                \File::isDirectory($directoryPath) or \File::makeDirectory($directoryPath, 0775, true, true);
                $path = Storage::disk('commonStorage')->put($subDirePath . $file_name, $file);
                $exists = Storage::disk('commonStorage')->exists($subDirePath . $file_name);
                if ($exists) {
                    $output[] = $file_name;
                }
            }
        } catch (\Exception $exception) {
            writeToLog('processEmailData method - attachment_url store error: ' . $exception->getMessage() , 'error');
        }

        return $output;
    }

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


    protected function processPushNotifyDataTmp($data) {
        try {
            $stakeholder_uid = $data['stakeholder_uid'];
            $pushData = $data['sms_data'];

            $stakeholder = $this->checkStakeholder($stakeholder_uid);
            if (!$stakeholder) {
                throw new \Exception('Invalid stakeholder! No stakeholder found.');
            }
            if ($stakeholder && empty($stakeholder->fcm_authorize_key)) {
                throw new \Exception('Stakeholder does not have fcm authorization.');
            }

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
                $pushInput['stakeholder_fcm_authorize_key'] = $stakeholder->stakeholder_uid;

                if ($saved_notification) {
                    dispatch(new SendPushNotification(
                        $pushInput['stakeholder'],
                        ['title' => $pushInput['title'], 'content' => $pushInput['content']],
                        $pushInput['recipient'],
                        $notificationLog->id)
                    )->delay(now()->addSeconds(config('misc.notification.delay_in_second')));;
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

    protected function processPushNotifyData($data, $notify_type = 'single', $is_stakeholder = true)
    {
        try {
            $stakeholder_uid = $data['stakeholder_uid'];
            $pushData = $data['push_notification_data'];
            $fcm_authorize_key = null;

            if ($notify_type != NotifyType::$_SINGLE && $notify_type != NotifyType::$_MULTIPLE) {
                throw new \Exception('Invalid notification type.');
            }

            if ($is_stakeholder) {
                $stakeholder = $this->checkStakeholder($stakeholder_uid);
                if (!$stakeholder) {
                    throw new \Exception('Invalid stakeholder! No stakeholder found.');
                }
                if ($stakeholder && empty($stakeholder->fcm_authorize_key)) {
                    throw new \Exception('Stakeholder does not have fcm authorization.');
                }
                $fcm_authorize_key = $stakeholder->fcm_authorize_key;
            } else {
                $fcm_authorize_key = $data['stakeholder_firebase_auth_key'] ?? null;
            }

            $stakeHolderData = [
                'stakeholder_uid' => $stakeholder_uid,
                'fcm_authorize_key' => $fcm_authorize_key
            ];

            $notificationLog = PushNotificationLog::create(['request' => json_encode($pushData)]);

            $limit = ($notify_type == NotifyType::$_SINGLE) ? 1 : config('misc.notification.multiple.limit');
            $totalRecipient = ($notify_type == NotifyType::$_SINGLE) ? $limit : count($pushData['recipient']);

            if($notificationLog) {
                if ($totalRecipient > $limit) {
                    throw new \Exception('Maximum limit of push notification recipient exceeded.');
                }

                $pushInput['notify_type'] = $notify_type;
                $pushInput['recipient'] = ($notify_type == NotifyType::$_SINGLE)
                    ? $pushData['recipient'] : json_encode($pushData['recipient']);
                $pushInput['content'] = $pushData['content'];
                $pushInput['title'] = $pushData['title'] ?? '';
                $pushInput['notify_log_id'] = $notificationLog->id;
                $saved_notification = PushNotification::create($pushInput);
                $pushInput['recipient'] = $pushData['recipient'];

                if ($saved_notification) {
                    dispatch(
                        new SendPushNotification($stakeHolderData, $pushInput, $notificationLog->id, $notify_type)
                    )->delay(now()->addSeconds(config('misc.notification.delay_in_second')));
                }
            } else {
                throw new \Exception('Notification log could not be stored.');
            }
        }
        catch (\Exception $exception) {
            writeToLog('processPushNotifyData method response: ' . $exception->getMessage() , 'error');
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'Process push notify data error.';

            if(isset($notificationLog) && $notificationLog->id) {
                PushNotificationLog::find($notificationLog->id)->update(['response' => $message]);
            }
        }
    }
}
