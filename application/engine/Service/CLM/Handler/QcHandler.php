<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Handler\OrderHandler;

class QcHandler {

	private $delivery;
	private $repository;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->CI =& get_instance();
		$this->repository = $repository;
		$this->delivery = new Delivery;
        $this->orderHandler = new OrderHandler($this->repository);
	}

    public function getQc($payload = []) {
        $join = [
            'admins' => [
                'type' => 'left',
                'value' => 'admins.id = qc_orders.qc_admin'
            ],
        ];
        $select = ['qc_orders.*','admins.username','admins.email','admins.first_name','admins.last_name','admins.phone'];
        $existQc = $this->repository->findOne('qc_orders', $payload, null, $join, $select, 'qc_orders.updated_at','DESC');
        if($existQc && !is_null($existQc)){
            if($existQc->qc_item && !empty($existQc->qc_item) && !is_null($existQc->qc_item)){
                $existQc->qc_item = json_decode($existQc->qc_item);
            }
        }
        return $existQc;
    }

    public function getHistory($payload = [], $loadOrder = false, $format = '', $attribute = []) {
        if($loadOrder){
            return $this->getHistoryFull($payload);
        }
        $join = [
            'admins' => [
                'type' => 'left',
                'value' => 'admins.id = qc_orders.qc_admin'
            ],
            'orders' => [
                'type' => 'left',
                'value' => 'orders.order_code = qc_orders.qc_order'
            ],
            'users' => [
                'type' => 'left',
                'value' => 'users.id=orders.user_id'
            ],
        ];
        $select = [
            'qc_orders.qc_id','qc_orders.qc_admin','qc_orders.qc_order','qc_orders.qc_item','qc_orders.qc_status',
            'qc_orders.created_at as qc_created','qc_orders.updated_at as qc_updated','qc_orders.deleted_at as qc_deleted',
            'admins.username as admin_username','admins.email as admin_email','admins.phone as admin_phone',
            'admins.first_name as admin_first_name','admins.last_name as admin_last_name',

            'orders.id_order','orders.order_code','orders.invoice_number','orders.member_address_receiver_name','orders.order_source','orders.jubelio_store_name',
            'orders.payment_method_name','orders.no_awb','orders.shipping_courier','orders.logistic_name','orders.status',

            'users.username as user_username',
            'users.first_name as user_first_name',
            'users.last_name as user_last_name',
            'users.email as user_email',
            'users.phone as user_phone',
            'users.picImage as user_image'
        ];
        if($format=='paginate'){
            $offset = 0; $limit = 50;
            if (isset($attribute['data']) && !empty($attribute['data'])) {
                $limit = (int)$attribute['data'];
            }
            if (isset($attribute['page']) && !empty($attribute['page'])) {
                $offset = ((int)($attribute['page'])-1) * $limit;
            }
            $qcList = $this->repository->findPaginated('qc_orders', $payload, null, $join, $select, $offset, $limit, 'qc_orders.updated_at','DESC');
        }else{
            $qcList = $this->repository->find('qc_orders', $payload, null, $join, $select, null, null, 'qc_orders.updated_at','DESC');
        }
        return $qcList;
    }

    public function getHistoryFull($payload = []) {
        $join = [
            'admins' => [
                'type' => 'left',
                'value' => 'admins.id = qc_orders.qc_admin'
            ],
        ];
        $select = ['qc_orders.*','admins.username','admins.email','admins.first_name','admins.last_name','admins.phone'];
        $qcList = $this->repository->find('qc_orders', $payload, null, $join, $select, null, null, 'qc_orders.updated_at','DESC');
        if($qcList && !is_null($qcList)){
            foreach($qcList as $qc){
                if($qc->qc_item && !empty($qc->qc_item) && !is_null($qc->qc_item)){
                    $qc->qc_item = json_decode($qc->qc_item);
                }
                if($loadOrder){
                    $order = $this->getOrder(['code'=>$qc->qc_order]);
                    if($order && !is_null($order)){
                        $detailOrder = $this->getDetailOrder($order);
                        $order->order_products = ($detailOrder && isset($detailOrder['products'])) ? $detailOrder['products'] : [];
                        $order->order_stores = ($detailOrder && isset($detailOrder['stores'])) ? $detailOrder['stores'] : [];
                        //$order->order_discounts = ($detailOrder && isset($detailOrder['discounts'])) ? $detailOrder['discounts'] : [];
                    }
                    $qc->detail_order = $order;
                }
            }
        }
        return $qcList;
    }

    public function getOrder($payload = [], $fromInStore = false) {
        $payload['code'] = str_replace(' ', '', $payload['code']);
        $filter = [
            'orders.deleted_at' => NULL,
        ];
        $orFilter = [
            'orders.order_code' => [
                'condition' => 'like',
                'value' => $payload['code']
            ],
            'orders.invoice_number' => [
                'condition' => 'like',
                'value' => $payload['code']
            ],
            'orders.no_awb' => [
                'condition' => 'like',
                'value' => $payload['code']
            ],
            //'orders.order_code' => $payload['code'],
            //'orders.invoice_number' => $payload['code'],
            //'orders.no_awb' => $payload['code'],
        ];
        $select = [
            'orders.*',
            'users.username as user_username',
            'users.first_name as user_first_name',
            'users.last_name as user_last_name',
            'users.email as user_email',
            'users.phone as user_phone',
            'users.picImage as user_image'
        ];
        $join = [
            'users' => [
                'type' => 'left',
                'value' => 'users.id=orders.user_id'
            ],
        ];
        $order = $this->repository->findOne('orders', $filter, $orFilter, $join, $select);
        if(!$fromInStore && (!$order || is_null($order))){
            return $this->getOrderInStore($payload);
        }
        return $order;
    }

    public function getOrderInStore($payload = []) {
        $filter = [
            'cart_stores.deleted_at' => NULL,
        ];
        $orFilter = [
            'cart_stores.crst_order' => [
                'condition' => 'like',
                'value' => $payload['code']
            ],
            'cart_stores.crst_awb_no' => [
                'condition' => 'like',
                'value' => $payload['code']
            ],
            //'cart_stores.crst_order' => $payload['code'],
            //'cart_stores.crst_awb_no' => $payload['code'],
        ];
        $select = [
            'cart_stores.*',
            'orders.id_order',
            'orders.order_code',
        ];
        $join = [
            'orders' => [
                'type' => 'left',
                'value' => 'orders.order_code=cart_stores.crst_order'
            ],
        ];
        $order = $this->repository->findOne('cart_stores', $filter, $orFilter, $join, $select);
        if($order && !is_null($order) && $order->order_code && !empty($order->order_code) && !is_null($order->order_code)){
            return $this->getOrder(['code'=>$order->order_code], true);
        }else{
            return false;
        }
    }

    public function getDetailOrder($order = null) {
        if(!$order || empty($order) || is_null($order)){
            return false;
        }
        if(!isset($order->order_code) || !$order->order_code || empty($order->order_code) || is_null($order->order_code)){
            return false;
        }

        $result = array();
        $selectDetail = [
            'order_details.id_order_detail',
            'order_details.id_product_detail',
            'product_details.id_product as id_product',
            'order_details.qty',
            'order_details.price',
            'order_details.discount_amount',
            'order_details.discount_type',
            'order_details.discount_source',
            'order_details.discount_value',
            'order_details.subtotal',
            'order_details.total',
            'product_details.sku_code as product_detail_sku_code',
            'product_details.sku_barcode as product_detail_sku_barcode',
            'product_details.host_path as product_detail_host_path',
            'product_details.image_path as product_detail_image_path',
            'product_details.variable as product_detail_variable',
            'product.product_name as product_name',
            'product.sku as product_sku',
            'order_details.sku_code',
        ];
        $conditionDetail = [
            'order_details.order_code' => $order->order_code,
            'order_details.deleted_at'=>NULL,
        ];
        $joinDetail = [
            'product_details' => [
                'type' => 'left',
                'value' => 'product_details.id_product_detail = order_details.id_product_detail OR product_details.sku_code = order_details.sku_code'
            ],
            'product' => [
                'type' => 'left',
                'value' => 'product.id_product = product_details.id_product'
            ],
        ];

        $lists = $this->repository->find('order_details', $conditionDetail, null, $joinDetail, $selectDetail);

        $listProduct = [];
        foreach($lists as $list){
            if($list->product_detail_variable && !empty($list->product_detail_variable) && !is_null($list->product_detail_variable)){
                if($list->product_detail_variable && !empty($list->product_detail_variable) && !is_null($list->product_detail_variable) && isJson($list->product_detail_variable)){
                    $list->product_detail_variable = json_decode($list->product_detail_variable);
                }
            }
            if(!isset($listProduct[$list->id_order_detail])){
                $listProduct[$list->id_order_detail] = $list;
            }
        }

        $result['products'] = ($listProduct && !is_null($listProduct)) ? array_values($listProduct) : [];

        $orderStore = [];
        $cekOrderStore = $this->orderHandler->getOrderStore($order->order_code, null, $order->status, ['format'=>'all']);
        if ($cekOrderStore['success']) {
            $orderStore = $cekOrderStore['data']['result'];
        }
        $result['stores'] = $orderStore;
        //$result['discounts'] = $this->orderHandler->getOrderDiscounts($order->id_order);

        return $result;
    }

    public function getQcFromDetail($payload = []) {
        $selectDetail = [
            'qc_orders.qc_id','qc_orders.qc_admin','qc_orders.qc_order','qc_orders.qc_item','qc_orders.qc_status',
            'qc_orders.created_at as qc_created','qc_orders.updated_at as qc_updated','qc_orders.deleted_at as qc_deleted',

            'orders.id_order','orders.order_code','orders.invoice_number','orders.member_address_receiver_name','orders.order_source','orders.jubelio_store_name',
            'orders.payment_method_name','orders.no_awb','orders.shipping_courier','orders.logistic_name','orders.status',

            'users.username as user_username',
            'users.first_name as user_first_name',
            'users.last_name as user_last_name',
            'users.email as user_email',
            'users.phone as user_phone',
            'users.picImage as user_image',

            'order_details.id_order_detail',
            'order_details.id_product_detail',
            'product_details.id_product as id_product',
            'order_details.qty',
            'order_details.price',
            'order_details.discount_amount',
            'order_details.discount_type',
            'order_details.discount_source',
            'order_details.discount_value',
            'order_details.subtotal',
            'order_details.total',
            'product_details.sku_code as product_detail_sku_code',
            'product_details.sku_barcode as product_detail_sku_barcode',
            'product_details.host_path as product_detail_host_path',
            'product_details.image_path as product_detail_image_path',
            'product_details.variable as product_detail_variable',
            'product.product_name as product_name',
            'product.sku as product_sku',
            'order_details.sku_code',
        ];
        $conditionDetail = [
            //'order_details.order_code' => $order->order_code,
            'order_details.deleted_at'=>NULL,
            'qc_orders.deleted_at'=>NULL,
        ];
        $joinDetail = [
            'qc_orders' => 'order_details.order_code=qc_orders.qc_order',
            'orders' => [
                'type' => 'left',
                'value' => 'orders.order_code=order_details.order_code'
            ],
            'product_details' => [
                'type' => 'left',
                'value' => 'product_details.id_product_detail = order_details.id_product_detail OR product_details.sku_code = order_details.sku_code'
            ],
            'product' => [
                'type' => 'left',
                'value' => 'product.id_product = product_details.id_product'
            ],
            'users' => [
                'type' => 'left',
                'value' => 'users.id=orders.user_id'
            ],
        ];

        $lists = $this->repository->find('order_details', $conditionDetail, null, $joinDetail, $selectDetail);
        return $lists;
    }


}