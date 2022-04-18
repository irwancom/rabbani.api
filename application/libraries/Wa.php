<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Wa {

    function SendWa($phone = '', $message = '') {

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://solo.wablas.com/api/send-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('phone' => $phone,'message' => $message),
        CURLOPT_HTTPHEADER => array(
            'Authorization: QucSVjBzdeKIclyGhJ11F7YoDiPKrAXgtzebUu4V9dgOnMn4rrssH72HpN57TgtL'
        ),
        ));

        $response = curl_exec($curl);

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
