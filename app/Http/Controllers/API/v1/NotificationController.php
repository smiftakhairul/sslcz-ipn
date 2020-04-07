<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Traits\ProcessNotifyApiTrait;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ProcessNotifyApiTrait;

    public function notify(Request $request) {
        try {

            $smsData = $request->input('sms_data');

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
}
