<?php
use GuzzleHttp\Client;

class Tripaypayment {

    private $instance;
    private $apiKey;
    private $privateKey;
    private $merchantCode;
    private $callbackUrl;
    private $returnUrl;
    private $client;
    private $prefixUrl;

    const TYPE_API_KEY = 'TRIPAY_PAYMENT_API_KEY';
    const TYPE_PRIVATE_KEY = 'TRIPAY_PAYMENT_PRIVATE_KEY';
    const TYPE_MERCHANT_CODE = 'TRIPAY_PAYMENT_MERCHANT_CODE';
    const TYPE_CALLBACK_URL = 'TRIPAY_PAYMENT_CALLBACK_URL';
    const TYPE_RETURN_URL = 'TRIPAY_PAYMENT_RETURN_URL';
//    const TYPE_APP = 'dev';
    const TYPE_APP = 'prod';

    public function __construct() {

        $this->instance = &get_instance();
        $this->instance->load->model('MainModel');

        $this->client = new Client([
            'base_uri' => 'https://tripay.co.id',
        ]);
        $this->prefixUrl = '/api-sandbox';
        if (self::TYPE_APP == 'prod') {
            $this->prefixUrl = '/api';
        }
    }

    public function channelPembayaran() {
        $this->authenticate();
        $resp = $this->httpRequest('GET', $this->prefixUrl . '/merchant/payment-channel');
        return $resp;
    }

    public function requestTransaksi($method, $merchantRef = null, $amount, $customerName = null, $customerEmail = null, $customerPhone = null, $orderItems = null, $expiredTime = null) {
        $this->authenticate();
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
            'signature' => $this->createSignature($merchantRef, $amount)
        ];
        $resp = $this->httpRequest('POST', $this->prefixUrl . '/transaction/create', $bodyReq);
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null) {
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
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

    public function createSignature($merchantRef, $amount) {
        return hash_hmac('sha256', $this->merchantCode . $merchantRef . $amount, $this->privateKey);
    }

    public function authenticate() {
        $this->apiKey = 'DEV-MOd9XvQ1yv26SZEHOzUgVphYbfAgbx9qbOfkR2xY';
        $this->privateKey = '7SB2I-Pz6wf-k9q9G-H8JST-frhnL';
        $this->merchantCode = 'T0316';
        $this->callbackUrl = 'https://api.1itmedia.co.id/payment/paymenttripay/callback';
        $this->returnUrl = 'https://www.1itmedia.co.id/landing/';
        
//        $this->apiKey = 'ZZUFAKR8Zp4UvC2Pcpaz9Bs0FdKu86Zq4SVrnKed';
//        $this->privateKey = 'Sfw9q-yuMNX-SK07D-qcggU-y1yCh';
//        $this->merchantCode = 'T2286';
//        $this->callbackUrl = 'https://api.1itmedia.co.id/payment/paymenttripay/callback';
//        $this->returnUrl = 'https://www.1itmedia.co.id/landing/';
    }

}
