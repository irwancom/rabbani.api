<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Ece extends REST_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('mailgun');
        $this->load->model('EceModel');
    }

    private function verify() {
        $KeyCode = $this->input->get_request_header('X-Token-KeyCode');
        $Secret = $this->input->get_request_header('X-Token-Secret');
        $dataVerify = $this->EceModel->verfyApi($KeyCode, $Secret);
        return $dataVerify;
    }

    public function index_get() {
        $dataVerify = $this->verify();
        print_r($dataVerify);
    }

    public function index_post() {
        $token = $this->input->get_request_header('X-Token-Secret');
        echo 'ece post ' . $token;
    }

    public function dataOrdersDetails_post() {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;
            $resp['status'] = 200;
            $resp['data'] = $this->EceModel->getOrdersDetails($this->input->post('noOrders'));
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    //for qc v2
    public function orderQC_post() {
        $resp = null;
        $resp['status'] = 200;
        $resp['data'] = $this->EceModel->getDataQC($this->input->post('noOrders'));
        if ($resp) {
            $this->response($resp, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    //for get member digital
    public function getMD_post() {
        $resp = null;
        $resp['status'] = 200;
        $resp['data'] = $this->EceModel->getDataMD($this->input->post('idPhone'));
        if ($resp) {
            $this->response($resp, 200);
        } else {
            $this->response(array('status' => 'fail', 404));
        }
    }

    //for create member digital RMB
    public function createMDRMB_post() {
        $resp = null;
        $resp['status'] = 200;
        $resp['data'] = $this->EceModel->createMDRMB($this->input->post('phone'), $this->input->post('name'), $this->input->post('idMember'));
        if ($resp) {
            $this->response($resp, 200);
        } else {
            $this->response(array('status' => 'fail', 404));
        }
    }
    
    public function dashboard_get($day='') {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;
            $resp['status'] = 200;
            if(empty($day)){
                $day = 0;
            }
            $resp['data'] = $this->EceModel->dashboard($day);
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }
    
    public function report_get($day='') {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;
            $resp['status'] = 200;
            if(empty($day)){
                $day = 0;
            }
            $resp['data'] = $this->EceModel->report($day);
            if ($resp) {
                $this->response($resp, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }
    
    public function sendMail_post() {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;

            $domain = $this->input->post('domain');
            $to = $this->input->post('to');
            $from = $this->input->post('from');
            $subject = $this->input->post('subject');
            $text = $this->input->post('text');

            $resp = $this->mailgun->send('', $domain, $from, $to, $subject, $text);

            $data = [
                'success' => true,
                'mailgunResponse' => $resp
            ];
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    public function dataTrx_get() {
        $resp['data'] = $this->EceModel->getDataCostumerJubelio();
        if ($resp) {
            $this->response($resp, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
        
    }

    public function member_get($p='') {
        $resp['data'] = $this->EceModel->getMemberData($p);
        if ($resp) {
            $this->response($resp, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
        
    }

    public function prov_get() {
        $resp['data'] = $this->EceModel->getProvData();
        if ($resp) {
            $this->response($resp, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
        
    }

    public function city_get($id='') {
        $resp['data'] = $this->EceModel->getCityData($id);
        if ($resp) {
            $this->response($resp, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
        
    }


    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

}
