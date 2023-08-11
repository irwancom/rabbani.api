<?php

use GuzzleHttp\Client;

class Sms {

    private $username;
    private $key;
    private $client;

    public function __construct() {
        $this->username = 'simsms';
        $this->key = '9d8d09cc7a767df670ccb5a5d71fe77e';
        $this->client = new Client([
            'base_uri' => 'http://sms114.xyz',
        ]);
    }

    public function send($number, $message) {
        $body = [
            'username' => $this->username,
            'key' => $this->key,
            'number' => $number,
            'message' => $message
        ];
//		$resp = $this->httpRequest('POST', '/sms/smsmasking.php', $body);
        $resp = $this->httpRequest('POST', '/sms/api_sms_otp_send_json.php', $body);
        return $resp;
    }

    public function httpRequest($method, $url, $bodyReq = null) {
        $resp = null;

        try {
            $response = $this->client->request($method, $url, [
                'query' => $bodyReq
            ]);

            $resp = $response->getBody();
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
        }

        return $resp;
    }

}
