<?php

namespace App\Jobs;

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
        Mail::to($this->getData()['recipient'])->send(new Greet($this->getData()['request_data']));
        if (count(Mail::failures()) > 0 && in_array($this->getData()['recipient'], Mail::failures())) {
            Email::find($this->getData()['request_data']['id'])->update(['status' => 'failed']);
        } else {
            Email::find($this->getData()['request_data']['id'])->update(['status' => 'success']);
        }
    }
}
