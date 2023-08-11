
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\StoreHandler;

class Store extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_province_get () {
        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getProvinces($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_district_get () {
        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getDistricts($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_sub_district_get () {
        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getSubDistricts($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_urban_village_get () {
        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getUrbanVillages($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_store_get () {
        $filters = $this->input->get();
        $filters['is_publish'] = 1;
        // $filters['is_publish_multi'] = 1;
        $handler = new StoreHandler($this->MainModel);
        // $handler->setUser($auth->data);
        if (isset($filters['format']) && $filters['format'] == 'pagination') {
            $result = $handler->getStoresPage($filters);
        } else {
            unset($filters['data'],$filters['format']);
            $result = $handler->getStores($filters);   
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_store_dpr_get () {
        $filters = $this->input->get();
        $filters['is_publish'] = 1;
        // $filters['is_publish_multi'] = 1;
        $handler = new StoreHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getStores($filters, true, true);

        $this->response($result->format(), $result->getStatusCode());
    }

}
