<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Email {

    function EmailSend($data) {
    //print_r($email_;exit;
	$curl = curl_init();

  curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.1itmedia.co.id/ece/sendMail",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => "domain=mg.rmall.id&to=".($data['email'])."&from=".($data['from'])."&subject=".($data['subject'])."&text=".($data['text'])."",
  CURLOPT_HTTPHEADER => array(
    "X-Token-KeyCode: 5bda62e71cd63f3f5fc2c76f9a37894f",
    "X-Token-Secret: 2dc2968735c4fa0b047834a73ce5dff7a46a73871a37265a35e1e3eff8df72c3"
  ),
));

$response = curl_exec($curl);

curl_close($curl);
return $response;
    
	}
}

