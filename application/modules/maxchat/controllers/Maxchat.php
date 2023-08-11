<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberDigital\MemberDigitalHandler;
use Library\MaxChatService;

class Maxchat extends REST_Controller {

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
            'phone' => str_replace("+", "", $body->data->from),
            'message' => $body->data->text,
            'receiver' => $body->data->to
        ];
        $logData = [
            'fromcall' => 'MaxChat',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $handler = new MemberDigitalHandler($this->MainModel);
        $result = $handler->callbackAction($wablasPayload);
        $handlerResult = $result->plainText();
        if (isJson($handlerResult)) {
            $data = json_decode($handlerResult, true);
            $logData['response_body'] = json_encode($handlerResult);
            $action = $this->MainModel->insert('logcallback', $logData);
            $config = $this->getMaxServiceDevice($wablasPayload['receiver']);
            if (!empty($config)) {
                $maxChat = new MaxChatService($config->domain_wablas, $config->wablas_token);
                foreach ($data['data'] as $d) {
                    if ($d['category'] == 'text') {
                        $actionConfig = $maxChat->sendMessage($wablasPayload['phone'], $d['message']);
                    } else if ($d['category'] == 'image') {
                        $actionConfig = $maxChat->sendImage($wablasPayload['phone'], $d['message'], $d['url_file']);
                    }
                }
            }
            $this->response(json_decode($handlerResult), 200);
        }
        else if (is_string($handlerResult)) {
            $payload = [
                'time' => time(),
                'event' => 'new',
                'type' => 'message',
                'data' => [
                    'id' => generateRandomString(20),
                    'date' => time(),
                    'platform' => 'whatsapp',
                    'fromMe' => false,
                    'from' => $wablasPayload['receiver'],
                    'to' => $wablasPayload['phone'],
                    'sender' => $wablasPayload['receiver'],
                    'senderName' => '',
                    'text' => $handlerResult,
                    'status' => 'none',
                    'thumbnail' => '',
                    'isNewMsg' => true,
                    'mentions' => [],
                    'type' => 'text',
                    'chat' => 'user'
                ]
            ];
            $logData['response_body'] = json_encode($payload);
            $action = $this->MainModel->insert('logcallback', $logData);
            $config = $this->getMaxServiceDevice($wablasPayload['receiver']);
            if (!empty($config)) {
                $maxChat = new MaxChatService($config->domain_wablas, $config->wablas_token);
                $actionConfig = $maxChat->sendMessage($wablasPayload['phone'], $handlerResult);
            }
            $this->response($payload, 200);
        } else {
            $this->response($result->format(), $result->getStatusCode());
        }
    }

    public function getMaxServiceDevice ($receiver) {
        $config = $this->MainModel->findOne('auth_api_wablas', ['wablas_phone_number' => $receiver]);
        if (empty($config)) {
            return false;
        } else {
            return $config;
        }
    }

}
