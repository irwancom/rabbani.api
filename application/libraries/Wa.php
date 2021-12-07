<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Wa {

    function SendWa($phone = '', $message = '') {
        $curl = curl_init();
        $token = "Z1ZrcYypiCPBJQ9NPAGc6SInTxj6dgiFZ8Km4c7EQziqKwNfa9pxlJpnZuI2QUpy";
        $data = [
            'phone' => $phone,
            'message' => $message
        ];

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $token",
                )
        );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_URL, "https://kemusu.wablas.com/api/send-message");
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($curl);
        curl_close($curl);
    }

   

}
