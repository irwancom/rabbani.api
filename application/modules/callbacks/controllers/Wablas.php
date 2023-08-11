<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberDigital\MemberDigitalHandler;
use Service\MemberDigitalParent\MemberDigitalParentHandler;
use Service\Member\MemberHandler;
use Service\MemberKb\MemberKbHandler;
use Service\MemberLaundry\MemberLaundryHandler;
use Service\MemberLaundry\MemberLaundryAgentHandler;

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
    public function index_post () {
        header("Content-Type: text/plain");
        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel);
        $result = $handler->callbackAction($payload);
        echo $result->plainText();
    }

    /**
     * @return text
     **/
    public function beta_post () {
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
            'fromcall' => 'WABLAS_V2',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $payload = [
            'phone' => $body->phone,
            'message' => $body->message,
            'receiver' => $body->sender
        ];
        $exceptionDictionary = [
            '~' => '',
            '<' => '',
            ' <~ ' => ''
        ];
        $payload['message'] = strtr($payload['message'], $exceptionDictionary);
        // $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new MemberDigitalHandler($this->MainModel);
        $result = $handler->callbackAction($payload);
        echo $result->plainText();
    }

    /**
     * @return text
     **/
    public function parent_post () {
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
            'fromcall' => 'WABLAS_PARENT',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $payload = [
            'phone' => $body->phone,
            'message' => $body->message,
            'receiver' => $body->sender
        ];
        $exceptionDictionary = [
            '~' => '',
            '<' => '',
            ' <~ ' => ''
        ];
        $payload['message'] = strtr($payload['message'], $exceptionDictionary);
        // $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new MemberDigitalParentHandler($this->MainModel);
        $result = $handler->callbackAction($payload);
        echo $result->plainText();
    }

    /**
     * @return text
     **/
    public function voucher_post () {
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
            'fromcall' => 'WABLAS_MEMBER',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $payload = [
            'phone' => $body->phone,
            'message' => $body->message,
            'receiver' => $body->sender
        ];
        $exceptionDictionary = [
            '~' => '',
            '<' => '',
            ' <~ ' => ''
        ];
        $payload['message'] = strtr($payload['message'], $exceptionDictionary);
        // $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new MemberHandler($this->MainModel);
        $result = $handler->callbackAction($payload);
        echo $result->plainText();
    }

    /**
     * @return text
     **/
    public function kb_post () {
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
            'fromcall' => 'WABLAS_KB',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $payload = [
            'id' => $body->id,
            'phone' => $body->phone,
            'message' => $body->message,
            'receiver' => $body->sender
        ];
        $exceptionDictionary = [
            '~' => '',
            '<' => '',
            ' <~ ' => ''
        ];
        $payload['message'] = strtr($payload['message'], $exceptionDictionary);
        $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new MemberKbHandler($this->MainModel);
        $result = $handler->callbackAction($payload);
        echo $result->plainText();
    }

    /**
     * @return text
     **/
    public function laundry_post () {
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
            'fromcall' => 'WABLAS_LAUNDRY',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $payload = [
            'id' => $body->id,
            'phone' => $body->phone,
            'message' => $body->message,
            'receiver' => $body->sender
        ];
        $exceptionDictionary = [
            '~' => '',
            '<' => '',
            ' <~ ' => ''
        ];
        $payload['message'] = strtr($payload['message'], $exceptionDictionary);
        $action = $this->MainModel->insert('logcallback', $logData);
        // $handler = new MemberLaundryHandler($this->MainModel);
        $result = $handler->callbackAction($payload);
        echo $result->plainText();
    }

    /**
     * @return text
     **/
    public function laundry_admin_post () {
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
            'fromcall' => 'WABLAS_LAUNDRY',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $payload = [
            'id' => $body->id,
            'phone' => $body->phone,
            'message' => $body->message,
            'receiver' => $body->sender
        ];
        $exceptionDictionary = [
            '~' => '',
            '<' => '',
            ' <~ ' => ''
        ];
        $payload['message'] = strtr($payload['message'], $exceptionDictionary);
        $action = $this->MainModel->insert('logcallback', $logData);
        // $handler = new MemberLaundryAgentHandler($this->MainModel);
        $result = $handler->callbackAction($payload);
        echo $result->plainText();
    }

}
