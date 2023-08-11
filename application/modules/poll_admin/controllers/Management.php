
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use \libphonenumber\PhoneNumberUtil;

class Management extends REST_Controller {

    private $validator;
    private $delivery;
    private $pollHandler;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function import_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][1])) {
                    $name = $sheetData[$i][0];
                    if (!empty($sheetData[$i][1])) {
                        $phoneNumber = getFormattedPhoneNumber($sheetData[$i][1]);
                        if (empty($phoneNumber)) {
                            $dataFailed[] = [
                                'name' => $name. ' Juri 1',
                                'phone_number' => $sheetData[$i][1]
                            ];
                        } else {
                            $data[] = [
                                'name' => $name. ' Juri 1',
                                'phone_number' => '0'.substr($phoneNumber,2)
                            ];   
                        }
                    }
                    if (!empty($sheetData[$i][2])) {
                        $phoneNumber = getFormattedPhoneNumber($sheetData[$i][2]);
                        if (empty($phoneNumber)) {
                            $dataFailed[] = [
                                'name' => $name. ' Juri 2',
                                'phone_number' => $sheetData[$i][2]
                            ];
                        } else {
                            $data[] = [
                                'name' => $name. ' Juri 2',
                                'phone_number' => '0'.substr($phoneNumber,2)
                            ];
                        }
                    }
                    if (!empty($sheetData[$i][3])) {
                        $phoneNumber = getFormattedPhoneNumber($sheetData[$i][3]);
                        if (empty($phoneNumber)) {
                            $dataFailed[] = [
                                'name' => $name. ' Juri 3',
                                'phone_number' => $sheetData[$i][3]
                            ];
                        } else {
                            $data[] = [
                                'name' => $name. ' Juri 3',
                                'phone_number' => '0'.substr($phoneNumber,2)
                            ];
                        }
                    }
                }
            }
        }

        $newData = [];
        $failData = [];
        foreach ($data as $d) {
            $args = [
                'phone' => $d['phone_number'],
            ];
            $existsData = $this->MainModel->findOne('admins', $args);
            if (empty($existsData)) {
                $newData[] = $d;
                $payload = [
                    'id_auth' => 1,
                    'username' => $d['phone_number'],
                    'email' => $d['phone_number'].'@rabbani.id',
                    'password' => password_hash('123456', PASSWORD_DEFAULT),
                    'created_on' => time(),
                    'first_name' => $d['name'],
                    'company' => 'rabbani',
                    'phone' => $d['phone_number'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $action = $this->MainModel->insert('admins', $payload);
            }
        }

        $result = new Delivery;
        $formattedResult = [
            'new' => $newData,
            'fail' => $dataFailed,
        ];
        $result->data = $formattedResult;
        $this->response($result->format(), $result->getStatusCode());
    }

}
