<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Fulfillment\FulfillmentPicklistHandler;
use Service\Fulfillment\FulfillmentOrderHandler;


class Picklist extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->load->model('PicklistModel');
        $this->validator = new Validator($this->PicklistModel);
        $this->delivery = new Delivery;
    }

    public function create_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $ids = $this->input->post('ids');
        $note = $this->input->post('note');
        if (empty($ids) || (is_array($ids) && count($ids) < 1)) {
            $this->delivery->addError(422, 'Sales Order ID should not be empty');
            $this->response($this->delivery->format(), 422);
        }

        $FulfillmentPicklistHandler = new FulfillmentPicklistHandler($this->PicklistModel, $auth->data);
        $result = $FulfillmentPicklistHandler->create($ids, $note);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_get() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $FulfillmentPicklistHandler = new FulfillmentPicklistHandler($this->PicklistModel, $auth->data);
        $result = $FulfillmentPicklistHandler->get($this->input->get());

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $picklistId = $this->input->post('id');
        if (empty($picklistId)) {
            $this->delivery->addError(422, 'Picklist Code should not be empty');
            $this->response($this->delivery->format(), 422);
        }

        $FulfillmentPicklistHandler = new FulfillmentPicklistHandler($this->PicklistModel, $auth->data);
        $result = $FulfillmentPicklistHandler->delete($picklistId);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_batch_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $picklistIds = $this->input->post('ids');
        if (!is_array($picklistIds)) {
            $this->delivery->addError(422, 'Need one or more existing picklist codes');
            return $this->delivery;
		}

        $FulfillmentPicklistHandler = new FulfillmentPicklistHandler($this->PicklistModel, $auth->data);
        $result = $FulfillmentPicklistHandler->deleteBatch($picklistIds);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function remove_item_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $id = $this->input->post('id');
        if (empty($id)) {
            $this->delivery->addError(422, 'Sales Order ID should not be empty');
            $this->response($this->delivery->format(), 422);
        }

        $FulfillmentPicklistHandler = new FulfillmentPicklistHandler($this->PicklistModel, $auth->data);
        $result = $FulfillmentPicklistHandler->removeItem($id);

        $this->response($result->format(), $result->getStatusCode());
    }
    
    public function export_get() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin-top' => 0,
            'default_font' => 'san-serif',
            'default_font_size' => 9,
        ]);

        $filters['picklist_id'] = $this->input->get('picklist_id');
        $handler = new FulfillmentOrderHandler($this->MainModel, $auth->data);
        $result = $handler->getOrders($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $items = [];
        $picklistCode = NULL;
        for ($i = 0; $i < count($result->data); $i++) {
            $order = $result->data[$i];
            if (!isset($picklistCode)) {
                $picklistCode = $order['picklist_code'];
            }
            if (!isset($order['tracking_number']) || !isset($order['invoice_no'])) {
                continue;
            }
            
            $items = array_merge($items, $order['transaction_items']);
        }

        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();

        if (!$picklistCode) {
            $this->response([
                'status' => 'error'
            ], 422);
        }

        $picklistCodeSrc = 'data:image/png;base64,' . base64_encode($generator->getBarcode($picklistCode, $generator::TYPE_CODE_128, 2, 50));

        $mpdf->WriteHTML($this->load->view('picklist', [
            'picklistCode' => $picklistCode,
            'picklistCodeSrc' => $picklistCodeSrc,
            'items' => $items,
        ], true));
        $mpdf->Output($items[0]['picklist_code'] . '.pdf', 'I');
    }

}
