
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Student\StudentHandler;

class Student extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_student_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new StudentHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getStudents($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function create_student_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StudentHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createStudent($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_student_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $handler = new StudentHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        
        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][1])) {
                    $rowData = [
                        'nik' => $sheetData[$i][0],
                        'name' => $sheetData[$i][1],
                        'father_name' => $sheetData[$i][2],
                        'student_name' => $sheetData[$i][3],
                        'address' => $sheetData[$i][4],
                    ];

                    if (isset($rowData['nik']) && isset($rowData['nik'])) {
                        $data[] = $rowData;
                    } else {
                        $dataFailed[] = $rowData;
                    }
                }
            }
        }

        $newData = [];
        $updateData = [];
        foreach ($data as $d) {
            $existsData = $handler->getStudent(['nik' => $d['nik']]);
            $payload = [
                'nik' => $d['nik'],
                'name' => $d['name'],
                'father_name' => $d['father_name'],
                'mother_name' => $d['mother_name'],
                'address'=> $d['address']
            ];
            if (empty($existsData->data)) {
                // create
                $newData[] = $d;
                $action = $handler->createStudent($payload);
            } else {
                // update
                $updateData[] = $d;
                $action = $handler->updateStudent($payload, ['id' => $existsData->data->id]);
            }
        }

        $result = [
            'new_data' => $newData,
            'update_data' => $updateData,
            'failed_data' => $dataFailed
        ];
        $this->delivery->data = $result;
        $this->response($this->delivery->format());
    }

    public function update_student_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StudentHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateStudent($payload, ['id' => $id]);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_student_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StudentHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteStudent((int)$id);

        $this->response($result->format(), $result->getStatusCode());
    }

}
