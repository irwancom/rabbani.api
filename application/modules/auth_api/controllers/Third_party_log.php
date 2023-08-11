<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\ThirdPartyLog\ThirdPartyLogManager;

class Third_party_log extends REST_Controller {

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
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new ThirdPartyLogManager($this->MainModel, $auth->data);
        $result = $handler->getLogs($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function webhook_post ($secret) {
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $rawData1 = file_get_contents("php://input");
        $payload = json_decode($rawData1, true);

        $logData = [
            'fromcall' => 'TPL_WEBHOOK',
            'dataJson' => json_encode($payload),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $action = $this->MainModel->insert('logcallback', $logData);

        $newPayload = [
            'request_body' => json_encode($payload)
        ];

        $args['query']['id'] = $payload['id'];
        $handler = new ThirdPartyLogManager($this->MainModel, $auth->data);
        $existsLog = $handler->getLog($args)->data;
        if (empty($existsLog)) {
            $result = $handler->createLog($newPayload);
            if ($result->hasErrors()) {
                $this->response($result->format(), $result->getStatusCode());
            }
        } else {
            $result = $handler->updateLog($newPayload, $args);
            if ($result->hasErrors()) {
                $this->response($result->format(), $result->getStatusCode());
            }
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $rawData1 = file_get_contents("php://input");
        $payload = json_decode($rawData1, true);
        $payload['request_body'] = json_encode($payload['request_body']);

        $handler = new ThirdPartyLogManager($this->MainModel, $auth->data);
        $result = $handler->createLog($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($externalId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['external_id' => $externalId];
        $rawData1 = file_get_contents("php://input");
        $payload = json_decode($rawData1, true);
        $newPayload['request_body'] = json_encode($payload);
        $handler = new ThirdPartyLogManager($this->MainModel, $auth->data);
        $result = $handler->updateLog($newPayload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($externalId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['external_id' => $externalId];
        $handler = new ThirdPartyLogManager($this->MainModel, $auth->data);
        $result = $handler->deleteLog($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

}
