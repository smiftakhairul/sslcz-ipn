<?php

namespace App\Http\Controllers;

use App\Events\GreetMailEvent;
use App\Mail\Greet;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Mail;
use App\Email;

class EmailController extends Controller
{
    public function show()
    {
        $sender = User::firstWhere('email', 's.m.iftakhairul@gmail.com');
        $users = User::where('email', '!=', $sender->email)->get();

        return view('email.show')->with([
            'sender' => $sender,
            'users' => $users
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'sender' => 'required|max:255',
            'recipient' => 'required',
            'subject' => 'required|min:10|max:255',
            'content' => 'required|min:200',
        ]);

//        dd($request->all());

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

        return redirect()->route('email.show')->with('success', 'Success! Mail has been sent.');
    }
}
