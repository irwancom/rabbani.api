<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Inventory\Handler\InventoryProductHandler;
use Service\Inventory\Handler\InventoryBoxHandler;
use Service\Inventory\Handler\InventoryRoomHandler;
use Service\Inventory\Handler\InventoryInboundHandler;
use Service\Inventory\Handler\InventoryActivityHandler;

class Inbound extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function scan_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $code = $this->input->post('code');
        if (empty($code)) {
            $this->delivery->addError(400, 'SKU code should not be empty');
            $this->response($this->delivery->format());
        }

        $boxHandler = new InventoryBoxHandler($this->MainModel, $auth->data);
        $roomHandler = new InventoryRoomHandler($this->MainModel, $auth->data);
        $productHandler = new InventoryProductHandler($this->MainModel, $auth->data);
        // check if room
        if ($boxHandler->isCodeExists($code)) {
            $result = $boxHandler->scanAction($code);
        } else if ($roomHandler->isCodeExists($code)) {
            // $result = $roomHandler->scanAction($code);
            $this->delivery->addError(400, 'There is no action for room scan');
            $this->response($this->delivery->format());
        } else {
            $result = $productHandler->scanAction($code);
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function stock_transfer_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $inventoryBoxCodeSource = $this->input->post('inventory_box_code_source');
        $inventoryRoomCodeDestination = $this->input->post('inventory_room_code_destination');

        $handler = new InventoryInboundHandler($this->MainModel, $auth->data);
        $result = $handler->stockTransfer($inventoryBoxCodeSource, $inventoryRoomCodeDestination);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function activity_get ($code) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = [
            'code' => $code
        ];

        $handler = new InventoryActivityHandler($this->MainModel, $auth->data);
        $result = $handler->getActivities($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

}
