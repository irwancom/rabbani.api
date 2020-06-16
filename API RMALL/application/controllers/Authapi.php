<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Authapi extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('authapi_model');

        $this->load->helper(array('form', 'url'));
    }

    function index_post() {
        $data = $this->authapi_model->apiauth($this->input->post('username'), $this->input->post('password'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function admin_post() {
        $data = $this->authapi_model->apiauth_admin($this->input->post('username'), $this->input->post('password'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function sellercenter_post() {
        $data = $this->authapi_model->apiauth_sellercenter($this->input->post('username'), $this->input->post('password'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function main_post() {
        $data = $this->authapi_model->apiauth_main($this->input->post('username'), $this->input->post('password'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function fulfillment_post() {
        $data = $this->authapi_model->apiauth_fulfillment($this->input->post('username'), $this->input->post('password'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

}
