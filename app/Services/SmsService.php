<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send_sms($data, $no_of_attempt = 0)
    {
        $status = false;
        $respond_status = null;
        $respond_message = null;
        $respond_reference = null;
        $returns = null;

        try {
            $url = $data['stakeholder_url'];
            $user = $data['stakeholder_user'];
            $pass = $data['stakeholder_pass'];
            $sid = $data['stakeholder_uid'];

            $sms_data = [];
            array_push($sms_data, [$data['recipient'], $data['content']]);

            $response = Http::asForm()->post($url, [
                'user' => $user,
                'pass' => $pass,
                'sid' => $sid,
                'sms' => $sms_data
            ]);

            if ($response->status() == '200' && $response->ok() && $response->successful()) {
                $status = true;
                $respond_status = getTextBetweenTags($response, 'LOGIN');
                $respond_message = getTextBetweenTags($response, 'STAKEHOLDERID');
                $respond_reference = getTextBetweenTags($response, 'REFERENCEID');
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
        $status = false;
        $respond_status = null;
        $respond_message = null;
        $respond_reference = null;

        try {
            $url = $data['stakeholder_url'];
            $user = $data['stakeholder_user'];
            $pass = $data['stakeholder_pass'];
            $sid = $data['stakeholder_uid'];

//                make sms data ready
            $sms_data = [];
            foreach ($data['sms_collections'] as $sms) {
                array_push($sms_data, [$sms['recipient'], $sms['content']]);
            }

            $response = Http::asForm()->post($url, [
                'user' => $user,
                'pass' => $pass,
                'sid' => $sid,
                'sms' => $sms_data
            ]);

            if ($response->status() == '200' && $response->ok() && $response->successful()) {
                $status = true;
//                    $respond_status = $response->status();
                $respond_status = getTextBetweenTags($response, 'LOGIN');
                $respond_message = getTextBetweenTags($response, 'STAKEHOLDERID');
                $respond_reference = getTextBetweenTags($response, 'REFERENCEID');
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

    public function send_stakeholder_sms($data, $no_of_attempt = 0)
    {
        $status = false;
        $respond_status = null;
        $respond_message = null;
        $respond_reference = null;
        $returns = null;

        try {
            $url = config('misc.sms.url');
            $user = $data['user'];
            $pass = $data['pass'];
            $sid = $data['sid'];

            $sms_data = [];
            array_push($sms_data, [$data['recipient'], $data['content']]);

            $response = Http::asForm()->post($url, [
                'user' => $user,
                'pass' => $pass,
                'sid' => $sid,
                'sms' => $sms_data
            ]);

            if ($response->status() == '200' && $response->ok() && $response->successful()) {
                $status = true;
                $respond_status = getTextBetweenTags($response, 'LOGIN');
                $respond_message = getTextBetweenTags($response, 'STAKEHOLDERID');
                $respond_reference = getTextBetweenTags($response, 'REFERENCEID');
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

    public function send_multiple_stakeholder_sms($data, $no_of_attempt = 0)
    {
        $status = false;
        $respond_status = null;
        $respond_message = null;
        $respond_reference = null;

        try {
            $url = config('misc.sms.url');
            $user = $data['user'];
            $pass = $data['pass'];
            $sid = $data['sid'];

//                make sms data ready
            $sms_data = [];
            foreach ($data['sms_collections'] as $sms) {
                array_push($sms_data, [$sms['recipient'], $sms['content']]);
            }

            $response = Http::asForm()->post($url, [
                'user' => $user,
                'pass' => $pass,
                'sid' => $sid,
                'sms' => $sms_data
            ]);

            if ($response->status() == '200' && $response->ok() && $response->successful()) {
                $status = true;
//                    $respond_status = $response->status();
                $respond_status = getTextBetweenTags($response, 'LOGIN');
                $respond_message = getTextBetweenTags($response, 'STAKEHOLDERID');
                $respond_reference = getTextBetweenTags($response, 'REFERENCEID');
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
