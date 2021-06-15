<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Pay extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('pay_model');
        $this->load->model('artificial_model');
        $this->load->library('xendit');
        $this->load->library('sms');
        $this->load->library('courir');

        $this->load->helper(array('form', 'url'));
    }

    function index_get() {
        echo 'ini pay';
    }

    function getPayType_post() {
        $data = $this->pay_model->getPayType($this->input->post('keyCode'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function createVa_post() {
        $data = $this->pay_model->createVa($this->input->post('noInvoice'), $this->input->post('keyCode'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function FVApaid_post() {
        $rawData1 = file_get_contents("php://input");
        $rawData = json_decode($rawData1);
        $data = $this->pay_model->payPaidHistories($rawData->external_id, $rawData1);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
        exit;
    }

    function FVAcreated_post() {
        $rawData1 = file_get_contents("php://input");
        $rawData = json_decode($rawData1);
        $data = $this->pay_model->payHistories($rawData->external_id, $rawData1);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
        exit;
    }

    function Moota_post() {
        //2TgI1Eki
        $rawData1 = file_get_contents("php://input");
        $rawData = json_decode($rawData1);
//        print_r($rawData[0]->amount);
//        exit;
        $data = $this->pay_model->payPaidHistoriesMoota($rawData[0]->amount, $rawData);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
        exit;
    }

}
