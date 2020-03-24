<?php

namespace App\Jobs;

use App\Sms;
use App\SmsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nexmo\Laravel\Facade\Nexmo;
use PHPUnit\Util\Exception;

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
        try {
            $message = Nexmo::message()->send([
                'to' => $this->getData()['request_data']['recipient'],
                'from' => $this->getData()['request_data']['sender'],
                'text' => $this->getData()['request_data']['content']
            ]);

            $response = $message->getResponseData();

            if ($response['messages'][0]['status'] == 0) {
                Sms::find($this->getData()['request_data']['id'])->update(['status' => 'success']);
            } else {
                Sms::find($this->getData()['request_data']['id'])->update(['status' => 'failed']);
            }
            SmsLog::firstWhere('sms_id', $this->getData()['request_data']['id'])->update(['response' => json_encode($response)]);
        } catch (\Exception $e) {
            Sms::find($this->getData()['request_data']['id'])->update(['status' => 'failed']);
        }
    }
}
