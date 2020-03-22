<?php

namespace App\Jobs;

use App\Sms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nexmo\Laravel\Facade\Nexmo;

class SendGreetSms implements ShouldQueue
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
        $response = Nexmo::message()->send([
            'to'   => $this->getData()['request_data']['recipient'],
            'from' => $this->getData()['request_data']['sender'],
            'text' => $this->getData()['request_data']['content']
        ]);

        if ($response) {
            Sms::find($this->getData()['request_data']['id'])->update(['status' => 'success']);
        } else {
            Sms::find($this->getData()['request_data']['id'])->update(['status' => 'failed']);
        }
    }
}
