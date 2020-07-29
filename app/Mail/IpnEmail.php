<?php

namespace App\Mail;

use App\Models\Email;
use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IpnEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            $email = $this->view('mail.v1.email');
            if ($this->data['sender']['email'] == $this->data['sender']['name']) {
                $email->from($this->data['sender']['email']);
            } else {
                $email->from($this->data['sender']['email'], $this->data['sender']['name']);
            }
            $email->subject($this->data['subject']);

            if (!empty($this->data['attachments'])) {
                $subDirePath = 'uploads/email-attachments/';
                foreach ($this->data['attachments'] as $attachment) {
                    $email->attachFromStorageDisk('commonStorage', $subDirePath . '/' . $attachment);
                }
            }

            $email->with(['body' => $this->data['content']]);
        } catch (\Exception $exception) {
            Log::error('Email process error: ' . $exception->getMessage().'--'.$exception->getFile().'--'.$exception->getLine());
            $message = ($exception->getMessage()) ? $exception->getMessage() :'job running error';
            EmailLog::firstWhere('id', $this->data['email_log_id'])->update(['response' => $message]);
            Email::firstWhere('email_log_id',$this->data['email_log_id'])->update(['status' => 'failed']);
        }
    }
}
