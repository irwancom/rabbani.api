<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Calculator;
use Library\TripayGateway;
use Library\DigitalOceanService;
use Library\WablasService;
use Library\JNEService;

class OrderHandler {

	const ORDER_STATUS_WAITING_PAYMENT = 'waiting_payment';
	const ORDER_STATUS_PAYMENT_EXPIRED = 'payment_expired';
	const ORDER_STATUS_OPEN = 'open';
	const ORDER_STATUS_IN_PROCESS = 'in_process';
	const ORDER_STATUS_IN_SHIPMENT = 'in_shipment';
	const ORDER_STATUS_DELIVERED = 'delivered';
	const ORDER_STATUS_COMPLETED = 'completed';
	const ORDER_STATUS_CANCELED = 'canceled';
	const ORDER_STATUS_DELETED = 'deleted';
	const ORDER_STATUS_UNKNOWN = 'unknown';

	const WABLAS_MAIN_NUMBER = '62895383334783';
	const WABLAS_MAIN_DOMAIN = 'https://solo.wablas.com';
	const WABLAS_MAIN_TOKEN = 'CZrRIT5qo1GNYdiFXySxc0oW4oINZ5WZmLi40HlHHAushg4S1GlSfnSTHQfJEQgs';

	const RESELLER_FIRST_ORDER_MINIMUM_AMOUNT = 1000000;

	private $delivery;
	private $repository;
	private $cartHandler;
	private $productDetailHandler;
	private $useCartFromAdmin = false;
	private $adminCart;

	private $user;
	private $admin;
	private $productDetail;
	private $sendNotif = false;

