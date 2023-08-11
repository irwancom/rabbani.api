<?php
namespace Library;

use GuzzleHttp\Client;

class JNEService {

	private $username;
	private $apiKey;
	private $client;
	private $env;

	public function __construct ($username = 'RABBANIASYSA', $apiKey = 'e072b9ac674b405ab58a5982fb79232b') {
		$this->username = $username;
		$this->apiKey = $apiKey;
		$this->client = new Client([
            'base_uri' => 'http://apiv2.jne.co.id:10102',
        ]);
	}

	public function setEnv ($env) {
		if ($env == 'production') {
			$this->client = new Client([
	            'base_uri' => 'http://apiv2.jne.co.id:10101',
	        ]);
		}
	}

	public function setUsername ($username) {
		$this->username = $username;
	}

	public function setApiKey ($apiKey) {
		$this->apiKey = $apiKey;
	}

	public function getName () {
		return 'JNE';
	}

	public function getOrigin () {
		$bodyReq = [
			'username' => $this->username,
			'api_key' => $this->apiKey
		];
		$resp = $this->httpRequest('POST', '/insert/getorigin', $bodyReq);
		return $resp;
	}

	public function getDestination () {
		$bodyReq = [
			'username' => $this->username,
			'api_key' => $this->apiKey
		];
		$resp = $this->httpRequest('POST', '/insert/getdestination', $bodyReq);
		return $resp;
	}

	public function getTariff ($from, $thru, $weight) {
		$bodyReq = [
			'username' => $this->username,
			'api_key' => $this->apiKey,
			'from' => $from, //CGK10000
			'thru' => $thru,
			'weight' => $weight
		];
		$resp = $this->httpRequest('POST', '/tracing/api/pricedev', $bodyReq);
		return $resp;
	}

	public function getTraceTracking ($awb) {
		$bodyReq = [
			'username' => $this->username,
			'api_key' => $this->apiKey
		];
		$resp = $this->httpRequest('POST', '/tracing/api/list/v1/cnote/'.$awb, $bodyReq);
		return $resp;
	}

	public function generateAirwayBill ($branch, $cust, $orderId, $shipperName, $shipperAddr1, $shipperAddr2, $shipperAddr3, $shipperCity, $shipperRegion, $shipperZip, $shipperPhone,
										$receiverName, $receiverAddr1, $receiverAddr2, $receiverAddr3, $receiverCity, $receiverRegion, $receiverZip, $receiverPhone,
										$qty, $weight, $goodsDesc, $goodsValue, $goodsType, $inst, $insFlag, $origin, $destination, $service, $codFlag, $codAmount
										) {
		$bodyReq = [
			'username' => $this->username,
			'api_key' => $this->apiKey,
			'OLSHOP_BRANCH' => $branch,
			'OLSHOP_CUST' => $cust,
			'OLSHOP_ORDERID' => $orderId,
			'OLSHOP_SHIPPER_NAME' => $shipperName,
			'OLSHOP_SHIPPER_ADDR1' => $shipperAddr1,
			'OLSHOP_SHIPPER_ADDR2' => $shipperAddr2,
			'OLSHOP_SHIPPER_ADDR3' => $shipperAddr3,
			'OLSHOP_SHIPPER_CITY' => $shipperCity,
			'OLSHOP_SHIPPER_REGION' => $shipperRegion,
			'OLSHOP_SHIPPER_ZIP' => $shipperZip,
			'OLSHOP_SHIPPER_PHONE' => $shipperPhone,
			'OLSHOP_RECEIVER_NAME' => $receiverName,
			'OLSHOP_RECEIVER_ADDR1' => $receiverAddr1,
			'OLSHOP_RECEIVER_ADDR2' => $receiverAddr2,
			'OLSHOP_RECEIVER_ADDR3' => $receiverAddr3,
			'OLSHOP_RECEIVER_CITY' => $receiverCity,
			'OLSHOP_RECEIVER_REGION' => $receiverRegion,
			'OLSHOP_RECEIVER_ZIP' => $receiverZip,
			'OLSHOP_RECEIVER_PHONE' => $receiverPhone,
			'OLSHOP_QTY' => $qty,
			'OLSHOP_WEIGHT' => $weight,
			'OLSHOP_GOODSDESC' => $goodsDesc,
			'OLSHOP_GOODSVALUE' => $goodsValue,
			'OLSHOP_GOODSTYPE' => $goodsType,
			'OLSHOP_INST' => $inst,
			'OLSHOP_INS_FLAG' => $insFlag,
			'OLSHOP_ORIG' => $origin,
			'OLSHOP_DEST' => $destination,
			'OLSHOP_SERVICE' => $service,
			'OLSHOP_COD_FLAG' => $codFlag,
			'OLSHOP_COD_AMOUNT' => $codAmount,
		];
		
		$resp = $this->httpRequest('POST', '/tracing/api/generatecnote', $bodyReq);
		return $resp;
	}

	public function httpRequest($method, $url, $bodyReq = null) {
        $resp = null;

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                	'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => $bodyReq
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp, true);
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
            $resp = json_decode($resp, true);
        }

        return $resp;
    }
}
