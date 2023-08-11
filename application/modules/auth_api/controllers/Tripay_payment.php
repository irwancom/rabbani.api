<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\TripayGateway;

class Tripay_payment extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function channel_pembayaran_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_TRIPAY_PAYMENT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $tripay = new TripayGateway();
        $tripay->setEnv($headers['X-Tripay-Env']);
        $tripay->setApiKey($headers['X-Tripay-Api-Key']);
        $tripay->setPrivateKey($headers['X-Tripay-Private-Key']);
        $tripay->setMerchantCode($headers['X-Tripay-Merchant-Code']);
        $action = $tripay->channelPembayaran();
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function request_transaksi_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_TRIPAY_PAYMENT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $tripay = new TripayGateway();
        $tripay->setEnv($headers['X-Tripay-Env']);
        $tripay->setApiKey($headers['X-Tripay-Api-Key']);
        $tripay->setPrivateKey($headers['X-Tripay-Private-Key']);
        $tripay->setMerchantCode($headers['X-Tripay-Merchant-Code']);

        $method = $payload['method'];
        $merchantRef = $payload['merchant_ref'];
        $amount = $payload['amount'];
        $customerName = $payload['customer_name'];
        $customerEmail = $payload['customer_email'];
        $customerPhone = $payload['customer_phone'];
        $orderItems = $payload['order_items'];
        $expiredTime = $payload['expired_time'];
        $action = $tripay->requestTransaksi($method, $merchantRef, $amount, $customerName, $customerEmail, $customerPhone, $orderItems, $expiredTime);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

}
