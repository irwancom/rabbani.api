<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Mailer extends REST_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('mailgun');
        $this->load->model('MainModel');
    }

    public function index() {
        echo 'mailer';
    }

    private function verify() {
        $KeyCode = $this->input->get_request_header('X-Token-KeyCode');
        $Secret = $this->input->get_request_header('X-Token-Secret');
        $query = $this->MainModel->findOne('auth_api', ['keyCode' => $KeyCode, 'secret' => $Secret]);

        if (!empty($query)) {
            $response['status'] = 200;
            $response['error'] = FALSE;
            $response['dataSecret'] = $query;

            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function sendMailshare_post() {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;

            $to = $this->input->post('to');
            $from = $this->input->post('from');
            $subject = $this->input->post('subject');
            $text = $this->input->post('text');

            $resp = $this->mailgun->send('', '', $from, $to, $subject, $text);

            $data = [
                'success' => true,
                'mailResponse' => $resp
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

    public function sendMailgun_post() {
        $dataVerify = $this->verify();
        if ($dataVerify['status'] == 200) {
            $resp = null;

            $key = $this->input->post('keyMailgun');
            $domain = $this->input->post('domain');
            $to = $this->input->post('to');
            $from = $this->input->post('from');
            $subject = $this->input->post('subject');
            $text = $this->input->post('text');
            
            $resp = $this->mailgun->send($key, $domain, $from, $to, $subject, $text);

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

    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

}
