<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Events\GreetSmsEvent;
use App\Notifications\SendGreetSMS;
use App\Models\Sms;
use App\Models\SmsLog;
use Bitfumes\KarixNotificationChannel\KarixChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Nexmo\Laravel\Facade\Nexmo;
use PHPUnit\Exception;

class SmsController extends Controller
{
    protected $sender = '+8801630132436';

    public function show()
    {
        $users = [
            '+8801630132436' => 'S M Iftakhairul',
            '+8801714847800' => 'Sheikh Ikram',
            '+8801716892046' => 'Moin Uddin',
            '+8801689028425' => 'Md Rhizu',
            '+8801674990944' => 'Rakib Ul Islam Rizu',
        ];
        return view('sms.show')->with([
            'users' => $users
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'recipient' => 'required',
            'content_data' => 'required|min:5',
        ]);

        foreach ($request->recipient as $recipient) {
            $sms = [
                '_token' => $request->_token,
                'sender' => $this->sender,
                'recipient' => $recipient,
                'content' => $request->content_data,
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

        return redirect()->route('sms.show')->with('success', 'Success! SMS has been sent.');
    }
}
