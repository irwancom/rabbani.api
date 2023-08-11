<?php
namespace Library;

use GuzzleHttp\Client;
use Redis\Producer\EmailProducer;

class MailgunService {

    private $instance;
    private $apiKey;
    private $domain;
    private $client;

    const TYPE_API_KEY = 'MAILGUN_API_KEY';
    const TYPE_DOMAIN = 'MAILGUN_DOMAIN';

    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://api.mailgun.net',
        ]);
    }

    public function send($key = '', $domain = '', $from = '', $to = '', $subject = '', $text = '') {
        $this->authenticate($key, $domain);
        $bodyReq = [
            [
                'name' => 'from',
                'contents' => $from // From Me <myfrom@domain.com>
            ],
            [
                'name' => 'to',
                'contents' => $to // Your Name <myemail@domain.com>
            ],
            [
                'name' => 'subject',
                'contents' => $subject
            ],
            [
                'name' => 'html',
                'contents' => $text
            ]
        ];

        $resp = $this->httpRequest('POST', '/v3/' . $this->domain . '/messages', $bodyReq);
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null) {
        $resp = null;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => $this->authorization,
                'multipart' => $bodyReq
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp);
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
            $resp = json_decode($resp);
        }

        return $resp;
    }

    public function authenticate($key = '', $domain = '') {
        if (!empty($key)) {
            $this->apiKey = $key;
        } else {
            $this->apiKey = 'key-0d89204653627cc8cbba67684cfff390';
        }
        if (!empty($domain)) {
            $this->domain = $domain;
        } else {
            $this->domain = 'mg.1itmedia.co.id';
        }
        $this->authorization = [
            'api',
            $this->apiKey
        ];
    }

    /**
     * Publish to redis
     **/
    public function publishEmail ($apiKey = null, $domain = null, $userId = null, $from = null, $to = null, $subject = null, $text = null, $notificationId = null) {
        $producer = new EmailProducer;
        $producer->apiKey = $apiKey;
        $producer->domain = $domain;
        $producer->userId = $userId;
        $producer->from = $from;
        $producer->to = $to;
        $producer->subject = $subject;
        $producer->text = $text;
        $producer->notificationId = $notificationId;
        $action = $producer->send();
        return $action;
    }

}
