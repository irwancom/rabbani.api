<?php
namespace Library;

use GuzzleHttp\Client;

class SMSService {

    private $username;
    private $key;
    private $client;

    public function __construct() {
        $this->username = 'irwancom';
        $this->key = 'fab702b290e19f921cf541afbd38112a';
        $this->client = new Client([
            'base_uri' => 'https://sms255.xyz'
        ]);
    }

    public function send($number, $message) {
        $body = [
            'apikey' => $this->key,
            'callbackurl' => '',
            'senderid' => uniqid(),
            'datapacket' => [],
        ];

        $body['datapacket'][] = [
            'number' => $number,
            'message' => $message,
        ];
//		$resp = $this->httpRequest('POST', '/sms/smsmasking.php', $body);
        $resp = $this->httpRequest('POST', '/sms/api_sms_otp_send_json.php', $body);
        return $resp;
    }

    public function httpRequest($method, $url, $bodyReq = null) {
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'body' => json_encode($bodyReq)
            ]);

            $resp = $response->getBody();
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
        }

        return $resp;
    }

}
