<?php // this class is duplicate of Tripaypayment.php
namespace Library;

use GuzzleHttp\Client;
use Redis\Producer\WablasProducer;

class WablasService {

    private $client;
    private $domain;
    private $token;

    public function __construct($domain, $token) {
        $this->domain = $domain;
        $this->token = $token;
        $this->client = new Client([
            'base_uri' => $this->domain,
        ]);
    }


    public function sendMessage($phone, $message) {
        $payload = [
            "data" => [
                [
                    'phone' => $phone,
                    'message' => $message,
                    'secret' => false, // or true
                    'retry' => false, // or true
                    'isGroup' => false, // or true
                ]
            ]
        ];
        $headers['Content-Type'] = 'application/json';
        $resp = $this->httpRequest('POST', '/api/v2/send-message', null, null, null, $payload);
        return $resp;
    }

    public function sendBulk($data) {
        $headers = [
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
        ];
        $formattedData = [];
        foreach ($data as $d) {
            $formattedData[] = [
                'phone' => $d['phone'],
                'message' => $d['message'],
                'retry' => false,
                'isGroup' => false,
                'secret' => (isset($d['secret']) && $d['secret'] == 'true') ? true : false
            ];
        }
        $payload = [];
        $payload['data'] = $formattedData;
        $resp = $this->httpRequest('POST', '/api/v2/send-message', null, null, null, $payload);
        return $resp;
    }

    public function sendImage ($phone, $caption, $image) {
        $payload = [
            "data" => [
                [
                    'phone' => $phone,
                    'image' => $image,
                    'caption' => $caption,
                    'secret' => false, // or true
                    'retry' => false, // or true
                    'isGroup' => false, // or true
                ]
            ]
        ];
        $headers['Content-Type'] = 'application/json';
        $resp = $this->httpRequest('POST', '/api/v2/send-image', null, null, null, $payload);
        return $resp;
    }

    public function sendBulkImage ($data) {
        $headers = [
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
        ];
        $formattedData = [];
        foreach ($data as $d) {
            $formattedData[] = [
                'phone' => $d['phone'],
                'image' => $d['image'],
                'caption' => $d['caption'],
                'retry' => false,
                'isGroup' => false,
                'secret' => (isset($d['secret']) && $d['secret'] == 'true') ? true : false
            ];
        }
        $payload = [];
        $payload['data'] = $formattedData;
        $resp = $this->httpRequest('POST', '/api/v2/send-image', null, null, null, $payload);
        return $resp;
    }

    public function sendVideo ($phone, $caption, $video) {
        $queryParams = [
            'phone' => $phone,
            'caption' => $caption,
            'video' => $video,
            'secret' => false,
            'prioprity' => false
        ];
        $resp = $this->httpRequest('POST', '/api/send-video', null, null, $queryParams);
        return $resp;
    }

    public function createScheduledMessage ($phone, $message, $date, $time) {
        $queryParams = [
            'phone' => $phone,
            'message' => $message,
            'date' => $date,
            'time' => $time
        ];
        $resp = $this->httpRequest('POST', '/api/schedule', null, null, $queryParams);
        return $resp;
    }

    public function httpRequest($method, $url, $headers = [], $bodyReq = null, $queryParams = null, $body = null) {
        $headers['Authorization'] = $this->token;
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'headers' => $headers,
                'query' => $queryParams,
                'json' => $body,
                'connect_timeout' => 10,
                'timeout' => 10
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp);
        } catch (\Exception $e) {
            if (empty($e->getResponse())) {
                $resp = 'Connection Error';
            } else {
                $resp = $e->getResponse()->getBody(true);
                $resp = json_decode($resp);   
            }
        }
        $payloadRequest = new \stdClass;
        $payloadRequest->query_params = $queryParams;
        $payloadRequest->body = $body;
        $resp->request_payload = $payloadRequest;
        return $resp;
    }

    /**
     * Publish to redis
     **/
    public function publishMessage ($type, $phoneNumber, $message = null, $image = null, $date = null, $time = null, $video = null, $amount = null, $code = null, $targets = null, $notificationId = null) {
        $producer = new WablasProducer;
        $producer->type = $type;
        $producer->phoneNumber = $phoneNumber;
        $producer->message = $message;
        $producer->image = $image;
        $producer->video = $video;
        $producer->date = $date;
        $producer->time = $time;
        $producer->amount = $amount;
        $producer->code = $code;
        $producer->wablasAuthorizationToken = $this->token;
        $producer->wablasDomain = $this->domain;
        $producer->targets = $targets;
        $producer->notificationId = $notificationId;
        $action = $producer->send();
        return $action;
    }

}
