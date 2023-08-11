<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\TripayGateway;

class Payment extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function index_get ($paymentCode = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $existCode = ($paymentCode && !empty($paymentCode) && !is_null($paymentCode)) ? $paymentCode : '';
        $config = $this->wooh_support->tripayConfig();
        $tripay = new TripayGateway;
        $tripay->setEnv($config['env']);
        $tripay->setMerchantCode($config['code']);
        $tripay->setApiKey($config['key']);
        $tripay->setPrivateKey($config['secret']);
        $tripayResult = $tripay->channelPembayaran($existCode);
        
        if (!$tripayResult->data || empty($tripayResult->data)) {
            $this->delivery->addError(400, 'Payment method not found'); $this->response($this->delivery->format());
        }

        $this->delivery->data = ($existCode) ? $tripayResult->data[0] : array('result'=>$tripayResult->data);
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
