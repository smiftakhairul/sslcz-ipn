<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send_sms($sms_to = "", $sms_msg = "", $no_of_attempt = 0)
    {
        $status = false;
        $respond_status = "";
        $respond_message = "";
        $respond_reference = "";
        $returns = "";
        try {
            if (checkNumberIsValid($sms_to)) {
                $url = config('misc.sms.url');

                $user = config('misc.sms.user');
                $pass = config('misc.sms.password');
                $sid = config('misc.sms.sid');

                /* TODO: Need to remove temporary code when test done. */
                /* Start of Temp code */
               /* return array(
                    'status' => true,
                    'respond_status' => "SUCCESSFULL",
                    'respond_message' => "SUCCESSFULL",
                    'respond_reference' => "SUCCESSFULL",
                );*/
                /* End of temp code */

                $param = "user=$user&pass=$pass&sms[0][0]=" . urlencode($sms_to) . "&sms[0][1]=" . urlencode($sms_msg) . "&sid=$sid";

                $returns = callToApi($url, $param, [], 'POST');

                writeToLog(json_encode($returns), 'debug');
                $respond_status = getTextBetweenTags($returns, 'LOGIN');
                $respond_message = getTextBetweenTags($returns, 'STAKEHOLDERID');
                $respond_reference = getTextBetweenTags($returns, 'REFERENCEID');

                if ($respond_status == 'SUCCESSFULL' && $respond_message != 'INVALID') {
                    $status = true;
                } else {
                    $status = false;
                }
            } else {
                $status = false;
            }

            return array(
                'status' => $status,
                'respond_status' => $respond_status,
                'respond_message' => $respond_message,
                'respond_reference' => $respond_reference,
            );
        } catch (\Exception $exception) {
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'Something went wrong';
            return array(
                'status' => false,
                'respond_message' => $message
            );
        }
    }

    public function send_multiple_sms($data)
    {
        $status = true;
        $respond_status = null;
        $respond_message = null;
        $respond_reference = null;

        try {
            foreach ($data as $sms) {
                if (!checkNumberIsValid($sms['recipient'])) {
                    $status = false;
                    $respond_status = 403;
                    $respond_message = 'Invalid recipient';
                    break;
                }
            }

            if ($status) {
                $url = config('misc.sms.url');
                $user = config('misc.sms.user');
                $pass = config('misc.sms.password');
                $sid = config('misc.sms.sid');

//                make sms data ready
                $sms_data = [];
                foreach ($data as $sms) {
                    array_push($sms_data, [$sms['recipient'], $sms['content']]);
                }

                $response = Http::asForm()->post($url, [
                    'user' => $user,
                    'pass' => $pass,
                    'sid' => $sid,
                    'sms' => $sms_data
                ]);

//                Log::debug('Response', [
//                    'main' => $response,
//                    'json' => $response->json(),
//                    'status' => $response->status(),
//                    'ok' => $response->ok(),
//                    'successful' => $response->successful(),
//                    'server_error' => $response->serverError(),
//                    'client_error' => $response->clientError(),
//                    'headers' => $response->headers()
//                ]);

                if ($response->status() == '200' && $response->ok() && $response->successful()) {
                    $status = true;
//                    $respond_status = $response->status();
                    $respond_status = getTextBetweenTags($response, 'LOGIN');
                    $respond_message = getTextBetweenTags($response, 'STAKEHOLDERID');
                    $respond_reference = getTextBetweenTags($response, 'REFERENCEID');

                }
            }

            return [
                'status' => $status,
                'respond_status' => $respond_status,
                'respond_message' => $respond_message,
                'respond_reference' => $respond_reference,
            ];
        } catch (\Exception $exception) {
            $message = ($exception->getMessage()) ? $exception->getMessage() : 'Something went wrong';
            return [
                'status' => false,
                'respond_message' => $message
            ];
        }
    }
}
