<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberLaundry\MemberLaundryHandler;
use Service\Member\MemberHandler;
use Service\CLM\Handler\OrderHandler;

class Tripay extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    /**
     * @return text
     **/
    public function laundry_post () {
        $rawJson = file_get_contents("php://input");
        $body = json_decode($rawJson);
        $logData = [
            'fromcall' => 'TRIPAY_LAUNDRY',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new MemberLaundryHandler($this->MainModel);
        $result = $handler->onTripayCallback($body);
        $this->response($result->format(), $result->getStatusCode());
    }

    /**
     * @return text
     **/
    public function clm_post () {
        $rawJson = file_get_contents("php://input");
        $body = json_decode($rawJson);
        $logData = [
            'fromcall' => 'TRIPAY_CLM',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new OrderHandler($this->MainModel);
        $result = $handler->onTripayCallback($body);
        $this->response($result->format(), $result->getStatusCode());
    }

    /**
     * @return text
     **/
    public function voucher_post () {
        $rawJson = file_get_contents("php://input");
        $body = json_decode($rawJson);
        $logData = [
            'fromcall' => 'TRIPAY_VOUCHER',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];

        $token = $this->input->get('token');
        $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new MemberHandler($this->MainModel);
        $result = $handler->onTripayCallback($body, $token);
        $this->response($result->format(), $result->getStatusCode());
    }

}
