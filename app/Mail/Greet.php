<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class Greet extends Mailable
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
        $email = $this->from($this->data['sender'])->view('mail.greet');

        if (!empty($this->data['email_attachments'])) {
            foreach ($this->data['email_attachments'] as $email_attachment) {
                $email->attach(storage_path('app/public/' . $email_attachment['path']), [
                    'as' => $email_attachment['filename'],
                    'mime' => $email_attachment['mime'],
                ]);
            }
        }

        $email->with([
            'data' => $this->data
        ]);

        return $email;
    }
}
