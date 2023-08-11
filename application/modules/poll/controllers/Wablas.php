<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Poll\PollWablasHandler;

class Wablas extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    /**
     * @return text
     **/
    public function callback_post () {
        $rawJson = file_get_contents("php://input");
        $body = json_decode($rawJson);
        $devices = [
            'wa api',
            'api service',
            'rabbani'
        ];
        if (isset($body->pushName) && in_array(strtolower($body->pushName), $devices)) {
            die();
        }
        $logData = [
            'fromcall' => 'WABLAS_DPR',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $payload = [
            'id' => $body->id,
            'phone' => $body->phone,
            'message' => $body->message,
            'receiver' => $body->sender
        ];
        if ($body->messageType == 'image') {
            $payload['message'] = $body->url;
        } else if ($body->messageType == 'video') {
            $payload['message'] = $body->url;
        }
        $exceptionDictionary = [
            '~' => '',
            '<' => '',
            ' <~ ' => ''
        ];
        $payload['message'] = strtr($payload['message'], $exceptionDictionary);
        $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new PollWablasHandler($this->MainModel);
        $result = $handler->callbackAction($payload);
        echo $result->plainText();
    }

}
