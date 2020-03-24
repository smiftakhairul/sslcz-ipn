<?php

namespace App\Http\Controllers;

use App\EmailAttachment;
use App\EmailLog;
use App\Events\GreetMailEvent;
use App\Mail\Greet;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Mail;
use App\Email;
use Illuminate\Support\Facades\Storage;

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
//            'attachments.*' => 'file|mimes:pdf|size:6144'
        ]);

//        dd($request->all());
        $request_data = $request->all();

        $request->merge(['recipient' => json_encode($request->recipient)]);
        if (isset($request->cc) && !empty($request->cc)) {
            $request->merge(['cc' => json_encode($request->cc)]);
        }
        if (isset($request->bcc) && !empty($request->bcc)) {
            $request->merge(['bcc' => json_encode($request->bcc)]);
        }

        $email = Email::create($request->except('attachments'));
        $email_attachments = [];

        if ($email && isset($request->attachments) && !empty($request->attachments)) {
            foreach ($request->attachments as $attachment) {
                $filename = $attachment->getClientOriginalName();
                $mime = $attachment->getClientMimeType();

                if (Storage::disk('public')->exists('attachments/emails/' . $email->id . '/'
                    . $attachment->getClientOriginalName())) {
                    $filename = time() . '_' . $attachment->getClientOriginalName();
                }

                $path = Storage::disk('public')->putFileAs('attachments/emails/' . $email->id, $attachment, $filename);
                $email_attachment = EmailAttachment::create([
                    'email_id' => $email->id,
                    'path' => $path
                ]);

                if ($email_attachment) {
                    array_push($email_attachments, [
                        'path' => $path,
                        'filename' => $filename,
                        'mime' => $mime
                    ]);
                }
            }
        }

        $request_data['email_attachments'] = $email_attachments;
        unset($request_data['attachments']);

//        write log
        if ($email) {
            $email_log = EmailLog::create([
                'email_id' => $email->id,
                'request' => json_encode($request_data)
            ]);
        }

        event(new GreetMailEvent(['request_data' => $request_data, 'email' => $email]));

        return redirect()->route('email.show')->with('success', 'Success! Mail has been sent.');
    }
}
