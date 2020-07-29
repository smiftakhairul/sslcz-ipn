<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\NotifyType;
use App\Http\Controllers\Controller;
use App\Traits\ProcessNotifyApiTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ProcessNotifyApiTrait, NotifyType;

    public function notify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stakeholder_uid' => 'required',
            'sms_data' => 'array',
            'push_notification_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request, NotifyType::$_SINGLE);
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        try {
            if($request->has('sms_data')) {
                $this->processSmsData(
                    $request->only(['stakeholder_uid', 'sms_data'])
                );
            }

            if($request->has('push_notification_data')) {
                $this->processPushNotifyData(
                    $request->only(['stakeholder_uid', 'push_notification_data']),
                    NotifyType::$_SINGLE
                );
            }

            return response()->json(['status' => 'success', 'message' => 'process done successfully!']);

        } catch (\Exception $exception) {
            writeToLog('Notification Notify method  response: ' . $exception->getTraceAsString() , 'error');
        }

    }

    public function multiple_notify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stakeholder_uid' => 'required',
            'sms_data' => 'array',
            'push_notification_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request, NotifyType::$_MULTIPLE);
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        try {
            if($request->has('sms_data')) {
                $this->processMultipleSmsData(
                    $request->only(['stakeholder_uid', 'sms_data'])
                );
            }

            if($request->has('push_notification_data')) {
                $this->processPushNotifyData(
                    $request->only(['stakeholder_uid', 'push_notification_data']),
                    NotifyType::$_MULTIPLE
                );
            }

            return response()->json(['status' => 'success', 'message' => 'process done successfully!']);
        } catch (\Exception $exception) {
            writeToLog('Notification Notify method  response: ' . $exception->getTraceAsString() , 'error');
        }
    }

    public function stakeholderNotify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stakeholder_uid' => 'required',
            'sms_data' => 'array',
            'push_notification_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request, NotifyType::$_SINGLE, false);
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        try {
            if($request->has('sms_data')) {
                $this->processStakeholderSmsData(
                    $request->only(['stakeholder_uid', 'stakeholder_user', 'stakeholder_pass', 'sms_data'])
                );
            }

            if($request->has('push_notification_data')) {
                $this->processPushNotifyData(
                    $request->only(['stakeholder_uid', 'stakeholder_firebase_auth_key', 'push_notification_data']),
                    NotifyType::$_SINGLE,
                    false
                );
            }

            return response()->json(['status' => 'success', 'message' => 'process done successfully!']);

        } catch (\Exception $exception) {
            writeToLog('Notification Notify method  response: ' . $exception->getTraceAsString() , 'error');
        }
    }

    public function multipleStakeholderNotify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stakeholder_uid' => 'required',
            'sms_data' => 'array',
            'push_notification_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request, NotifyType::$_MULTIPLE, false);
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        try {
            if($request->has('sms_data')) {
                $this->processMultipleStakeholderSmsData(
                    $request->only(['stakeholder_uid', 'stakeholder_user', 'stakeholder_pass', 'sms_data'])
                );
            }

            if($request->has('push_notification_data')) {
                $this->processPushNotifyData(
                    $request->only(['stakeholder_uid', 'stakeholder_firebase_auth_key', 'push_notification_data']),
                    NotifyType::$_MULTIPLE,
                    false
                );
            }

            return response()->json(['status' => 'success', 'message' => 'process done successfully!']);
        } catch (\Exception $exception) {
            writeToLog('Notification Notify method  response: ' . $exception->getTraceAsString() , 'error');
        }
    }


//    custom validation
    protected function customRequestValidation(Request $request, $type, $is_stakeholder = true)
    {
        $response = [];
        if (!$request->has('sms_data') && !$request->has('push_notification_data')) {
            return $this->customRequestValidationErrorResponse('data', [
                'sms or push notification data is required.'
            ]);
        }

//        validate sms data
        if ($request->has('sms_data')) {
            $sms_data = $request->input('sms_data');
            $sms_data = ($type == NotifyType::$_SINGLE) ? [$sms_data] : $sms_data;

            if (!empty($sms_data)) {
                foreach ($sms_data as $single_sms_data) {
                    if (!isset($single_sms_data['recipient']) || empty($single_sms_data['recipient']) || is_array($single_sms_data['recipient'])) {
                        return $this->customRequestValidationErrorResponse('sms_data', [
                            'sms recipient not found.'
                        ]);
                    } else {
                        if (!checkNumberIsValid($single_sms_data['recipient'])) {
                            return $this->customRequestValidationErrorResponse('sms_data', [
                                'sms recipient number format is invalid.'
                            ]);
                        }
                    }

                    if (!isset($single_sms_data['content']) || empty($single_sms_data['content']) || is_array($single_sms_data['content'])) {
                        return $this->customRequestValidationErrorResponse('sms_data', [
                            'sms content not found.'
                        ]);
                    }
                }
            }

            if (!$is_stakeholder) {
                if (!$request->has('stakeholder_user') || empty($request->input('stakeholder_user')) || is_array($request->input('stakeholder_user'))) {
                    return $this->customRequestValidationErrorResponse('stakeholder_user', [
                        'stakeholder user not found or invalid.'
                    ]);
                }
                if (!$request->has('stakeholder_pass') || empty($request->input('stakeholder_pass')) || is_array($request->input('stakeholder_pass'))) {
                    return $this->customRequestValidationErrorResponse('stakeholder_pass', [
                        'stakeholder pass not found or invalid.'
                    ]);
                }
            }
        }

//        validate push notification data
        if ($request->has('push_notification_data')) {
            $push_notification_data = $request->input('push_notification_data');

            if ($type == NotifyType::$_SINGLE) {
                if (!isset($push_notification_data['recipient'])
                        || empty($push_notification_data['recipient'])
                        || is_array($push_notification_data['recipient'])) {
                    return $this->customRequestValidationErrorResponse('push_notification_data', [
                        'push notification recipient not found or invalid.'
                    ]);
                }
            }
            if ($type == NotifyType::$_MULTIPLE) {
                if (!isset($push_notification_data['recipient']) || !is_array($push_notification_data['recipient'])) {
                    return $this->customRequestValidationErrorResponse('push_notification_data', [
                        'push notification recipient not found or invalid.'
                    ]);
                } else {
                    foreach ($push_notification_data['recipient'] as $recipient) {
                        if (empty($recipient)) {
                            return $this->customRequestValidationErrorResponse('push_notification_data', [
                                'push notification recipient not found or invalid.'
                            ]);
                        }
                    }
                }
            }

            if (!isset($push_notification_data['content']) || empty($push_notification_data['content']) || is_array($push_notification_data['content'])) {
                return $this->customRequestValidationErrorResponse('push_notification_data', [
                    'push notification content not found or invalid.'
                ]);
            }

            if (isset($push_notification_data['title']) && (empty($push_notification_data['title']) || is_array($push_notification_data['title']))) {
                return $this->customRequestValidationErrorResponse('push_notification_data', [
                    'push notification title not found or invalid.'
                ]);
            }

            if (!$is_stakeholder) {
                if (!$request->has('stakeholder_firebase_auth_key') || empty($request->input('stakeholder_firebase_auth_key')) || is_array($request->input('stakeholder_firebase_auth_key'))) {
                    return $this->customRequestValidationErrorResponse('stakeholder_firebase_auth_key', [
                        'stakeholder firebase auth key not found or invalid.'
                    ]);
                }
            }
        }

        return $response;
    }

    protected function customRequestValidationErrorResponse($key, $message)
    {
        return [
            'status' => 'error',
            'validation' => [
                $key => $message
            ]
        ];
    }
}
