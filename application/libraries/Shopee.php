<?php

use GuzzleHttp\Client;

class Shopee {

	private $instance;
	private $baseUrl;
	private $key;
	private $partnerId;
	private $shopId;
	private $client;

	const TYPE_KEY = 'SHOPEE_KEY';
	const TYPE_SHOP_ID = 'SHOPEE_SHOP_ID';
	const TYPE_PARTNER_ID = 'SHOPEE_PARTNER_ID';
	const TYPE_BASE_URL = 'SHOPEE_BASE_URL';

	public function __construct () {

		$this->instance = &get_instance();
		$this->instance->load->model('MainModel');

	}

	public function getShopInfo () {
		$this->authenticate();
    	$url = '/api/v1/shop/get';

    	$resp = $this->httpRequest('POST', $url);
    	return $resp;
    }

    public function getShopCategories ( $paginationOffset = 0, $paginationEntriesPerPage = 100) {
    	$this->authenticate();
    	$url = '/api/v1/shop_categorys/get';

    	$bodyReq['pagination_offset'] = $paginationOffset;
    	$bodyReq['pagination_entries_per_page'] = $paginationEntriesPerPage;

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }

    public function addShopCategory ( $name) {
    	$this->authenticate();
    	$url = '/api/v1/shop_category/add';

    	$bodyReq = [
    		'name' => $name
    	];

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }

    public function uploadImg ( $images) {
    	$this->authenticate();
    	$url = '/api/v1/image/upload';

    	$bodyReq = [
    		'images' => $images
    	];

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }

    public function getCategoriesByCountry ( $language) {
    	$this->authenticate();
    	$url = '/api/v1/item/categories/get_by_country';

    	$bodyReq = [
    		'country' => 'ID',
    		'is_cb' => 0,
    		'language' => $language
    	];

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }

    public function getAttributes ( $categoryId, $language) {
    	$this->authenticate();
    	$url = '/api/v1/item/attributes/get';

    	$bodyReq = [
    		'category_id' => (int)$categoryId,
    		'language' => $language
    	];
    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }

    public function getLogistics () {
    	$this->authenticate();
    	$url = '/api/v1/logistics/channel/get';

    	$resp = $this->httpRequest('POST', $url);
    	return $resp;
    }

    public function addItem ( $categoryId, $name, $description, $price, $stock, $itemSku, $images, $attributes, $logistics, $weight, $packageLength, $packageWidth, $packageHeight, $daysToShip, $wholesales, $sizeChart, $condition, $status, $isPreOrder) {
    	$this->authenticate();
    	$url = '/api/v1/item/add';

    	for ($i = 0; $i < count($logistics); $i++) {
    		$logistics[$i]['logistic_id'] = (int)$logistics[$i]['logistic_id'];
    		if ($logistics[$i]['enabled'] == 'true') {
    			$logistics[$i]['enabled'] = true;
    		}
    	}

    	for ($i = 0; $i < count($attributes); $i++) {
    		$attributes[$i]['attributes_id'] = (int)$attributes[$i]['attributes_id'];
    	}

    	$isPreOrder = ($isPreOrder == 'true') ? true : false;

    	$bodyReq = [
    		'category_id' => (int)$categoryId,
    		'name' => $name,
    		'description' => $description,
    		'price' => (int)$price,
    		'stock' => (int)$stock,
    		'item_sku' => $itemSku,
    		'images' => $images,
    		'attributes' => $attributes,
    		'logistics' => $logistics,
    		'weight' => (int)$weight,
    		'size_chart' => $sizeChart,
    		'condition' => $condition,
    		'is_pre_order' => $isPreOrder
    	];

    	if (!empty($packageLength)) {
    		$bodyReq['package_length'] = (int)$packageLength;
    	}

    	if (!empty($packageHeight)) {
    		$bodyReq['package_height'] = (int)$packageHeight;
    	}

    	if (!empty($status)) {
    		$bodyReq['status'] = $status;
    	}

    	if (!empty($daysToShip)) {
    		$bodyReq['days_to_ship'] = (int)$daysToShip;
    	}

    	if (!empty($wholesales)) {
    		for ($i = 0; $i < count($wholesales); $i++) {
	    		$wholesales[$i]['min'] = (int)$wholesales[$i]['min'];
	    		$wholesales[$i]['max'] = (int)$wholesales[$i]['max'];
	    		$wholesales[$i]['unit_price'] = (int)$wholesales[$i]['unit_price'];
	    	}
    		$bodyReq['wholesales'] = $wholesales;
    	}

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }

