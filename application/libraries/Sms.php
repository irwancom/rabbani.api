<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Sms {

    function SendSms($phone = '', $message = '') {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://sms241.xyz/sms/smsmasking.php?username=simsms&key=e31e22edcca0e660aa8c03555e2cbbad&number=".$phone."&message=". urlencode($message),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

}
