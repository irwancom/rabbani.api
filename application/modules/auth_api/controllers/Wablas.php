<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Service\MemberDigital\MemberDigitalHandler;
use Library\WablasService;

class Wablas extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function send_message_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_WABLAS, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $message = $payload['message'];
        $phoneNumber = $payload['phone_number'];

        $wablas = new WablasService($headers['X-Wablas-Domain'], $headers['X-Wablas-Token']);
        $action = $wablas->sendMessage($phoneNumber, $message);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function send_bulk_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post('data');
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_WABLAS, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $wablas = new WablasService($headers['X-Wablas-Domain'], $headers['X-Wablas-Token']);
        $action = $wablas->sendBulk($payload);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function send_bulk_file_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post('data');
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_WABLAS, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        $data = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][1])) {
                    $rowData = [
                        'name' => $sheetData[$i][0],
                        'phone_number' => $sheetData[$i][1],
                        'message' => $sheetData[$i][2],
                        'type' => $sheetData[$i][3],
                        'link' => $sheetData[$i][4],
                    ];
                    $data[] = $rowData;
                }
            }
        }

        $formattedDataText = [];
        $formattedDataImage = [];
        $formattedDataVideo = [];
        foreach ($data as $d) {
            $customFormat = [
                '{name}' => $d['name']
            ];
            $d['message'] = strtr($d['message'], $customFormat);

            if ($d['type'] == 'video') {
                $formattedDataVideo[] = [
                    'video' => $d['link'],
                    'phone_number' => $d['phone_number'],
                    'caption' => $d['message']
                ];
            } else if ($d['type'] == 'image') {
                $formattedDataImage[] = [
                    'image' => $d['link'],
                    'phone_number' => $d['phone_number'],
                    'caption' => $d['message']
                ];
            } else {
                $formattedDataText[] = [
                    'phone' => $d['phone_number'],
                    'message' => $d['message'],
                    'priority' => false,
                    'secret' => false
                ];
            }
        }

        $result = [];
        $wablas = new WablasService($headers['X-Wablas-Domain'], $headers['X-Wablas-Token']);
        if (!empty($formattedDataText)) {
            $action = $wablas->sendBulk($formattedDataText);
            $result['text'] = $action;
        }
        if (!empty($formattedDataImage)) {
            foreach ($formattedDataImage as $value) {
                $action = $wablas->publishMessage('send_image', $value['phone_number'], $value['caption'], $value['image']);
                $result['image'][] = $action;
            }
        }

        if (!empty($formattedDataVideo)) {
            foreach ($formattedDataVideo as $value) {
                $action = $wablas->publishMessage('send_video', $value['phone_number'], $value['caption'], null, null, null, $value['video']);
                $result['video'][] = $action;
            }
        }
        $this->delivery->data = $result;
        $this->response($this->delivery->format());
    }

    public function send_image_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_WABLAS, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $caption = $payload['caption'];
        $phoneNumber = $payload['phone_number'];
        $image = $payload['image'];

        $wablas = new WablasService($headers['X-Wablas-Domain'], $headers['X-Wablas-Token']);
        $action = $wablas->sendImage($phoneNumber, $caption, $image);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function send_video_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_WABLAS, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $caption = $payload['caption'];
        $phoneNumber = $payload['phone_number'];
        $video = $payload['video'];

        $wablas = new WablasService($headers['X-Wablas-Domain'], $headers['X-Wablas-Token']);
        $action = $wablas->sendVideo($phoneNumber, $caption, $video);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_scheduled_message_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_WABLAS, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $phoneNumber = $payload['phone_number'];
        $message = $payload['message'];
        $date = $payload['date'];
        $time = $payload['time'];

        $wablas = new WablasService($headers['X-Wablas-Domain'], $headers['X-Wablas-Token']);
        $action = $wablas->createScheduledMessage($phoneNumber, $message, $date, $time);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function publish_message_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_WABLAS, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $message = $payload['message'];
        $phoneNumber = $payload['phone_number'];

        $wablas = new WablasService($headers['X-Wablas-Domain'], $headers['X-Wablas-Token']);
        $action = $wablas->publishMessage('send_message', $phoneNumber, $message);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

}
