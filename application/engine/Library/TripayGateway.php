<?php // this class is duplicate of Tripaypayment.php
namespace Library;

use GuzzleHttp\Client;

class TripayGateway {

    private $instance;
    private $apiKey;
    private $privateKey;
    private $merchantCode;
    private $callbackUrl;
    private $returnUrl;
    private $client;
    private $prefixUrl;
    private $env;

//    const TYPE_APP = 'dev';
    const TYPE_APP = 'prod';

    public function __construct() {
        $this->env = 'sandbox';
        $this->apiKey = 'DEV-MOd9XvQ1yv26SZEHOzUgVphYbfAgbx9qbOfkR2xY';
        $this->privateKey = '7SB2I-Pz6wf-k9q9G-H8JST-frhnL';
        $this->merchantCode = 'T0316';
        $this->callbackUrl = 'https://api.1itmedia.co.id/payment/paymenttripay/callback';
        $this->returnUrl = 'https://www.1itmedia.co.id/landing/';
        $this->client = new Client([
            'base_uri' => 'https://tripay.co.id',
        ]);
        $this->prefixUrl = '/api-sandbox';
        if (self::TYPE_APP == 'prod') {
            $this->prefixUrl = '/api';
        }
    }

    public function setEnv ($env) {
        if ($env == 'production') {
            $this->prefixUrl = '/api';
        } else {
            $this->prefixUrl = '/api-sandbox';
        }
    }

    public function setApiKey ($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function setPrivateKey ($privateKey) {
        $this->privateKey = $privateKey;
    }

    public function setMerchantCode ($merchantCode) {
        $this->merchantCode = $merchantCode;
    }

    public function channelPembayaran($code = '') {
        $queryParams = [];
        if (!empty($code)) {
            $queryParams['code'] = $code;
        }
        $resp = $this->httpRequest('GET', $this->prefixUrl . '/merchant/payment-channel', null, $queryParams);
        return $resp;
    }

    public function detailTransaksiClosed ($reference) {
        $queryParams = [
            'reference' => $reference
        ];
        $resp = $this->httpRequest('GET', $this->prefixUrl . '/transaction/detail', null, $queryParams);
        return $resp;
    }

    public function requestTransaksi($method, $merchantRef = null, $amount, $customerName = null, $customerEmail = null, $customerPhone = null, $orderItems = [], $expiredTime = null, $callbackUrl = null) {
        $formattedOrderItems = [];
        foreach ($orderItems as $item) {
            $ot = [
                'sku' => $item['sku'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity']
            ];
            $formattedOrderItems[] = $ot;
        }

        $bodyReq = [
            'method' => $method,
            'merchant_ref' => $merchantRef,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'order_items' => $orderItems,
            'amount' => $amount,
            'expired_time' => $expiredTime,
            'signature' => $this->createSignature($merchantRef, $amount),
            // 'callback_url' => $callbackUrl,
        ];
        $resp = $this->httpRequest('POST', $this->prefixUrl . '/transaction/create', $bodyReq);
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null, $queryParams = null) {
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
                ],
                'json' => $bodyReq,
                'query' => $queryParams
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp);
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
            $resp = json_decode($resp);
            $resp->isError = true;
        }
        return $resp;
    }

    public function createSignature($merchantRef, $amount) {
        return hash_hmac('sha256', $this->merchantCode . $merchantRef . $amount, $this->privateKey);
    }

    public function authenticate() {
    }

}
