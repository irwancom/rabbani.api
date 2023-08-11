<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\JNEService;

class Jne extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function get_origin_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_JNE, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $jne = new JNEService($headers['X-Jne-Username'], $headers['X-Jne-Api-Key']);
        $jne->setEnv($headers['X-Jne-Env']);
        $jne->setUsername($headers['X-Jne-Username']);
        $jne->setApiKey($headers['X-Jne-Api-Key']);
        $action = $jne->getOrigin();
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_destination_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_JNE, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $jne = new JNEService($headers['X-Jne-Username'], $headers['X-Jne-Api-Key']);
        $jne->setEnv($headers['X-Jne-Env']);
        $jne->setUsername($headers['X-Jne-Username']);
        $jne->setApiKey($headers['X-Jne-Api-Key']);
        $action = $jne->getDestination();
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_tariff_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_JNE, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $from = $payload['from'];
        $thru = $payload['thru'];
        $weight = $payload['weight'];

        $jne = new JNEService($headers['X-Jne-Username'], $headers['X-Jne-Api-Key']);
        $jne->setEnv($headers['X-Jne-Env']);
        $jne->setUsername($headers['X-Jne-Username']);
        $jne->setApiKey($headers['X-Jne-Api-Key']);
        $action = $jne->getTariff($from, $thru, $weight);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function jne_track_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_JNE, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }
        
        $awb = $payload['awb'];

        $jne = new JNEService($headers['X-Jne-Username'], $headers['X-Jne-Api-Key']);
        $jne->setEnv($headers['X-Jne-Env']);
        $jne->setUsername($headers['X-Jne-Username']);
        $jne->setApiKey($headers['X-Jne-Api-Key']);
        $action = $jne->getTraceTracking($awb);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function jne_airwaybill_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_JNE, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $jne = new JNEService($headers['X-Jne-Username'], $headers['X-Jne-Api-Key']);
        $jne->setEnv($headers['X-Jne-Env']);
        $jne->setUsername($headers['X-Jne-Username']);
        $jne->setApiKey($headers['X-Jne-Api-Key']);
        $action = $jne->generateAirwayBill ($payload['branch'], $payload['cust'], $payload['order_id'], $payload['shipper_name'], $payload['shipper_addr1'], $payload['shipper_addr2'], $payload['shipper_addr3'], $payload['shipper_city'], $payload['shipper_region'], $payload['shipper_zip'], $payload['shipper_phone'],
                $payload['receiver_name'], $payload['receiver_addr1'], $payload['receiver_addr2'], $payload['receiver_addr3'], $payload['receiver_city'], $payload['receiver_region'], $payload['receiver_zip'], $payload['receiver_phone'],
                $payload['qty'], $payload['weight'], $payload['goods_desc'], $payload['goods_value'], $payload['goods_type'], $payload['inst'], $payload['ins_flag'], $payload['origin'], $payload['destination'], $payload['service'], $payload['cod_flag'], $payload['cod_amount']
                );
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

}
