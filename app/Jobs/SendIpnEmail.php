<?php

namespace App\Jobs;

use App\Mail\IpnEmail;
use App\Models\Email;
use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendIpnEmail implements ShouldQueue
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

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $response = Mail::to($this->data['recipient'])->cc($this->data['cc'])->bcc($this->data['bcc'])
                ->send(new IpnEmail($this->data));

            $mail_failures = count(Mail::failures()) > 0;
            $status = $mail_failures ? 'failed' : 'success';
            $message = $mail_failures ? 'Mail send error.' : 'Mail successfully sent';

            EmailLog::firstWhere('id', $this->data['email_log_id'])->update(['response' => $message]);
            Email::firstWhere('email_log_id',$this->data['email_log_id'])->update(['status' => $status]);
        } catch (\Exception $exception) {
            $message = ($exception->getMessage()) ? $exception->getMessage() :'job running error';
            EmailLog::firstWhere('id', $this->data['email_log_id'])->update(['response' => $message]);
            Email::firstWhere('email_log_id',$this->data['email_log_id'])->update(['status' => 'failed']);
        }
    }
}
