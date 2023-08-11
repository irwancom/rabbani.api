<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\DigitalOceanService;

class Digital_ocean extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function upload_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_DIGITAL_OCEAN, $this->input->request_headers());
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }


        $service = new DigitalOceanService;
        $service->setCdnLink($headers['X-Digital-Ocean-Cdn-Link']);
        $service->setKey($headers['X-Digital-Ocean-Key']);
        $service->setSecret($headers['X-Digital-Ocean-Secret']);
        $service->setSpaceName($headers['X-Digital-Ocean-Space-Name']);
        $service->setRegion($headers['X-Digital-Ocean-Region']);
        $action = $service->upload($_FILES, 'file');
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function delete_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_DIGITAL_OCEAN, $this->input->request_headers());
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $filename = $this->input->post('filename');

        $service = new DigitalOceanService;
        $service->setCdnLink($headers['X-Digital-Ocean-Cdn-Link']);
        $service->setKey($headers['X-Digital-Ocean-Key']);
        $service->setSecret($headers['X-Digital-Ocean-Secret']);
        $service->setSpaceName($headers['X-Digital-Ocean-Space-Name']);
        $service->setRegion($headers['X-Digital-Ocean-Region']);
        $action  = $service->delete($filename);

        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

}
