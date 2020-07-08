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
        $this->load->library('courir');

        $this->load->helper(array('form', 'url'));
    }

    function index_get($id = '') {
        $file = fopen("file/list_dest_02.csv", "r");
        while (!feof($file)) {
            $data = fgetcsv($file);
            $datax = array(
                'PROVINCE_NAME' => $data[0],
                'CITY_NAME' => $data[1],
                'DISTRICT_NAME' => $data[2],
                'SUBDISTRICT_NAME' => $data[3],
                'ZIP_CODE' => $data[4],
                'CITY_CODE' => $data[5]
            );
//            $this->h2h_model->inputJne($datax);
            print_r($data);
        }
        fclose($file);
        exit;
//        $this->h2h_model->cron(122);
//        exit;
        //M001-O0001->DU
        //M001-O0004->KOPO
        //M001-O0006->JATINANGOR
        //M001-O0029->CIMAHI
        //M001-O0041->BUBAT
        //M001-O0043->BUBAT KEMKO
//        $data = $this->quantum->callAPi('FBA0AA300QF1B22', 3,'M001-O0001');
//        print_r($data);
//        exit;
//        $aut2 = $this->quantum->callAPi('BAA0CE09241A42F',3);
//        if (empty($id)) {
//            $id = 1;
//        }
//        $idx = $id + 1;
//        echo '<html>';
//        echo '<meta http-equiv="refresh" content="10; url=https://api.rmall.id/h2h/' . $idx . '">';
//        echo '</html>';
//        $this->h2h_model->cron(12);
//        print_r($aut2);
    }

    function cron_get() {
        $this->h2h_model->cron();
    }
    
    function index_post(){
        $url = $this->input->post('url');
        $data = $this->h2h_model->short($url);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function syncStock_get($pg = '') {
        $data = $this->h2h_model->syncStock($pg);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
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
            'price' => $this->input->post('price'),
            'weight' => $this->input->post('weight')
        );
        $data = $this->h2h_model->dataSku($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function printOrder_get($param = '') {
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
