<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\MailgunService;

class Mailgun extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function send_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_MAILGUN, $this->input->request_headers());
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $mailgun = new MailgunService();
        $action = $mailgun->send($headers['X-Mailgun-Key'], $headers['X-Mailgun-Domain'], $payload['from'], $payload['to'], $payload['subject'], $payload['text']);
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
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_MAILGUN, $this->input->request_headers());
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        
        $data = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][0])) {
                    $rowData = [
                        'from' => $sheetData[$i][0],
                        'to' => $sheetData[$i][1],
                        'subject' => $sheetData[$i][2],
                        'message' => $sheetData[$i][3]
                    ];
                    $data[] = $rowData;
                }
            }
        }

        $actions = [];
        $mailgun = new MailgunService();
        foreach ($data as $d) {
           $actions[] = $mailgun->send($headers['X-Mailgun-Key'], $headers['X-Mailgun-Domain'], $d['from'], $d['to'], $d['subject'], $d['message']);
        }
        $this->delivery->data = $actions;
        $this->response($this->delivery->format());
    }

}
