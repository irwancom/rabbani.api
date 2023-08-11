<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Inventory\Handler\InventoryRackHandler;

class Rack extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret, ['inventory_role' => 'head']);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new InventoryRackHandler($this->MainModel, $auth->data);
        $result = $handler->createRack($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret, ['inventory_role' => 'head']);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new InventoryRackHandler($this->MainModel, $auth->data);
        $result = $handler->updateRack($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret, ['inventory_role' => 'head']);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new InventoryRackHandler($this->MainModel, $auth->data);
        $result = $handler->deleteRack($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret, ['inventory_role' => 'head']);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new InventoryRackHandler($this->MainModel, $auth->data);
        $result = $handler->getRacks($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret, ['inventory_role' => 'head']);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new InventoryRackHandler($this->MainModel, $auth->data);
        $filters = [
            'id' => $id
        ];
        $result = $handler->getRack($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function generate_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret, ['inventory_role' => 'head']);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new InventoryRackHandler($this->MainModel, $auth->data);
        $result = $handler->generateRack($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

}
