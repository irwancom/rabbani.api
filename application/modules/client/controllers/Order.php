<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';


class Order extends REST_Controller {

    private $jubelioTokenPath;

    public function __construct() {
        parent::__construct();

        $this->jubelioTokenPath = 'internal/jubelio_token.txt';
        $this->load->library('jubelio');
        $this->load->model('Orders');
        $this->load->model('Carts');
        $this->load->model('MainModel');
    }

    /* public function index_post() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;
        
        $this->isError($data);

        $cart = $this->Carts->detail('id_cart', $data['id_cart']);
        if (!$cart) 
            return $this->response(failed_format(402, ['order' => 'error.order.global.not_item_found_in_cart']));

        $data['cart'] = $cart;
        $result = $this->Orders->store($data);
        
        if ($result) 
            return $this->response(success_format(['status' => 'success', 'order_code' => $result['order_code']]));
        
        return $this->response(failed_format(402, ['order' => 'error.order.global.failed_to_store_order']));
    } */

    public function index_post () {
        $payload = $this->input->post();

        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            try {
                $jubelioToken = '';
                if (file_exists($this->jubelioTokenPath)) {
                    $jubelioToken = file_get_contents($this->jubelioTokenPath);
                } else {
                    throw new \Exception('Jubelio token file not found');
                }
                
                if (empty($jubelioToken)) {
                    throw new \Exception('Error');
                }
                $jubelioToken = file_get_contents($this->jubelioTokenPath);
                
                if (!empty($jubelioToken)) {

                    $action = $this->jubelio->createSalesOrder($jubelioToken, $payload);
                    $lastResp = $action;

                    if (isset($action['success']) && $action['success'] == false) {
                        throw new \Exception('Error');
                    } else {
                        // $result = $this->createTransactionFromJubelioSalesOrder($jubelioSalesOrder);
                        // $data = ['status' => 'ok'];
                        $data = $action;
                        return $this->response($data);
                    } 
                }
            } catch (\Exception $e) {
                $this->jubelioLogin();
                $try++;
            }
        }


