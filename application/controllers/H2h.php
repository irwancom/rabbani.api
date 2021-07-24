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
        $this->load->library('wa');
        $this->load->library('sms');
        $this->load->library('sim');

        $this->load->helper(array('form', 'url'));
    }

//    function index_post($id = '') {
//        $this->wa->SendWa2('08986002287');
//        exit;
//        $file = fopen("file/data.csv", "r");
//        while (!feof($file)) {
//            $data = fgetcsv($file);
//            if (!empty($data)) {
//                foreach ($data as $dd) {
//                    $this->wa->SendWa2($dd);
//                    echo $dd;
//                    if(!empty($dd[1])){
//                    $this->wa->SendWa2($dd[1]);
//                    }
//                    $userkey = 'ba2d2d4ae8d5';
//                    $passkey = 'c1ac28hnu3';
//                    $telepon = $dd[1];
//                    $image_link = 'http://api.rmall.id/file/img.png';
//                    $caption = 'Assalamualaikum...
//Rabbani is inviting you to a scheduled Rabbani Talks Zoom meeting.
//
//Tema:
//Belajar Makhorijul Huruf bersama Kak Nabilah Abdul Rahim
//7 Agustus 2020 pukul 14.00 WIB
//
//Join Zoom Meeting
//https://us02web.zoom.us/j/5725504765?pwd=UDhIWGFSb2NTQ1lUeU5aODVKYnZFdz09
//
//Meeting ID: 572 550 4765
//Passcode: rabbani
//
//Mohon untuk join 30 menit sebelum acara dimulai ya ☺️';
//                    $url = 'https://gsm.zenziva.net/api/sendWAFile/';
//                    $curlHandle = curl_init();
//                    curl_setopt($curlHandle, CURLOPT_URL, $url);
//                    curl_setopt($curlHandle, CURLOPT_HEADER, 0);
//                    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
//                    curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
//                    curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
//                    curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
//                    curl_setopt($curlHandle, CURLOPT_POST, 1);
//                    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, array(
//                        'userkey' => $userkey,
//                        'passkey' => $passkey,
//                        'nohp' => $telepon,
//                        'link' => $image_link,
//                        'caption' => $caption
//                    ));
//                    $results = json_decode(curl_exec($curlHandle), true);
//                    curl_close($curlHandle);
//                }
//            }
//            exit;
//        }
//        fclose($file);
//        exit;
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
//    }

    function cron_get() {
        $this->h2h_model->cron();
    }

    function index_post() {
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

    function product_post() {
        $data = array(
            'keyCode' => $this->input->post('keyCode'),
            'secret' => $this->input->post('secret'),
            'idstorequantum' => $this->input->post('idstorequantum'),
            'skuProduct' => $this->input->post('skuProduct'),
            'nameProduct' => $this->input->post('nameProduct'),
            'collor' => $this->input->post('collor'),
            'priceProduct' => $this->input->post('priceProduct'),
            'stockStore' => $this->input->post('stockStore')
        );
        $data = $this->h2h_model->product($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

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

    public function po_post() {
        $data = array(
            $this->input->post('data')
        );

        $data = $this->h2h_model->po($data);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function posub_post() {
        $data = array(
            $this->input->post('data')
        );

        $data = $this->h2h_model->posub($data);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function cod_post() {


        $data = $this->h2h_model->cod();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function category_get() {

        $data = $this->h2h_model->category();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function productByCat_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('cat')
            );

            $data = $this->h2h_model->getDataByCat($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function productditails_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('idproduct')
            );

            $data = $this->h2h_model->productditails($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function productditailssku_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('sku')
            );

            $data = $this->h2h_model->productditailssku($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    function member_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('data')
            );
            $data = $this->h2h_model->addmember($data);
        } elseif ($pg == 'search') {
            $data = array(
                $this->input->post('search')
            );
            $data = $this->h2h_model->searchmember($data);
        } else {


            $data = $this->h2h_model->getmember($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function autoview_post() {


        $data = $this->h2h_model->autoview();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function waBlash_get() {
//        $this->wa->SendWa2('Irwan Hermawan','08986002287');
//        exit;
        $file = fopen("file/data.csv", "r");
        while (!feof($file)) {
            $data = fgetcsv($file);
//            echo $data[0];
            $datax = explode(";", $data[0]);
//            print_r($datax);
//            exit;
            $this->wa->SendWa2($datax[0], $datax[1]);
//            exit;
        }
        fclose($file);
        exit;
    }

    //function pos_get() {
    // $data = array(
    // 'keyCode' => $this->input->post('keyCode'),
    //   'secret' => $this->input->post('secret'),
    //  );
    //$data = $this->h2h_model->pos($data);
    //$data = $this->h2h_model->pos(); 
    //  if ($data) {
    // $this->response($data, 200);
    //  } else {
    //  $this->response(array('status' => 'fail', 502));
    // }
    //  }



    public function insertposview_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret')
            );
            $data = $this->h2h_model->insertposview($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function updateposview_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('id_quantum')
            );
            $data = $this->h2h_model->updateposview($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function syncSim_get() {
        //'sku=KAS150&product_name=KERUDUNG%20INSTAN%20KARIMUN&desc=*Jangan%20menggunakan%20sikat%20%0A-%20mencuci%20dengan%20mesin%20cuci%20menggunakan%20level%20rendah%0A-%20Penyetrikaan%20cukup%20dengan%20suhu%20rendah-sedang&sku_code=KAS15040041A100&variable=%7B%22COLOR%22%3A%22PUTIH%22%2C%22SIZE%22%3A%22ALL%20SIZE%22%7D&price=29800'
        $data = $this->h2h_model->getDataProduct();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function productact_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret')
            );

            $data = $this->h2h_model->productact($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function productcatact_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('idcat')
            );

            $data = $this->h2h_model->productcatact($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function productditailsact_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('idproduct')
            );

            $data = $this->h2h_model->productditailsact($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function tailor_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->h2h_model->tailor($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    function viewtailor_get() {
        $data = $this->h2h_model->viewtailor();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function rumahjahit_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->h2h_model->rumahjahit($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    function rumahjahit_get() {
        $data = $this->h2h_model->viewtailor();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function inputstore_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->h2h_model->inputstore($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function citystore_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('idprov'),
                    //$this->input->post('idstore')
            );

            $data = $this->h2h_model->citystore($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function storebyprov_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $data = $this->h2h_model->storebyprov($this->input->post('idprov'));
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    function provstore_get() {
        $data = $this->h2h_model->provstore();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

//end
}
