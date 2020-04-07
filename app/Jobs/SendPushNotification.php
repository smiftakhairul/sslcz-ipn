<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Models\PushNotificationLog;
use App\Models\SmsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotification implements ShouldQueue
{
    /***
    * To dispatch this job one should call
    * dispatch(new SendPushNotification($this->data, $fcmTokens));
    * Where array $data must contains title & description field
    * @author Risul Islam <risul.islam@sslwireless.com><risul321@gmail.com>
    */
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;
    private $fcm_token;
    private $notificationId;

    public $tries = 5;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $recipient, $notifyId=0)
    {
        $this->data = $data;
        $this->fcm_token = $recipient;
        $this->notificationId = $notifyId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $url = config('misc.firebase.url');
            $fields = array(
                "priority" => "high",
                "data" => $this->data,
            );

            if (is_array($this->fcm_token)) {
                $fields["registration_ids"] = $this->fcm_token;
            } else {
                $fields["to"] = $this->fcm_token;
            }

            $headers = array(
                'Authorization: key=' . config('misc.firebase.authorization_key'),
                'Content-Type: application/json'
            );

            $result = callToApi($url, json_encode($fields), $headers, 'POST');
            writeToLog('Firebase Push notification response; ' . $result, 'info');
            $response = json_decode($result, true);

            if(isset($response['success']) && $response['success'] == 1) {
                PushNotification::firstWhere('notify_log_id', $this->notificationId)->update(['status' => 'success']);

            } else {
                PushNotification::firstWhere('notify_log_id', $this->notificationId)->update(['status' => 'failed']);
            }


            PushNotificationLog::firstWhere('id', $this->notificationId)->update(['response' => json_encode($result)]);
        } catch (\Exception $exception) {
            writeToLog('Firebase Push notification response; ' . $exception->getMessage(), 'error');
            $message = ($exception->getMessage()) ? $exception->getMessage() :'job running error';
            PushNotificationLog::firstWhere('id', $this->notificationId)->update(['response' => $message]);
            PushNotification::firstWhere('notify_log_id', $this->notificationId)->update(['response' => json_encode($result)]);
        }

    }
}
