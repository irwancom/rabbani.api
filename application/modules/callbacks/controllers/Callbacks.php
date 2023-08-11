<?php

use Service\CLM\Handler\OrderHandler;
use Carbon\Carbon;

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Callbacks extends REST_Controller {

    private $jubelioTokenPath;

    public function __construct() {
        parent::__construct();

        $this->jubelioTokenPath = 'internal/jubelio_token.txt';
        $this->load->library('xendit');
        $this->load->library('jubelio');
        $this->load->model('Call');
        $this->load->model('MainModel');
//        $this->load->model('PpobModel');
    }

    function index_get() {
        echo 'xxx';
        exit;
    }

    function xendit_post() {
        $rawData1 = file_get_contents("php://input");
        $data = $this->Call->callbackXENDIT($rawData1);
        if (!empty($data)) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
        exit;
    }

    function jubelio_get() {
        echo 'jubelio';
        exit;
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

    function jubelio_post() {
        $request = file_get_contents("php://input");
        $request = json_decode($request, true);
        $callback = [
            'fromcall' => 'JUBELIO',
            'dataJson' => json_encode($request)
        ];

        $this->MainModel->insert('logcallback', $callback);
        // no jubelio check
        $parsedRequest = $request;
        if (isset($parsedRequest['action']) && $parsedRequest['action'] == 'DELETED' ) {
            $data = ['status' => 'ok', 'version' => 'v1.0', 'extras' => 'No Action for Delete'];
            $this->returnJSON($data);
        }
        unset($parsedRequest['action']);
        $result = $this->createTransactionFromJubelioSalesOrder($parsedRequest);
        $data = ['status' => 'ok', 'version' => 'v4.0', 'extras' => $result];
        $this->returnJSON($data);


        // include jubelio check
        /* $maxTry = 3;
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
          $this->jubelioLogin();
          }
          $jubelioToken = file_get_contents($this->jubelioTokenPath);
          if (!empty($jubelioToken)) {
          $jubelioSalesOrder = $this->jubelio->getSalesOrder($jubelioToken, $request['salesorder_id']);
          $lastResp = $jubelioSalesOrder;
          if (isset($jubelioSalesOrder['success']) && $jubelioSalesOrder['success'] == false) {
          $this->jubelioLogin();
          } else {
          $result = $this->createTransactionFromJubelioSalesOrder($jubelioSalesOrder);
          $data = ['status' => 'ok'];
          $this->returnJSON($data);
          }
          }
          } catch (\Exception $e) {
          $this->jubelioLogin();
          $try++;
          }
          } */


        $data = ['code' => 400, 'message' => 'Unknown error', 'last_response' => $lastResp];
        $this->returnJSON($data);
    }

    function jubelio_stock_post () {
        ini_set('max_execution_time', 30000000000);
        $request = file_get_contents("php://input");
        $request = json_decode($request, true);
        $callback = [
            'fromcall' => 'JUBELIO_STOCK',
            'dataJson' => json_encode($request)
        ];

        $this->MainModel->insert('logcallback', $callback);

        $maxTry = 3;
        $try = 0;
        $lastResp = null;
        while ($try < $maxTry) {
            $jubelioToken = file_get_contents($this->jubelioTokenPath);

            try {
                $itemGroupId = $request['item_group_id'];
                $resp = $this->jubelio->getProductGroup($jubelioToken, $itemGroupId);
                if (isset($resp['success']) && $resp['success'] == false) {
                    throw new \Exception('unknown error');
                    // $this->returnJSON(['status' => 'unknown error'], 400);
                } else {
                    $productSkus = $resp['product_skus'];
                    $updates = [];
                    $missings = [];
                    foreach ($productSkus as $productSku) {
                        $productAtJubelio = $this->jubelio->getAllProductStock($jubelioToken, ['q' => $productSku['item_code']]);
                        $newStock = [];
                        if (count($productAtJubelio['data']) > 0) {
                            $newStock['stock'] = $productAtJubelio['data'][0]['total_stocks']['available'];
                            $newStock['on_hand'] = $productAtJubelio['data'][0]['total_stocks']['on_hand'];
                            $newStock['on_order'] = $productAtJubelio['data'][0]['total_stocks']['on_order'];
                        }


                        $existsProduct = $this->MainModel->findOne('product_details', ['sku_code' => $productSku['item_code']]);
                        if (empty($existsProduct)) {
                            $newData = [
                                'sku_code' => $productSku['item_code'],
                                'stock' => $newStock
                            ];
                            $missings[] = $newData;
                            // $this->returnJSON(['status' => 'Product detail not found'], '404');
                        } else {
                            $stock = $productSku['end_qty'];
                            $action = $this->MainModel->update('product_details', $newStock, ['sku_code' => $productSku['item_code']]);
                            $newData = [
                                'sku_code' => $productSku['item_code'],
                                'stock' => $newStock
                            ];
                            $updates[] = $newData;
                        }
                    }
                }

                $this->returnJSON(['status' => 'ok', 'data_update' => $updates, 'data_missing' => $missings]);
            } catch (\Exception $e) {
                $try++;
                $this->jubelioLogin();
            }
        }


        $this->returnJSON(['status' => 'ok']);
    }

    function sync_ready_to_pick_jubelio_post() {
        ini_set('max_execution_time', 30000000000);

        $play = true;
        $result = null;
        while ($play) {
            $jubelioToken = file_get_contents($this->jubelioTokenPath);
            $result = $this->jubelio->getReadyToPick($jubelioToken);
            if (isset($result['success']) && $result['success'] == false){
                $this->jubelioLogin();
            } else {
                $play = false;   
            }
        }

        $data = $result['data'];
        $key = [];
        $key['missing'] = [];
        foreach ($data as $d) {
            $transaction = $this->MainModel->findOne('transaction', ['salesorder_id' => $d['salesorder_id']]);
            $result[] = $transaction;
            if (!empty($transaction)) {
                $key[$transaction->channel_status] = !isset($key[$transaction->channel_status]) ? 1 : $key[$transaction->channel_status] += 1;
            } else {
                $key['missing'][] = $d['salesorder_id'] . ' ' . $d['salesorder_no'];
            }
        }

        $this->returnJSON($key);
    }

    function sync_item_jubelio_post () {
        ini_set('max_execution_time', 30000000000);
        $this->jubelioLogin();
        $jubelioToken = file_get_contents(($this->jubelioTokenPath));
        $result = $this->jubelio->getItems($jubelioToken);
        $datas = $result['data'];
        foreach ($datas as $data) {
            $itemGroupId = $data['item_group_id'];
            $variations = $data['variants'];
            $onlineStatus = $data['online_status'];

            foreach ($variations as $variation) {
                $skuCode = $variation['item_code'];
                $itemId = $variation['item_id'];
                $data = [
                    'id_product_detail' => $itemId,
                    'timeUpdate'=>date('Y-m-d H:i:s'),
                    'sku_code' => $skuCode,
                    'item_group_id' => $itemGroupId,
                    'item_id' => $itemId,
                    // 'dataJson' => json_encode($data),
                    'mpDataJson' => json_encode($onlineStatus)
                ];

                $jubelioItem = $this->MainModel->findOne('product_sync_jubelio', ['item_id' => $data['item_id']]);
                if (empty($jubelioItem)) {
                    $this->MainModel->insert('product_sync_jubelio', $data);
                } else {
                    $this->MainModel->update('product_sync_jubelio', $data, ['item_id' => $itemId]);
                }
            }
        }

        $return = [
            'data' => count($datas)
        ];

        $this->returnJSON($return);

    }

    function sync_jubelio_post() {
        ini_set('max_execution_time', 30000000000);

        $this->jubelioLogin();
        $jubelioToken = file_get_contents($this->jubelioTokenPath);
        $unsynchronizedTransactions = $this->MainModel->find('transaction', ['channel_status' => 'pending']);
        $errors = $unsynchronizedTransactions;
        while (count($errors) > 0) {
            $error = [];
            foreach ($errors as $salesOrder) {
                $salesOrderId = $salesOrder->salesorder_id;
                $jubelioSalesOrder = $this->jubelio->getSalesOrder($jubelioToken, $salesOrderId);
                if (isset($jubelioSalesOrder['success']) && $jubelioSalesOrder['success'] == false) {
                    $e = new \stdClass;
                    $error[] = $e;
                } else {
                    $result = $this->createTransactionFromJubelioSalesOrder($jubelioSalesOrder);
                }
            }
            $errors = $error;
        }


        $this->returnJSON(['data' => 'ok', 'errors' => $error]);
    }

    function send_stock_post () {
        $sku = $this->input->post('sku');
        $stock = $this->input->post('stock');
        $amount = $this->input->post('amount');
        $note = $this->input->post('note');
        $locationId = -1;
        $isOpeningBalance = false;

        $jubelioItem = $this->MainModel->findOne('product_sync_jubelio', ['sku_code' => $sku]);
        if (empty($jubelioItem)) {
            $data = [
                'success' => false,
                'message' => 'Jubelio item not found.'
            ];
            $this->returnJSON($data);
        }
        
        $play = true;
        $result = null;
        $temp = 1;
        $jubelioToken = null;
        while ($temp <= 3) {
            $jubelioToken = file_get_contents($this->jubelioTokenPath);
            $item = [
                'item_adj_detail_id' => 0,
                'item_id' => $jubelioItem->item_id,
                'serial_no' => 'PHP'.time(),
                'qty_in_base' => (int)$stock,
                'uom_id' => -1,
                'unit' => 'Buah',
                'cost' => (int)$amount,
                'amount' => (int)$amount,
                'location_id' => $locationId,
                'account_id' => 75,
                'description' => $note
            ];
            $items = [];
            $items[] = $item;
            $result = $this->jubelio->addStockAdjustments($jubelioToken, $items, $note, $locationId, $isOpeningBalance);
            if (isset($result['success']) && $result['success'] == false){
                $this->jubelioLogin();
                $temp++;
            } else {
                $temp = 100;
            }
        }

        $data = [
            'success' => true,
            'jubelioResponse' => $result
        ];
        $this->returnJSON($data);
    }

    public function createTransactionFromJubelioSalesOrder($jubelio) {
        $this->db->trans_start();
        $payload = [];
        $data = array(
            'salesorder_id' => $jubelio['salesorder_id'],
            'salesorder_no' => $jubelio['salesorder_no'],
            'contact_id' => null,
            'customer_name' => $jubelio['customer_name'],
            'customer_phone' => $jubelio['customer_phone'],
            'transaction_date' => date('Y-m-d H:i:s', strtotime($jubelio['transaction_date'])),
            'created_date' => date('Y-m-d H:i:s', strtotime($jubelio['created_date'])),
            'invoice_no' => $jubelio['invoice_no'],
            'invoice_id' => $jubelio['invoice_id'],
            'is_tax_included' => $jubelio['is_tax_included'],
            'note' => $jubelio['note'],
            'sub_total' => $jubelio['sub_total'],
            'total_disc' => $jubelio['total_disc'],
            'total_tax' => $jubelio['total_tax'],
            'grand_total' => $jubelio['grand_total'],
            'ref_no' => $jubelio['ref_no'],
            'payment_method' => $jubelio['payment_method'],
            'location_id' => $jubelio['location_id'],
            'source' => is_int($jubelio['source']) ? $jubelio['source'] : null,
            'is_canceled' => $jubelio['is_canceled'],
            'cancel_reason' => $jubelio['cancel_reason'],
            'cancel_reason_detail' => $jubelio['cancel_reason_detail'],
            'channel_status' => $jubelio['channel_status'],
            'shipping_cost' => $jubelio['shipping_cost'],
            'insurance_cost' => $jubelio['insurance_cost'],
            'is_paid' => $jubelio['is_paid'],
            'shipping_full_name' => $jubelio['shipping_full_name'],
            'shipping_phone' => $jubelio['shipping_phone'],
            'shipping_address' => $jubelio['shipping_address'],
            'shipping_area' => $jubelio['shipping_area'],
            'shipping_city' => $jubelio['shipping_city'],
            'shipping_province' => $jubelio['shipping_province'],
            'shipping_post_code' => $jubelio['shipping_post_code'],
            'shipping_country' => $jubelio['shipping_country'],
            'last_modified' => Carbon::parse($jubelio['last_modified'])->format('Y-m-d H:i:s'),
            'register_session_id' => $jubelio['register_session_id'],
            'user_name' => $jubelio['user_name'],
            'ordprdseq' => $jubelio['ordprdseq'],
            'store_id' => $jubelio['store_id'],
            'marked_as_complete' => $jubelio['marked_as_complete'],
            'is_tracked' => $jubelio['is_tracked'],
            'store_so_number' => $jubelio['store_so_number'],
            'is_deleted_from_picklist' => $jubelio['is_deleted_from_picklist'],
            'deleted_from_picklist_by' => $jubelio['deleted_from_picklist_by'],
            'dropshipper' => $jubelio['dropshipper'],
            'dropshipper_note' => $jubelio['dropshipper_note'],
            'dropshipper_address' => $jubelio['dropshipper_address'],
            'is_shipped' => $jubelio['is_shipped'],
            'due_date' => $jubelio['due_date'],
            'received_date' => $jubelio['received_date'],
            'salesmen_id' => $jubelio['salesmen_id'],
            'salesmen_name' => $jubelio['salesmen_name'],
            'escrow_amount' => $jubelio['escrow_amount'],
            'is_acknowledge' => $jubelio['is_acknowledge'],
            'acknowledge_status' => $jubelio['acknowledge_status'],
            'is_label_printed' => $jubelio['is_label_printed'],
            'is_invoice_printed' => $jubelio['is_invoice_printed'],
            'total_amount_mp' => $jubelio['total_amount_mp'],
            'internal_do_number' => $jubelio['internal_do_number'],
            'internal_so_number' => $jubelio['internal_so_number'],
            'tracking_number' => $jubelio['tracking_number'],
            'courier' => $jubelio['courier'],
            'username' => $jubelio['username'],
            'is_po' => $jubelio['is_po'],
            'picked_in' => $jubelio['picked_in'],
            'district_cd' => $jubelio['district_cd'],
            'sort_code' => $jubelio['sort_code'],
            'shipment_type' => $jubelio['shipment_type'],
            'status_details' => $jubelio['status_details'],
            'service_fee' => $jubelio['service_fee'],
            'source_name' => $jubelio['source_name'],
            'store_name' => $jubelio['store_name'],
            // 'location_tax' => $jubelio['location_tax'],
            // 'location_discount' => $jubelio['location_discount'],
            'location_name' => $jubelio['location_name'],
            'shipper' => $jubelio['shipper'],
            'tracking_no' => $jubelio['tracking_no'],
            'add_disc' => $jubelio['add_disc'],
            'add_fee' => $jubelio['add_fee'],
            // 'dlvmthdcd' => $jubelio['dlvmthdcd'],
            // 'dlvetprscd' => $jubelio['dlvetprscd'],
            // 'dlvetprsnm' => $jubelio['dlvetprsnm'],
            // 'dlvno' => $jubelio['dlvno'],
            // 'closure_id' => $jubelio['closure_id'],
            // 'mp_timestamp' => $jubelio['mp_timestamp'],
            // 'buyer_shipping_cost' => $jubelio['buyer_shipping_cost'],
            'total_weight_in_kg' => $jubelio['total_weight_in_kg'],
            // 'tn_created_date' => $jubelio['tn_created_date'],
            // 'mp_cancel_reason' => $jubelio['mp_cancel_reason'],
            // 'mp_cancel_by' => $jubelio['mp_cancel_by'],
            //'mp_cancel_date' => $jubelio['mp_cancel_date'],
            // 'is_cod' => $jubelio['is_cod'],
            // 'id_voucher' => $jubelio['id_voucher'],
            // 'nama_voucher' => $jubelio['nama_voucher'],
            // 'disc_voucher' => $jubelio['disc_voucher'],
            // 'tax_percentage' => $jubelio['tax_percentage'],
            // 'id_address' => $jubelio['id_address'],
            // 'address_name' => $jubelio['address_name'],
            'shipping_urbanvillage' => null,
            'status' => $jubelio['status']
        );
        
        $dictionaryOrderStatus = [
            'COMPLETED' => OrderHandler::ORDER_STATUS_COMPLETED,
            'CANCELED' => OrderHandler::ORDER_STATUS_CANCELED,
            'DELETED' => OrderHandler::ORDER_STATUS_DELETED,
            'INVOICED' => OrderHandler::ORDER_STATUS_IN_PROCESS,
            'IN_CANCEL' => OrderHandler::ORDER_STATUS_CANCELED,
            'PAID' => OrderHandler::ORDER_STATUS_OPEN,
            'PENDING' => OrderHandler::ORDER_STATUS_WAITING_PAYMENT,
            'PROCESSING' => OrderHandler::ORDER_STATUS_OPEN,
            'RETURNED' => OrderHandler::ORDER_STATUS_CANCELED,
            'SHIPPED' => OrderHandler::ORDER_STATUS_DELIVERED,
        ];

        $items = $jubelio['items'];

        $filter = [
            'salesorder_id' => $data['salesorder_id']
        ];

        $state = null;

        //deprecated v1
        /* $existsOrder = $this->MainModel->findOne('transaction', $filter);
        if (!empty($existsOrder)) {
            $action = $this->MainModel->update('transaction', $data, $filter);
            if (!empty($data['is_paid']) && empty($existsOrder->is_paid) && $data['is_paid'] == true) {
                $state = 'decrement';
            }
            if ( ($data['channel_status'] == 'CANCELLED' || $data['channel_status'] == 'CANCELED') && $data['channel_status'] != $existsOrder->channel_status) {
                $state = 'increment';
            }
        } else {
            if (!empty($data['is_paid'])) {
                $state = 'decrement';
            }
            $action = $this->MainModel->insert('transaction', $data);
        } */

        $orderData = array(
            'salesorder_id' => $data['salesorder_id'],
            'id_auth' => 1,
            'order_code' => $data['salesorder_no'],
            'id_auth_user' => null,
            'user_id' => null,
            'id_cart' => null,
            'order_info' => $data['note'],
            'total_price' => $data['grand_total'],
            'total_qty' => 0,
            'status' => (isset($dictionaryOrderStatus[$data['status']]) ? $dictionaryOrderStatus[$data['status']] : OrderHandler::ORDER_STATUS_UNKNOWN),
            'no_awb' => $data['tracking_number'],
            'shipping_status' => null,
            'shipping_courier' => $data['shipper'],
            'discount_noted' => null,
            'order_source' => $data['source'],
            'jubelio_store_name' => $data['store_name'],
            'invoice_number' => $data['invoice_no'],
            'member_address_province_name' => $data['shipping_province'],
            'member_address_city_name' => $data['shipping_city'],
            'member_address_suburb_name' => $data['shipping_post_code'],
            'member_address_area_name' => $data['shipping_area'],
            'member_address_address' => $data['shipping_address'],
            'member_address_name' => $data['shipping_full_name'],
            'member_address_receiver_name' => $data['shipping_full_name'],
            'member_address_receiver_phone' => $data['customer_phone'],
            'voucher_code' => null,
            'logistic_name' => $data['shipper'],
            'logistic_rate_name' => $data['shipment_type'],
            'total_weight' => $data['total_weight_in_kg'],
            'shipping_cost' => $data['shipping_cost'],
            'total_discount' => $data['total_disc'],
            'payment_amount' => $data['grand_total'],
            'final_price' => $data['grand_total'],
            'shopping_price' => $data['sub_total'],
            'payment_method_code' => $data['payment_method'],
            'payment_reference_no' => $data['ref_no'],
            'payment_method_name' => $data['payment_method'],
            'is_paid' => $data['is_paid'],
            'is_delivered' => $data['is_shipped'], 
            'jubelio_picked_in' => $data['picked_in']
        );

        if ($orderData['status'] == OrderHandler::ORDER_STATUS_OPEN && !empty($data['picked_in'])) {
            $orderData['status'] = OrderHandler::ORDER_STATUS_IN_PROCESS;
        }
        /* if ($orderData['status'] == OrderHandler::ORDER_STATUS_OPEN && !empty($orderData['no_awb']) && $orderData['source'] != "INTERNAL") {
            $orderData['status'] = OrderHandler::ORDER_STATUS_IN_SHIPMENT;
        } */

        $existsClmOrder = $this->MainModel->findOne('orders', ['salesorder_id' => $orderData['salesorder_id']]);
        if (!empty($existsClmOrder)) {
            $orderData['updated_at'] = date('Y-m-d H:i:s');
            $orderDataAction = $this->MainModel->update('orders', $orderData, ['salesorder_id' => $orderData['salesorder_id']]);
        } else {
            $orderData['created_at'] = date('Y-m-d H:i:s');
            $orderDataAction = $this->MainModel->insert('orders', $orderData);
            // $existsClmOrder = $this->MainModel->findOne('orders', ['id_order' => $orderDataAction]);
        }

        // START OF V2
        if (!empty($existsClmOrder)) {
            // $action = $this->MainModel->update('orders', $data, $filter);
            if (!empty($data['is_paid']) && empty($existsClmOrder->is_paid) && $data['is_paid'] == true) {
                $state = 'decrement';
            }
            if ( ($data['channel_status'] == 'CANCELLED' || $data['channel_status'] == 'CANCELED') && $data['channel_status'] != $existsClmOrder->channel_status) {
                $state = 'increment';
            }
        } else {
            if (!empty($data['is_paid'])) {
                $state = 'decrement';
            }
        }
        // END OF V2

        if(empty($existsClmOrder)){
            foreach ($items as $item) {
                $dataItem = array(
                    'salesorder_id' => $jubelio['salesorder_id'],
                    'salesorder_detail_id' => $item['salesorder_detail_id'],
                    'item_id' => $item['item_id'],
                    'serial_no' => $item['serial_no'],
                    'description' => $item['description'],
                    'tax_id' => $item['tax_id'],
                    'price' => $item['price'],
                    'unit' => $item['unit'],
                    'qty_in_base' => $item['qty_in_base'],
                    'disc' => $item['disc'],
                    'disc_amount' => $item['disc_amount'],
                    'tax_amount' => $item['tax_amount'],
                    'amount' => $item['amount'],
                    'location_id' => (isset($item['location_id']) ? $item['location_id'] : null),
                    'shipper' => (isset($item['shipper']) ? $item['shipper'] : ''),
                    'qty' => $item['qty'],
                    'uom_id' => $item['uom_id'],
                    'shipped_date' => $item['shipped_date'],
                    'channel_order_detail_id' => $item['channel_order_detail_id'],
                    'is_return_resolved' => $item['is_return_resolved'],
                    'reject_return_reason' => $item['reject_return_reason'],
                    'awb_created_date' => convertDateFromTimezone($item['awb_created_date']),
                    'ticket_no' => $item['ticket_no'],
                    'pack_scanned_date' => $item['pack_scanned_date'],
                    'pick_scanned_date' => $item['pick_scanned_date'],
                    'destination_code' => $item['destination_code'],
                    'origin_code' => $item['origin_code'],
                    'status' => $item['status'],
                    'item_code' => $item['item_code'],
                    'item_name' => $item['item_name'],
                    'sell_price' => $item['sell_price'],
                    'original_price' => $item['original_price'],
                    'rate' => $item['rate'],
                    'tax_name' => $item['tax_name'],
                    'item_group_id' => $item['item_group_id'],
                    'loc_id' => $item['loc_id'],
                    'thumbnail' => $item['thumbnail'],
                    'fbm' => $item['fbm'],
                        // 'weight_in_gram' => $item['weight_in_gram'],
                        // 'is_fbm' => $item['is_fbm'],
                        // 'item_detail_id' => $item['item_detail_id'],
                        // 'disc_type' => $item['disc_type'] 
                );

                $filterItem = [
                    'salesorder_id' => $dataItem['salesorder_id'],
                    'salesorder_detail_id' => $dataItem['salesorder_detail_id']
                ];

                // DEPRECATED
                /* $existsItem = $this->MainModel->findOne('transaction_item', $filterItem);
                if (!empty($existsItem)) {
                    $actionItem = $this->MainModel->update('transaction_item', $dataItem, $filterItem);
                } else {
                    $actionItem = $this->MainModel->insert('transaction_item', $dataItem);
                } */

                $discType = null;
                if (!empty($dataItem['disc_amount'])) {
                    $discType = 2;
                }
                if (!empty($dataItem['disc'])) {
                    $discType = 1;
                }
                $fixQty = intval($dataItem['qty']);
                if (empty($qty)) {
                    $fixQty = intval($dataItem['qty_in_base']);
                }
                $orderDetailData = array(
                    'order_code' => $orderData['order_code'],
                    'salesorder_detail_id' => $dataItem['salesorder_detail_id'],
                    'sku_code' => $dataItem['item_code'],
                    'price' => (int)$dataItem['sell_price'],
                    'qty' => $fixQty,
                    'discount_type' => $discType,
                    'discount_value' => $dataItem['disc'],
                    'total' => $dataItem['amount'],
                    'subtotal' => (int)$dataItem['sell_price']*$dataItem['qty'],
                    'discount_amount' => $dataItem['disc_amount']
                );

                $filterOrderDetail = [
                    'order_code' => $orderData['order_code'],
                    'salesorder_detail_id' => $orderDetailData['salesorder_detail_id']
                ];
                $existsOrderDetail = $this->MainModel->findOne('order_details', ['order_code' => $orderData['order_code'], 'salesorder_detail_id' => $orderDetailData['salesorder_detail_id']]);
                if (!empty($existsOrderDetail)) {
                    $orderDetailAction = $this->MainModel->update('order_details', $orderDetailData, $filterOrderDetail);
                } else {
                    $orderDetailAction = $this->MainModel->insert('order_details', $orderDetailData);
                }

                if (in_array($state, ['increment', 'decrement'])) {
                    $productDetail = $this->MainModel->findOne('product_details', ['sku_code' => $dataItem['item_code']]);
                    $beforeStock = 0;
                    $stock = 0;
                    if (!empty($productDetail)) {
                        $beforeStock = $productDetail->stock;
                        $stock = $productDetail->stock;
                    } else {
                        $prefix = substr($dataItem['item_code'], 0, 6);
                        $existsProductBySku = $this->MainModel->findOne('product', ['sku' => $prefix]);
                        $idProduct = 976;
                        if (!empty($existsProductBySku)) {
                            $idProduct = $existsProductBySku->id_product;
                        }

                        $dataProductDetail = [
                            'id_product' => $idProduct,
                            'sku_code' => $dataItem['item_code'],
                            'stock' => $stock,
                            'price' => $dataItem['price'],
                        ];
                        $this->MainModel->insert('product_details', $dataProductDetail);
                        $productDetail = $this->MainModel->findOne('product_details', ['sku_code' => $dataProductDetail['sku_code']]);
                        $payload[] = [
                            'state' => 'new-product-detail',
                            'data' => [
                                $productDetail,
                            ]
                        ];
                    }
                    $product = $this->MainModel->findOne('product', ['id_product' => $productDetail->id_product]);

                    if ($state == 'increment') {
                        $stock += $dataItem['qty_in_base'];
                    } else if ($state == 'decrement') {
                        $stock -= $dataItem['qty_in_base'];
                    }
                    $updateData = [
                        'stock' => $stock
                    ];
                    $updateStockAction = $this->MainModel->update('product_details', $updateData, ['sku_code' => $productDetail->sku_code]);
                    $payload[] = [
                        'state' => 'update-stock',
                        'data' => [
                            'sku_code' => $productDetail->sku_code,
                            'before_stock' => $beforeStock,
                            'after_stock' => $stock
                        ]
                    ];

                    if ($state == 'decrement') {
                        $updatePurchasedProductDetail = $this->MainModel->update('product_details', ['total_purchased' => $productDetail->total_purchased + $dataItem['qty_in_base']], ['id_product_detail' => $productDetail->id_product_detail]);
                        $updatePurchasedProduct = $this->MainModel->update('product', ['total_purchased' => $product->total_purchased + $dataItem['qty_in_base']], ['id_product' => $product->id_product]);
                        $payload[] = [
                            'state' => 'update-total-purchased',
                            'id_product' => $product->id_product,
                            'id_product_detail' => $productDetail->id_product_detail,
                            'sku_code' => $productDetail->sku_code
                        ];
                    }
                }
            }
        }


        if ($data['status'] == 'COMPLETED') {
            if (isset($data['customer_phone'])) {
                $customerPhone = $data['customer_phone'];
                $customerPhone = str_replace('+', '', $customerPhone);
                if ($customerPhone[0] == '0') {
                    // 0 prefix
                    $customerPhone = '62'.substr(1);
                } else if ($customerPhone[0].$customerPhone[1] == '62') {
                    // 62 prefix
                    $customerPhone = $customerPhone;
                } else {
                    // national number (8172718981)
                    $customerPhone = '62'.$customerPhone;
                }
            }

            $rabbaniAuth = $this->MainModel->findOne('auth_api', ['id_auth' => 1]);
            $existsMemberDigital = $this->MainModel->findOne('member_digitals', ['phone_number' => $customerPhone, 'id_auth_api' => $rabbaniAuth->id_auth]);
            if (!empty($existsMemberDigital)) {
                $validator = new Service\Validator($this->MainModel);
                $secret = $rabbaniAuth->secret;
                $auth = $validator->validateAuthApi($secret);
                if ($auth->hasErrors()) {
                    $payload[] = [
                        'state' => 'update-point',
                        'message' => 'Auth api not valid'
                    ];
                } else {
                    $pointPayload = [
                        // 'phone_number' => $customerPhone,
                        'order_id' => $data['salesorder_no'],
                        'source_name' => $data['source_name'],
                        'store_name' => $data['store_name'],
                        'payment_amount' => intval($data['grand_total']),
                        'member_point' => intval(intval($data['grand_total'])/100000),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $handler = new Service\MemberDigital\MemberDigitalHandler($this->MainModel, $auth->data);
                    $result = $handler->createMemberDigitalTransaction($customerPhone, $pointPayload);
                    if ($result->hasErrors()) {
                        $payload[] = [
                            'state' => 'update-point',
                            'data' => [
                                'payload' => $pointPayload,
                                'error' => $result->errors
                            ]
                        ];
                    } else {
                        $payload[] = [
                            'state' => 'update-point',
                            'data' => [
                                'payload' => $pointPayload,
                                'old_member_data' => $existsMemberDigital,
                                'new_member_data' => $result->data
                            ]
                        ];
                    }
                }
            } else {
                $payload[] = [
                    'state' => 'update-point',
                    'data' => [
                        'customer_phone' => $customerPhone,
                        'message' => 'Member digital not found'
                    ]
                ];
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return $payload;
        }

        return $payload;
    }

    public function tripayPpob_get() {
//        $data = '[{"trxid":82847,"api_trxid":"2","via":"API","code":"PLNPASCH","produk":"PLN Pascabayar","harga":"1501250","target":"081241447283","mtrpln":"2785554916","note":"Pembayaran PLN Pascabayar 2785554916 a\/n Nama Pelanggan BERHASIL. SN\/Ref: 1836236942451196","token":"1836236942451196","status":1,"saldo_before_trx":2000000,"saldo_after_trx":498750,"created_at":"2020-11-19 19:03:10","updated_at":"2020-11-19 19:03:10","tagihan":{"id":24059,"nama":"Nama Pelanggan","periode":"202008","jumlah_tagihan":1500000,"admin":1250,"jumlah_bayar":1501250}}]';
        $data = '[{"trxid":22499,"api_trxid":"1","via":"API","code":"S100","produk":"Telkomsel 100","harga":"97765","target":"081290338745","mtrpln":"-","note":"Trx S100 081290338745 SUKSES. SN: 6408921795434446","token":"6408921795434446","status":0,"saldo_before_trx":100000,"saldo_after_trx":2235,"created_at":"2020-11-19 18:46:50","updated_at":"2020-11-19 18:46:50","tagihan":null}]';
//        $this->Call->tripayPpob($data);
        echo $data;
    }
    
    public function tripayPpob_post() {
        $Secret = $this->input->get_request_header('X-Callback-Secret');
        if ($Secret == 'BVfR0Z3aO1UJ6uDU2G6pkxbSGbdCVXaw') {
            $payloadx = file_get_contents("php://input");
            $payload = json_decode($payloadx, true);

            $this->Call->tripayPpob(json_encode($payload));
            
            $data = [
                'fromcall' => 'TRIPAY',
                'dataJson' => json_encode($payload),
                'dateTime' => date('Y-m-d H:i:s')
            ];
            $action = $this->MainModel->insert('logcallback', $data);
            return $this->returnJSON($payload);
        } else {
            $payload = array('msg' => 'error', 'data' => 'null');
            return $this->returnJSON($payload);
        }
    }

    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

}
