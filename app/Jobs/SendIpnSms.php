<?php

namespace App\Jobs;

use App\Models\Sms;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nexmo\Laravel\Facade\Nexmo;
use PHPUnit\Util\Exception;

class SendIpnSms implements ShouldQueue
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
            $smsService = new SmsService();
            $result = $smsService->send_sms($this->data);
            if (isset($result['status']) && $result['status']) {
                Sms::firstWhere('sms_log_id',$this->data['sms_log_id'])->update(['status' => 'success']);
            } else {
                Sms::firstWhere('sms_log_id',$this->data['sms_log_id'])->update(['status' => 'failed']);
            }
            SmsLog::firstWhere('id', $this->data['sms_log_id'])->update(['response' => json_encode($result)]);
        } catch (\Exception $e) {
            $message = ($e->getMessage()) ? $e->getMessage() :'job running error';
            SmsLog::firstWhere('id', $this->data['sms_log_id'])->update(['response' => $message]);
            Sms::firstWhere('sms_log_id',$this->data['sms_id'])->update(['status' => 'failed']);
        }
    }
}
