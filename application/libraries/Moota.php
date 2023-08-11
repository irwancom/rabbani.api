<?php

use GuzzleHttp\Client;

class Moota {

    private $instance;
    private $apiKey;
    private $client;

    const TYPE_API_KEY = 'VHtjWq3GPkMFhpFDeJmIiEZYGVGapuyL91skuZbGc27YLnQipx';
    const TYPE_VERSION = 'v1';

    public function __construct() {

        $this->instance = &get_instance();
        $this->instance->load->model('MainModel');
        $this->apiKey = self::TYPE_API_KEY;
        $this->client = new Client([
            'base_uri' => 'https://app.moota.co',
        ]);
    }

    public function getProfile () {
        $resp = $this->httpRequest('GET', sprintf('/api/%s/%s', self::TYPE_VERSION, 'profile'));
        return $resp;
    }

    public function getBalance () {
        $resp = $this->httpRequest('GET', sprintf('/api/%s/%s', self::TYPE_VERSION, 'balance'));
        return $resp;
    }

    public function getBank ($page = 1) {
        $resp = $this->httpRequest('GET', sprintf('/api/%s/%s', self::TYPE_VERSION, 'bank'), null, ['page' => $page]);
        return $resp;
    }

    public function getBankDetail ($bankId) {
        $resp = $this->httpRequest('GET', sprintf('/api/%s/%s', self::TYPE_VERSION, 'bank/'.$bankId));
        return $resp;
    }

    public function getThisMonthMutation ($bankId) {
        $resp = $this->httpRequest('GET', sprintf('/api/%s/%s', self::TYPE_VERSION, 'bank/'.$bankId.'/mutation/'));
        return $resp;
    }

    public function getRecentMutation ($bankId, $amount = '') {
        $resp = $this->httpRequest('GET', sprintf('/api/%s/%s', self::TYPE_VERSION, 'bank/'.$bankId.'/mutation/recent/'.$amount));
        return $resp;
    }

    public function getMutationByAmount ($bankId, $amount) {
        $resp = $this->httpRequest('GET', sprintf('/api/%s/%s', self::TYPE_VERSION, 'bank/'.$bankId.'/mutation/search/'.$amount));
        return $resp;
    }

    public function getMutationByDescription ($bankId, $description) {
        $resp = $this->httpRequest('GET', sprintf('/api/%s/%s', self::TYPE_VERSION, 'bank/'.$bankId.'/mutation/search/description/'.$description));
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null, $query = null) {
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'query' => $query,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json'
                ],
                'json' => $bodyReq
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp);
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
            $resp = json_decode($resp);
        }

        return $resp;
    }

    public function authenticate() {
        $this->apiKey = 'BVfR0Z3aO1UJ6uDU2G6pkxbSGbdCVXaw';
    }

}
