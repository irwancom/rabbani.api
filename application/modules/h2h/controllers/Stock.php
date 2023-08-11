
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\StoreHandler;
use \libphonenumber\PhoneNumberUtil;

class Stock extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function update_post () {

        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $limit = 1000;
        $stocks = $this->input->post('stocks');
        if (empty($stocks) || count($stocks) >= $limit) {
            $this->delivery->addError(400, 'Data missing or to many ('.$limit.')');
            $this->response($this->delivery->format());
        }
        
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);

        $storeResult = $handler->getStores(null, true);
        $stores = $storeResult->data;
        $formattedStores = [];
        foreach ($stores as $store) {
            $formattedStores[$store->code] = $store;
        }

        $newData = [];
        $updateData = [];
        $dataFailed = [];
        foreach ($stocks as $stock) {
            if (isset($stock['store_code']) && isset($stock['sku']) && isset($stock['stock'])) {
                $storeCode = $stock['store_code'];
                $sku = $stock['sku'];
                $stockValue = intval($stock['stock']);

                if (!isset($formattedStores[$storeCode])) {
                    $dataFailed[] = $stock;
                    continue;
                }

                $existsData = $handler->getStock(['sku_code' => $sku, 'store_code' => $storeCode]);
                $payload = [
                    'store_code' => $storeCode,
                    'sku_code' => $sku,
                    'stock' => $stockValue
                ];
                if (empty($existsData->data)) {
                    // create
                    $newData[] = $stock;
                    $action = $handler->createStock($payload);
                } else {
                    // update
                    $updateData[] = $stock;
                    $action = $handler->updateStock($payload, ['id' => $existsData->data->id]);
                }

            } else {
                $dataFailed[] = $stock;
            }
        }

        $result = [
            'new_data' => $newData,
            'update_data' => $updateData,
            'failed_data' => $dataFailed
        ];
        $this->delivery->data = $result;
        $this->response($this->delivery->format());
    }

}
