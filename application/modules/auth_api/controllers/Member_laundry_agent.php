<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberLaundry\MemberLaundryManager;

class Member_laundry_agent extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberLaundryManager($this->MainModel, $auth->data);
        $result = $handler->getMemberLaundryAgents($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberLaundryManager($this->MainModel, $auth->data);
        $result = $handler->createMemberLaundryAgent($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberLaundryManager($this->MainModel, $auth->data);
        $result = $handler->updateMemberLaundryAgents($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberLaundryManager($this->MainModel, $auth->data);
        $result = $handler->deleteMemberLaundryAgents($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

}
