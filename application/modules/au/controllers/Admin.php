
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\AdminHandler;

class Admin extends REST_Controller {

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
        $handler = new AdminHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getAdmins($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = [
            'id' => $id
        ];
        $handler = new AdminHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getAdminEntity($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new AdminHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createAdmin($payload);

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
        $handler = new AdminHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateAdmins($payload, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new AdminHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteAdmin($id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function reset_password_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        /* if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        } */
        $slug = $this->input->post('slug');

        $filters = [
            'slug' => $slug
        ];
        $handler = new AdminHandler($this->MainModel);
        if (!empty($auth->data)) {
           $handler->setAdmin($auth->data);
        }
        $result = $handler->getAdminEntity($filters);
        // $handler->setAdmin((array)$result->data);
        $result = $handler->resetPassword($result->data);

        $this->response($result->format(), $result->getStatusCode());
    }

}
