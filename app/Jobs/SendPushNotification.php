<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Models\PushNotificationLog;
use App\Models\SmsLog;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class SendPushNotification implements ShouldQueue
{
    /***
    * To dispatch this job one should call
    * dispatch(new SendPushNotification($this->data, $fcmTokens));
    * Where array $data must contains title & description field
    * @author Risul Islam <risul.islam@sslwireless.com><risul321@gmail.com>
    */
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $stakeholder;
    private $push_data;
    private $log_id;
    private $notify_type;
    private $firebase_url;

    public $tries = 5;

    /**
     * SendPushNotification constructor.
     * @param $stakeholder
     * @param $push_data
     * @param $log_id
     * @param $notify_type
     */
    public function __construct($stakeholder, $push_data, $log_id, $notify_type)
    {
        $this->stakeholder = $stakeholder;
        $this->push_data = $push_data;
        $this->log_id = $log_id;
        $this->notify_type = $notify_type;
        $this->firebase_url = config('misc.firebase.url');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $pnService = new PushNotificationService();
            $response = $pnService->sendPushNotification(
                $this->stakeholder,
                $this->push_data,
                $this->notify_type,
                $this->firebase_url
            );

            if(isset($response['success']) && $response['success']) {
                PushNotification::firstWhere('notify_log_id', $this->log_id)->update(['status' => 'success']);
            } else {
                PushNotification::firstWhere('notify_log_id', $this->log_id)->update(['status' => 'failed']);
            }
            PushNotificationLog::find($this->log_id)->update(['response' => json_encode($response)]);
        } catch (Exception $exception) {
            writeToLog('Firebase Push notification response:' . $exception->getMessage(), 'error');
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'job running error';
            PushNotificationLog::find($this->log_id)->update(['response' => $message]);
            PushNotification::firstWhere('notify_log_id', $this->log_id)->update(['response' => json_encode($response)]);
        }
    }
}
