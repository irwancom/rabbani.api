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
//        $data = array('KAA6CC09140A518', 'KAA02019340A525', 'CJC7LA00038A902', 'KAA9AB09240A905', 'CDC5LA00040A100');
//        echo json_encode($data);
//        exit;
//        $data = array('1', '1', '2', '2', '3');
//        echo json_encode($data);
//        exit;
        $dataCourir = $this->courir->getCostExpedition('fdc5017ffe12f8a6f91a4ab338913d63', 23, $this->input->post('idCity'), '1000', 'jne');
        $dataCourir = json_decode($dataCourir);
//        print_r($dataCourir->rajaongkir);
        $data = array(
            'status' => $dataCourir->rajaongkir->status,
            'destination_details' => $dataCourir->rajaongkir->destination_details,
            'origin_details' => array($dataCourir->rajaongkir->origin_details),
            'costs' => $dataCourir->rajaongkir->results[0]->costs[0]->cost[0]
        );
//        print_r($data);
//        exit;
//        $data = array(
//            'keyCode' => $this->input->post('keyCode'),
//            'idCity' => $this->input->post('idCity'),
//            'sku' => $this->input->post('sku'),
//            'qty' => $this->input->post('qty')
//        );
//        print_r($data);
//        exit();
//        $data = $this->artificial_model->expedition($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function dev_post() {
//        $data = array('KAA6CC09140A518', 'KAA02019340A525', 'CJC7LA00038A902', 'KAA9AB09240A905', 'CDC5LA00040A100');
//        echo json_encode($data);
//        exit;
        $data = array(
            'keyCode' => $this->input->post('keyCode'),
            'idCity' => $this->input->post('idCity'),
            'idProv' => $this->input->post('idProv'),
            'sku' => $this->input->post('sku'),
            'qty' => $this->input->post('qty')
        );
        $data = $this->artificial_model->artorder($data);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

}
