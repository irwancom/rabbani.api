<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Courier\CourierHandler;

class Courier extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function lionparcel_tarif_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->getLionParcelTarif($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function lionparcel_track_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->getLionParcelTrack($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function lionparcel_details_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->getLionParcelDetails($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function lionparcel_booking_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->createLionParcelBooking($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function jne_origin_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->getJNEOrigin($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function jne_destination_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->getJNEDestination($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function jne_tariff_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->getJNETariff($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function jne_track_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->getJNETraceTracking($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function jne_airwaybill_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuth($secret);
        $payload = $this->input->post();
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CourierHandler($this->MainModel, $auth->data);
        $result = $handler->generateJNEAirwayBill($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

}
