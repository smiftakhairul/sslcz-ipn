<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\NotifyType;
use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\EmailLog;
use App\Events\GreetMailEvent;
use App\Http\Controllers\Controller;
use App\Mail\Greet;
use App\Traits\ProcessNotifyApiTrait;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    use ProcessNotifyApiTrait;

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender' => 'required|array',
            'recipient' => 'required|array',
            'subject' => 'required',
            'content' => 'required',
            'cc' => 'array',
            'bcc' => 'array',
            'attachments' => 'array',
            'attachments.*' => 'required|file|max:'.config('misc.email.max_email_attachment_size'),
            'attachment_url' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'validation' => $validator->errors()]);
        }

        $validationResponse = $this->customRequestValidation($request);
        if (!empty($validationResponse) && isset($validationResponse['status']) && $validationResponse['status'] == 'error') {
            return response()->json([
                'status' => $validationResponse['status'],
                'validation' => $validationResponse['validation']
            ]);
        }

        $requestData = $this->manageInput($request);

        try {
            if ($requestData && !empty($requestData)) {
                $this->processEmailData($requestData);
            }

            return response()->json(['status' => 'success', 'message' => 'process done successfully!']);
        } catch (\Exception $exception) {
            Log::error('Email Send: ' . $exception->getMessage().'--'.$exception->getFile().'--'.$exception->getLine());
        }
    }

    protected function manageInput(Request $request)
    {
        $input = [];
        $sender = $request->input('sender') ?? [];
        if (!empty($sender)) {
            $sender['name'] = (isset($sender['name']) && !empty($sender['name'])) ? $sender['name'] : $sender['email'];
        }
        $input['sender'] = $sender;
        $input['recipient'] = $request->input('recipient') ?? [];
        $input['subject'] = $request->input('subject') ?? '';
        $input['content'] = $request->input('content') ?? '';
        $input['cc'] = $request->input('cc') ?? [];
        $input['bcc'] = $request->input('bcc') ?? [];
        $input['attachments'] = $request->file('attachments') ?? [];
        $input['attachment_url'] = $request->input('attachment_url') ?? [];
        return $input;
    }

    protected function customRequestValidation(Request $request)
    {
        if (is_array($request->input('sender')) && !empty($request->input('sender'))) {
            if (!isset($request->input('sender')['email']) || empty($request->input('sender')['email'])) {
                return $this->customRequestValidationErrorResponse('sender', [
                    'Sender email not found.'
                ]);
            } else {
                if (!$this->isEmail($request->input('sender')['email'])) {
                    return $this->customRequestValidationErrorResponse('sender', [
                        'Sender must be email.'
                    ]);
                }
            }
        }
        if (is_array($request->input('recipient')) && !empty($request->input('recipient'))) {
            foreach ($request->input('recipient') as $recipient) {
                if (!$this->isEmail($recipient)) {
                    return $this->customRequestValidationErrorResponse('recipient', [
                        'Recipient must be email.'
                    ]);
                }
            }
        }
        if ($request->has('cc') && is_array($request->input('cc')) && !empty($request->input('cc'))) {
            foreach ($request->input('cc') as $cc) {
                if (!$this->isEmail($cc)) {
                    return $this->customRequestValidationErrorResponse('cc', [
                        'CC must be email.'
                    ]);
                }
            }
        }
        if ($request->has('bcc') && is_array($request->input('bcc')) && !empty($request->input('bcc'))) {
            foreach ($request->input('bcc') as $bcc) {
                if (!$this->isEmail($bcc)) {
                    return $this->customRequestValidationErrorResponse('bcc', [
                        'BCC must be email.'
                    ]);
                }
            }
        }
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

    protected function isEmail($email)
    {
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        return false;
    }
}
