<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Otp {

    function SendOtp($phone = '', $message = '') {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://sms114.xyz/sms/wamasking.php?username=simsms&key=9d8d09cc7a767df670ccb5a5d71fe77e&number=" . $phone . "&message=" . urlencode($message),
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

