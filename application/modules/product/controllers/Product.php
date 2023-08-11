<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Product extends REST_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('ProductModel');
    }

    private function verify() {
        $KeyCode = $this->input->get_request_header('X-Token-KeyCode');
        $Secret = $this->input->get_request_header('X-Token-Secret');
        $dataVerify = $this->ProductModel->verfyApi($KeyCode, $Secret);
        return $dataVerify;
    }

    public function category_get() {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;
            $resp['status'] = 200;
            $resp['data'] = $this->ProductModel->getCategory($dataVerify['dataSecret'][0]->id_auth);
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function index_get($dataView = '', $page = '') {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;
            $resp['status'] = 200;
            $resp['data'] = $this->ProductModel->getProduct($dataVerify['dataSecret'][0]->id_auth, $dataView, $page);
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }
    
     public function addUpdate_post($dataView = '', $page = '') {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;
            $resp['status'] = 200;
            $resp['data'] = $this->ProductModel->addUpdate($dataVerify['dataSecret'][0]->id_auth, $this->input->post('sku'), $this->input->post('product_name'), $this->input->post('desc'), $this->input->post('sku_code'), $this->input->post('variable'), $this->input->post('price'));
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

}