	private $tripayCallbackUrl;
	private $resellerOrderInfo;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->cartHandler = new CartHandler($this->repository);
		$this->productDetailHandler = new ProductDetailHandler($this->repository);
		$this->tripayCallbackUrl = 'https://api.1itmedia.co.id/callbacks/tripay/clm';
		$this->resellerOrderInfo = 'Pembayaran Pendaftaran Registrasi Reseller';
	}

	public function listStatusOrder(){
		$isStatus = array();
		$isStatus[] = ['name'=>'Unpaid', 'slug'=>'waiting_payment', 'style'=>'secondary'];
		$isStatus[] = ['name'=>'Pending', 'slug'=>'open', 'style'=>'warning'];
		$isStatus[] = ['name'=>'Process', 'slug'=>'in_process', 'style'=>'info'];
		$isStatus[] = ['name'=>'Shipment', 'slug'=>'in_shipment', 'style'=>'primary'];
		$isStatus[] = ['name'=>'Delivered', 'slug'=>'delivered', 'style'=>'teal'];
		$isStatus[] = ['name'=>'Finish', 'slug'=>'completed', 'style'=>'success'];
		$isStatus[] = ['name'=>'Cancel', 'slug'=>'canceled', 'style'=>'danger'];
		$isStatus[] = ['name'=>'Delete', 'slug'=>'deleted', 'style'=>'danger'];
		return $isStatus;
	}

	public function listStatusOrderStore(){
		$isStatus = array();
		$isStatus[] = ['name'=>'On Cart', 'slug'=>'on_cart', 'style'=>'light'];
		$isStatus[] = ['name'=>'By Order', 'slug'=>'by_order', 'style'=>'info'];
		$isStatus[] = ['name'=>'Process', 'slug'=>'in_process', 'style'=>'info'];
		$isStatus[] = ['name'=>'Shipment', 'slug'=>'in_shipment', 'style'=>'primary'];
		$isStatus[] = ['name'=>'Delivered', 'slug'=>'delivered', 'style'=>'teal'];
		$isStatus[] = ['name'=>'Finish', 'slug'=>'completed', 'style'=>'success'];
		$isStatus[] = ['name'=>'Cancel', 'slug'=>'canceled', 'style'=>'danger'];
		return $isStatus;
	}

	public function setUser ($user) {
		$this->user = $user;
	}

	public function getUser () {
		return $this->user;
	}

	public function setAdmin ($admin) {
		$this->admin = $admin;
	}

	public function getAdmin () {
		return $this->admin;
	}

	public function setSendNotif ($sendNotif) {
		$this->sendNotif = $sendNotif;
	}

	public function isSendNotif () {
		return $this->sendNotif;
	}

	public function setUseCartFromAdmin($useCartFromAdmin) {
		$this->useCartFromAdmin = $useCartFromAdmin;
	}

	public function isUseCartFromAdmin () {
		return $this->useCartFromAdmin;
	}

	public function setAdminCart ($adminCart) {
		$this->adminCart = $adminCart;
	}

	public function getAdminCart () {
		return $this->adminCart;
	}

	public function getOrders ($filters = null) {
		$args = [];
		$argsOrWhere = [];
		if (!empty($this->getAdmin())) {
			$args = [
				'orders.id_auth' => $this->admin['id_auth']
			];
		} else if (!empty($this->getUser())) {
			$args = [
				'orders.user_id' => $this->user['id']
			];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['id'] = [
				'condition' => 'like',
				'value' => $filters['id']
			];
		}

		if (isset($filters['order_code']) && !empty($filters['order_code'])) {
			$args['order_code'] = [
				'condition' => 'like',
				'value' => $filters['order_code']
			];
		}

		if (isset($filters['status']) && !empty($filters['status'])) {
			$args['status'] = $filters['status'];
		}
		
		if (isset($filters['user_id']) && !empty($filters['user_id'])) {
			$args['orders.user_id'] = $filters['user_id'];
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			$argsOrWhere['order_code'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['invoice_number'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		if (isset($filters['picklist_id'])) {
			if ($filters['picklist_id'] == '~~') {
				$args['picklist_id <>'] = null;
			} else if ($filters['picklist_id'] == '~') {
				$args['picklist_id'] = null;
			} else {
				$args['picklist_id'] = $filters['picklist_id'];
			}
		}
		if (isset($filters['source']) && !empty($filters['source'])) {
			$args['order_source'] = $filters['source'];
		}

		if(isset($filters['date_from']) && $filters['date_from'] && !empty($filters['date_from']) && !is_null($filters['date_from']) && strtotime($filters['date_from'])){
            $args['DATE(orders.created_at) >='] = date('Y-m-d H:i:s', strtotime($filters['date_from']));
        }
        if(isset($filters['date_to']) && $filters['date_to'] && !empty($filters['date_to']) && !is_null($filters['date_to']) && strtotime($filters['date_to'])){
            $args['DATE(orders.created_at) <='] = date('Y-m-d H:i:s', strtotime($filters['date_to']));
        }


		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'orders.id_order',
			'orders.id_auth',
			'orders.user_id',
			'users.username',
			'users.phone',
			'users.email',
			'users.first_name',
			'users.last_name',
			'users.picImage',
			'users.is_reseller',
			'orders.id_cart',
			'orders.order_code',
			'orders.invoice_number',
			'orders.status',
			'orders.order_source',
			'orders.order_info',
			'orders.is_paid',
			'orders.paid_at',
			'orders.is_delivered',
			'orders.is_completed',
			'orders.completed_at',
			'orders.total_weight',
			'orders.total_price',
			'orders.total_qty',
			'orders.shopping_price',
			'orders.total_discount',
			'orders.payment_amount',
			'orders.final_price',
			'orders.donation',
			'orders.payment_method_instruction',
			'orders.payment_method_instruction_qris_value',
			'orders.payment_method_code',
			'orders.payment_method_name',
			'orders.payment_reference_no',
			'orders.payment_fee_total',
			'orders.shipping_courier',
			'orders.shipping_status',
			'orders.logistic_name',
			'orders.logistic_rate_name',
			'orders.logistic_min_day',
			'orders.logistic_max_day',
			'orders.no_awb',
			'orders.referral_code',
			'orders.rate',
			'orders.jubelio_store_name',
			'stores.name as store_name',
			'orders.created_at',
			'orders.updated_at',
		];
		$join = [
			'users' => [
				'type' => 'left',
				'value' => 'orders.user_id = users.id'
			],
			'store_agents' => [
				'type' => 'left',
				'value' => 'users.referred_by_store_agent_id = store_agents.id'
			],
			'stores' => [
				'type' => 'left',
				'value' => 'stores.code = store_agents.store_code'
			]
		];
		$orderKey = 'orders.id_order';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$orders = $this->repository->findPaginated('orders', $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($orders['result'] as $order) {
			$storeName = $order->jubelio_store_name;
			if (empty($storeName)) {
				$storeName = $order->store_name;
			}
			$order->store_name = $storeName;
			if (isJson($order->payment_method_instruction)) {
				$order->payment_method_instruction = json_decode($order->payment_method_instruction);
			}
			$orderStore = null;
			$cekOrderStore = $this->getOrderStore($order->order_code, null, $order->status, ['store_only'=>true]);
			if ($cekOrderStore && !is_null($cekOrderStore)) {
				$orderStore = $cekOrderStore;
			}
			$order->order_stores = $orderStore;
		}
		$this->delivery->data = $orders;
		return $this->delivery;
	}

	public function getOrder ($filters) {
		if (!empty($this->getAdmin())) {
			$filters['orders.id_auth'] = $this->admin['id_auth'];
		} else if (!empty($this->getUser())) {
			$filters['orders.user_id'] = $this->user['id'];
		}

		$filterOrWhere = [];
		if (isset($filters['slug']) && !empty($filters['slug'])) {
			$filterOrWhere = [
				'orders.id_order' => $filters['slug'],
				'orders.order_code' => $filters['slug']
			];
			unset($filters['slug']);
		}

		$selectOrder = [
			'orders.*',
			'users.phone as user_phone',
			'users.username as user_username',
			'users.first_name as user_first_name',
			'users.can_order as user_can_order',
			'users.is_reseller as user_is_reseller',
		];
		$join = [
			'users' => [
				'type' => 'left',
				'value' => 'users.id = orders.user_id'
			]
		];
		$order = $this->repository->findOne('orders', $filters, $filterOrWhere, $join, $selectOrder);
		if (!empty($order)) {
			$joinOrderDetail = [
				'product_details' => [
					'type' => 'left',
					'value' => 'product_details.id_product_detail = order_details.id_product_detail OR product_details.sku_code = order_details.sku_code'
				],
				'product' => [
					'type'=>'left',
					'value' => 'product.id_product = product_details.id_product'
				]
			];
			$select = [
				'order_details.id_order_detail',
				'order_details.order_code',
				'order_details.id_product_detail',
				'product_details.id_product as id_product',
				'product_details.sku_code as product_detail_sku_code',
				'product_details.sku_barcode as product_detail_sku_barcode',
				'product_details.host_path as product_detail_host_path',
				'product_details.image_path as product_detail_image_path',
				'product_details.price as product_detail_price',
				'product_details.stock as product_detail_stock',
				'product_details.variable as product_detail_variable',
				'product.product_name as product_name',
				'product.sku as product_sku',
				'product.desc as product_desc',
				'product.weight as product_weight',
				'product.length as product_length',
				'product.height as product_height',
				'product.published as product_published',
				'product.price as product_price',
				'product.total_purchased as product_total_purchased',
				'order_details.discount_type',
				'order_details.discount_source',
				'order_details.discount_value',
				'order_details.price',
				'order_details.qty',
				'order_details.discount_amount',
				'order_details.subtotal',
				'order_details.total',
				'order_details.salesorder_detail_id',
				'order_details.sku_code',
				'order_details.picklist_status',
				'order_details.picklist_source_inventory_box_id',
				'order_details.picklist_picked_at',
				'order_details.picklist_is_picked',
				'order_details.deleted_at',
			];
			$orderDetails = $this->repository->find('order_details', ['order_code' => $order->order_code], null, $joinOrderDetail, $select);
			foreach ($orderDetails as $orderDetail) {
				$productDetailHandler = new ProductDetailHandler($this->repository);
				$productDetailHandler->setUseCalculator(false);
				if (empty($orderDetail->sku_code) && !empty($orderDetail->product_detail_sku_code)) {
					$orderDetail->sku_code = $orderDetail->product_detail_sku_code;
				}
				$orderDetail->product_detail = $productDetailHandler->getProductDetail(['id_product_detail' => $orderDetail->id_product_detail])->data;
				$orderDetail->product_detail_variable = isJson($orderDetail->product_detail_variable) ? json_decode($orderDetail->product_detail_variable) : $orderDetail->product_detail_variable;
				$orderDetail->product_images = $this->repository->find('product_images', ['id_product' => $orderDetail->id_product]);
 			}
			$order->order_details = $orderDetails;
			$order->payment_method_instruction = isJson($order->payment_method_instruction) ? json_decode($order->payment_method_instruction) : $order->payment_method_instruction;
			$order->order_discounts = $this->getOrderDiscounts($order->id_order);
			if ($order->status == self::ORDER_STATUS_WAITING_PAYMENT && !empty($order->payment_reference_no)) {
				$tripay = new TripayGateway;
		        // $tripay->setEnv('development');
		        $tripay->setEnv('production');
		        $tripay->setMerchantCode('T1441');
		        $tripay->setApiKey('fQGB4tyeCghSF844Um01J6mEDWnH1KqlvB0LHa8N');
		        $tripay->setPrivateKey('TLMQO-Y0eMk-Si4DN-V28zC-JU1UK');
				$tripayTransaction = $tripay->detailTransaksiClosed($order->payment_reference_no);
				$order->payment_method = $tripayTransaction->data;
			}

			$orderStore = null;
			$cekOrderStore = $this->getOrderStore($order->order_code, null, $order->status);
			if ($cekOrderStore['success']) {
				$orderStore = $cekOrderStore['data'];
			}
			$order->order_stores = $orderStore;
		}

		$this->delivery->data = $order;
		return $this->delivery;
	}

	public function updateOrder ($payload, $filters = null) {
		if (!empty($this->getAdmin())) {
			$filters['orders.id_auth'] = $this->admin['id_auth'];
		} else if (!empty($this->getUser())) {
			$filters['orders.user_id'] = $this->user['id'];
		}

		$existsOrder = $this->repository->findOne('orders', $filters);
		if (empty($existsOrder)) {
			$this->delivery->addError(409, 'No order found.');
			return $this->delivery;
		}
		if(isset($payload['check_store']) && $payload['check_store']==1){
			//$existStore = $this->repository->findOne('cart_stores', ['crst_order'=>$existsOrder->order_code]);
			//if($existStore && !empty($existStore) && !is_null($existStore)){
				//$this->delivery->addError(400, 'Transaksi merupakan multi store. Silahkan update melalui data store yang berkaitan.'); return $this->delivery;
			//}
			unset($payload['check_store']);
		}

		$trackType = null;
		if(isset($payload['track_type'])){
			if($payload['track_type']=='auto' || $payload['track_type']=='manual'){
				$trackType = $payload['track_type'];
			}
			unset($payload['track_type']);
		}
		$trackNo = null;
		if(isset($payload['track_nomor'])){
			if($payload['track_nomor'] && !empty($payload['track_nomor']) && !is_null($payload['track_nomor'])){
				$trackNo = $payload['track_nomor'];
			}
			unset($payload['track_nomor']);
		}

		$existTrackType = ($existsOrder->awb_type && !empty($existsOrder->awb_type) && !is_null($existsOrder->awb_type)) ? $existsOrder->awb_type : null;
        $existTrackNomor = ($existsOrder->no_awb && !empty($existsOrder->no_awb) && !is_null($existsOrder->no_awb)) ? $existsOrder->no_awb : null;

        $upTrackType = false; $upTrackNomor = false;
        if($trackType && !is_null($trackType)){
        	$centralStore = $this->repository->findOne('stores', ['is_central'=>1]);
        	$upTrackType = $trackType;
            $forUpTrackNomor = $existTrackNomor;
            if($trackType=='manual'){
                if($trackNo!=$existTrackNomor){
                    if(!$trackNo || is_null($trackNo)){
                        $this->delivery->addError(400, 'Track nomor is required for update.'); return $this->delivery;
                    }
                    $forUpTrackNomor = $trackNo;
                }
            }else{
            	if($existTrackType=='auto' && ($existTrackNomor && !is_null($existTrackNomor) && !empty($existTrackNomor))){
                    $this->delivery->addError(400, 'Generate awb is already available for the transaction.'); return $this->delivery;
                }else{
                	if(!$existsOrder->shipping_courier || empty($existsOrder->shipping_courier) || is_null($existsOrder->shipping_courier)){
                        $this->delivery->addError(400, 'Shipping data not found in the transaction.'); $this->response($this->delivery->format());
                    }
                    if($existsOrder->shipping_courier!='jne'){
                    	$this->delivery->addError(400, 'Generate awb for JNE service only.'); $this->response($this->delivery->format());
                    }

                    $orderNote = ($existsOrder->order_info && !empty($existsOrder->order_info) && !is_null($existsOrder->order_info)) ? $existsOrder->order_info : '';
                    $orderNote = $existsOrder->order_code.' - '.$orderNote;
                    $storeName = ($centralStore) ? $centralStore->name : 'Rabbani';
                    if(isset($existsOrder->jubelio_store_name) && $existsOrder->jubelio_store_name && !empty($existsOrder->jubelio_store_name) && !is_null($existsOrder->jubelio_store_name)){
                    	$storeName = $existsOrder->jubelio_store_name;
                    }

                    $jneService = new JNEService();
                    $jneService->setEnv('production');
                    $tracking = $jneService->generateAirwayBill(
                        'BDO000',//($shipment && !is_null($shipment)) ? $shipment->origin->city_code : 'BDO000', //branch
                        '10950700',//($detailAddress->phone_number) ? $detailAddress->phone_number : $order->user_phone, //cust
                        $existsOrder->order_id, //orderId
                        $storeName, //shipperName
                        ($centralStore) ? $centralStore->address : 'Bandung', //shipperAddr1
                        '', //shipperAddr2
                        '', //shipperAddr3
                        '', //shipperCity
                        '', //shipperRegion
                        '12345', //shipperZip
                        '', //shipperPhone
                        $existsOrder->member_address_receiver_name, //receiverName
                        $existsOrder->member_address_address, //receiverAddr1
                        $existsOrder->member_address_area_name, //receiverAddr2
                        '', //receiverAddr3
                        $existsOrder->member_address_city_name, //receiverCity
                        $existsOrder->member_address_province_name, //receiverRegion
                        '', //receiverZip
                        $existsOrder->member_address_receiver_phone, //receiverPhone
                        (isset($existsOrder->total_qty) && !empty($existsOrder->total_qty) && !is_null($existsOrder->total_qty)) ? intval($existsOrder->total_qty) : 1, //qty
                        (isset($existsOrder->total_weight) && !empty($existsOrder->total_weight) && !is_null($existsOrder->total_weight)) ? intval($existsOrder->total_weight) : 1, //weight
                        $orderNote, //goodsDesc
                        $existsOrder->payment_amount, //goodsValue
                        1, //goodsType
                        '', //inst
                        'N', //insFlag
                        '', //origin
                        '', //desti
                        $existsOrder->logistic_rate_name, //service
                        'N', //codFlag
                        '', //codAmount
                    );

                    if(!$tracking || empty($tracking) || is_null($tracking)){
                        $this->delivery->addError(400, 'Failed generate awb system.'); return $this->delivery;
                    }

                    if(isset($tracking['detail'])){
                        if(isset($tracking['detail'][0]) && isset($tracking['detail'][0]['status'])){
                            if(!$tracking['detail'][0]['status'] || $tracking['detail'][0]['status']=='error' || $tracking['detail'][0]['status']=='Error' || $tracking['detail'][0]['status']=='failed'){
                                $reason = (isset($tracking['detail'][0]['reason'])) ? $tracking['detail'][0]['reason'] : '';
                                $this->delivery->addError(400, 'Failed generate awb system, '.$reason); $this->response($this->delivery->format());
                            }else if($tracking['detail'][0]['status'] || $tracking['detail'][0]['status']=='sukses' || $tracking['detail'][0]['status']=='Sukses' || $tracking['detail'][0]['status']=='success'){
                                $cnote_no = (isset($tracking['detail'][0]['cnote_no'])) ? $tracking['detail'][0]['cnote_no'] : null;
                                if((!$cnote_no || empty($cnote_no) || is_null($cnote_no)) && isset($tracking['detail'][0]['airwaybill'])){
                                    $cnote_no = $tracking['detail'][0]['airwaybill'];
                                }
                                if(!$cnote_no || empty($cnote_no) || is_null($cnote_no)){
                                    $this->delivery->addError(400, 'Failed generate awb system, airwaybill number is not available.'); return $this->delivery;
                                }
                                $forUpTrackNomor = $cnote_no;
                            }
                        }
                    }
                }
            }

            if($forUpTrackNomor && !is_null($forUpTrackNomor) && $forUpTrackNomor!=$existTrackNomor){
                $upTrackNomor = $forUpTrackNomor;
            }
        }

        if($upTrackType && $upTrackNomor){
        	$payload['no_awb'] = $upTrackNomor;
        	$payload['awb_type'] = $upTrackType;
        }

		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('orders', $payload, $filters);
		$result = $this->getOrder($filters);

		$order = $result->data;
		if ($this->isSendNotif()) {
			if ($existsOrder->status != $order->status) {
				$order->extras = $this->notifyUser($order);
			}
		}

		$this->delivery->data = $order;
		return $this->delivery;
	}

	public function check ($userAddressId, $shipmentCode, $paymentMethodCode, $referralCode, $voucherCode = null, $donation = null) {
		if ($this->isUseCartFromAdmin()) {
			$cartResult = $this->cartHandler->setCartAdmin($this->user, $this->getAdminCart());
			$cart = $cartResult->data;
		} else {
			$this->cartHandler->setUser($this->user);
			$cart = $this->cartHandler->getCart(['user_id' => $this->user['id']]);
			$cart = $cart->data;
			if (empty($cart)) {
				$this->delivery->addError(400, 'Cart not found');
			}
			//update//
			if(isset($cart->total_product_checkout)){
				if(!$cart->total_product_checkout || empty($cart->total_product_checkout) || is_null($cart->total_product_checkout) || $cart->total_product_checkout==0){
					$this->delivery->addError(400, 'Cart item for checkout not found');
				}
			}	
		}

		if ($this->user['can_order'] == 0) {
			$this->delivery->addError(400, 'User cannot order. Please contact team');
		}

		$referralCodeUser = null;
		if (!empty($referralCode)) {
			$referralCodeUser = $this->repository->findOne('users', ['referral_code' => $referralCode], null, null , ['id', 'referral_code']);
			if (empty($referralCodeUser)) {
				$this->delivery->addError(400, 'Referral user not found');
			}

			if ($this->user['referral_code'] == $referralCode) {
				$this->delivery->addError(400, 'You can not use your own referral code');
			}

			if (!empty($this->user['clm_referrer_by_user_id'])) {
				$this->delivery->addError(400, 'You can not use another referral code');
			}

			if (!empty($this->user['clm_level'])) {
				$this->delivery->addError(400, 'You can not use another referral code');
			}
		}

		$voucher = null;
		if (!empty($voucherCode)) {
			$voucherHandler = new VoucherHandler($this->repository);
			$voucherResult = $voucherHandler->getVoucher(['code' => strtoupper($voucherCode), 'current_time' => date('Y-m-d H:i:s')]);
			$voucher = $voucherResult->data;
			if (empty($voucher)) {
				$this->delivery->addError(400, 'Voucher '.$voucherCode.' not found');
			}
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$userAddress = $this->repository->findOne('user_address', ['id' => $userAddressId, 'user_id' => $this->user['id']]);
		$cartCalculator = $this->cartHandler->getCalculatorResult();

		$cartCalculator->setUserAddress($userAddress);
		$cartCalculator->setShipmentCode($shipmentCode);
		$cartCalculator->setVoucher($voucher, $cart);

		/* $shipmentDetails = $cartCalculator->getShipmentDetails();
		if ($shipmentDetails->hasErrors()) {
			return $shipmentDetails;
		} */
		$cartCalculator->setPaymentMethodCode($paymentMethodCode);

		$result = new \stdClass;
		$result->detail = $cartCalculator->checkCart();

		$result->payment_method_options = $cartCalculator->getPaymentMethodOptions();
		$result->payment_method = $cartCalculator->getPaymentMethod();
		$result->shipment = $cartCalculator->getShipment();
		$result->shipment_options = $cartCalculator->getShipmentOptions();
		$result->destination = $cartCalculator->getUserAddress();

		$foundStoreCart = 0;
		$cart->checkout_by_store = $this->cartHandler->getCartStoreCheckout($this->user['id']);
		if($cart->checkout_by_store && !empty($cart->checkout_by_store) && !is_null($cart->checkout_by_store)){
			$isCartStore = $cart->checkout_by_store;
			$setCostShipping = 0;
			foreach($isCartStore as $k_crtStore=>$crtStore){
				$isCostShipping = intval($crtStore['detail']['shipping_cost']);
				$setCostShipping += $isCostShipping;
				$foundStoreCart = $foundStoreCart+1;
			}
			unset($cart->cart_by_store, $cart->checkout_by_store);

			if($foundStoreCart>0){
				$newFinalPrice = intval($result->detail->final_price) - intval($result->detail->shipping_cost);
				$result->detail->final_price = intval($newFinalPrice) + intval($setCostShipping);
				$result->detail->payment_amount = intval($result->detail->final_price) + intval($result->detail->payment_fee_customer);
				$result->detail->shipping_cost = $setCostShipping;
			}
			$result->shopping_cart_store = $isCartStore;
		}

		//donasi//
		$isDonasi = ($donation==1) ? 1000 : 0;
		$result->detail->donation = $isDonasi;
		$result->detail->payment_amount = $result->detail->payment_amount+$isDonasi;

		$result->shopping_cart = $cart;
		$result->referral = $referralCodeUser;
		$result->voucher = $cartCalculator->getVoucher();
		$result->donation = $isDonasi;
		$result->count_cart_store = $foundStoreCart;

		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Member address refer to user address
	 **/
	public function purchase ($userAddressId, $shipmentCode, $paymentMethodCode, $referralCode, $voucherCode = null, $donation = null) {
		if ($this->isUseCartFromAdmin()) {
			$cartResult = $this->cartHandler->setCartAdmin($this->user, $this->getAdminCart());
			$cart = $cartResult->data;
		} else {
			$this->cartHandler->setUser($this->user);
			$cart = $this->cartHandler->getCart(['user_id' => $this->user['id']]);
			$cart = $cart->data;
			if (empty($cart)) {
				$this->delivery->addError(400, 'Cart not found');
			}
			//update//
			if(isset($cart->total_product_checkout)){
				if(!$cart->total_product_checkout || empty($cart->total_product_checkout) || is_null($cart->total_product_checkout) || $cart->total_product_checkout==0){
					$this->delivery->addError(400, 'Cart item for checkout not found');
				}
			}	
		}
		$checkResult = $this->check($userAddressId, $shipmentCode, $paymentMethodCode, $referralCode, $voucherCode, $donation);
		if ($checkResult->hasErrors()) {
			return $checkResult;
		}
		$check = $checkResult->data;

		$cekShippingMethod = (isset($check->count_cart_store)) ? ( ($check->count_cart_store==0) ? true : false ) : true;
		if($cekShippingMethod){
			if (empty($check->shipment)) {
				$this->delivery->addError(400, 'Shipment is required'); return $this->delivery;
			}
		}else{
			if(!isset($check->shopping_cart_store) || empty($check->shopping_cart_store) || is_null($check->shopping_cart_store)){
				$this->delivery->addError(400, 'Cart item by store for checkout not found');
			}
			if(count($check->shopping_cart_store)!=$check->count_cart_store){
				$this->delivery->addError(400, 'Shipment is required for store order'); return $this->delivery;
			}
		}

		if (empty($check->payment_method)) {
			$this->delivery->addError(400, 'Payment method is required');
			return $this->delivery;
		}

		$isShipment = ($check->shipment && !empty($check->shipment) && !is_null($check->shipment)) ? $check->shipment : null;
		$shoppingCart = $check->shopping_cart;
		$shipment = $isShipment;
		$detail = $check->detail;
		$destination = $check->destination;
		$paymentMethod = $check->payment_method;
		$paymentMethodOptions = $check->payment_method_options;
		$referral = $check->referral;
		$voucher = $check->voucher;
		$donation = $check->donation;
		$discountBooks = $detail->discount_books;

		$currentDate = date('Y-m-d H:i:s');
		$orderSource = 'website';
		if ($this->user['is_reseller'] == 1) {
			// cari pembelian pertama dengan nilai 1jt
			if ($detail->shopping_price + $detail->total_discount < self::RESELLER_FIRST_ORDER_MINIMUM_AMOUNT) {
				$args = [
					'shopping_price >=' => self::RESELLER_FIRST_ORDER_MINIMUM_AMOUNT,
					'status' => self::ORDER_STATUS_COMPLETED,
					'user_id' => $this->user['id']
				];
				$alreadyMadePurchase = $this->repository->findOne('orders', $args);
				if (empty($alreadyMadePurchase)) {
					$this->delivery->addError(400, 'First purchase minimum for reseller is Rp 1.000.000');
					return $this->delivery;
				}
			}
			$orderSource = 'reseller';
		}
		try {
			$this->repository->beginTransaction();
			$orderCode = strtoupper(sprintf('ORC-%s', uniqid()));
			$invoiceNumber = sprintf('INV-%s', generate_invoice_number());
			$data = [
				'id_auth' => $this->user['id_auth'],
				'user_id' => $this->user['id'],
				'id_cart' => $shoppingCart->id,
				'order_code' => $orderCode,
				'order_info' => '',
				'total_price' => $detail->final_price,
				'total_qty' => $detail->total_qty,
				'total_weight' => $detail->total_weight,
				'shopping_price' => $detail->shopping_price,
				'shipping_cost' => $detail->shipping_cost,
				'total_discount' => $detail->total_discount,
				'final_price' => $detail->final_price,
				'payment_amount' => $detail->payment_amount,
				'status' => self::ORDER_STATUS_WAITING_PAYMENT,
				'no_awb' => '',
				'shipping_status' => null,
				'shipping_courier' => 'jne',
				'discount_noted' => '',
				'order_source' => $orderSource,
				'created_at' => $currentDate,
				'updated_at' => $currentDate,
				'invoice_number' => $invoiceNumber,
				'logistic_name' => 'jne',
				'logistic_rate_id' => ($isShipment && !is_null($isShipment)) ?  $shipment['service_code'] : null,
				'logistic_rate_name' => ($isShipment && !is_null($isShipment)) ?  $shipment['service_display'] : null,
				'logistic_min_day' => ($isShipment && !is_null($isShipment)) ?  $shipment['etd_from'] : null,
				'logistic_max_day' => ($isShipment && !is_null($isShipment)) ?  $shipment['etd_thru'] : null,
				'member_address_id_member_address' => $destination->id,
				'member_address_province_id' => $destination->province_id,
				'member_address_city_id' => $destination->city_code,
				'member_address_city_name' => $destination->city_name,
				'member_address_address' => $destination->address,
				'member_address_receiver_name' => $destination->received_name,
				'member_address_receiver_phone' => $destination->phone_number,
				'is_paid' => 0,
				'is_delivered' => 0,
				'is_completed' => 0,
				'paid_at' => null,
				'completed_at' => null,
				'payment_fee_merchant' => $detail->payment_fee_merchant,
				'payment_fee_customer' => $detail->payment_fee_customer,
				'payment_fee_total' => $detail->payment_fee_total,
				'payment_method_instruction' => json_encode($paymentMethodOptions),
				'voucher_id' => (empty($voucher) ? null : $voucher->id),
				'voucher_discount_amount' => $detail->voucher_discount_amount,
				'donation' => $donation,
			];

			if (!empty($referral)) {
				$data['referral_code'] = $referral->referral_code;
				$data['referral_user_id'] = $referral->id;
			}

			$orderId = $this->repository->insert('orders', $data);
			$cartValues = $shoppingCart->cart;

			//print_r(json_encode(['title'=>'insert_order', 'data'=>$orderId], true));

			$reCart = array(); $k_reCart = 0;
			foreach ($cartValues as $cartValue) {
				$statCheckout = (isset($cartValue->checkout)) ? (($cartValue->checkout==1) ? true : false) : true;
				if($statCheckout){
					$orderDetailData = [
						'order_code' => $orderCode,
						'id_product_detail' => $cartValue->id_product_detail,
						'discount_type' => $cartValue->details->discount_type,
						'discount_source' => $cartValue->details->discount_source,
						'discount_value' => $cartValue->details->discount_value,
						'discount_amount' => $cartValue->details->discount_amount,
						'price' => $cartValue->details->item_price,
						'qty' => $cartValue->details->qty,
						'total' => $cartValue->details->total,
						'subtotal' => $cartValue->details->subtotal,
						'note' => (isset($cartValue->note)) ? $cartValue->note : null,
					];
					$action = $this->repository->insert('order_details', $orderDetailData);
				}else{
					$reCart[$k_reCart] = $cartValue;
					$k_reCart = $k_reCart+1;
				}
			}

			foreach ($discountBooks as $discountBook) {
				$discountData = [
					'id_order' => $orderId,
					'type' => $discountBook['type'],
					'discount_amount' => $discountBook['discount_amount'],
					'created_at' => $currentDate,
					'updated_at' => $currentDate,
				];
				if ($discountBook['type'] == Calculator::DISCOUNT_TYPE_VOUCHER)  {
					$voucher = $discountBook['voucher'];
					$discountData['id_voucher'] = $voucher->id;
				} else if ($discountBook['type'] == Calculator::DISCOUNT_TYPE_FLASHSALE) {
					$flashsale = $discountBook['flashsale'];
					$discountData['id_flash_sale'] = $flashsale->id_flash_sale;
				} else if ($discountBook['type'] == Calculator::DISCOUNT_TYPE_PRODUCT) {
					$discountProduct = $discountBook['product'];
					$discountData['id_discount_product'] = $discountProduct->id;
				} else if ($discountBook['type'] == Calculator::DISCOUNT_TYPE_PRODUCT_DETAIL) {
					$discountProductDetail = $discountBook['product_detail'];
					$discountData['id_discount_product_detail'] = $discountProductDetail->id;
				}
				$action = $this->repository->insert('order_discounts', $discountData);
			}

			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
			}

			$finalPrice = $detail->final_price+$donation;
			$tripayCart = [
				[
					'sku' => $orderCode,
					'name' => sprintf('Payment %s', $orderCode),
					'price' => $finalPrice,
					'quantity' => 1
				]
			];
			$tripay = new TripayGateway;
	        // $tripay->setEnv('development');
	        $tripay->setEnv('production');
	        $tripay->setMerchantCode('T1441');
	        $tripay->setApiKey('fQGB4tyeCghSF844Um01J6mEDWnH1KqlvB0LHa8N');
	        $tripay->setPrivateKey('TLMQO-Y0eMk-Si4DN-V28zC-JU1UK');
			// gunakan amount sebelum dicharge fee (customer_fee)
			$tripayRequest = $tripay->requestTransaksi($paymentMethod->code, $invoiceNumber, $finalPrice, $this->user['first_name'], (empty($this->user['email']) ? 'no-reply@rabbani.id' : $this->user['email']), $this->user['phone'], $tripayCart, null);
			$tripayResult = $tripayRequest->data;
			$paymentExpiredAt = date('Y-m-d H:i:s', $tripayResult->expired_time);
			$dataUpdate = [
				'payment_reference_no' => $tripayResult->reference, 
				'payment_method_code' => $paymentMethod->code, 
				'payment_method_name' => $paymentMethod->name, 
				'payment_method_instruction' => json_encode($tripayResult->instructions), 
				'payment_method_instruction_qris_value' => (isset($tripayResult->qr_string) ? $tripayResult->qr_string : null),
				'checkout_url' => (isset($tripayResult->checkout_url)) ? $tripayResult->checkout_url : null,
				'payment_detail' => json_encode($tripayResult),
				'payment_expired_at' => $paymentExpiredAt,
			];
			$action = $this->updateOrder($dataUpdate, ['order_code' => $orderCode]);

			/* $waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
			$message = 'Halo '.$this->getUser()['first_name'].' pesanan kaka sudah kami terima'.PHP_EOL.PHP_EOL.'Berikut ditail pesanan kaka'.PHP_EOL.'No Invoice: '.$invoiceNumber.PHP_EOL.'Total Tagihan: '.toRupiahFormat($finalPrice).PHP_EOL.PHP_EOL.'Biaya Bank: '.toRupiahFormat($detail->payment_fee_customer).PHP_EOL.'Transfer Ke Bank '.$paymentMethod->name.PHP_EOL.'No '.$tripayResult->pay_code.PHP_EOL.PHP_EOL.'Atau melalui link berikut '.$tripayResult->checkout_url;
			$sendWa = $waService->publishMessage('send_message', $this->getUser()['phone'], $message); */
			$message = 'Halo '.$this->getUser()['first_name'].' pesanan kaka sudah kami terima'.PHP_EOL.PHP_EOL.'Berikut ditail pesanan kaka'.PHP_EOL.'No Invoice: '.$invoiceNumber.PHP_EOL.'Total Tagihan: '.toRupiahFormat($finalPrice).PHP_EOL.PHP_EOL.'Biaya Bank: '.toRupiahFormat($detail->payment_fee_customer).PHP_EOL.'Transfer Ke Bank '.$paymentMethod->name.PHP_EOL.'No '.$tripayResult->pay_code.PHP_EOL.PHP_EOL.'Atau melalui link berikut '.$tripayResult->checkout_url;

			$notification = new NotificationHandler($this->repository);
			$notification->setUser($this->getUser());
			$notification->setSendWhatsapp(true);
			$notification->setSendEmail(true);
			$notification->setSendPushNotification(true);
			$notification->setTypeToInfo();
			$notifAction = $notification->sendToUser('Lakukan Pembayaran', $message);
			$resp['notif'] = $notifAction->data;

			// empty cart
			$this->cartHandler->setUser($this->user);
			$action = $this->cartHandler->updateCart(['cart' => $reCart]);

			$forUpCartStore = ['crst_user'=>$this->user['id'],'crst_order'=>NULL,'crst_status'=>0,'deleted_at'=>NULL];
			$actionCartStore = $this->repository->update('cart_stores', ['crst_order'=>$orderCode,'crst_status'=>1,'created_at'=>$currentDate,'updated_at'=>$currentDate], $forUpCartStore); 
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}

		$order = $this->getOrder(['id_order' => $orderId]);
		$this->delivery->data = $order->data;
		return $this->delivery;
	}

	public function onTripayCallback ($payload) {
		$payload = (array)$payload;
		$reference = $payload['reference'];
		$merchantRef = $payload['merchant_ref'];
		$filterTrans = [
			'invoice_number' => $merchantRef,
			'payment_reference_no' => $reference
		];

		$existsOrder = $this->getOrder($filterTrans);
		$notification = new NotificationHandler($this->repository);
		if (!empty($existsOrder->data)) {
			$resp = [];
			$order = $existsOrder->data;
			$targetUser = $this->repository->findOne('users', ['id' => $existsOrder->data->user_id]);
			$notification->setUser($targetUser);
			$notification->setSendWhatsapp(true);
			$notification->setSendEmail(true);
			$notification->setTypeToInfo();

			if ($payload['status'] == 'EXPIRED' && $order->status == self::ORDER_STATUS_WAITING_PAYMENT) {
				
				$payloadUpdate = [
					'status' => self::ORDER_STATUS_PAYMENT_EXPIRED,
					'updated_at' => date("Y-m-d H:i:s")
				];
				$action = $this->updateOrder($payloadUpdate, $filterTrans);
				$resp['action'] = $action->data;

				$message = 'Halo '.$order->user_first_name.PHP_EOL.'Pesanan dengan no invoice '.$order->invoice_number.' telah kami batalkan karena waktu pembayaran telah habis.'.PHP_EOL.'Terima kasih';
				$notifAction = $notification->sendToUser('Pembayaran Kadaluarsa', $message);
				$resp['notif'] = $notifAction->data;

				/* $waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
				$message = 'Halo '.$order->user_first_name.PHP_EOL.'Pesanan dengan no invoice '.$order->invoice_number.' telah kami batalkan karena waktu pembayaran telah habis.'.PHP_EOL.'Terima kasih';
				$sendWa = $waService->publishMessage('send_message', $order->user_phone, $message);
				$resp['notif'] = $sendWa; */

				$this->delivery->data = $resp;
				return $this->delivery;

			} else if ($payload['status'] == 'PAID') {
				
				$status = self::ORDER_STATUS_OPEN;
				$notifType = 'payment';
				if ($existsOrder->data->order_info == $this->resellerOrderInfo) {
					$status = self::ORDER_STATUS_COMPLETED;
					$notifType = 'reseller_registration';
				}

				$payloadUpdate = [
					'is_paid' => 1,
					'paid_at' => date('Y-m-d H:i:s'),
					'payment_method_name' => $payload['payment_method'],
					'payment_method_code' => $payload['payment_method_code'],
					'status' => $status
				];
				$action = $this->updateOrder($payloadUpdate, $filterTrans);
				if ($existsOrder->data->order_info == $this->resellerOrderInfo) {
					$user = $this->repository->update('users', ['can_order' => 1], ['id' => $existsOrder->data->user_id]);
				}

				if ($notifType == 'reseller_registration') {
					// $waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
					$message = 'Selamat datang dalam program rabbani jamaah, berikut data ke anggotaan kaka'.PHP_EOL.PHP_EOL.'ID Keanggotaan: '.$order->user_username.PHP_EOL.'Nama: '.$order->user_first_name.PHP_EOL.'Status: Aktif'.PHP_EOL.PHP_EOL.'Terima kasih sudah bergabung';
					// $sendWa = $waService->publishMessage('send_message', $order->user_phone, $message);
					// $resp['notif'] = $sendWa;
					$notifAction = $notification->sendToUser('Pembayaran Diterima', $message);
					$resp['notif'] = $notifAction->data;
				} else if ($notifType == 'payment') {
					// $waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
					$message = 'Halo '.$order->user_first_name.PHP_EOL.'Pembayaran sebesar '.toRupiahFormat($payload['total_amount']).' dengan no invoice '.$order->invoice_number.' telah kami terima'.PHP_EOL.'Terima kasih';
					// $sendWa = $waService->publishMessage('send_message', $order->user_phone, $message);
					// $resp['notif'] = $sendWa;
					$notifAction = $notification->sendToUser('Pembayaran Diterima', $message);
					$resp['notif'] = $notifAction->data;
				}
				$resp['action'] = $action->data;
				$this->delivery->data = $resp;
				return $this->delivery;
			}
		}
		$this->delivery->data = null;
		return $this->delivery;
	}

	public function rateOrder ($orderCode, $payload) {
		$orderData = $this->getOrder(['order_code' => $orderCode]);
		$order = $orderData->data;
		if (empty($order)) {
			$this->delivery->addError(400, 'Order is required');
		}

		if (!isset($payload['rate']) || empty($payload['rate'])) {
			$this->delivery->addError(400, 'Rate is required');
		}

		if (!empty($order->rate)) {
			$this->delivery->addError(400, 'Rate already submitted');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		if ($order->status != self::ORDER_STATUS_COMPLETED) {
			$this->delivery->addError(400, 'Order need to be completed first');
			return $this->delivery;
		}

		$orderDetails = $order->order_details;

		$rate = (int)$payload['rate'];
		$message = $payload['message'];

		$update = $this->repository->update('orders', ['rate' => $rate], ['order_code' => $orderCode]);

		$idProducts = [];

		$imagesKey = [
			'images_1',
			'images_2',
			'images_3'
		];
		$images = [];

		foreach ($imagesKey as $key) {
			if (isset($_FILES[$key]) && !empty($_FILES[$key]['tmp_name'])) {
				$digitalOceanService = new DigitalOceanService();
				$uploadResult = $digitalOceanService->upload($payload, $key);
				$images[] = $uploadResult['cdn_url'];
			}
		}
		
		foreach ($orderDetails as $orderDetail) {
			if (in_array($orderDetail->id_product, $idProducts)) {
				continue;
			} else {
				$idProducts[] = $orderDetail->id_product;
			}
			$payloadRate = [
				'id_product' => $orderDetail->id_product,
				'rate' => $rate,
				'order_code' => $order->order_code,
				'message' => $message,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('product_rates', $payloadRate);

			if (!empty($images)) {
				foreach ($images as $imageUrl) {
					$payloadImages = [
						'id_product' => $orderDetail->id_product,
						'image_url' => $imageUrl,
						'order_code' => $order->order_code,
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s')
					];
					$action = $this->repository->insert('product_rate_images', $payloadImages);
				}
			}
		}

		$orderData = $this->getOrder(['order_code' => $orderCode]);
		$order = $orderData->data;
		$this->delivery->data = $order;
		return $this->delivery;
	}

	public function generateResellerRegistrationOrder ($paymentMethodCode, $amount = 50000) {
		if (empty($this->getUser())) {
			$this->delivery->addError(400, 'User is required');
			return $this->delivery;
		}

		$tripay = new TripayGateway;
        // $tripay->setEnv('development');
        $tripay->setEnv('production');
        $tripay->setMerchantCode('T13840');
        $tripay->setApiKey('XW1h01alwrID3xpwcqV2jIa9lUFxV9o89fQMwso2');
        $tripay->setPrivateKey('CNvlC-sMN5n-14mnA-EtIq5-pC7Z8');
		$tripayResult = $tripay->channelPembayaran($paymentMethodCode);
		if (empty($tripayResult->data)) {
			$this->delivery->addError(400, 'Payment method not found');
			return $this->delivery;
		}
		$paymentMethod = $tripayResult->data[0];

		try {
			$this->repository->beginTransaction();
			$orderCode = strtoupper(sprintf('RR-%s', uniqid()));
			$invoiceNumber = sprintf('INV-%s', generate_invoice_number());

            $feeMerchant = ($amount * $paymentMethod->fee_merchant->percent / 100) + $paymentMethod->fee_merchant->flat;
            $feeCustomer = ($amount * $paymentMethod->fee_customer->percent / 100) + $paymentMethod->fee_customer->flat;
            $totalFee = ($amount * $paymentMethod->total_fee->percent / 100) + $paymentMethod->total_fee->flat;

			$paymentAmount = $amount + $totalFee;
			$data = [
				'id_auth' => $this->getUser()['id_auth'],
				'user_id' => $this->getUser()['id'],
				'id_cart' => null,
				'order_code' => $orderCode,
				'order_info' => '',
				'total_price' => $amount,
				'total_qty' => 1,
				'total_weight' => 1,
				'shopping_price' => $amount,
				'shipping_cost' => 0,
				'total_discount' => 0,
				'final_price' => $amount,
				'payment_amount' => $paymentAmount,
				'status' => self::ORDER_STATUS_WAITING_PAYMENT,
				'no_awb' => '',
				'shipping_status' => null,
				'shipping_courier' => null,
				'discount_noted' => null,
				'order_source' => 'reseller',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
				'invoice_number' => $invoiceNumber,
				'is_paid' => 0,
				'is_delivered' => 0,
				'is_completed' => 0,
				'paid_at' => null,
				'completed_at' => null,
				'payment_fee_merchant' => $feeMerchant,
				'payment_fee_customer' => $feeCustomer,
				'payment_fee_total' => $totalFee,
				'payment_method_instruction' => json_encode($tripayResult->data),
				'order_info' => $this->resellerOrderInfo
			];

			$orderId = $this->repository->insert('orders', $data);

			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
			}

			$tripayCart = [
				[
					'sku' => $orderCode,
					'name' => sprintf('Payment %s', $orderCode),
					'price' => $amount,
					'quantity' => 1
				]
			];
			// gunakan amount sebelum dicharge fee (customer_fee)
			$tripayRequest = $tripay->requestTransaksi($paymentMethodCode, $invoiceNumber, $amount, $this->user['first_name'], (empty($this->user['email']) ? 'no-reply@1itmedia.co.id' : $this->user['email']), $this->user['phone'], $tripayCart, null);
			$tripayResult = $tripayRequest->data;
			$updateData = [
				'payment_reference_no' => $tripayResult->reference,
				'payment_method_code' => $paymentMethod->code,
				'payment_method_name' => $paymentMethod->name,
				'checkout_url' => $tripayResult->checkout_url,
				'payment_method_instruction' => json_encode($tripayResult->instructions),
				'payment_method_instruction_qris_value' => $tripayResult->qr_string,
				'payment_detail' => json_encode($tripayResult),
			];
			$action = $this->updateOrder($updateData, ['order_code' => $orderCode]);

			$waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
			$message = 'Halo '.$this->getUser()['first_name'].' pesanan kaka sudah kami terima'.PHP_EOL.PHP_EOL.'Berikut ditail pesanan kaka'.PHP_EOL.'No Invoice: '.$invoiceNumber.PHP_EOL.'Total Tagihan: '.toRupiahFormat($amount).PHP_EOL.PHP_EOL.'Ditail Pesanan'.PHP_EOL.'1. Pendaftaran Keanggotaan'.PHP_EOL.PHP_EOL.'Biaya Bank: '.toRupiahFormat($totalFee).PHP_EOL.'Metode Pembayaran: '.$paymentMethod->name.PHP_EOL.'No '.$tripayResult->pay_code.PHP_EOL.PHP_EOL.'Atau melalui link berikut '.$tripayResult->checkout_url;
			$sendWa = $waService->publishMessage('send_message', $this->getUser()['phone'], $message);
			$this->delivery->data = $sendWa;

			$orderResponse = [
				'payment_reference_no' => $tripayResult->reference,
				'payment_method_code' => $paymentMethod->code,
				'payment_method_name' => $paymentMethod->name,
				'checkout_url' => $tripayResult->checkout_url,
				'payment_method_instruction' => $tripayResult->instructions
			];
			$this->delivery->data = $orderResponse;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}

		$this->delivery->data = 'ok';
		return $this->delivery;

	}

	public function recheckPaymentMethod ($order, $paymentMethodCode) {
		if ($order->status != OrderHandler::ORDER_STATUS_PAYMENT_EXPIRED && $order->status != OrderHandler::ORDER_STATUS_WAITING_PAYMENT) {
            $this->delivery->addError(400, 'Cannot recreate payment method. Forbidden status. Only allowed when payment expired.');
            return $this->delivery;
        }

		$calculator = new Calculator($this->repository, true, $this->getUser());
		$calculator->setShoppingPrice($order->shopping_price);
		$calculator->setTotalDiscount($order->total_discount);
		$calculator->setShippingCost($order->shipping_cost);
		$paymentAmount = $calculator->getShoppingPrice() + $calculator->getTotalDiscount() + $calculator->getShippingCost();
		$calculator->setFinalPrice($paymentAmount);
		$calculator->setPaymentAmount($paymentAmount);
		$calculator->setPaymentMethodCode($paymentMethodCode);

		$result = new \stdClass;
		$result->detail = $calculator->checkCart();
		$result->payment_method_options = $calculator->getPaymentMethodOptions();
		$result->payment_method = $calculator->getPaymentMethod();

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function recreatePaymentMethod ($order, $paymentMethodCode) {
		$checkResult = $this->recheckPaymentMethod($order, $paymentMethodCode);
		if ($checkResult->hasErrors()) {
			return $checkResult;
		}

		$checkData = $checkResult->data;
		$detail = $checkData->detail;
		if (empty($checkData->payment_method)) {
			$this->delivery->addError(400, 'Payment method is required');return $this->delivery;
		}

		$paymentMethod = $checkData->payment_method;

		$orderCode = $order->order_code;
		$tripayCart = [
			[
				'sku' => $orderCode,
				'name' => sprintf('Payment %s', $orderCode),
				'price' => $detail->final_price,
				'quantity' => 1
			]
		];
		$tripay = new TripayGateway;
        // $tripay->setEnv('development');
        $tripay->setEnv('production');
        $tripay->setMerchantCode('T1441');
        $tripay->setApiKey('fQGB4tyeCghSF844Um01J6mEDWnH1KqlvB0LHa8N');
        $tripay->setPrivateKey('TLMQO-Y0eMk-Si4DN-V28zC-JU1UK');
		// gunakan amount sebelum dicharge fee (customer_fee)
		$tripayRequest = $tripay->requestTransaksi($paymentMethodCode, $order->invoice_number, $detail->final_price, $this->user['first_name'], (empty($this->user['email']) ? 'no-reply@1itmedia.co.id' : $this->user['email']), $this->user['phone'], $tripayCart, null);
		if ($tripayRequest->success === false) {
			$this->delivery->addError(400, $tripayRequest->message);
			return $this->delivery;
		}
		$tripayResult = $tripayRequest->data;
		$paymentExpiredAt = date('Y-m-d H:i:s', $tripayResult->expired_time);
		$dataUpdate = [
			'status' => self::ORDER_STATUS_WAITING_PAYMENT,
			'payment_reference_no' => $tripayResult->reference, 
			'payment_method_code' => $paymentMethod->code, 
			'payment_method_name' => $paymentMethod->name, 
			'payment_method_instruction' => json_encode($tripayResult->instructions), 
			'payment_method_instruction_qris_value' => (isset($tripayResult->qr_string) ? $tripayResult->qr_string : null),
			'checkout_url' => (isset($tripayResult->checkout_url)) ? $tripayResult->checkout_url : null,
			'payment_detail' => json_encode($tripayResult),
			'payment_fee_merchant' => $detail->payment_fee_merchant,
			'payment_fee_customer' => $detail->payment_fee_customer,
			'payment_fee_total' => $detail->payment_fee_total,
			'payment_expired_at' => $paymentExpiredAt,
		];
		$action = $this->updateOrder($dataUpdate, ['order_code' => $order->order_code]);

		$waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
		$message = 'Halo '.$this->getUser()['first_name'].' pesanan kaka sudah kami terima'.PHP_EOL.PHP_EOL.'Berikut ditail pesanan kaka'.PHP_EOL.'No Invoice: '.$order->invoice_number.PHP_EOL.'Total Tagihan: '.toRupiahFormat($detail->final_price).PHP_EOL.PHP_EOL.'Biaya Bank: '.toRupiahFormat($detail->payment_fee_customer).PHP_EOL.'Transfer Ke Bank '.$paymentMethod->name.PHP_EOL.'No '.$tripayResult->pay_code.PHP_EOL.PHP_EOL.'Atau melalui link berikut '.$tripayResult->checkout_url;
		$sendWa = $waService->publishMessage('send_message', $this->getUser()['phone'], $message);

		$order = $this->getOrder(['id_order' => $order->id_order]);
		$orderData = $order->data;
		$orderData->extras = $sendWa;
		$this->delivery->data = $orderData;
		return $this->delivery;
	}

	public function notifyUser($order) {
		$notification = new NotificationHandler($this->repository);
		$targetUser = $this->repository->findOne('users', ['id' => $order->user_id]);
		$notification->setUser($targetUser);
		$notification->setSendWhatsapp(true);
		$notification->setSendEmail(true);
		$notification->setSendPushNotification(true);
		$notification->setTypeToInfo();
		$message = '';
		if ($order->status == self::ORDER_STATUS_IN_PROCESS) {
			$message = 'Halo '.$order->user_first_name.PHP_EOL.'Pesanan dengan no invoice '.$order->invoice_number.' sedang dalam proses.'.PHP_EOL.'Terima kasih';
		} else if ($order->status == self::ORDER_STATUS_IN_SHIPMENT) {
			$message = 'Halo '.$order->user_first_name.PHP_EOL.'Pesanan dengan no invoice '.$order->invoice_number.' sedang dalam perjalanan.'.PHP_EOL.'Terima kasih';
		}
		$notifAction = $notification->sendToUser('Status Transaksi Anda', $message);
		return $notifAction->data;

		/* $waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
		$message = '';
		$sendWa = null;
		if ($order->status == self::ORDER_STATUS_IN_PROCESS) {
			$message = 'Halo '.$order->user_first_name.PHP_EOL.'Pesanan dengan no invoice '.$order->invoice_number.' sedang dalam proses.'.PHP_EOL.'Terima kasih';
		} else if ($order->status == self::ORDER_STATUS_IN_SHIPMENT) {
			$message = 'Halo '.$order->user_first_name.PHP_EOL.'Pesanan dengan no invoice '.$order->invoice_number.' sedang dalam perjalanan.'.PHP_EOL.'Terima kasih';
		}
		if (!empty($message)) {
			$sendWa = $waService->publishMessage('send_message', $order->user_phone, $message);
		}
		return $sendWa; */
	}

	public function getOrderStore($orderCode = null, $storeId = null, $orderStatus = null, $payload = []){
		$statusList = $this->listStatusOrderStore();
		if(!$orderCode || empty($orderCode) || is_null($orderCode)){
            $this->delivery->addError(400, 'Order code is required'); return $this->delivery;
        }
        $isDetail = ($storeId && !empty($storeId) && !is_null($storeId)) ? true : false;

        $sort = array('id'=>'crst_id','updated'=>'updated_at','created'=>'created_at');
        $orderBy = 'updated_at'; $orderVal = 'DESC';

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $sortBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $sortVal;
        }

        $offset = 0; $limit = 100;
		if (isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data'])) {
			$limit = intval($payload['data']);
		}
		if (isset($payload['page']) && $payload['page'] && !empty($payload['page']) && !is_null($payload['page'])) {
			$offset = (intval($payload['page'])-1) * $limit;
		}

		$formatView = 'pagination';
		if(isset($payload['format']) && $payload['format'] && !empty($payload['format']) && !is_null($payload['format'])){
			$formatView = $payload['format'];
		}

        $select = [
            'cart_stores.crst_id as id',
            'cart_stores.crst_order as order_code',
            'cart_stores.crst_note as note',
            'cart_stores.crst_status as status',
            'cart_stores.crst_shipping as shipping',
            'cart_stores.crst_weight as total_weight',
            'cart_stores.crst_awb_no as awb_nomor',
            'cart_stores.created_at as created',
            'cart_stores.updated_at as updated',

            'cart_stores.crst_store as store_id',
            'cart_stores.crst_store_detail as store_detail',

            'cart_stores.crst_address as user_address',
            'cart_stores.crst_address_detail as user_address_detail',

            'cart_stores.crst_shipment as shipment',
            'cart_stores.crst_item as products',
        ];

        $condition = [
            'cart_stores.crst_order'=>$orderCode,
            'cart_stores.crst_status >'=>0,
            'cart_stores.deleted_at'=>NULL
        ];
        if($isDetail){
            $condition['cart_stores.crst_store'] = $storeId;
        }
        $join = null;

		if($isDetail){
			$orders = $this->repository->find('cart_stores', $condition, null, $join, $select);
			$loadOrder = $orders;
		}else{
			if($formatView=='pagination'){
				$orders = $this->repository->findPaginated('cart_stores', $condition, null, $join, $select, $offset, $limit, 'cart_stores.'.$orderBy, $orderVal);
				$loadOrder = $orders['result'];
			}else{
				$orders = $this->repository->find('cart_stores', $condition, null, $join, $select, null, null, 'cart_stores.'.$orderBy, $orderVal);
				$loadOrder = $orders;
			}
		}
		if(!$loadOrder || is_null($loadOrder)){
			return array('success'=>false, 'msg'=>'Transaction store not found');
		}

		if(isset($payload['store_only']) && $payload['store_only']){
			$resultStore = array();
			foreach($loadOrder as $k_order=>$order){
				$isStoreOrder = null;
				if($order->store_detail && !empty($order->store_detail) && !is_null($order->store_detail)){
	                $isStoreOrder = json_decode($order->store_detail);
	                $isStoreOrder->order_code_store = $order->order_code.'-'.$order->id;
	                if(isset($statusList[$order->status])){
		            	$isStatus = $statusList[$order->status]['slug'];
		            	if($isStatus=='by_order' && $orderStatus && !is_null($orderStatus)){
		            		$isStatus = $orderStatus;
		            	}
		            	$isStoreOrder->status = $isStatus;
		            }
	            }
	            $resultStore[$k_order] = $isStoreOrder;
			}
			return $resultStore;
		}

        foreach($loadOrder as $order){
        	$cekTrackingOrder = $this->checkTrackingOrderStore($order);
        	if($cekTrackingOrder && !is_null($cekTrackingOrder)){
        		if(isset($cekTrackingOrder['status']) && $cekTrackingOrder['status'] && !is_null($cekTrackingOrder['status']) && is_numeric($cekTrackingOrder['status'])){
        			$order->status = intval($cekTrackingOrder['status']);
        		}
        	}

        	$order->order_code_store = $order->order_code.'-'.$order->id;
			if($order->shipment && !empty($order->shipment) && !is_null($order->shipment)){
                $order->shipment = json_decode($order->shipment);
                if(!isset($order->shipment->tracking)){
                	$order->shipment->tracking = array('type'=>null, 'awb_nomor'=>null);
                }
            }
            if($order->store_detail && !empty($order->store_detail) && !is_null($order->store_detail)){
                $order->store_detail = json_decode($order->store_detail);
            }
            if($order->user_address_detail && !empty($order->user_address_detail) && !is_null($order->user_address_detail)){
                $order->user_address_detail = json_decode($order->user_address_detail);
            }

            $setQty = 0; $setSubtotal = 0; $setPrice = 0;
            $setDiscount = 0; $setTotal = 0;
            $orderProduct = array();
            if($order->products && !empty($order->products) && !is_null($order->products)){
                $order->products = json_decode($order->products);
                foreach($order->products as $item){
                    $detailId = $item->id_product_detail;
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
                        'product.weight as product_weight',
						'product.length as product_length',
						'product.height as product_height',

                        'order_details.sku_code',
                    ];
                    $conditionDetail = [
                        'order_details.order_code' => $order->order_code,
                        'order_details.id_product_detail'=>$detailId,
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
						]
					];
                    $cekDetail = $this->repository->findOne('order_details', $conditionDetail, null, $joinDetail, $selectDetail);
                    if($cekDetail && !is_null($cekDetail)){
                        if($cekDetail->product_detail_variable && !empty($cekDetail->product_detail_variable) && !is_null($cekDetail->product_detail_variable)){
                            if(isJson($cekDetail->product_detail_variable)){
                                $cekDetail->product_detail_variable = json_decode($cekDetail->product_detail_variable);
                            }
                        }
                        $orderProduct[] = $cekDetail;
                        $setQty += $cekDetail->qty;
                        $setPrice += $cekDetail->price;
                        $setSubtotal += $cekDetail->subtotal;
                        $isDiscItem = 0;
                        if($cekDetail->discount_amount && !empty($cekDetail->discount_amount) && !is_null($cekDetail->discount_amount)){
                            $isDiscItem = intval(abs($cekDetail->discount_amount));
                        }
                        $setDiscount += $isDiscItem; 
                        $setTotal += $cekDetail->total; 
                    }
                }
            }

            $order->products = ($orderProduct && !is_null($orderProduct)) ? $orderProduct : null;
            $order->detail = array();
            $order->detail['qty'] = $setQty;
            $order->detail['price'] = $setPrice;
            $order->detail['subtotal'] = $setSubtotal;
            $order->detail['discount'] = $setDiscount;
            $order->detail['total'] = $setTotal;
            $order->detail['shipping'] = $order->shipping;
            $order->detail['weight'] = $order->total_weight;
            $order->detail['final_total'] = intval($setTotal)+intval($order->shipping);

            if(isset($statusList[$order->status])){
            	$isStatus = $statusList[$order->status]['slug'];
            	if($isStatus=='by_order' && $orderStatus && !is_null($orderStatus)){
            		$isStatus = $orderStatus;
            	}
            	$order->status = $isStatus;
            }
            unset($order->shipping, $order->total_weight);
		}

		if(!$isDetail){
			$orders['result'] = $loadOrder;
		}
		$isResult = ($isDetail) ? $loadOrder[0] : $orders;
		return array('success'=>true, 'msg'=>'Transaction store found','data'=>$isResult);
	}

	public function checkTrackingOrderStore($order = null){
		$resTracking = null; $statusData = null;
		if($order->status==3 && ($order->awb_nomor && !empty($order->awb_nomor) && !is_null($order->awb_nomor)) ){
			$jneService = new JNEService();
	        $jneService->setEnv('production');
	        $tracking = $jneService->getTraceTracking($order->awb_nomor);

	        if(isset($tracking['cnote']) && $tracking['cnote'] && !empty($tracking['cnote']) && !is_null($tracking['cnote'])){
	        	if(isset($tracking['cnote']['pod_status'])){
	        		$statusTrack = $tracking['cnote']['pod_status'];
	        		if($statusTrack=='DELIVERED'){
	        			$statusData = 4;
	        		}
	        	}

	        	if($statusData && !is_null($statusData) && is_numeric($statusData)){
		        	$action = $this->repository->update('cart_stores', ['crst_status'=>$statusData], ['crst_id'=>$order->id]);
		        }
		        $resTracking =  array('status'=>$statusData, 'data'=>$tracking);
	        }
    	}
    	return $resTracking;
	}

	public function getOrderDiscounts ($idOrder) {
		$args = [
			'id_order' => $idOrder
		];

		$select = [
			'order_discounts.id',
			'order_discounts.type',
			'order_discounts.id_order',
			'order_discounts.id_voucher',
			'order_discounts.id_flash_sale',
			'order_discounts.id_discount_product',
			'order_discounts.id_discount_product_detail',
			'order_discounts.discount_amount',
			'order_discounts.created_at',
			'order_discounts.updated_at',
			'order_discounts.deleted_at',
			'vouchers.code as voucher_code',
			'vouchers.name as voucher_name',
			'vouchers.type as voucher_type',
			'vouchers.discount_type as voucher_discount_type',
			'vouchers.discount_value as voucher_discount_value',
			'vouchers.min_shopping_amount as voucher_min_shopping_amount',
			'vouchers.max_discount_amount as voucher_max_discount_amount',
			'vouchers.start_time as voucher_start_time',
			'vouchers.end_time as voucher_end_time',
			'flash_sale.total_discount as flash_sale_total_discount',
			'flash_sale.date_started as flash_sale_date_started',
			'flash_sale.date_end as flash_sale_date_end',
			'flash_sale.discount_type as flash_sale_discount_type',
			'flash_sale.type as flash_sale_type',
			'flash_sale.product_id as flash_sale_product_id',
			'discount_products.start_time as discount_product_start_time',
			'discount_products.end_time as discount_product_end_time',
			'discount_products.id_product as discount_product_id_product',
			'discount_products.discount_type as discount_product_discount_type',
			'discount_products.discount_value as discount_product_discount_value',
			'discount_product_details.id_product_detail as discount_product_detail_id_product_detail',
			'discount_product_details.discount_type as discount_product_detail_discount_type',
			'discount_product_details.discount_value as discount_product_detail_discount_value',
		];

		$join = [
			'vouchers' => [
				'type' => 'left',
				'value' => 'vouchers.id = order_discounts.id_voucher',
			],
			'flash_sale' => [
				'type' => 'left',
				'value' => 'flash_sale.id = order_discounts.id_flash_sale',
			],
			'discount_products' => [
				'type' => 'left',
				'value' => 'discount_products.id = order_discounts.id_discount_product',
			],
			'discount_product_details' => [
				'type' => 'left',
				'value' => 'discount_product_details.id = order_discounts.id_discount_product_detail',
			],
		];

		$discounts = $this->repository->find('order_discounts', $args, null, $join, $select);
		$formatted = [];
		if (!empty($discounts)) {
			foreach ($discounts as $discount) {
				$obj = [
					'id' => $discount->id,
					'type' => $discount->type,
					'id_order' => $discount->id_order,
					'discount_amount' => $discount->discount_amount,
					'created_at' => $discount->created_at,
					'updated_at' => $discount->updated_at,
					'deleted_at' => $discount->deleted_at,
				];
				if ($discount->type == Calculator::DISCOUNT_TYPE_VOUCHER) {
					$obj['id_voucher'] = $discount->id_voucher;
					$voucher = [
						'code' => $discount->voucher_code,
						'name' => $discount->voucher_name,
						'type' => $discount->voucher_type,
						'discount_type' => $discount->voucher_discount_type,
						'discount_value' => $discount->voucher_discount_value,
						'min_shopping_amount' => $discount->voucher_min_shopping_amount,
						'max_discount_amount' => $discount->voucher_max_discount_amount,
						'start_time' => $discount->voucher_start_time,
						'end_time' => $discount->voucher_end_time,
					];
					$obj['voucher'] = $voucher;
				} else if ($discount->type == Calculator::DISCOUNT_TYPE_FLASHSALE) {
					$obj['id_flash_sale'] = $discount->id_flash_sale;
					$flashsale = [
						'total_discount' => $discount->flash_sale_total_discount,
						'date_started' => $discount->flash_sale_date_started,
						'date_end' => $discount->flash_sale_date_end,
						'discount_type' => $discount->flash_sale_discount_type,
						'type' => $discount->flash_sale_type,
						'product_id' => $discount->flash_sale_product_id
					];
					$obj['flash_sale'] = $flashsale;
				} else if ($discount->type == Calculator::DISCOUNT_TYPE_PRODUCT) {
					$obj['id_discount_product'] = $discount->id_discount_product;
					$dp = [
						'start_time' => $discount->discount_product_start_time,
						'end_time' => $discount->discount_product_end_time,
						'id_product' => $discount->discount_product_id_product,
						'discount_type' => $discount->discount_product_discount_type,
						'discount_value' => $discount->discount_product_discount_value,
					];
					$obj['discount_product'] = $dp;
				} else if ($discount->type == Calculator::DISCOUNT_TYPE_PRODUCT_DETAIL) {
					$obj['id_discount_product_detail'] = $discount->id_discount_product_detail;
					$dpp = [
						'id_product_detail' => $discount->discount_product_detail_id_product_detail,
						'discount_type' => $discount->discount_product_detail_discount_type,
						'discount_value' => $discount->discount_product_detail_discount_value,
					];
					$obj['discount_product_detail'] = $dpp;
				}

				$formatted[] = $obj;
			}
		}
		return $formatted;
	}

}