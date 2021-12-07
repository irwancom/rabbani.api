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

    function SendWa2($name ='',$phone = '', $message = '') {
        $userkey = 'ba2d2d4ae8d5';
        $passkey = 'c1ac28hnu3';
        $telepon = $phone;
        $image_link = 'http://api.rmall.id/file/img.png';
        if(!empty($name)){
            $name = $name;
        }else{
            $name = 'xxx';
        }
        $caption = 'Assalamualaikum kak '.$name.'
            
Rabbani Holding is inviting you to a scheduled Zoom meeting.

Topic: Rabbani Talks w/ Ust. Nur Ihsan Jundulloh, Lc. Tema: Kutinggalkan dengan Bismillah diakhiri Alhamdulillah
Time: This is a recurring meeting Meet anytime

Join Zoom Meeting
https://us02web.zoom.us/j/5725504765?pwd=UDhIWGFSb2NTQ1lUeU5aODVKYnZFdz09

Meeting ID: 572 550 4765
Passcode: rabbani

No Message '. rand(0000000000, 9999999999);
        
        $data = array(
            'userkey' => $userkey,
            'passkey' => $passkey,
            'nohp' => $telepon,
            'link' => $image_link,
            'caption' => $caption
        );
        
        $url = 'https://gsm.zenziva.net/api/sendWAFile/';
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
        $results = json_decode(curl_exec($curlHandle), true);
        print_r($results);
        print_r($data);
        curl_close($curlHandle);
        
    }

}
