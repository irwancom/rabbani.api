<?php

use GuzzleHttp\Client;

class Shipper {

	private $instance;
	private $client;
	private $apiKey;
	private $baseUrl;

	const TYPE_API_KEY = 'SHIPPER_API_KEY';
	const TYPE_BASE_URL = 'SHIPPER_BASE_URL';

	public function __construct () {

		$this->instance = &get_instance();
		$this->instance->load->model('MainModel');

	}

	public function getCountries () {
		$this->authenticate();
		$resp = null;
		$resp = $this->httpRequest('GET', '/public/v1/countries');
        return $resp;
	}

	public function getProvinces () {
		$this->authenticate();
		$resp = null;
		$resp = $this->httpRequest('GET', '/public/v1/provinces');
		return $resp;
	}

	public function getCities ( $origin, $province = 0) {
		$this->authenticate();
		$resp = null;
		$queryParams = [];

		if (!empty($origin)) {
			$queryParams['origin'] = 'all';
		} else {
			$queryParams['province'] = $province;
		}

		$resp = $this->httpRequest('GET', '/public/v1/cities', $queryParams);
		return $resp;
	}

	public function getSuburbs ( $city) {
		$this->authenticate();
		$resp = null;
		$queryParams['city'] = $city;

		$resp = $this->httpRequest('GET', '/public/v1/suburbs', $queryParams);
		return $resp;
	}

	public function getAreas ( $suburb) {
		$this->authenticate();
		$resp = null;
		$queryParams['suburb'] = $suburb;

		$resp = $this->httpRequest('GET', '/public/v1/areas', $queryParams);
		return $resp;
	}

	public function searchAllLocation ( $substring) {
		$this->authenticate();
		$resp = null;
		
		$resp = $this->httpRequest('GET', '/public/v1/details/'.$substring);
		return $resp;	
	}

	public function getDomesticRates ( $o, $d, $l, $w, $h, $wt, $v, $type = 0, $cod = 0, $order = 0, $originCoord = null, $destinationCoord = null) {
		$this->authenticate();
		$resp = null;

		$queryParams['o'] = $o;
		$queryParams['d'] = $d;
		$queryParams['l'] = $l;
		$queryParams['w'] = $w;
		$queryParams['h'] = $h;
		$queryParams['wt'] = $wt;
		$queryParams['v'] = $v;

		if (!empty($type)) {
			$queryParams['type'] = $type;
		}
		if (!empty($cod)) {
			$queryParams['cod'] = $cod;
		}
		if (!empty($order)) {
			$queryParams['order'] = $order;
		}
		if (!empty($originCoord)) {
			$queryParams['originCoord'] = $originCoord;
		}
		if (!empty($destinationCoord)) {
			$queryParams['destinationCoord'] = $destinationCoord;
		}

		$resp = $this->httpRequest('GET', '/public/v1/domesticRates', $queryParams);
		return $resp;
	}

	public function domesticOrderCreation ( $o, $d, $l, $w, $h, $wt, $v, $rateId, $consigneeName = '', $consigneePhoneNumber = '', $consignerName = '', $consignerPhoneNumber = '', $originAddress, $originDirection, $destinationAddress, $destinationDirection, $items = array(), $contents, $useInsurance = 0, $externalId = null, $paymentType = null, $packageType, $cod = 0, $originCoord = null, $destinationCoord = null) {
		$this->authenticate();
		$resp = null;

		$bodyReq['o'] = $o;
		$bodyReq['d'] = $d;
		$bodyReq['l'] = $l;
		$bodyReq['w'] = $w;
		$bodyReq['h'] = $h;
		$bodyReq['wt'] = $wt;
		$bodyReq['v'] = $v;
		$bodyReq['rateID'] = $rateId;
		$bodyReq['consigneeName'] = $consigneeName;
		$bodyReq['consigneePhoneNumber'] = $consigneePhoneNumber;
		$bodyReq['consignerName'] = $consignerName;
		$bodyReq['consignerPhoneNumber'] = $consignerPhoneNumber;
		$bodyReq['originAddress'] = $originAddress;
		$bodyReq['originDirection'] = $originDirection;
		$bodyReq['destinationAddress'] = $destinationAddress;
		$bodyReq['destinationDirection'] = $destinationDirection;
		$bodyReq['itemName'] = $items;
		$bodyReq['contents'] = 'Send to API';
		$bodyReq['useInsurance'] = $useInsurance;
		$bodyReq['externalId'] = $externalId;
		$bodyReq['paymentType'] = $paymentType;
		$bodyReq['packageType'] = $packageType;
		$bodyReq['cod'] = $cod;
		$bodyReq['originCoord'] = $originCoord;
		$bodyReq['destinationCoord'] = $destinationCoord;

		$resp = $this->httpRequest('POST', '/public/v1/orders/domestics', [], $bodyReq);
		return $resp;
	}

