<?php

namespace App\Jobs;

use App\EmailLog;
use App\Mail\Greet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Util\Exception;
use App\Email;

class SendGreetMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public $tries = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $recipient = $this->getData()['request_data']['recipient'];
        $cc = (isset($this->getData()['request_data']['cc']) && !empty($this->getData()['request_data']['cc'])) ?
            $this->getData()['request_data']['cc'] : [];
        $bcc = (isset($this->getData()['request_data']['bcc']) && !empty($this->getData()['request_data']['bcc'])) ?
            $this->getData()['request_data']['bcc'] : [];

        $response = Mail::to($recipient)->cc($cc)->bcc($bcc)->send(new Greet($this->getData()['request_data']));

        if (count(Mail::failures()) > 0) {
            Email::find($this->getData()['email']['id'])->update(['status' => 'failed']);
        } else {
            Email::find($this->getData()['email']['id'])->update(['status' => 'success']);
        }
        EmailLog::firstWhere('email_id', $this->getData()['email']['id'])->update(['response' => json_encode($response)]);
    }
}
