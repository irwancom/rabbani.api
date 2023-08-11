
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\StoreHandler;
use \libphonenumber\PhoneNumberUtil;

class Store extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_province_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getProvinces($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_district_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getDistricts($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_store_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getStores($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_store_page_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getStoresPage($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function create_store_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createStore($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_store_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);

        $provinceResult = $handler->getProvinces();
        $provinces = $provinceResult->data;

        $districtResult = $handler->getDistricts();
        $districts = $districtResult->data;
        
        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][1])) {
                    $rowData = [
                        'name' => $sheetData[$i][0],
                        'province' => $sheetData[$i][1],
                        'district' => $sheetData[$i][2],
                        'address' => $sheetData[$i][3],
                        'target' => $sheetData[$i][4],
                    ];

                    $existsProvince = array_search($rowData['province'], array_column($provinces, 'name'), true);
                    if ($existsProvince !== false) {
                        $existsProvince = $provinces[$existsProvince];
                        $rowData['province_id'] = $existsProvince->id;
                    }
                    $existsDistrict = array_search(strtoupper($rowData['district']), array_column($districts, 'nama'), true);
                    if ($existsDistrict !== false) {
                        $existsDistrict = $districts[$existsDistrict];
                        $rowData['district_id_kab'] = $existsDistrict->id_kab;
                    }

                    if (isset($rowData['province_id']) && isset($rowData['district_id_kab'])) {
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
            $existsData = $handler->getStore(['name' => $d['name']]);
            $payload = [
                'id_kab' => $d['district_id_kab'],
                'name' => $d['name'],
                'address' => $d['address'],
                'target' => $d['target']
            ];
            if (empty($existsData->data)) {
                // create
                $newData[] = $d;
                $action = $handler->createStore($payload);
            } else {
                // update
                $updateData[] = $d;
                $action = $handler->updateStore($payload, ['id' => $existsData->data->id]);
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

    public function update_store_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateStore($payload, ['id' => $id]);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_store_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteStore((int)$id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_agent_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getAgents($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function create_agent_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createAgent($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_agent_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateAgent($payload, ['id' => $id]);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_agent_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteAgent((int)$id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_agent_post ($storeId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);

        $storeResult = $handler->getStore(['id' => (int)$storeId]);
        if (empty($storeResult->data)) {
            $this->delivery->addError(400, 'Store is required');
            $this->response($this->delivery->format());
        }
        $store = $storeResult->data;
        
        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][1])) {
                    $rowData = [
                        'name' => $sheetData[$i][0],
                        'phone_number' => $sheetData[$i][1],
                        'nik' => $sheetData[$i][2]
                    ];
                    $rowData['phone_number'] = getFormattedPhoneNumber($rowData['phone_number']);
                    if (!empty($rowData['nik'])) {
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
            $existsData = $handler->getAgent(['nik' => $d['nik']]);
            $payload = [
                'store_code' => $store->code,
                'name' => $d['name'],
                'phone_number' => $d['phone_number'],
                'nik' => $d['nik']
            ];
            if (empty($existsData->data)) {
                // create
                $newData[] = $d;
                $action = $handler->createAgent($payload);
            } else {
                // update
                $updateData[] = $d;
                $action = $handler->updateAgent($payload, ['id' => $existsData->data->id]);
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

    public function list_stock_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getStocks($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function create_stock_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createStock($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_stock_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateStock($payload, ['id' => $id]);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_stock_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteStock((int)$id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_stock_post ($storeId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);

        $storeResult = $handler->getStore(['id' => (int)$storeId]);
        if (empty($storeResult->data)) {
            $this->delivery->addError(400, 'Store is required');
            $this->response($this->delivery->format());
        }
        $store = $storeResult->data;
        
        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][0])) {
                    $rowData = [
                        'sku_code' => $sheetData[$i][0],
                        'stock' => $sheetData[$i][1]
                    ];
                    if (!empty($rowData['sku_code'])) {
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
            $existsData = $handler->getStock(['sku_code' => $d['sku_code'], 'store_code' => $store->code]);
            $payload = [
                'store_code' => $store->code,
                'sku_code' => $d['sku_code'],
                'stock' => (int)$d['stock']
            ];
            if (empty($existsData->data)) {
                // create
                $newData[] = $d;
                $action = $handler->createStock($payload);
            } else {
                // update
                $updateData[] = $d;
                $action = $handler->updateStock($payload, ['id' => $existsData->data->id]);
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


    //======================== Admin Store ========================//

    public function admin_get ($storeId = null, $adminId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        if(!$storeId || empty($storeId) || is_null($storeId)){
            $this->delivery->addError(400, 'Store ID is required'); $this->response($this->delivery->format());
        }

        $filters = $this->input->get();
        $filters['admin_store'] = intval($storeId);
        if($adminId && !empty($adminId) && !is_null($adminId)){
            $filters['id'] = intval($adminId);
        }
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getAdminList($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function admin_post ($storeId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        if(!$storeId || empty($storeId) || is_null($storeId)){
            $this->delivery->addError(400, 'Store ID is required'); $this->response($this->delivery->format());
        }

        $payload = $this->input->post();
        $payload['admin_store'] = intval($storeId);

        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateAdminStore($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function admin_delete ($storeId = null, $adminId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        if(!$storeId || empty($storeId) || is_null($storeId)){
            $this->delivery->addError(400, 'Store ID is required'); $this->response($this->delivery->format());
        }
        if(!$adminId || empty($adminId) || is_null($adminId)){
            $this->delivery->addError(400, 'Admin ID is required'); $this->response($this->delivery->format());
        }

        $payload = array();
        $payload['admin_store'] = intval($storeId);
        $payload['id'] = intval($adminId);

        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteAdminStore($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    

//======================== End Line ========================//
}
