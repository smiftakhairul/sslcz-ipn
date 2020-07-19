<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Traits\ProcessNotifyApiTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ProcessNotifyApiTrait;

    public function notify(Request $request) {
        $validator = Validator::make($request->all(), [
            'stakeholder_uid' => 'required',
            'sms_data' => 'required|array',
            'push_notification_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request, 'single');
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        $data = $request->only(['stakeholder_uid', 'sms_data']);

        try {
            if($data) {
                $this->processSmsData($data);
            }

            $pushNotificationData = $request->input('push_notification_data');

            if($pushNotificationData) {
                $this->processPushNotifyData($pushNotificationData);
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
            'sms_data' => 'required|array',
            'push_notification_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request, 'multiple');
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        $data = $request->only(['stakeholder_uid', 'sms_data']);

        try {
            if ($data) {
                $this->processMultipleSmsData($data);
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
            'stakeholder_user' => 'required',
            'stakeholder_pass' => 'required',
            'sms_data' => 'required|array',
            'push_notification_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request, 'single');
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        $data = $request->only([
            'stakeholder_uid',
            'stakeholder_user',
            'stakeholder_pass',
            'sms_data'
        ]);

        try {
            if($data) {
                $this->processStakeholderSmsData($data);
            }

            $pushNotificationData = $request->input('push_notification_data');

            if($pushNotificationData) {
                $this->processPushNotifyData($pushNotificationData);
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
            'stakeholder_user' => 'required',
            'stakeholder_pass' => 'required',
            'sms_data' => 'required|array',
            'push_notification_data' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request, 'multiple');
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        $data = $request->only([
            'stakeholder_uid',
            'stakeholder_user',
            'stakeholder_pass',
            'sms_data'
        ]);

        try {
            if ($data) {
                $this->processMultipleStakeholderSmsData($data);
            }

            return response()->json(['status' => 'success', 'message' => 'process done successfully!']);
        } catch (\Exception $exception) {
            writeToLog('Notification Notify method  response: ' . $exception->getTraceAsString() , 'error');
        }
    }


//    custom validation
    protected function customRequestValidation(Request $request, $type)
    {
        $response = [];
        if ($request->has('push_notification_data')) {
            if (!is_array($request->input('push_notification_data')) || empty($request->input('push_notification_data'))) {
                return $response = [
                    'status' => 'error',
                    'validation' => ['push_notification_data' => 'push notification data is invalid.']
                ];
            }
            if (!$request->has('stakeholder_firebase_auth_key')) {
                return $response = [
                    'status' => 'error',
                    'validation' => ['stakeholder_firebase_auth_key' => 'stakeholder firebase auth key field is required.']
                ];
            }
        }
        if ($request->has('stakeholder_firebase_auth_key')) {
            if (empty($request->input('stakeholder_firebase_auth_key'))) {
                return $response = [
                    'status' => 'error',
                    'validation' => ['stakeholder_firebase_auth_key' => 'stakeholder firebase auth key is invalid.']
                ];
            }
            if (!$request->has('push_notification_data')) {
                return $response = [
                    'status' => 'error',
                    'validation' => ['push_notification_data' => 'push notification data field is required.']
                ];
            }
        }
        if ($type == 'single') {
            if (!checkNumberIsValid($request->input('sms_data')['recipient'])) {
                return $response = [
                    'status' => 'error',
                    'validation' => ['sms_data' => 'Invalid recipient number format.']
                ];
            }
        } elseif ($type == 'multiple') {
            foreach ($request->input('sms_data') as $sms) {
                if (!checkNumberIsValid($sms['recipient'])) {
                    return $response = [
                        'status' => 'error',
                        'validation' => ['sms_data' => 'Invalid recipient number format.']
                    ];
                }
            }
        }

        return $response;
    }
}
