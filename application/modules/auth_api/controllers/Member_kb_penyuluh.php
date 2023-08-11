<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberKb\MemberKbManager;

class Member_kb_penyuluh extends REST_Controller {

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
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->getMemberKbPenyuluhs($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->createMemberKbPenyuluh($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->updateMemberKbPenyuluhs($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberKbManager($this->MainModel, $auth->data);
        $result = $handler->deleteMemberKbPenyuluhs($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $payload = $this->input->post();
        $handler = new MemberKbManager($this->MainModel, $auth->data);

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][1])) {
                    $rowData = [
                        'kabupaten' => $sheetData[$i][1],
                        'kecamatan' => $sheetData[$i][2],
                        'kelurahan' => $sheetData[$i][3],
                        'nama' => $sheetData[$i][4],
                        'whatsapp_number' => $sheetData[$i][5],
                        'phone_number' => $sheetData[$i][6],
                        'address' => $sheetData[$i][7]
                    ];
                    $data[] = $rowData;
                }
            }
        }

        $newData = [];
        $updateData = [];
        foreach ($data as $d) {
            $args = [
                'kelurahan' => $d['kelurahan'],
                'whatsapp_number' => $d['whatsapp_number']
            ];
            $existsData = $handler->getMemberKbPenyuluh($args)->data;
            if (!empty($existsData)) {
                $updateData[] = $existsData;
                $action = $handler->updateMemberKbPenyuluhs($d, ['id' => $existsData->id]);
            } else {
                $newData[] = $d;
                $action = $handler->createMemberKbPenyuluh($d);
            }
        }

        $result = [
            'new_data' => $newData,
            'update_data' => $updateData,
            'failed_data' => $dataFailed
        ];
        $this->delivery->data = $result;
        $this->response($this->delivery->format());


        $this->response($result->format(), $result->getStatusCode());
    }

}