    public function updateStock ( $itemId, $stock) {
    	$this->authenticate();
    	$url = '/api/v1/items/update_stock';

    	$bodyReq = [
    		'item_id' => (int)$itemId,
    		'stock' => (int)$stock
    	];

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }

    public function getOrdersList ( $createTimeFrom, $createTimeTo, $updateTimeFrom, $updateTimeTo, $paginationEntriesPerPage, $paginationOffset) {
    	$this->authenticate();
    	$url = '/api/v1/orders/basics';

    	$bodyReq = [];
    	if (!empty($createTimeFrom)) {
    		$bodyReq['create_time_from'] = (int)$createTimeFrom;
    	}
    	if (!empty($createTimeTo)) {
    		$bodyReq['create_time_to'] = (int)$createTimeTo;
    	}
    	if (!empty($updateTimeFrom)) {
    		$bodyReq['update_time_from'] = (int)$updateTimeFrom;
    	}
    	if (!empty($updateTimeTo)) {
    		$bodyReq['update_time_to'] = (int)$updateTimeTo;
    	}
    	if (!empty($paginationEntriesPerPage)) {
    		$bodyReq['pagination_entries_per_page'] = (int)$paginationEntriesPerPage;
    	}
    	if (!empty($paginationOffset)) {
    		$bodyReq['pagination_offset'] = (int)$paginationOffset;
    	}

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;

    }

    public function getOrdersByStatus ( $orderStatus, $createTimeFrom, $createTimeTo, $updateTimeFrom, $updateTimeTo, $paginationEntriesPerPage, $paginationOffset) {
    	$this->authenticate();
    	$url = '/api/v1/orders/get';

    	$bodyReq = [
    		'order_status' => $orderStatus
    	];

    	if (!empty($createTimeFrom)) {
    		$bodyReq['create_time_from'] = (int)$createTimeFrom;
    	}
    	if (!empty($createTimeTo)) {
    		$bodyReq['create_time_to'] = (int)$createTimeTo;
    	}
    	if (!empty($updateTimeFrom)) {
    		$bodyReq['update_time_from'] = (int)$updateTimeFrom;
    	}
    	if (!empty($updateTimeTo)) {
    		$bodyReq['update_time_to'] = (int)$updateTimeTo;
    	}
    	if (!empty($paginationEntriesPerPage)) {
    		$bodyReq['pagination_entries_per_page'] = (int)$paginationEntriesPerPage;
    	}
    	if (!empty($paginationOffset)) {
    		$bodyReq['pagination_offset'] = (int)$paginationOffset;
    	}

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;

    }

    public function getOrderDetails ( $ordersnList) {
    	$this->authenticate();
		$url = '/api/v1/orders/detail';

    	$bodyReq = [
    		'ordersn_list' => $ordersnList
    	];

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }

    public function getOrderLogistics ( $ordersn) {
    	$this->authenticate();
    	$url = '/api/v1/logistics/order/get';

    	$bodyReq = [
    		'ordersn' => $ordersn
    	];

    	$resp = $this->httpRequest('POST', $url, $bodyReq);
    	return $resp;
    }


    /**************************************************************************/

    public function httpRequest($method, $url, $bodyReq = null) {
		$resp = null;

		$bodyReq['partner_id'] = (int)$this->partnerId;
		$bodyReq['shopid'] = (int)$this->shopId;
		$bodyReq['timestamp'] = time();

		try {
			$response = $this->client->request($method, $url, [
				'headers' => [
					'Authorization' => $this->generateSignature($url, $bodyReq)
				],
				'json' => $bodyReq
			]);

			$resp = $response->getBody();
			$resp = json_decode($resp);
		} catch (\Exception $e) {
			$resp = $e->getResponse()->getBody(true);
			$resp = json_decode($resp);
		}

		return $resp;
	}


	public function authenticate () {
        $this->key = '';
		$this->shopId = '';
		$this->partnerId = '';
		$this->baseUrl = '';
		$this->client = new Client([
            'base_uri' => $this->baseUrl,
        ]);
	}

	public function generateSignature ($url, $body) {
		return hash_hmac('sha256', $this->baseUrl.$url.'|'.json_encode($body), $this->key);
	}
}