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
use Illuminate\Support\Facades\Log;

class SendIpnMultipleSms implements ShouldQueue
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
        $sms_log_id = null;
        foreach ($this->data as $data) {
            $sms_log_id = $data['sms_log_id'];
            break;
        }

        try {
            $sms_service = new SmsService();
            $result = $sms_service->send_multiple_sms($this->data);

            if (isset($result['status']) && $result['status']) {
                Sms::where('sms_log_id', $sms_log_id)->update(['status' => 'success']);
            } else {
                Sms::where('sms_log_id', $sms_log_id)->update(['status' => 'failed']);
            }

            SmsLog::firstWhere('id', $sms_log_id)->update(['response' => json_encode($result)]);
        } catch (\Exception $exception) {
            Log::debug('SMS Log ID: ', $sms_log_id);
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'job running error';
            SmsLog::firstWhere('id', $sms_log_id)->update(['response' => $message]);
            Sms::where('sms_log_id', $sms_log_id)->update(['status' => 'failed']);
        }
    }
}
