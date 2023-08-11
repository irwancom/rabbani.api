<?php

class Main extends CI_Controller {

    private $token;
    private $jubelioTokenPath;

    public function __construct() {
        parent::__construct();

        $this->load->library('session');
        $this->load->library('jubelio');

        $this->load->model('JubelioModel');
        $this->load->model('Transaction');
        $this->load->model('TransactionItem');
        $this->jubelioTokenPath = 'internal/jubelio_token.txt';
    }

    public function index() {
        // validations here
    }

    public function addStockAdjustments() {
        $this->authenticate();
        $reqItems           = $this->input->post('items');
        $note               = $this->input->post('note');
        $locationId         = $this->input->post('location_id');
        $isOpeningBalance   = $this->input->post('is_opening_balance');
        $items              = null;

        foreach ($reqItems as $req) {
            $item = [
                'item_adj_detail_id' => 0,
                'item_id' => (int) $req['item_id'],
                'description' => $req['description'],
                'unit' => 'Buah',
                'qty_in_base' => (int) $req['qty_in_base'],
                'serial_no' => null,
                'batch_no' => null,
                'cost' => (int) $req['cost'],
                'amount' => (int) $req['qty_in_base'] * (int) $req['cost'],
                'location_id' => (int) $this->input->post('location_id'),
                'account_id' => 75 // hard coded
            ];
            $items[] = $item;
        }

        if (empty($items)) {
            $data = [
                "success" => false,
                "message" => "Items cannot be empty."
            ];

            return $this->returnJSON($data);
        }

        $body = $this->jubelio->addStockAdjustments($this->authorization, $items, $note, $locationId, $isOpeningBalance) ;

        $data = [
            'success' => true,
            'jubelioResponse ' => $body
        ];
        return $this->returnJSON($data);
    }

    public function editStockAdjustments( $itemAdjId, $itemAdjNo) {
        $this->authenticate();
        $note               = $this->input->post('note');
        $locationId         = $this->input->post('location_id');
        $isOpeningBalance   = $this->input->post('is_opening_balance');
        $reqItems = $this->input->post('items');
        $items = null;
        foreach ($reqItems as $req) {
            $item = [
                'item_adj_detail_id' => (int) $req['item_adj_detail_id'],
                'item_id' => (int) $req['item_id'],
                'description' => $req['description'],
                'unit' => 'Buah',
                'qty_in_base' => (int) $req['qty_in_base'],
                // 'serial_no' 				=> null,
                // 'batch_no' 					=> null,
                'cost' => (int) $req['cost'],
                'amount' => (int) $req['qty_in_base'] * (int) $req['cost'],
                // 'location_id' 				=> (int)$this->input->post('location_id'),
                'account_id' => 75 // hard coded
            ];
            $items[] = $item;
        }

        if (empty($items)) {
            $data = [
                "success" => false,
                "message" => "Items cannot be empty."
            ];
            return $this->returnJSON($data);
        }

        $body = $this->jubelio->editStockAdjustments($this->authorization, $itemAdjId, $itemAdjNo, $items, $note, $locationId, $isOpeningBalance);

        $data = [
            'success' => true,
            'jubelioResponse ' => $body
        ];
        return $this->returnJSON($data);
    }

    public function getSalesOrders() {
        $this->authenticate();
        $page = 1;
        $pageSize = 50;
        $body = null;
        $totalData = $pageSize;
        $currentData = 0;

        while ($currentData < $totalData) {
            $resp = $this->jubelio->getSalesOrders($this->authorization, $page, $pageSize);
            
            $transactions = $resp['data'];
            foreach ($transactions as $transaction) {
                $this->Transaction->insert_or_update($transaction['transaction']);
                foreach ($transaction['transaction_item'] as $transactionItem) {
                    $this->TransactionItem->insert_or_update($transactionItem);
                }
            }

            if (isset($resp['totalCount'])) {
                $totalData = $resp['totalCount'];
            }

            $currentData += $pageSize;
        }
        
        $data = [
            'success' => true
        ];
        return $this->returnJSON($data);
    }

    public function getSalesOrderById ( $salesOrderId) {
        $this->authenticate();
        $resp = $this->jubelio->getSalesOrdersById($this->authorization, $salesOrderId);
        $data = [
            'success' => true,
            'jubelioResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getAllProductStock () {
        $this->action('get_all_product_stock');
    }

    public function getProduct ( $id) {
        $this->action('get_product', $id);
    }

    public function getProductBySku ( $sku) {
        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            $jubelioToken = file_get_contents($this->jubelioTokenPath);
            try {
                $resp = $this->jubelio->getProductBySku($jubelioToken, $sku);
                if (isset($resp['success']) && $resp['success'] == false) {
                    throw new \Exception('unknown error');
                    // $this->returnJSON(['status' => 'unknown error'], 400);
                } else {
                    $this->returnJSON($resp);
                }

                $this->returnJSON(['status' => 'ok', 'data_update' => $updates, 'data_missing' => $missings]);
            } catch (\Exception $e) {
                $try++;
                $this->jubelioLogin();
            }
        }
        return $this->returnJSON($data);
    }

    public function action ($action, $args = null) {
        $maxTry = 1;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            $jubelioToken = file_get_contents($this->jubelioTokenPath);
            try {
                if ($action == 'get_all_product_stock') {
                    $lastResp = $this->fetchAllProductStock($jubelioToken);
                } else if ($action == 'get_product') {
                    $lastResp = $this->fetchProduct($jubelioToken, $args);
                }
                if (isset($result['success']) && $result['success'] == false) {
                    throw new \Exception($result['message']);
                }
                $data = [
                    'success' => true,
                    'jubelioResponse' => $lastResp
                ];
                return $this->returnJSON($data);
            } catch (\Exception $e) {
                $try++;
                $this->jubelioLogin();
            }
        }
        $data = [
            'success' => false,
            'last_response' => $lastResp
        ];
        return $this->returnJSON($data);
    }

    public function fetchProduct ($token, $id) {
        $result = $this->jubelio->getProduct($token, $id);
        return $result;
    }

    public function fetchAllProductStock ($token) {
        $page = 1;
        $pageSize = 50;
        $body = null;
        $totalData = $pageSize;
        $currentData = 0;

        $result = [];


        $result = $this->jubelio->getAllProductStock($token, null);
        return $result;
    }

    /*     * ************************************************************************************** */

    public function authenticate () {
        $this->token = file_get_contents('internal/jubelio_token.txt');
        $this->authorization = [
            'Authorization' => $this->token
        ];

        if (empty($this->token)) {
            $data = [
                "success" => false,
                "message" => "Please provide Jubelio token."
            ];
            return $this->returnJSON($data);
        }

    }

    public function jubelioLogin() {
        $call = $this->jubelio->login();
        $data = [];
        if (isset($call['token'])) {
            $file = fopen($this->jubelioTokenPath, 'w+');
            fwrite($file, $call['token']);
            fclose($file);
            $data = ['code' => 200, 'message' => 'ok'];
        } else {
            $data = ['code' => 400, 'message' => 'Unknown error'];
        }
        // $this->returnJSON($data);
        return $data;
    }

    public function returnJSON($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

}
