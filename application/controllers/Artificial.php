<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Artificial extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('artificial_model');
        $this->load->library('courir');
        $this->load->library('quantum');

        $this->load->helper(array('form', 'url'));
    }

    function index_get() {
        
    }

    function expedition_post() {
        $dataCourir = $this->courir->getCostExpedition('fdc5017ffe12f8a6f91a4ab338913d63', 23, $this->input->post('idCity'), '1000', 'jne');
        $dataCourir = json_decode($dataCourir);
        $data = array(
            'status' => $dataCourir->rajaongkir->status,
            'destination_details' => $dataCourir->rajaongkir->destination_details,
            'origin_details' => array($dataCourir->rajaongkir->origin_details),
            'costs' => $dataCourir->rajaongkir->results[0]->costs[0]->cost[0]
        );

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function dev_post() {
        $a=array("3871bb58397ecada5c7ed4f915c39ad4","fdc5017ffe12f8a6f91a4ab338913d63","9bc683408f7ff71bde648ac9914746db");
        $random_keys=array_rand($a,1);
//        print_r($a[$random_keys]);
//        exit;
        $dataCourir = $this->courir->getCostExpedition($a[$random_keys], 23, $this->input->post('idCity'), $this->input->post('weight'), 'jne');
        $dataCourir = json_decode($dataCourir);

        $data = array(
            'status' => $dataCourir->rajaongkir->status,
            'destination_details' => $dataCourir->rajaongkir->destination_details,
            'origin_details' => array($dataCourir->rajaongkir->origin_details),
            'costs' => array(
                $dataCourir->rajaongkir->results[0]->costs[0]->cost[0],
//                'discShipping' => $this->input->post('totalBay') * 0.1,
                'discShipping' => 0,
                'uniqCode'=> rand(000, 999)
            )
        );

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

}
