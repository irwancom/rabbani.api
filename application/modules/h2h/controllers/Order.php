<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Service\CLM\Handler\OrderHandler;
use Service\CLM\Handler\QcHandler;

class Order extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function index_get() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $payload = $this->input->get();

        $sort = array('id_order','created_at','updated_at');
        $orderKey = 'orders.id_order'; $orderValue = 'DESC';
        if(isset($payload['order_key']) && $payload['order_key'] && !empty($payload['order_key']) && !is_null($payload['order_key'])){
            $isOrderBy = strtolower($payload['order_key']);
            $orderKey = (in_array($isOrderBy, $sort)) ? 'orders.'.$isOrderBy : $orderKey;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderValue = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderValue;
        }

        $limit = 20; $page = 1;
        if(isset($payload['data']) && $payload['data'] && !empty($payload['data']) && is_numeric($payload['data'])){
            $isLimit = intval($payload['data']);
            $limit = ($isLimit<=1) ? 1 : $isLimit;
        }
        if(isset($payload['page']) && $payload['page'] && !empty($payload['page']) && is_numeric($payload['page'])){
            $isPage = intval($payload['page']);
            $page = ($isPage<=1) ? 1 : $isPage;
        }

        $filters = array('orders.order_source'=>'website','orders.deleted_at'=>null);
        if(isset($payload['invoice_number']) && $payload['invoice_number'] && !empty($payload['invoice_number']) && !is_null($payload['invoice_number'])){
            $filters['orders.invoice_number'] = $payload['invoice_number'];
        }
        if(isset($payload['id_order']) && $payload['id_order'] && !empty($payload['id_order']) && !is_null($payload['id_order'])){
            $filters['orders.id_order'] = $payload['id_order'];
        }
        if(isset($payload['order_code']) && $payload['order_code'] && !empty($payload['order_code']) && !is_null($payload['order_code'])){
            $filters['orders.order_code'] = $payload['order_code'];
        }
        if(isset($payload['order_source']) && $payload['order_source'] && !empty($payload['order_source']) && !is_null($payload['order_source'])){
            if($payload['order_source']=='skip'){
                unset($filters['orders.order_source']);
            }else{
                $filters['orders.order_source'] = $payload['order_source'];
            }
        }
        if(isset($payload['status']) && $payload['status'] && !empty($payload['status']) && !is_null($payload['status'])){
            $filters['orders.status'] = $payload['status'];
        }
        if(isset($payload['startdate']) && $payload['startdate'] && !empty($payload['startdate']) && !is_null($payload['startdate']) && strtotime($payload['startdate'])){
            $filters['DATE(orders.created_at) >='] = date('Y-m-d', strtotime($payload['startdate']));
        }
        if(isset($payload['enddate']) && $payload['enddate'] && !empty($payload['enddate']) && !is_null($payload['enddate']) && strtotime($payload['enddate'])){
            $filters['DATE(orders.created_at) <='] = date('Y-m-d', strtotime($payload['enddate']));
        }

        $select = [
            'orders.order_code',
            'orders.invoice_number',
            'orders.order_source',
            'orders.created_at',
            'orders.updated_at',

            'orders.status',
            'orders.discount_noted',

            'orders.shipping_courier',
            'orders.no_awb',

            'orders.member_address_city_id',
            'orders.member_address_city_name',
            'orders.member_address_address',
            'orders.member_address_receiver_name',
            'orders.member_address_receiver_phone',

            'orders.payment_method_code',
            'orders.payment_method_name',
            'orders.payment_reference_no',

            'orders.payment_fee_merchant',
            'orders.payment_fee_customer',
            'orders.payment_fee_total',

            'orders.is_paid',
            'orders.paid_at',
            'orders.is_completed',
            'orders.completed_at',

            'orders.total_qty',
            'orders.shopping_price',
            'orders.total_discount',
            'orders.shipping_cost',
            'orders.final_price',
            'orders.payment_amount',
            'orders.jubelio_store_name',
        ];

        $results = $this->db->select($select)->from('orders')->where($filters);
        $countData = $results->count_all_results('', false);

        $paging['sort_by'] = $orderKey;
        $paging['sort_value'] = $orderValue;
        $paging['limit'] = $limit;
        $paging['page'] = $page;
        $forPager = $this->wooh_support->pagerData($paging, $countData, [], ['sort'=>$orderKey]);
        $pagination = $forPager['data'];

        $results = $results->limit($pagination['limit'], $forPager['offset']);
        $orders = $results->order_by($forPager['sort_by'], $forPager['sort_value'])->get()->result_object();

        $handler = new OrderHandler($this->MainModel);
        $statusList = $handler->listStatusOrderStore();

        $selectStore = [
            'cart_stores.crst_id as id',
            'cart_stores.crst_order as code',
            'cart_stores.crst_store_detail as store',
            'cart_stores.crst_address_detail as address',
            'cart_stores.crst_item as item',
            'cart_stores.crst_qty as qty',
            'cart_stores.crst_weight as weight',
            'cart_stores.crst_subtotal as subtotal',
            'cart_stores.crst_discount as discount',
            'cart_stores.crst_total as total',
            'cart_stores.crst_shipping as shipping',
            'cart_stores.crst_shipment as shipment',
            'cart_stores.crst_status as status',
            'cart_stores.crst_awb_no as awb_no',
        ];

        $resultOrder = []; $restes = [];
        foreach($orders as $order){
            $order->order_code_store = null;
            $order->store_code = null;
            $storeName = 'Rabbani Official';
            if($order->jubelio_store_name && !empty($report->jubelio_store_name) && !is_null($report->jubelio_store_name)){
                $storeName = $order->jubelio_store_name;
            }
            $order->store_name = $storeName;
            $order->total_discount = ($order->total_discount && !empty($order->total_discount) && !is_null($order->total_discount)) ? abs($order->total_discount) : 0;
            unset($order->jubelio_store_name);

            $stores = $this->db->select($selectStore)->from('cart_stores')->where(['crst_order'=>$order->order_code,'deleted_at'=>NULL])->get()->result_object();
            if($stores && !is_null($stores)){
                foreach($stores as $store){
                    $orderStore = new stdClass();
                    $orderStore->order_code = $order->order_code;
                    $orderStore->invoice_number = $order->invoice_number;
                    $orderStore->order_source = $order->order_source;
                    $orderStore->created_at = $order->created_at;
                    $orderStore->updated_at = $order->updated_at;

                    $statusOrderStore = $order->status;
                    if(isset($statusList[$store->status])){
                        $isStatus = $statusList[$store->status]['slug'];
                        if($isStatus!='by_order'){
                            $statusOrderStore = $isStatus;
                        }
                    }
                    $orderStore->status = $statusOrderStore;
                    $orderStore->discount_noted = $order->discount_noted;

                    $shippingCourier = $order->shipping_courier;
                    $addressCode = $order->member_address_city_id;
                    $addressName = $order->member_address_city_name;
                    if($store->shipment && !empty($store->shipment) && !is_null($store->shipment)){
                        $shipment = json_decode($store->shipment);
                        $shippingCourier = $shipment->service->service_display;
                        $addressCode = $shipment->destination->city_code;
                        $addressName = $shipment->destination->city_name;
                    }

                    $orderStore->shipping_courier = $shippingCourier;
                    $orderStore->no_awb = $store->awb_no;
                    $orderStore->member_address_city_id = $addressCode;
                    $orderStore->member_address_city_name = $addressName;

                    $addressMember = $order->member_address_address;
                    $addressMemberName = $order->member_address_receiver_name;
                    $addressMemberPhone = $order->member_address_receiver_phone;
                    if($store->address && !empty($store->address) && !is_null($store->address)){
                        $address = json_decode($store->address);
                        $addressMember = $address->address;
                        $addressMemberName = $address->received_name;
                        $addressMemberPhone = $address->phone_number;
                    }

                    $orderStore->member_address_address = $addressMember;
                    $orderStore->member_address_receiver_name = $addressMemberName;
                    $orderStore->member_address_receiver_phone = $addressMemberPhone;

                    $orderStore->payment_method_code = $order->payment_method_code;
                    $orderStore->payment_method_name = $order->payment_method_name;
                    $orderStore->payment_reference_no = $order->payment_reference_no;

                    $orderStore->payment_fee_merchant = $order->payment_fee_merchant;
                    $orderStore->payment_fee_customer = $order->payment_fee_customer;
                    $orderStore->payment_fee_total = $order->payment_fee_total;

                    $orderStore->is_paid = $order->is_paid;
                    $orderStore->paid_at = $order->paid_at;
                    $orderStore->is_completed = $order->is_completed;
                    $orderStore->completed_at = $order->completed_at;

                    $orderStore->total_qty = $store->qty;
                    $orderStore->shopping_price = $store->subtotal;
                    $orderStore->total_discount = ($store->discount && !empty($store->discount) && !is_null($store->discount)) ? abs($store->discount) : 0;
                    $orderStore->shipping_cost = $store->shipping;
                    $orderStore->final_price = $store->total;
                    $orderStore->payment_amount = $order->payment_amount;

                    $isStoreCode = $order->store_code;
                    $isStoreName = $order->store_name;
                    if($store->store && !empty($store->store) && !is_null($store->store)){
                        $storeDetail = json_decode($store->store);
                        $isStoreCode = $storeDetail->code;
                        $isStoreName = $storeDetail->name;
                    }

                    $orderStore->order_code_store = $store->code.'-'.$store->id;
                    $orderStore->store_code = $isStoreCode;
                    $orderStore->store_name = $isStoreName;

                    $products = [];
                    if($store->item && !empty($store->item) && !is_null($store->item)){
                        $storeItem = json_decode($store->item);
                        foreach($storeItem as $item){
                            $detailItem = $this->getProductListOrder($store->code, $item->id_product_detail);
                            $detailItem->qty = $item->qty;
                            $detailItem->price = $item->price;
                            $detailItem->discount = $item->discount;
                            $detailItem->subtotal = $item->subtotal;
                            $detailItem->total = $item->total;
                            $products[] = $detailItem;
                        }
                    }
                    $orderStore->products = $products;
                    $resultOrder[] = $orderStore;
                }
            }else{
                $order->products = $this->getProductListOrder($order->order_code);
                $resultOrder[] = $order;
            }
        }

        $response = array('result'=>$resultOrder);
        foreach($pagination as $k_pg=>$pg){ $response[$k_pg] = $pg; }

        $this->delivery->data = $response;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function group_get() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $handler = new OrderHandler($this->MainModel);
        $payload = $this->input->get();

        $sort = array('id_order','created_at','updated_at','payment_amount');
        $orderKey = 'orders.id_order'; $orderValue = 'DESC';
        if(isset($payload['order_key']) && $payload['order_key'] && !empty($payload['order_key']) && !is_null($payload['order_key'])){
            $isOrderBy = strtolower($payload['order_key']);
            $orderKey = (in_array($isOrderBy, $sort)) ? 'orders.'.$isOrderBy : $orderKey;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderValue = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderValue;
        }

        $limit = 20;
        if(isset($payload['data']) && $payload['data'] && !empty($payload['data']) && is_numeric($payload['data'])){
            $isLimit = intval($payload['data']);
            $limit = ($isLimit<=1) ? 1 : $isLimit;
        }
        $page = 1;
        if(isset($payload['page']) && $payload['page'] && !empty($payload['page']) && is_numeric($payload['page'])){
            $isPage = intval($payload['page']);
            $page = ($isPage<=1) ? 1 : $isPage;
        }

        $filters = array('orders.order_source'=>'website','orders.deleted_at'=>null);
        if(isset($payload['invoice_number']) && $payload['invoice_number'] && !empty($payload['invoice_number']) && !is_null($payload['invoice_number'])){
            $filters['orders.invoice_number'] = $payload['invoice_number'];
        }
        if(isset($payload['id_order']) && $payload['id_order'] && !empty($payload['id_order']) && !is_null($payload['id_order'])){
            $filters['orders.id_order'] = $payload['id_order'];
        }
        if(isset($payload['order_code']) && $payload['order_code'] && !empty($payload['order_code']) && !is_null($payload['order_code'])){
            $filters['orders.order_code'] = $payload['order_code'];
        }
        if(isset($payload['order_source']) && $payload['order_source'] && !empty($payload['order_source']) && !is_null($payload['order_source'])){
            if($payload['order_source']=='skip'){
                unset($filters['orders.order_source']);
            }else{
                $filters['orders.order_source'] = $payload['order_source'];
            }
        }
        if(isset($payload['status']) && $payload['status'] && !empty($payload['status']) && !is_null($payload['status'])){
            $filters['orders.status'] = $payload['status'];
        }

        $select = [
            'orders.*',
            'users.username as user_username',
            'users.first_name as user_first_name',
            'users.last_name as user_last_name',
            'users.email as user_email',
            'users.phone as user_phone',
            'users.picImage as user_image'
        ];
        $results = $this->db->select($select)->from('orders')->where($filters);
        $results  = $results->join('users', 'users.id=orders.user_id', 'left');
        $countData = $results->count_all_results('', false);

        $paging['sort_by'] = $orderKey;
        $paging['sort_value'] = $orderValue;
        $paging['limit'] = $limit;
        $paging['page'] = $page;
        $forPager = $this->wooh_support->pagerData($paging, $countData, [], ['sort'=>$orderKey]);
        $pagination = $forPager['data'];

        $results = $results->limit($pagination['limit'], $forPager['offset']);
        $orders = $results->order_by($forPager['sort_by'], $forPager['sort_value'])->get()->result_object();

        //$handlerQc = new QcHandler($this->MainModel);
        foreach($orders as $order){
            if($order->payment_method_instruction && !empty($order->payment_method_instruction) && !is_null($order->payment_method_instruction) && isJson($order->payment_method_instruction)){
                $order->payment_method_instruction = json_decode($order->payment_method_instruction);
            }
            
            $orderStore = [];
            $cekOrderStore = $handler->getOrderStore($order->order_code, null, $order->status, ['format'=>'all']);
            if ($cekOrderStore['success']) {
                $orderStore = $cekOrderStore['data']['result'];
            }
            $order->order_stores = $orderStore;

            $orderproduct = $this->getProductListOrder($order->order_code);
            $order->order_products = ($orderproduct && !is_null($orderproduct)) ? $orderproduct : [];

            //$order->order_discounts = $handler->getOrderDiscounts($order->id_order);
            //$order->order_qc_histories = $handlerQc->getQc(['qc_order'=>$order->order_code]);
        }

        $response = array('result'=>$orders);
        foreach($pagination as $k_pg=>$pg){ $response[$k_pg] = $pg; }

        $this->delivery->data = $response;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function getProductListOrder($orderCode, $detailId = null) {
        $selectDetail = [
            'order_details.id_order_detail',
            'product_details.id_product as id_product',
            'product.sku as product_sku',
            'order_details.id_product_detail',
            'product_details.sku_code as product_detail_sku_code',
            'product.product_name as product_name',
            'product_details.variable as product_detail_variable',
            'order_details.qty',
            'order_details.price',
            'order_details.discount_amount as discount',
            'order_details.subtotal',
            'order_details.total',
        ];
        $conditionDetail = [
            'order_details.order_code' => $orderCode,
            'order_details.deleted_at'=>NULL,
        ];

        $singleData = false;
        if($detailId && !empty($detailId) && !is_null($detailId)){
            $conditionDetail['order_details.id_product_detail'] = $detailId;
            $singleData = true;
        }

        $lists = $this->db->select($selectDetail)->from('order_details')->where($conditionDetail);
        $lists = $lists->join('product_details', 'product_details.id_product_detail = order_details.id_product_detail OR product_details.sku_code = order_details.sku_code', 'left');
        $lists = $lists->join('product', 'product.id_product = product_details.id_product', 'left');
        $lists = $lists->get()->result_object();

        foreach($lists as $list){
            if($list->product_detail_variable && !empty($list->product_detail_variable) && !is_null($list->product_detail_variable)){
                if($list->product_detail_variable && !empty($list->product_detail_variable) && !is_null($list->product_detail_variable) && isJson($list->product_detail_variable)){
                    $list->product_detail_variable = json_decode($list->product_detail_variable);
                }
            }
        }

        $result = [];
        if($lists && !is_null($lists)){
            $result = ($singleData) ? $lists[0] : $lists;
        }
        return $result;
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $filters['select_type'] = 'reseller';
        $handler = new OrderHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getOrders($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($orderCode) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }


        $handler = new OrderHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getOrder(['slug' => $orderCode]);

        $this->response($result->format(), $result->getStatusCode());
    }



//========================================== END LINE ==========================================//
}
