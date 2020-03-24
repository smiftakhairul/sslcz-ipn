<?php

namespace App\Http\Controllers\API\v1;

use App\Events\GreetSmsEvent;
use App\Http\Controllers\Controller;
use App\Sms;
use App\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    protected $sender = '+8801630132436';

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient' => 'required',
            'content' => 'required|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        foreach ($request->recipient as $recipient) {
            $sms = [
                '_token' => $request->_token,
                'sender' => $this->sender,
                'recipient' => $recipient,
                'content' => $request->content,
            ];
            $saved_sms = Sms::create($sms);

//        write log
            if ($saved_sms) {
                $sms_log = SmsLog::create([
                    'sms_id' => $saved_sms->id,
                    'request' => json_encode($sms)
                ]);
            }

            event(new GreetSmsEvent(['request_data' => $saved_sms]));
        }

        return response()->json(['status' => 'success', 'message' => 'SMS sent successfully!']);
    }
}
