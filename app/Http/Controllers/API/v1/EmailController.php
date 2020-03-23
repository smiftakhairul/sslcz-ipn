<?php

namespace App\Http\Controllers\API\v1;

use App\Email;
use App\Events\GreetMailEvent;
use App\Http\Controllers\Controller;
use App\Mail\Greet;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender' => 'required|max:255',
            'recipient' => 'required',
            'subject' => 'required|min:10|max:255',
            'content' => 'required|min:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

//        return response()->json($request->all());

        foreach ($request->recipient as $index => $recipient) {
            $email = [
                '_token' => $request->_token,
                'sender' => $request->sender,
                'recipient' => $recipient,
                'subject' => $request->subject,
                'content' => $request->content
            ];
            $saved_email = Email::create($email);
            event(new GreetMailEvent(['recipient' => $recipient, 'request_data' => $saved_email]));
        }

        if (isset($request->cc) && !empty($request->cc)) {
            foreach ($request->cc as $index => $cc) {
                $email = [
                    '_token' => $request->_token,
                    'sender' => $request->sender,
                    'recipient' => $cc,
                    'subject' => $request->subject,
                    'content' => $request->content,
                    'type' => 'cc'
                ];
                $saved_email = Email::create($email);
                event(new GreetMailEvent(['recipient' => $cc, 'request_data' => $saved_email]));
            }
        }

        if (isset($request->bcc) && !empty($request->bcc)) {
            foreach ($request->bcc as $index => $bcc) {
                $email = [
                    '_token' => $request->_token,
                    'sender' => $request->sender,
                    'recipient' => $bcc,
                    'subject' => $request->subject,
                    'content' => $request->content,
                    'type' => 'cc'
                ];
                $saved_email = Email::create($email);
                event(new GreetMailEvent(['recipient' => $bcc, 'request_data' => $saved_email]));
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Mail sent successfully!']);
    }
}
