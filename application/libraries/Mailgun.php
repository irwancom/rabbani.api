<?php

use GuzzleHttp\Client;

class Mailgun {

    private $instance;
    private $apiKey;
    private $domain;
    private $client;

    const TYPE_API_KEY = 'MAILGUN_API_KEY';
    const TYPE_DOMAIN = 'MAILGUN_DOMAIN';

    public function __construct() {

        $this->instance = &get_instance();
        $this->instance->load->model('MainModel');

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

}
