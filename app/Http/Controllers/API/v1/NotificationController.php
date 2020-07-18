<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Traits\ProcessNotifyApiTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ProcessNotifyApiTrait;

    public function notify(Request $request) {
        $validator = Validator::make($request->all(), [
            'sms_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $smsData = $request->input('sms_data');

        if (!checkNumberIsValid($smsData['recipient'])) {
            return response()->json([
                'status' => 'error',
                'validation' => ['sms_data' => 'Invalid recipient number format.']
            ]);
        }

        try {
            if($smsData) {
                $this->processSmsData($smsData);
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
            'sms_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        foreach ($request->input('sms_data') as $sms) {
            if (!checkNumberIsValid($sms['recipient'])) {
                return response()->json([
                    'status' => 'error',
                    'validation' => ['sms_data' => 'Invalid recipient number format.']
                ]);
            }
        }

        try {
            $sms_data = $request->input('sms_data');

            if ($sms_data) {
                $this->processMultipleSmsData($sms_data);
            }

            return response()->json(['status' => 'success', 'message' => 'process done successfully!']);
        } catch (\Exception $exception) {
            writeToLog('Notification Notify method  response: ' . $exception->getTraceAsString() , 'error');
        }
    }

    public function stackholderNotify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sid' => 'required',
            'user' => 'required',
            'pass' => 'required',
            'sms_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $smsData = $request->input('sms_data');
        $stackholderData = [
            'sid' => $request->input('sid'),
            'user' => $request->input('user'),
            'pass' => $request->input('pass'),
        ];

        if (!checkNumberIsValid($smsData['recipient'])) {
            return response()->json([
                'status' => 'error',
                'validation' => ['sms_data' => 'Invalid recipient number format.']
            ]);
        }

        try {
            if($smsData) {
                $this->processStackholderSmsData($stackholderData, $smsData);
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
}