        $data = [
            'success' => 'false',
            'error' => $lastResp
        ];
        return $this->response($data);
    }

    public function paid_post () {
        $payload = $this->input->post('salesorder_id');

        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            try {
                $jubelioToken = '';
                if (file_exists($this->jubelioTokenPath)) {
                    $jubelioToken = file_get_contents($this->jubelioTokenPath);
                } else {
                    throw new \Exception('Jubelio token file not found');
                }
                
                if (empty($jubelioToken)) {
                    throw new \Exception('Error');
                }
                $jubelioToken = file_get_contents($this->jubelioTokenPath);
                
                if (!empty($jubelioToken)) {

                    $action = $this->jubelio->setAsPaid($jubelioToken, $payload);
                    $lastResp = $action;

                    if (isset($action['success']) && $action['success'] == false) {
                        throw new \Exception('Error');
                    } else {
                        $data = $action;
                        return $this->response($data);
                    } 
                }
            } catch (\Exception $e) {
                $this->jubelioLogin();
                $try++;
            }
        }


        $data = [
            'success' => 'false',
            'error' => $lastResp
        ];
        return $this->response($data);
    }

    public function create_invoice_payment_post () {
        $salesOrderId = $this->input->post('salesorder_id');

        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            try {
                $jubelioToken = '';
                if (file_exists($this->jubelioTokenPath)) {
                    $jubelioToken = file_get_contents($this->jubelioTokenPath);
                } else {
                    throw new \Exception('Jubelio token file not found');
                }
                
                if (empty($jubelioToken)) {
                    throw new \Exception('Error');
                }
                $jubelioToken = file_get_contents($this->jubelioTokenPath);
                
                if (!empty($jubelioToken)) {

                    $action = $this->jubelio->createInvoicePayment($jubelioToken, $salesOrderId);
                    $lastResp = $action;

                    if (isset($action['success']) && $action['success'] == false) {
                        throw new \Exception('Error');
                    } else {
                        $data = $action;
                        return $this->response($data);
                    } 
                }
            } catch (\Exception $e) {
                $this->jubelioLogin();
                $try++;
            }
        }


        $data = [
            'success' => 'false',
            'error' => $lastResp
        ];
        return $this->response($data);
    }

    public function picklist_post () {
        $salesOrderIds = $this->input->post('salesorder_id');
        $items = $this->input->post('items');

        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            try {
                $jubelioToken = '';
                if (file_exists($this->jubelioTokenPath)) {
                    $jubelioToken = file_get_contents($this->jubelioTokenPath);
                } else {
                    throw new \Exception('Jubelio token file not found');
                }
                
                if (empty($jubelioToken)) {
                    throw new \Exception('Error');
                }
                $jubelioToken = file_get_contents($this->jubelioTokenPath);
                
                if (!empty($jubelioToken)) {
                    $action = $this->jubelio->createPickList($jubelioToken, $salesOrderIds, $items);
                    $lastResp = $action;

                    if (isset($action['success']) && $action['success'] == false) {
                        throw new \Exception('Error');
                    } else {
                        $data = $action;
                        return $this->response($data);
                    } 
                }
            } catch (\Exception $e) {
                $this->jubelioLogin();
                $try++;
            }
        }


        $data = [
            'success' => 'false',
            'error' => $lastResp
        ];
        return $this->response($data);
    }

    public function airwaybill_post () {
        $salesOrderId = $this->input->post('salesorder_id');
        $trackingNo = $this->input->post('tracking_no');
        $shipper = $this->input->post('shipper');

        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            try {
                $jubelioToken = '';
                if (file_exists($this->jubelioTokenPath)) {
                    $jubelioToken = file_get_contents($this->jubelioTokenPath);
                } else {
                    throw new \Exception('Jubelio token file not found');
                }
                
                if (empty($jubelioToken)) {
                    throw new \Exception('Error');
                }
                $jubelioToken = file_get_contents($this->jubelioTokenPath);
                
                if (!empty($jubelioToken)) {
                    $action = $this->jubelio->saveAirwayBill($jubelioToken, $salesOrderId, $trackingNo, $shipper);
                    $lastResp = $action;

                    if (isset($action['success']) && $action['success'] == false) {
                        throw new \Exception('Error');
                    } else {
                        $data = $action;
                        return $this->response($data);
                    } 
                }
            } catch (\Exception $e) {
                $this->jubelioLogin();
                $try++;
            }
        }


        $data = [
            'success' => 'false',
            'error' => $lastResp
        ];
        return $this->response($data);
    }

    public function received_date_post () {
        $salesOrderId = $this->input->post('salesorder_id');
        $receivedDate = $this->input->post('received_date');

        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            try {
                $jubelioToken = '';
                if (file_exists($this->jubelioTokenPath)) {
                    $jubelioToken = file_get_contents($this->jubelioTokenPath);
                } else {
                    throw new \Exception('Jubelio token file not found');
                }
                
                if (empty($jubelioToken)) {
                    throw new \Exception('Error');
                }
                $jubelioToken = file_get_contents($this->jubelioTokenPath);
                
                if (!empty($jubelioToken)) {

                    $action = $this->jubelio->saveReceivedDate($jubelioToken, $salesOrderId, $receivedDate);
                    $lastResp = $action;

                    if (isset($action['success']) && $action['success'] == false) {
                        throw new \Exception('Error');
                    } else {
                        $data = $action;
                        return $this->response($data);
                    } 
                }
            } catch (\Exception $e) {
                $this->jubelioLogin();
                $try++;
            }
        }


        $data = [
            'success' => 'false',
            'error' => $lastResp
        ];
        return $this->response($data);
    }

    public function complete_post () {

        $payload = $this->input->post('salesorder_id');

        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            try {
                $jubelioToken = '';
                if (file_exists($this->jubelioTokenPath)) {
                    $jubelioToken = file_get_contents($this->jubelioTokenPath);
                } else {
                    throw new \Exception('Jubelio token file not found');
                }
                
                if (empty($jubelioToken)) {
                    throw new \Exception('Error');
                }
                $jubelioToken = file_get_contents($this->jubelioTokenPath);
                
                if (!empty($jubelioToken)) {

                    $action = $this->jubelio->setAsComplete($jubelioToken, $payload);
                    $lastResp = $action;

                    if (isset($action['success']) && $action['success'] == false) {
                        throw new \Exception('Error');
                    } else {
                        $data = $action;
                        return $this->response($data);
                    } 
                }
            } catch (\Exception $e) {
                $this->jubelioLogin();
                $try++;
            }
        }


        $data = [
            'success' => 'false',
            'error' => $lastResp
        ];
        return $this->response($data);
    }

    private function isError($data) {

        if (!isset($data['id_cart'])) {
            return $this->response(failed_format(402, ['id_cart' => 'error.order.id_cart.is_required']));
        }

        if (!isset($data['id_auth_user'])) {
            return $this->response(failed_format(402, ['id_auth_user' => 'error.order.id_auth_user.is_required']));
        }

        if (!isset($data['order_info']['name'])) {
            return $this->response(failed_format(402, ['name' => 'error.order.name.is_required']));
        }

        if (!isset($data['order_info']['address'])) {
            return $this->response(failed_format(402, ['address' => 'error.order.address.is_required']));
        }

        if (!isset($data['order_info']['phone'])) {
            return $this->response(failed_format(402, ['phone' => 'error.order.phone.is_required']));
        }

        if (!isset($data['shipping_courier'])) {
            return $this->response(failed_format(402, ['phone' => 'error.order.shipping_courier.is_required']));
        }

        return null;
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

}
