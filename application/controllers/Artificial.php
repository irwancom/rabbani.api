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
        // $data = $this->courir->jne(1,'CGK10000',10000*ceil(200/1000));
        // $data = json_decode($data);
        // print_r($data->price);
        // $data = $this->courir->jne(2,'BDOEC04435462320');
        // $data = json_decode($data);
        // print_r($data);
    }

    function getPriceExpetion_post() {
		
		
        $dataCode = $this->artificial_model->getCode($this->input->post('idCity'));
		//print_r($dataCode);exit;
        if (!empty($dataCode)) {
            $data = $this->courir->jne(1, $dataCode->CITY_CODE, ceil($this->input->post('weight') / 1000));;
			$data = json_decode($data);
			
            $data = array(
                'dataPriceShipping' => $data,
                'subsidiesShipping' => $this->input->post('totalBay') * 0.02,
                'uniqCode' => -rand(000, 999)
            );
        } else {
            $data = array(
                'dataPriceShipping' => array('price' => array(array(
                        "origin_name" => "nan",
                        "destination_name" => "nan",
                        "service_display" => "REG",
                        "service_code" => "REG19",
                        "goods_type" => "Document/Paket",
                        "currency" => "IDR",
                        "price" => 10000*ceil($this->input->post('weight') / 1000),
                        "etd_from" => "0",
                        "etd_thru" => "0",
                        "times" => "D"
                    ))),
                'subsidiesShipping' => $this->input->post('totalBay') * 0.04,
                'uniqCode' => -rand(000, 999)
            );
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 function getPriceExpetion1_post() {
        $dataCode = $this->artificial_model->getCode($this->input->post('idCity'));
		//print_r($dataCode);exit
        if (!empty($dataCode)) {
            $data = $this->courir->jne(1, $dataCode->CITY_CODE, ceil($this->input->post('weight') / 1000));
            $data = json_decode($data);
             
            $data = array(
                'dataPriceShipping' => $data,
                'subsidiesShipping' => $this->input->post('totalBay') * 0.02,
                'uniqCode' => -rand(000, 999)
            );
        } else {
            $data = array(
                'dataPriceShipping' => array('price' => array(array(
                        "origin_name" => "nan",
                        "destination_name" => "nan",
                        "service_display" => "REG",
                        "service_code" => "REG19",
                        "goods_type" => "Document/Paket",
                        "currency" => "IDR",
                        "price" => 10000*ceil($this->input->post('weight') / 1000),
                        "etd_from" => "0",
                        "etd_thru" => "0",
                        "times" => "D"
                    ))),
                'subsidiesShipping' => $this->input->post('totalBay') * 0.04,
                'uniqCode' => -rand(000, 999)
            );
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function getTracking_post() {
        $dataUser = $this->artificial_model->getInfoTracking($this->input->post('keyCode'), $this->input->post('awb'));
        if (!empty($dataUser)) {
            $data = $this->courir->jne(2, $this->input->post('awb'));
            $data = json_decode($data);
            // print_r($data->price);
        }
        if (!empty($data)) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function getPrint_get() {
        $dataPrint2 = $this->artificial_model->getPrintData(2);
        // print_r($dataPrint2);
        if ($dataPrint2) {
            $this->response($dataPrint2, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function getAwb_post() {
//        echo $this->input->post('keyCodeStaff');
//        echo $this->input->post('secret');
//        echo $this->input->post('noInvoice');
        $dataUser0 = $this->artificial_model->getInfoAwb($this->input->post('keyCodeStaff'), $this->input->post('secret'), $this->input->post('noInvoice'));
//         print_r($dataUser0);
//         exit;
        if (!empty($dataUser0)) {
            $dataUser = $dataUser0['dataOrder'];
            $dataProd = $dataUser0['dataProduct'];
            $shipping = explode(' ', $dataUser->shipping);
            if (!empty($shipping[1])) {
                $shipping = $shipping[1];
            } else {
                $shipping = 'OKE';
            }
//            print_r($shipping);
//            exit;
            if (!empty($dataUser)) {
                $arrayData = array(
                    'OLSHOP_BRANCH' => 'BDO000',
                    'OLSHOP_CUST' => '10381800',//10381802
                    'OLSHOP_ORDERID' => $dataUser->noInvoice,
                    'OLSHOP_SHIPPER_NAME' => rawurlencode('RABBANI ONLINE'),
                    'OLSHOP_SHIPPER_ADDR1' => rawurlencode('Jl. Mekar Mulya No. 8 Panghegar Panyileukan'),
                    'OLSHOP_SHIPPER_ADDR2' => rawurlencode('Jl. Mekar Mulya No. 8 Panghegar Panyileukan'),
                    'OLSHOP_SHIPPER_ADDR3' => rawurlencode(''),
                    'OLSHOP_SHIPPER_CITY' => rawurlencode('KOTA BANDUNG'),
                    'OLSHOP_SHIPPER_REGION' => rawurlencode('JAWA BARAT'),
                    'OLSHOP_SHIPPER_ZIP' => '40292',
                    'OLSHOP_SHIPPER_PHONE' => '08112346165',
                    'OLSHOP_RECEIVER_NAME' => rawurlencode($dataUser->name),
                    'OLSHOP_RECEIVER_ADDR1' => rawurlencode(str_replace('\n',' ',$dataUser->address)),
                    'OLSHOP_RECEIVER_ADDR2' => rawurlencode(str_replace('\n',' ',$dataUser->address)),
                    'OLSHOP_RECEIVER_ADDR3' => rawurlencode(''),
                    'OLSHOP_RECEIVER_CITY' => rawurlencode($dataUser->nameCity),
                    'OLSHOP_RECEIVER_REGION' => rawurlencode($dataUser->province_name),
                    'OLSHOP_RECEIVER_ZIP' => $dataUser->postcode,
                    'OLSHOP_RECEIVER_PHONE' => $dataUser->phone,
                    'OLSHOP_QTY' => $dataProd->qty,
                    'OLSHOP_WEIGHT' => ceil($dataProd->weight / 1000),
                    'OLSHOP_GOODSDESC' => rawurlencode('fashion muslim'),
                    'OLSHOP_GOODSVALUE' => '1',
                    'OLSHOP_GOODSTYPE' => '1',
                    'OLSHOP_INST' => '',
                    'OLSHOP_INS_FLAG' => 'N',
                    'OLSHOP_ORIG' => 'BDO10000',
                    'OLSHOP_DEST' => $dataUser->JNEcode,
                    'OLSHOP_SERVICE' => $shipping,
                    'OLSHOP_COD_FLAG' => 'N',
                    'OLSHOP_COD_AMOUNT' => '0'
                );
               // print_r($arrayData);
                // exit;
                $data = $this->courir->jne(3, $arrayData);
                $data = json_decode($data);
                // print_r($data1);
                $data = array(
                    'dataTransaction' => $dataUser,
                    'dataAwb' => $data
                );
				  //print_r($data);
                if (!empty($data['dataAwb']->detail[0]->cnote_no)) {
                    $this->artificial_model->updateAwbToInvoice($data['dataAwb']->detail[0]->cnote_no, $dataUser->noInvoice);
                }
            }
        } else {
            $data = 0;
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

   // function expedition_post() {
      //  $a = array("f88928e8873a09b097e66f1b77c5e994");
      //  $random_keys = array_rand($a, 1);
//        print_r($a[$random_keys]);
//        exit;
       // $dataCourir = $this->courir->getCostExpedition($a[$random_keys], 23, $this->input->post('idCity'), $this->input->post('weight'), 'jne');
      //  $dataCourir = json_decode($dataCourir);

      //  $data = array(
         //   'status' => $dataCourir->rajaongkir->status,
          //  'destination_details' => $dataCourir->rajaongkir->destination_details,
          //  'origin_details' => array($dataCourir->rajaongkir->origin_details),
          //  'costs' => array(
           //     $dataCourir->rajaongkir->results[0]->costs[0]->cost[0],
//                'discShipping' => $this->input->post('totalBay') * 0.1,
               // 'discShipping' => 0,
          //     'uniqCode' => rand(000, 999)
         //   )
     //   );

        //if ($data) {
      //      $this->response($data, 200);
     //   } else {
      //      $this->response(array('status' => 'fail', 502));
  //    //  }
   // }

    function dev_post() {
        /*
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
         */
        // $a=array("3871bb58397ecada5c7ed4f915c39ad4","fdc5017ffe12f8a6f91a4ab338913d63","9bc683408f7ff71bde648ac9914746db");
        $a = array(
            "8a9e9d99a7b3fb3320350bb67ab419b6",
            "2fe4176879d13facc987fbf3dbc05897"
        );
        $random_keys = array_rand($a, 1);
//        print_r($a[$random_keys]);
//        exit;
        // $dataCourir = $this->courir->getCostExpedition($a[$random_keys], 23, $this->input->post('idCity'), $this->input->post('weight'), 'jne');
        // $dataCourir = json_decode($dataCourir);

        $data = array(
            'status' => 0,
            'destination_details' => 0,
            'origin_details' => array(0),
            'costs' => array(
                10000 * ceil($this->input->post('weight') / 1000),
                'discShipping' => $this->input->post('totalBay') * 0.04,
                // 'discShipping' => 0,
                'uniqCode' => rand(000, 999)
            )
        );

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

}