	public function getTrackingId ( $id) {
		$this->authenticate();
		$resp = null;
		$queryParams['id'] = $id;

		$resp = $this->httpRequest('GET', '/public/v1/orders', $queryParams);
		return $resp;
	}

	public function orderActivation ( $orderId, $active, $agentId) {
		$this->authenticate();
		$resp = null;
		$reqBody = [
			'active' => $active,
			'agentId' => $agentId
		];

		$resp = $this->httpRequest('PUT', '/public/v1/activations/' . $orderId, [], $reqBody);
		return $resp;
	}

	public function orderDetail ( $orderId) {
		$this->authenticate();
		$resp = null;
		$resp = $this->httpRequest('GET', '/public/v1/orders/' . $orderId);
		return $resp;
	}

	public function orderUpdate ( $orderId, $l, $w, $h, $wt) {
		$this->authenticate();
		$resp = null;
		$reqBody = [
			'l' => $l,
			'w' => $w,
			'h' => $h,
			'wt' => $wt
		];

		$resp = $this->httpRequest('PUT', '/public/v1/orders/' . $orderId, [] ,$reqBody);
		return $resp;
	}

	public function orderCancellation ( $orderId) {
		$this->authenticate();
		$resp = null;
		$resp = $this->httpRequest('PUT', '/public/v1/orders/' . $orderId .'/cancel');
		return $resp;
	}

	public function pickupRequest ( array $orderIds = array(), $datePickup, $agentId) {
		$this->authenticate();
		$resp = null;
		$bodyReq = [
			'orderIds' => $orderIds,
			'datePickup' => $datePickup,
			'agentId' => $agentId
		];
		
		$resp = $this->httpRequest('POST', '/public/v1/pickup', [], $bodyReq);
		return $resp;
	}

	public function cancelPickup ( $orderIds = array()) {
		$this->authenticate();
		$resp = null;
		$bodyReq = [
			'orderIds' => $orderIds
		];

		$resp = $this->httpRequest('PUT', '/public/v1/pickup/cancel', [], $bodyReq);
		return $resp;
	}

	public function getAgentsBySuburb ( $suburbId) {
		$this->authenticate();
		$resp = null;
		$queryParams['suburbId'] = $suburbId;

		$resp = $this->httpRequest('GET', '/public/v1/agents', $queryParams);
		return $resp;
	}

	public function getAllTrackingStatus () {
		$this->authenticate();
		$resp = null;
		$resp = $this->httpRequest('GET', '/public/v1/logistics/status');
		return $resp;
	}

	public function generateAwbNumber ( $eid, $oid) {
		$this->authenticate();
		$resp = null;
		$queryParams['eid'] = $eid;
		$queryParams['oid'] = $oid;

		$resp = $this->httpRequest('GET', '/public/v1/awbs/generate', $queryParams);
		return $resp;
	}

	/*************************/

	public function httpRequest($method, $url, $queryParams = [], $bodyReq = null) {
		$resp = null;
		$queryParams['apiKey'] = $this->apiKey;
		$query = http_build_query($queryParams);

		$formattedUrl = $this->baseUrl.$url.'?'.$query;
		try {
			$response = $this->client->request($method, $formattedUrl, [
				'headers' => [
					'User-Agent' => 'Shipper/'
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
        $this->baseUrl = 'https://sandbox-api.shipper.id';
		$this->apiKey = '6e64060e1a7ac0473df5f284237768eb';
		$this->client = new Client([
            'base_uri' => $this->baseUrl,
        ]);
	}
}