<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberDigital\MemberDigitalHandler;

class Wassenger extends REST_Controller {

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
        $wablasPayload = [
            'phone' => str_replace("+", "", $body->data->fromNumber),
            'message' => $body->data->body,
            'receiver' => $body->device->phone
        ];
        $handler = new MemberDigitalHandler($this->MainModel);
        $result = $handler->callbackAction($wablasPayload);
        $handlerResult = $result->plainText();
        if (is_string($handlerResult)) {
            $this->response($result->format(), 200);
        } else {
            $this->response($result->format(), $result->getStatusCode());
        }
    }

}
