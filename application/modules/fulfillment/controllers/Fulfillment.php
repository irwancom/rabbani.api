<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Handler;

class Fulfillment extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->load->model('FulfillmentModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    private function verify() {
        $Secret = $this->input->get_request_header('X-Token-Secret');
        $dataVerify = $this->MainModel->verfyUser($Secret);
        return $dataVerify;
    }
    
    public function index_get() {
        echo 'ini api fulfillment.';
    }

    public function inBOX_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        // $dataAuth = $auth->format(), $auth->getStatusCode();

        $result = $this->FulfillmentModel->inBOX($dataAuth, $this->input->post('codeBox'),$this->input->post('sku'));

        $this->response(success_format($result), 200);
    }
    
    public function inRACK_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        // $dataAuth = $auth->format(), $auth->getStatusCode();

        $result = $this->FulfillmentModel->inRACK($dataAuth, $this->input->post('codeBox'),$this->input->post('codeRack'));

        $this->response(success_format($result), 200);
    }
    
    public function stockOPNAME_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        // $dataAuth = $auth->format(), $auth->getStatusCode();

        $result = $this->FulfillmentModel->stockOPNAME($dataAuth, $this->input->post('codeBox'),$this->input->post('codeRack'),$this->input->post('skuBarcode'));

        $this->response(success_format($result), 200);
    }

}
