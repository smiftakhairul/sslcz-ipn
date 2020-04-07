<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\EmailLog;
use App\Events\GreetMailEvent;
use App\Http\Controllers\Controller;
use App\Mail\Greet;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

        return response()->json(['status' => 'success', 'message' => 'Mail sent successfully!']);
    }
}
