
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\ProductDetailHandler;

class Product_detail extends REST_Controller {

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
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $filters = $this->input->get();
        $handler = new ProductDetailHandler($this->MainModel);
        $handler->setUser($auth->data);
        $filters['from_stock'] = 1;
        $result = $handler->getProductDetailsForReseller($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function available_stores_get ($idProductDetail) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        /* if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        } */
        
        $handler = new ProductDetailHandler($this->MainModel);
        if (isset($auth->data)) {
           $handler->setUser($auth->data);
        }
        $result = $handler->getAvailableOnStores($idProductDetail);

        $this->response($result->format(), $result->getStatusCode());
    }

}
