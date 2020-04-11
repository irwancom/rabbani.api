<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class H2h extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('h2h_model');
        $this->load->library('quantum');

        $this->load->helper(array('form', 'url'));
    }

    function index_get() {
        $aut2 = $this->quantum->callAPi('BBA0DA19241A700',3);
        print_r($aut2);
    }

    function store_post() {
        $data = array(
            'keyCode' => $this->input->post('keyCode'),
            'secret' => $this->input->post('secret'),
            'idquantum' => $this->input->post('idquantum'),
            'namestore' => $this->input->post('namestore'),
            'phonestore' => $this->input->post('phonestore'),
            'addrstore' => $this->input->post('addrstore'),
            'typeStore' => $this->input->post('typeStore')
        );
        $data = $this->h2h_model->store($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

//    function product_post() {
//        $data = array(
//            'keyCode' => $this->input->post('keyCode'),
//            'secret' => $this->input->post('secret'),
//            'idstorequantum' => $this->input->post('idstorequantum'),
//            'skuProduct' => $this->input->post('skuProduct'),
//            'nameProduct' => $this->input->post('nameProduct'),
//            'collor' => $this->input->post('collor'),
//            'priceProduct' => $this->input->post('priceProduct'),
//            'stockStore' => $this->input->post('stockStore')
//        );
//        $data = $this->h2h_model->product($data);
//
//        if ($data) {
//            $this->response($data, 200);
//        } else {
//            $this->response(array('status' => 'fail', 502));
//        }
//    }

    function transaction_post() {
        $data = array(
            'keyCode' => $this->input->post('keyCode'),
            'secret' => $this->input->post('secret')
        );
        $data = $this->h2h_model->transaction($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function transactionUpdate_post() {
        $data = array(
            'keyCode' => $this->input->post('keyCode'),
            'secret' => $this->input->post('secret'),
            'noInvoice' => $this->input->post('noInvoice')
        );
        $data = $this->h2h_model->transactionUpdate($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

//    function listData_post($page = '') {
//        $data = array(
//            'keyCode' => $this->input->post('keyCode'),
//            'secret' => $this->input->post('secret'),
//            'page' => $page
//        );
//        $data = $this->h2h_model->listData($data);
//
//        if ($data) {
//            $this->response($data, 200);
//        } else {
//            $this->response(array('status' => 'fail', 502));
//        }
//    }

    function entryDataSensus_get() {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rajaongkir.com/starter/province?key=fdc5017ffe12f8a6f91a4ab338913d63",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $data = json_decode($response);
        $data = $data->rajaongkir->results;
        $this->h2h_model->entryDataSensus($data);
    }

    function skuProductDitails_post() {
        $data = array(
            'keyCode' => $this->input->post('keyCode'),
            'secret' => $this->input->post('secret'),
            'sku' => $this->input->post('barcode6'),
            'productName' => $this->input->post('productName'),
            'yearSku' => $this->input->post('yearBarocde'),
            'skuDitails' => $this->input->post('barcode15'),
            'collor' => $this->input->post('collor'),
            'size' => $this->input->post('size'),
            'weight' => $this->input->post('weight')
        );
        $data = $this->h2h_model->dataSku($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
    
    function printOrder_get($param='') {
        $data = $this->h2h_model->dataPrint($param);
//        print_r($data);
//        exit;
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

}
