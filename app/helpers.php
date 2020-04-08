<?php

if (!function_exists('callToApi')) {


    /**
     * @param $url
     * @param $data
     * @param array $header
     * @param string $method
     * @param string $port
     * @return bool|string
     */
    function callToApi($url, $data, $header = [], $method = 'POST')
    {
        try {
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // The default value for this option is 2. It means, it has to have the same name in the certificate as is in the URL you operate against.
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($curl, CURLOPT_TIMEOUT, 20);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            if ($method == 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlErrorNo = curl_errno($curl);
            curl_close($curl);

            if ($code == 200 & !($curlErrorNo)) {
                return $response;
            } else {
                $logMessage = "FAILED TO CONNECT WITH EXTERNAL API due to ". $err . " and cURL error code is " . $code . " and response is: " . $response;
                writeToLog($logMessage, 'alert');

                return $response;
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}

/*number validate check*/
if (!function_exists('checkNumberIsValid')) {
    function checkNumberIsValid($msisdn)
    {
        $regexp = "/^(88|)01[3,4,5,6,7,8,9]{1}[0-9]{8}$/";
        if (preg_match($regexp, $msisdn)) return true;
        return false;
    }
}


if (!function_exists('writeToLog')) {
    /**
     * @param $logMessage
     * @param string $logType
     */
    function writeToLog($logMessage, $logType = 'error')
    {
        try {
            $allLogTypes = ['alert', 'critical', 'debug', 'emergency', 'error', 'info'];

            $logType = strtolower($logType);

            if (in_array($logType, $allLogTypes)) {
                \Log::$logType($logMessage);
            }

        } catch (\Exception $exception) {
            //
        }
    }
}

if (!function_exists('getTextBetweenTags')) {
# XML PARSE  FUNCTION
    function getTextBetweenTags($string, $tagname)
    {
        $pattern = "/<$tagname ?.*>(.*)<\/$tagname>/";
        preg_match($pattern, $string, $matches);
        return isset($matches[1]) ? $matches[1] : "";
    }
}
