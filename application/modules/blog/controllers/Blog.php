<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Blog extends REST_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('BlogModel');
    }

    public function category_get() {
        $resp = $this->BlogModel->getAllCategory();
        if (!empty($resp)) {
            $this->response($resp, 200);
        } else {
            $this->response('access denied', 403);
        }
    }

    public function posting_get($page='') {
        $selectData = $this->input->get_request_header('selectData');
        $resp = $this->BlogModel->getBlog($selectData, $page);
        if (!empty($resp)) {
            $this->response($resp, 200);
        } else {
            $this->response('access denied', 403);
        }
    }

    public function postingDetails_get() {
        $selectData = $this->input->get_request_header('selectData');
        $resp = $this->BlogModel->getBlogDetails($selectData);
        if (!empty($resp)) {
            $this->response($resp, 200);
        } else {
            $this->response('access denied', 403);
        }
    }

    public function postingSearch_post() {
        $selectData = $this->input->post('dataSearch');
        $resp = $this->BlogModel->searchData($selectData);
        if (!empty($resp)) {
            $this->response($resp, 200);
        } else {
            $this->response('access denied', 403);
        }
    }

}
