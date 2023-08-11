
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\DiscountHandler;

class Discount extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function product_list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new DiscountHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getDiscountProducts($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function product_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new DiscountHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createDiscountProduct($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function product_delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new DiscountHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteDiscountProduct($id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function product_detail_post ($idDiscountProduct) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $payload['id_discount_product'] = $idDiscountProduct;
        $handler = new DiscountHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createDiscountProductDetail($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

}
