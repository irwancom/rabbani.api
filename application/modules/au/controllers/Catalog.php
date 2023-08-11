
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\CatalogHandler;

class Catalog extends REST_Controller {

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
        $handler = new CatalogHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getCatalogs($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CatalogHandler($this->MainModel);
        $handler->setUser($auth->data);
        $filters = [
            'id' => $id
        ];
        $result = $handler->getCatalog($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function create_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new CatalogHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createCatalog($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function image_post ($idCatalog) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new CatalogHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $payload['id_catalog'] = $idCatalog;
        $result = $handler->createCatalogImage($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new CatalogHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $filters = [
            'id' => $id
        ];
        $result = $handler->updateCatalog($payload, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CatalogHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteCatalog($id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_image_post ($idCatalogImage) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CatalogHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteCatalogImage($idCatalogImage);

        $this->response($result->format(), $result->getStatusCode());
    }

}
