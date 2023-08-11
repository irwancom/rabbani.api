
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\SearchKeywordHandler;

class Search_keyword extends REST_Controller {

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
        $handler = new SearchKeywordHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getSearchKeywords($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new SearchKeywordHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createSearchKeyword($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = [
            'id' => $id
        ];
        $payload = $this->input->post();
        $handler = new SearchKeywordHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateSearchKeyword($payload, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new SearchKeywordHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteSearchKeyword($id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function report_get() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new SearchKeywordHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getSearchKeywordReports($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

}
