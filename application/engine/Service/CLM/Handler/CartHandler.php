<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Calculator;
use Service\CLM\Handler\StoreHandler;
use Library\JNEService;
use Service\CLM\Handler\ShippingLocationHandler;
use Service\CLM\Handler\ShippingPriceHandler;

class CartHandler {

	private $user; // user
	private $delivery;
	private $repository;
	private $productDetailHandler;
	private $cart;
	private $calculatorResult;
	private $productHandler;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->productDetailHandler = new ProductDetailHandler($this->repository);
		$this->storeHandler = new StoreHandler($this->repository);
		$this->shippingLocationHandler = new ShippingLocationHandler($this->repository);
		$this->shippingPriceHandler = new ShippingPriceHandler($this->repository);
	}

	public function setUser ($user) {
		$this->user = $user;
	}

	public function getUser () {
		return $this->user;
	}

	public function setCalculatorResult ($calculatorResult) {
		$this->calculatorResult = $calculatorResult;
	}

	public function getCalculatorResult () {
		return $this->calculatorResult;
	}

	public function getCart ($filters = null) {
		if (!empty($this->getUser())) {
			$filters['user_id'] = $this->user['id'];
		}
		$cart = $this->repository->findOne('carts', $filters);
		if (!empty($cart) && isJson($cart->cart)) {
			$cart->cart = json_decode($cart->cart);
		} else {
			$cart = new \stdClass;
			$cart->cart = [];
		}

		$cartValues = $cart->cart;
		$calculator = new Calculator($this->repository, true, $this->getUser());
		if (!empty($this->getUser()['referred_by_store_agent_id'])) {
			$calculator->setCalculateForReseller(true);
		}

		$productHandler = new ProductHandler($this->repository);
		$productHandler->setShowProductDetails(true);
		$productHandler->setShowProductRates(false);
		$productHandler->setShowProductRateImages(false);

		$countCheckout = 0; $groupStore = array(); $groupCheckoutStore = array(); //update//
		//$withGroupStore = (isset($condition['group_store'])) ? ( ($condition['group_store']===true) ? true : false ) : true;
		foreach ($cartValues as $key_cart=>$cartValue) {
			$productDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $cartValue->id_product_detail]);
			$cartValue->product_detail = $productDetail->data;
			$product = $productHandler->getProduct(['id_product' => $productDetail->data->id_product]);
			$cartValue->details = $calculator->getDetails($productDetail->data, $cartValue->qty);
			$cartValue->product = $product->data;

			//update//
			$cartValue->checkout = (isset($cartValue->checkout)) ? $cartValue->checkout : 1;
			$isCheckout = ($cartValue->checkout==1) ? true : false;
			$cartValue->note = (isset($cartValue->note)) ? $cartValue->note : null;
			$cartValue->store = (isset($cartValue->store)) ? $cartValue->store : null;

			//detail store if exist//
			$storeItem = null; $forGroup = 'online';
			if(isset($cartValue->store) && $cartValue->store && !empty($cartValue->store) && !is_null($cartValue->store)){
				$cekStore = $this->storeHandler->getStores(['id'=>$cartValue->store]);
				if(isset($cekStore->data) && !empty($cekStore->data)){
					$storeItem = $cekStore->data[0];
					$forGroup = $storeItem->id;
				}
			}
			$cartValue->store_detail = $storeItem;

			$forDetailItemGroup = $cartValue->details;
			$forDetailItemGroup->note = $cartValue->note;
			$forDetailItemGroup->checkout = $cartValue->checkout;
			$forDetailItemGroup->product_detail = $cartValue->product_detail;
			$forDetailItemGroup->product = $cartValue->product;

			$isDiscItem = 0;
			if($cartValue->details->discount_amount && !empty($cartValue->details->discount_amount) && !is_null($cartValue->details->discount_amount)){
				$isDiscItem = intval(abs($cartValue->details->discount_amount));
			}
			$forDetailGroup = array();
			$forDetailGroup['total_qty'] = $cartValue->details->qty;
			$forDetailGroup['total_weight'] = $cartValue->details->total_weight;
			$forDetailGroup['total_item_price'] = $cartValue->details->item_price;
			$forDetailGroup['subtotal_price'] = $cartValue->details->subtotal;
			$forDetailGroup['total_discount'] = $isDiscItem;
			$forDetailGroup['total_price'] = $cartValue->details->total;
			//$forDetailGroup['shipping_cost'] = 0;
			//$forDetailGroup['final_price'] = $cartValue->details->total;

			if(isset($groupStore[$forGroup])){
				$groupStore[$forGroup]['items'][] = $forDetailItemGroup;
				$groupStore[$forGroup]['detail']['total_qty'] += $forDetailGroup['total_qty'];
				$groupStore[$forGroup]['detail']['total_weight'] += $forDetailGroup['total_weight'];
				$groupStore[$forGroup]['detail']['total_item_price'] += $forDetailGroup['total_item_price'];
				$groupStore[$forGroup]['detail']['subtotal_price'] += $forDetailGroup['subtotal_price'];
				$groupStore[$forGroup]['detail']['total_discount'] += $forDetailGroup['total_discount'];
				$groupStore[$forGroup]['detail']['total_price'] += $forDetailGroup['total_price'];
				//$groupStore[$forGroup]['detail']['final_price'] += $forDetailGroup['final_price'];
			}else{
				$groupStore[$forGroup]['store'] = $storeItem;
				$groupStore[$forGroup]['items'] = array();
				$groupStore[$forGroup]['items'][] = $forDetailItemGroup;
				$groupStore[$forGroup]['detail'] = $forDetailGroup;
			}
			//$groupStore[$forGroup]['shipment'] = null;

			if($isCheckout){
				$countCheckout = $countCheckout+1;
				if(isset($groupCheckoutStore[$forGroup])){
					$groupCheckoutStore[$forGroup]['items'][] = $forDetailItemGroup;
					$groupCheckoutStore[$forGroup]['detail']['total_qty'] += $forDetailGroup['total_qty'];
					$groupCheckoutStore[$forGroup]['detail']['total_weight'] += $forDetailGroup['total_weight'];
					$groupCheckoutStore[$forGroup]['detail']['total_item_price'] += $forDetailGroup['total_item_price'];
					$groupCheckoutStore[$forGroup]['detail']['subtotal_price'] += $forDetailGroup['subtotal_price'];
					$groupCheckoutStore[$forGroup]['detail']['total_discount'] += $forDetailGroup['total_discount'];
					$groupCheckoutStore[$forGroup]['detail']['total_price'] += $forDetailGroup['total_price'];
					//$groupCheckoutStore[$forGroup]['detail']['final_price'] += $forDetailGroup['final_price'];
				}else{
					$groupCheckoutStore[$forGroup]['store'] = $storeItem;
					$groupCheckoutStore[$forGroup]['items'] = array();
					$groupCheckoutStore[$forGroup]['items'][] = $forDetailItemGroup;
					$groupCheckoutStore[$forGroup]['detail'] = $forDetailGroup;
				}
				//$groupCheckoutStore[$forGroup]['shipment'] = null;
			}

			$itemRelated = array(); $k_related = 0;
			foreach ($product->data->product_details as $isRelated) {
				if($isRelated->id_product_detail!=$cartValue->id_product_detail){
					$itemRelated[$k_related] = $isRelated;
					$k_related = $k_related+1;
				}
			}
			$cartValue->related = $itemRelated;
			$calculator->addProductDetail($productDetail->data, $cartValue->qty, $isCheckout);
		}

		$cart->total_product_details = count($cartValues);
		$cart->total_product_checkout = $countCheckout;
		$cart->details = $calculator->checkCart();

		$groupStore = array_values($groupStore);
		$cart->cart_by_store = $groupStore;
		$groupCheckoutStore = array_values($groupCheckoutStore);
		$cart->checkout_by_store = $groupCheckoutStore;
		//$cart->checkout_by_store = $this->getCartStoreCheckout($this->user['id']);

		$this->delivery->data = $cart;
		$this->setCalculatorResult($calculator);
		return $this->delivery;
	}

	public function setCartAdmin ($user, $cartValues = []) {
		$cart = new \stdClass;
		$cart->id = null;
		$cart->cart = $cartValues;
		$calculator = new Calculator($this->repository);
		if (!empty($user['referred_by_store_agent_id'])) {
			$calculator->setCalculateForReseller(true);
		}

		$productHandler = new ProductHandler($this->repository);
		$productHandler->setShowProductDetails(false);
		$productHandler->setShowProductRates(false);
		$productHandler->setShowProductRateImages(false);

		foreach ($cartValues as $cartValue) {
			$productDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $cartValue->id_product_detail]);
			$cartValue->product_detail = $productDetail->data;
			$product = $productHandler->getProduct(['id_product' => $productDetail->data->id_product]);
			$cartValue->details = $calculator->getDetails($productDetail->data, $cartValue->qty);
			$cartValue->product = $product->data;
			$calculator->addProductDetail($productDetail->data, $cartValue->qty);
		}
		$cart->total_product_details = count($cartValues);
		$cart->details = $calculator->checkCart();
		$this->delivery->data = $cart;
		$this->setCalculatorResult($calculator);
		return $this->delivery;
	}

	public function createCart ($payload) {
		$payload['user_id'] = $this->user['id'];

		$existsCart = $this->repository->findOne('carts', ['user_id' => $this->user['id']]);
		if (!empty($existsCart)) {
			$this->delivery->data = $existsCart;
		}

		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('carts', $payload);
		$result = $this->repository->findOne('carts', ['id' => $action]); 
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Update cart dalam db
	 **/
	public function updateCart ($payload, $filters = null) {
		$filters['user_id'] = $this->user['id'];
		$existsCarts = $this->repository->find('carts', $filters);
		if (empty($existsCarts)) {
			$this->delivery->addError(409, 'No carts found.');
			return $this->delivery;
		}

		if (isset($payload['cart'])) {
			$payload['cart'] = json_encode($payload['cart']);
		}

		unset($payload['user_id']);

		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('carts', $payload, $filters);
		$result = $this->getCart($filters);
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

	public function addToCart ($idProductDetail, $qty, $store = null) {
		if (empty($this->user)) {
			$this->delivery->addError(400, 'User is required');
		}

		$this->productDetailHandler->setUser($this->user);
		$productDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $idProductDetail])->data;
		if (empty($productDetail)) {
			$this->delivery->addError(400, 'Product detail not found');
			return $this->delivery;
		}

		if (empty($qty) || $qty < 0) {
			$this->delivery->addError(400, 'Qty is required');
		}

		if (intval($qty) > intval($productDetail->stock)) {
			$this->delivery->addError(400, 'Invalid quantity');
		}

		$storeId = null;
		if($store && !is_null($store)){
			$cekStockAtStore = $this->cekStockAtStore($store['id'], $productDetail, $qty);
			if(!$cekStockAtStore['success']){
				$this->delivery->addError(400, $cekStockAtStore['msg']);
			}
			$storeId = $store['id'];
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		try {
			$this->repository->beginTransaction();

			$cart = $this->getCart(['user_id' => $this->user['id']])->data;
			$currentCart = new \stdClass;
			$currentCart->id_product_detail = $productDetail->id_product_detail;
			$currentCart->qty = 0;
			$currentCart->note = null; //update//
			$currentCart->checkout = 1; //update//
			$currentCart->store = $storeId; //update//
			$cartValues = [
				$currentCart
			];
			if (!isset($cart->id)) {
				$action = $this->createCart(['cart' => json_encode($cartValues)]);
			} else {
				$cartValues = $cart->cart;
			}

			$existsProductDetail = false; $setCartValue = [];
			foreach ($cartValues as $cartValue) {
				$confirmProductDetail = ($cartValue->id_product_detail ==  $productDetail->id_product_detail) ? true : false;
				$confirmProductStore = true;
				if($storeId && !is_null($storeId)){
					if(isset($cartValue->store) && $cartValue->store!=$storeId){
						$confirmProductStore = false;
					}
				}

				if ($confirmProductDetail && $confirmProductStore) {
					$cartValue->qty = $cartValue->qty + $qty;
					if ($cartValue->qty > intval($productDetail->stock)) {
						throw new \Exception('Invalid quantity');
					}
					$existsProductDetail = true;

					$cartValue->store = ($storeId && !is_null($storeId)) ? $storeId : $cartValue->store; 
					if(isset($cartValue->store) && $cartValue->store && !empty($cartValue->store) && !is_null($cartValue->store)){
						$cekStockAtStore = $this->cekStockAtStore($cartValue->store, $productDetail, $cartValue->qty);
						if(!$cekStockAtStore['success']){
							throw new \Exception($cekStockAtStore['msg']);
						}
					}
				}
				$itemValue = array();
				$itemValue['id_product_detail'] = $cartValue->id_product_detail;
				$itemValue['qty'] = $cartValue->qty;
				$itemValue['note'] = (isset($cartValue->note)) ? $cartValue->note : '';
				$itemValue['checkout'] = (isset($cartValue->checkout)) ? $cartValue->checkout : 1;
				$itemValue['store'] = (isset($cartValue->store)) ? $cartValue->store : null;
				$setCartValue[] = $itemValue;
			}
			$cartValues = $setCartValue;

			if (!$existsProductDetail) {
				$cartValues[] = [
					'id_product_detail' => $productDetail->id_product_detail,
					'qty' => $qty,
					'note' => null, //update//
					'checkout' => 1, //update//
					'store' => $storeId, //update//
				];
			}

			$payload = [
				'cart' => $cartValues
			];

			$action = $this->updateCart($payload, ['user_id' => $this->user['id']]);
			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
			}
			if($storeId && !is_null($storeId)){
				$syncCart = $this->syncCartStore($this->user['id'], $storeId, $action->data);
			}
			//$action->data->checkout_by_store = $this->getCartStoreCheckout($this->user['id']);
			return $action;
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(400, $e->getMessage());
			return $this->delivery;
		}
	}

	/**
	 * Update product detail dalam cart
	 **/
	public function modifyCart ($idProductDetail, $qty, $type = 'qty', $params = [], $storeId = null) {
		$this->productDetailHandler->setUser($this->getUser());
		$productDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $idProductDetail])->data;
		if (empty($productDetail)) {
			$this->delivery->addError(400, 'Product detail not found');
		}

		if ($type=='qty' && $qty <= 0) {
			$this->delivery->addError(400, 'Qty is required');
		}

		//update//
		if($type=='checkout' && ($params['checkout']!='0' && $params['checkout']!='1')){
			$this->delivery->addError(400, 'Checkout status is required (Type)');
		}
		if($type=='store'){
			$cekStore = $this->storeHandler->getStores(['id'=>$params['store']]);
			if(!isset($cekStore->data) || empty($cekStore->data)){
				$this->delivery->addError(400, 'Store target not found');
			}
		}

		$cart = $this->getCart(['user_id' => $this->user['id']])->data;
		if (empty($cart)) {
			$this->delivery->addError(400, 'Cart not found');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$cartValues = $cart->cart;
		$existsProductDetail = false; $setCartValue = []; $foundStore = array();
		$cekStoreStock = ($type=='qty' || $type=='store') ? true : false;

		foreach ($cartValues as $cartValue) {
			$confirmProductDetail = ($cartValue->id_product_detail ==  $productDetail->id_product_detail) ? true : false;
			$confirmProductStore = true;
			if($storeId && !is_null($storeId)){
				if(isset($cartValue->store) && $cartValue->store!=$storeId){
					$confirmProductStore = false;
				}
			}

			if ($confirmProductDetail && $confirmProductStore) {
				$existsProductDetail = true;
				$foundStore['before'] = (isset($cartValue->store)) ? $cartValue->store : null;

				//update//
				if($type=='qty'){
					$cartValue->qty = $qty;
					if ($cartValue->qty > intval($productDetail->stock)) {
						$this->delivery->addError(400, 'Invalid quantity'); return $this->delivery;
					}
				}else{
					$cartValue->{$type} = ($type=='checkout') ? intval($params[$type]) : $params[$type];
				}

				if(isset($cartValue->store) && $cartValue->store && !empty($cartValue->store) && !is_null($cartValue->store)){
					if($cekStoreStock){
						$cekStockAtStore = $this->cekStockAtStore($cartValue->store, $productDetail, $cartValue->qty);
						if(!$cekStockAtStore['success']){
							$this->delivery->addError(400, $cekStockAtStore['msg']); return $this->delivery;
						}
					}
					if($cartValue->store!=$foundStore['before']){
						$foundStore['after'] = $cartValue->store;
					}
				}
			}
			$itemValue = array();
			$itemValue['id_product_detail'] = $cartValue->id_product_detail;
			$itemValue['qty'] = $cartValue->qty;
			$itemValue['note'] = (isset($cartValue->note)) ? $cartValue->note : '';
			$itemValue['checkout'] = (isset($cartValue->checkout)) ? $cartValue->checkout : 1;
			$itemValue['store'] = (isset($cartValue->store)) ? $cartValue->store : null;
			$setCartValue[] = $itemValue;
		}
		$cartValues = $setCartValue;

		if (!$existsProductDetail) {
			$this->delivery->addError(400, 'Add this product to the cart first');
			return $this->delivery;
		}

		$payloadData = [
			'cart' => $cartValues
		];
		$action = $this->updateCart($payloadData, ['id' => $cart->id]);
		if($foundStore && !is_null($foundStore)){
			foreach($foundStore as $fnStore){
				$syncCart = $this->syncCartStore($this->user['id'], $fnStore, $action->data);
			}
		}
		$action->data->checkout_by_store = $this->getCartStoreCheckout($this->user['id']);

		$this->delivery->data = $action->data;
		return $this->delivery;		
	}

	public function removeFromCart ($idProductDetail, $storeProduct = null) {
			if(!$idProductDetail || empty($idProductDetail) || is_null($idProductDetail)){
				$this->delivery->addError(400, 'ID product detail is required'); return $this->delivery;
			}

			$cart = $this->getCart(['user_id' => $this->user['id']])->data;
			if (empty($cart)) {
				$this->delivery->addError(400, 'Cart not found'); return $this->delivery;
			}
			$cartValues = $cart->cart;

			$delAll = ($idProductDetail=='all') ? true : false;
			$delMulti = explode(',', $idProductDetail);
			$delMultiStore = [];
			if($storeProduct && !is_null($storeProduct) && !empty($storeProduct)){
				$delMultiStore = explode(',', $storeProduct);
			}
			$newCartValues = [];

			//$productDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $delItem])->data;
			//if (empty($productDetail)) {
				//$this->delivery->addError(400, 'Product detail not found (item number '.$noItem.')'); return $this->delivery;
			//}
			$forSyncStore = []; $foundProductStore = 0;
			if(!$delAll){
				foreach ($cartValues as $key => $cartValue) {
					$confirmProductDetail = (in_array($cartValue->id_product_detail, $delMulti)) ? true : false;
					$confirmProductStore = true; $readyCartStore = false;
					if(isset($cartValue->store) && $cartValue->store && !empty($cartValue->store) && !is_null($cartValue->store)){
						$confirmProductStore = (in_array($cartValue->store, $delMultiStore)) ? true : false;
						$readyCartStore = true;
					}
					if($confirmProductDetail && $confirmProductStore){
						if($readyCartStore && !isset($forSyncStore[$cartValue->store])){
							$forSyncStore[] = $cartValue->store;
						}
						$foundProductStore = $foundProductStore+1;
					}else{
						$itemValue = array();
						$itemValue['id_product_detail'] = $cartValue->id_product_detail;
						$itemValue['qty'] = $cartValue->qty;
						$itemValue['note'] = (isset($cartValue->note)) ? $cartValue->note : '';
						$itemValue['checkout'] = (isset($cartValue->checkout)) ? $cartValue->checkout : 1;
						$itemValue['store'] = (isset($cartValue->store)) ? $cartValue->store : null;
						$newCartValues[] = $itemValue;
					}
				}
			}
			if($foundProductStore==0){
				$this->delivery->addError(400, 'No matching products found to delete'); return $this->delivery;
			}

			$payloadData = [
				'cart' => $newCartValues
			];
			$action = $this->updateCart($payloadData, ['id' => $cart->id]);

			if(!$newCartValues || empty($newCartValues) || is_null($newCartValues)){
				$syncCart = $this->syncCartStore($this->user['id'], null, $action->data);
			}else{
				foreach($forSyncStore as $syncStore){
					$syncCart = $this->syncCartStore($this->user['id'], $syncStore, $action->data);
				}
			}
			$action->data->checkout_by_store = $this->getCartStoreCheckout($this->user['id']);

			$this->delivery->data = $action->data;
			return $this->delivery;
	}

	public function modifyAllCart ($type = 'qty', $params = [], $storeId = null) {

		//the feature is currently only for checkout status
		if($type!='checkout'){
			$this->delivery->addError(400, 'The feature is currently only for checkout status'); return $this->delivery;
		}

		if ($type=='qty' && $params['qty'] <= 0) {
			$this->delivery->addError(400, 'Qty is required'); return $this->delivery;
		}

		if($type=='checkout' && ($params['checkout']!='0' && $params['checkout']!='1')){
			$this->delivery->addError(400, 'Checkout status is required (Type)'); return $this->delivery;
		}

		$cart = $this->getCart(['user_id' => $this->user['id']])->data;
		if (empty($cart)) {
			$this->delivery->addError(400, 'Cart not found'); return $this->delivery;
		}

		$cartValues = $cart->cart; $setCartValue = [];
		$existsProductDetail = array(); $keyExist = 0;
		$notExistsProductDetail = array(); $keyNoExist = 0;
		$this->productDetailHandler->setUser($this->getUser());
		foreach ($cartValues as $cartValue) {
			$productDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $cartValue->id_product_detail])->data;
			if($productDetail && !empty($productDetail)){
				if($type=='qty'){
					$cartValue->qty = $params['qty'];
					if ($cartValue->qty > intval($productDetail->stock)) {
						$this->delivery->addError(400, ['msg'=>'Invalid quantity at stock', 'data'=>$cartValue, 'product'=>$productDetail]); return $this->delivery;
					}
				}else{
					$cartValue->{$type} = ($type=='checkout') ? intval($params[$type]) : $params[$type];
				}
				$existsProductDetail[$keyExist] = $cartValue; $keyExist = $keyExist+1;
			}else{
				$notExistsProductDetail[$keyNoExist] = $cartValue; $keyNoExist = $keyNoExist+1;
			}

			$itemValue = array();
			$itemValue['id_product_detail'] = $cartValue->id_product_detail;
			$itemValue['qty'] = $cartValue->qty;
			$itemValue['note'] = (isset($cartValue->note)) ? $cartValue->note : '';
			$itemValue['checkout'] = (isset($cartValue->checkout)) ? $cartValue->checkout : 1;
			$itemValue['store'] = (isset($cartValue->store)) ? $cartValue->store : null;
			$setCartValue[] = $itemValue;
		}
		$cartValues = $setCartValue;

		if (!$existsProductDetail || is_null($existsProductDetail)) {
			$this->delivery->addError(400, 'All products in cart are not available'); return $this->delivery;
		}
		if($notExistsProductDetail && !is_null($notExistsProductDetail)){
			$this->delivery->addError(400, ['msg'=>'Some products in cart are not available','ready'=>$existsProductDetail,'not_ready'=>$notExistsProductDetail]);
			return $this->delivery;
		}

		$action = $this->updateCart(['cart' => $cartValues], ['id' => $cart->id]);

		$isCart = $action->data;
		$cartCheckout = (isset($isCart->checkout_by_store) && $isCart->checkout_by_store && !empty($isCart->checkout_by_store)) ? $isCart->checkout_by_store : null;
		if(!$cartCheckout || is_null($cartCheckout)){
			$syncCart = $this->syncCartStore($this->user['id'], null, $action->data);
		}else{
			foreach($cartCheckout as $crtCheck){
				if($crtCheck['store'] && !empty($crtCheck['store']) && !is_null($crtCheck['store'])){
					$syncCart = $this->syncCartStore($this->user['id'], $crtCheck['store']->id, $action->data);
				}
			}
		}

		$action->data->checkout_by_store = $this->getCartStoreCheckout($this->user['id']);
		$this->delivery->data = $action->data;
		return $this->delivery;		
	}

	public function cekStockAtStore($storeId, $productDetail, $qty){
		if(!$storeId || is_null($storeId)){
			return array('success'=>false, 'msg'=>'Store ID is required for check stock', 'data'=>null);
		}
		
		$status = true; $msgStatus = 'Stock is ready';
		$skuDetail = $productDetail->sku_code;
		$filterStoreStock = [
			'stores.id'=>$storeId,
			'store_product_detail_stocks.sku_code'=>$skuDetail,
			'store_product_detail_stocks.stock !='=>NULL,
			'store_product_detail_stocks.stock >'=>0,
			'store_product_detail_stocks.deleted_at'=>NULL
		];
		$joinStock = [
			'stores' => [
				'type' => 'left',
				'value' => 'stores.code = store_product_detail_stocks.store_code'
			]
		];
		$selectStock = [
			'stores.id as store_id',
			'stores.code as store_code',
			'store_product_detail_stocks.sku_code as sku_code',
			'store_product_detail_stocks.stock',
			'store_product_detail_stocks.deleted_at',
		];
		$existStock = $this->repository->findOne('store_product_detail_stocks', $filterStoreStock, null, $joinStock, $selectStock);
		if(!$existStock || is_null($existStock)){
			$status = false; $msgStatus = 'Stock in store is not available.';
		}else{
			$stockAtStore = intval($existStock->stock);
			if($stockAtStore < intval($qty)){
				$status = false; $msgStatus = 'Invalid quantity, stock in store available '.$stockAtStore;
			}
		}
		return array('success'=>$status, 'msg'=>$msgStatus, 'data'=>$existStock);
	}

	public function syncCartStore($userId, $storeId, $isCart, $payload = []){
		$cartCheckout = (isset($isCart->checkout_by_store) && $isCart->checkout_by_store && !empty($isCart->checkout_by_store)) ? $isCart->checkout_by_store : null;

		$currentDate = date('Y-m-d H:i:s');
        $upData = array('updated_at'=>$currentDate);
        $upData['crst_store_detail'] = null;
        $upData['crst_address'] = null;
        $upData['crst_address_detail'] = null;
        $upData['crst_qty'] = null;
        $upData['crst_weight'] = null;
        $upData['crst_subtotal'] = null;
        $upData['crst_discount'] = null;
        $upData['crst_total'] = null;
        $upData['crst_item'] = null;
        $upData['crst_shipping'] = null;
        $upData['crst_shipment'] = null;

        if(!$cartCheckout || is_null($cartCheckout)){
        	$upData['deleted_at'] = $currentDate;
			$upCart = $this->repository->update('cart_stores', $upData, ['crst_user'=>$userId,'crst_order'=>NULL,'crst_status'=>0]);
			return null;
		}else{
			$filterCart = array('crst_user'=>$userId,'crst_order'=>NULL,'crst_store'=>$storeId,'crst_status'=>0);
			$cekExistCart = $this->repository->findOne('cart_stores', $filterCart);
			$existCart = ($cekExistCart && !is_null($cekExistCart)) ? $cekExistCart : false;
			
			$foundStore = false;
			foreach($cartCheckout as $crCheck){
	            if($crCheck['store'] && !empty($crCheck['store']) && !is_null($crCheck['store'])){
	                if($crCheck['store']->id==$storeId){
	                	$foundStore = $crCheck; break;
	                }
	            }
	        }

	        if($foundStore){
	        	$isStore = $foundStore['store'];
		        $addressId = null; $addressDetail = null;
		        if($existCart){
		        	$addressId = $existCart->crst_address;
		        	$addressDetail = $existCart->crst_address_detail;
		        }

		        $upData['crst_user'] = $userId;
		        $upData['crst_store'] = $storeId;
	            $upData['crst_store_detail'] = json_encode($isStore, true);
	            $upData['crst_address'] = $addressId;
	            $upData['crst_address_detail'] = $addressDetail;
	            $upData['crst_qty'] = $foundStore['detail']['total_qty'];
	            $upData['crst_weight'] = $foundStore['detail']['total_weight'];
	            $upData['crst_subtotal'] = $foundStore['detail']['subtotal_price'];
	            $upData['crst_discount'] = $foundStore['detail']['total_discount'];
	            $upData['crst_total'] = $foundStore['detail']['total_price'];

	            $forItem = array();
	            foreach($foundStore['items'] as $kItem=>$item){
	                $detailItem = $item->product_detail;
	                $forItem[$kItem]['id_product'] = $detailItem->id_product;
	                $forItem[$kItem]['id_product_detail'] = $detailItem->id_product_detail;
	                $forItem[$kItem]['qty'] = $item->qty;
	                $forItem[$kItem]['weight'] = $item->total_weight;
	                $forItem[$kItem]['price'] = $item->item_price;
	                $forItem[$kItem]['subtotal'] = $item->subtotal;
	                $isDiscItem = 0;
	                if($item->discount_amount && !empty($item->discount_amount) && !is_null($item->discount_amount)){
	                    $isDiscItem = intval(abs($item->discount_amount));
	                }
	                $forItem[$kItem]['discount'] = $isDiscItem;
	                $forItem[$kItem]['total'] = $item->total;
	            }
	            $upData['crst_item'] = json_encode($forItem, true);

	            $upData['deleted_at'] = null;
	            if($existCart){
	                $upCart = $this->repository->update('cart_stores', $upData, ['crst_id'=>$existCart->crst_id]);
	            }else{
	                $upData['created_at'] = $currentDate;
	                $upCart =  $this->repository->insert('cart_stores', $upData);
	            }

	            $filterCart['deleted_at'] = NULL;
	            $cartStore = $this->repository->findOne('cart_stores', $filterCart);
				return $cartStore;	
	        }else{
	        	if($existCart){
	        		$upData['deleted_at'] = $currentDate;
	        		$upCart = $this->repository->update('cart_stores', $upData, ['crst_id'=>$existCart->crst_id]);
	        	}
	        	return null;
	        }
		}
	}

	public function getCartStoreCheckout($userId, $payload = []){
		$filterCartStore = array('user_id'=>$userId,'order_code'=>NULL,'status'=>0);
		if(isset($payload['address_id'])){
			$filterCartStore['address_id'] = $payload['address_id'];
		}
		return $this->getCartStore($filterCartStore, 'checkout');
	}

	public function getCartStore($payload, $action){
		$filterCart = array('deleted_at'=>NULL);
		if(isset($payload['user_id'])){
			$filterCart['crst_user'] = $payload['user_id'];
		}
		if(isset($payload['store_id'])){
			$filterCart['crst_store'] = $payload['store_id'];
		}
		if(isset($payload['order_code'])){
			$filterCart['crst_order'] = $payload['order_code'];
		}
		if(isset($payload['status'])){
			$filterCart['crst_status'] = $payload['status'];
		}

		$productHandler = new ProductHandler($this->repository);
		$productHandler->setShowProductDetails(true);
		$productHandler->setShowProductRates(false);
		$productHandler->setShowProductRateImages(false);
		$calculator = new Calculator($this->repository);

		$existAddressShipment = null;
		if(isset($payload['address_id']) && $payload['address_id'] && !empty($payload['address_id']) && !is_null($payload['address_id'])){
			$existAddressShipment = $payload['address_id'];
		}

		if($action=='checkout'){
			$existCart = $this->repository->find('cart_stores', $filterCart, null, null);
			if(!$existCart || is_null($existCart)){
				return null;
			}
			$results = array();
			foreach($existCart as $k_cart=>$exCart){
				$storeDetail = null;
				if($exCart->crst_store_detail && !empty($exCart->crst_store_detail) && !is_null($exCart->crst_store_detail)){
					$storeDetail = json_decode($exCart->crst_store_detail, true);
					$locationStore = $storeDetail['location'];
					if(!$locationStore || empty($locationStore) || is_null($locationStore) || !isset($locationStore['origin']) || empty($locationStore['origin']) || is_null($locationStore['origin'])){
						$cekLocation = $this->storeHandler->handleLocationStore((object)$storeDetail);
						if($cekLocation && !is_null($cekLocation)){
							$storeDetail['location'] = json_decode(json_encode($cekLocation), true);
						}
					}
				}
				$cartItem = null;
				if($exCart->crst_item && !empty($exCart->crst_item) && !is_null($exCart)){
					$thisCartItem = json_decode($exCart->crst_item, true);
					$cartItem = new \stdClass;
					foreach($thisCartItem as $k_item=>$item){
						$product = $productHandler->getProduct(['id_product' => $item['id_product']]);
						$productDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $item['id_product_detail']]);
						$cartItem->$k_item = $calculator->getDetails($productDetail->data, $item['qty']);
						$cartItem->$k_item->product_detail = $productDetail->data;
						$cartItem->$k_item->product = $product->data;
					}
				}
				$results[$k_cart]['store'] = $storeDetail;
				$results[$k_cart]['items'] = $cartItem;
				$results[$k_cart]['detail'] = array();
				$results[$k_cart]['detail']['total_qty'] = $exCart->crst_qty;
				$results[$k_cart]['detail']['total_weight'] = $exCart->crst_weight;
				$results[$k_cart]['detail']['subtotal_price'] = $exCart->crst_subtotal;
				$results[$k_cart]['detail']['total_discount'] = $exCart->crst_discount;
				$results[$k_cart]['detail']['total_price'] = $exCart->crst_total;
				$shippingCost = ($exCart->crst_shipping && !empty($exCart->crst_shipping) && !is_null($exCart->crst_shipping)) ? intval($exCart->crst_shipping) : 0;
				$results[$k_cart]['detail']['shipping_cost'] = $shippingCost;
				$results[$k_cart]['detail']['final_price'] = intval($exCart->crst_total) + intval($shippingCost);

				$shipmentCart = null;
				if($exCart->crst_shipment && !empty($exCart->crst_shipment) && !is_null($exCart->crst_shipment)){
					$shipmentCart = json_decode($exCart->crst_shipment, true);
				}
				if(!$shipmentCart || is_null($shipmentCart)){
					$existAddressAutoShipment = ($existAddressShipment && !is_null($existAddressShipment)) ? $existAddressShipment : $exCart->crst_address;
					$shipmentCart = $this->getCartStoreShipmentOption(['user_id'=>$payload['user_id'],'address_id'=>$existAddressAutoShipment,'weight'=>$exCart->crst_weight], $storeDetail);
				}
				$results[$k_cart]['shipment'] = $shipmentCart;
			}
			return $results;die;
		}

		return null;
	}

	public function getCartStoreShipmentOption($payload = [], $storeDetail = []){
		if(!isset($payload['user_id']) || !$payload['user_id'] || empty($payload['user_id']) || is_null($payload['user_id'])){
			return null;die;
		}
		$userId = $payload['user_id'];

		$filterAddress = ['user_id'=>$userId,'deleted_at'=>NULL];
		if(isset($payload['address_id']) && $payload['address_id'] && !empty($payload['address_id']) && !is_null($payload['address_id'])){
			$filterAddress['id'] = $payload['address_id'];
		}
		$address = $this->repository->findOne('user_address', $filterAddress, null, null, null, 'main_address');
		if(!$address || is_null($address)){
			return null;die;
		}

		$setDestination = null;
        if($address->sub_district_id && !empty($address->sub_district_id) && !is_null($address->sub_district_id)){
            $cekDesti = $this->shippingLocationHandler->destinationFromSubdistrict($address->sub_district_id);
            $setDestination = $cekDesti->data;
        }
        if(!$setDestination || is_null($setDestination)){
            if($address->districts_id && !empty($address->districts_id) && !is_null($address->districts_id)){
                $cekDesti = $this->shippingLocationHandler->originDestiFromDistrict('destination', $address->districts_id);
                $setDestination = $cekDesti->data;
            }
        }
        if(!$setDestination || empty($setDestination) || is_null($setDestination)){
           	return null;die;
        }
        $destinationData = $setDestination[0];

        if(!isset($storeDetail['location']) || !$storeDetail['location'] || empty($storeDetail['location']) || is_null($storeDetail['location'])){
            return null;die;
        }
        $storeLocation = $storeDetail['location'];
        if(!isset($storeLocation['origin']) || !$storeLocation['origin'] || empty($storeLocation['origin']) || is_null($storeLocation['origin'])){
            return null;die;
        }
        $originData = $storeLocation['origin'][0];

        $isWeight = (isset($payload['weight']) && $payload['weight'] && !empty($payload['weight']) && !is_null($payload['weight'])) ? $payload['weight'] : 1000;
        $jneService = new JNEService();
        $jneService->setEnv('production');

        $setWeight = intval($isWeight/1000);
        $ongkir = $jneService->getTariff($originData['city_code'], $destinationData->city_code, $setWeight);

        $foundShippingJne = true;
        if(isset($ongkir['error']) && !empty($ongkir['error'])){
            $foundShippingJne = false;
        }

        if($foundShippingJne && isset($ongkir['price']) && !empty($ongkir['price']) && !is_null($ongkir['price'])){
        	$serviceOptions = $ongkir['price'];
        }else{
        	$serviceOptions = $this->shippingPriceHandler->getOngkirRabbani((Array)$destinationData, $originData, $setWeight);
        }

        $dataShipment = array();
        $dataShipment['destination'] = $destinationData;
        $dataShipment['origin'] = $originData;
        $dataShipment['service'] = null;
        $dataShipment['service_options'] = $serviceOptions;
        return $dataShipment;

	}


//====================================== END LINE ======================================//
}