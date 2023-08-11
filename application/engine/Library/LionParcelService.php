<?php
namespace Library;

use GuzzleHttp\Client;

class LionParcelService {

    private $instance;
    private $apiKey;
    private $domain;
    private $client;
    private $authorization;

    public function __construct($authorization) {
        $this->client = new Client([
            'base_uri' => 'http://lpapi.cargoflash.com',
        ]);
        $this->authorization = $authorization;
    }

    public function getTarif ($origin, $destination, $weight = 1, $commodity = '', $goodsValue = 0, $isInsurance = 0, $isWoodPacking = 0) {
        $queryParams = [
            'origin' => $origin,
            'destination' => $destination,
            'weight' => $weight,
            'commodity' => $commodity,
            'goods_value' => $goodsValue,
            'is_insurance' => $isInsurance,
            'is_wood_packing' => $isWoodPacking
        ];
        $resp = $this->httpRequest('GET', '/v3/tariff', null, null, $queryParams);
        return $resp;
    }

    public function getDetails ($q = '') {
        $queryParams = [
            'q' => (empty($q) ? '' : $q)
        ];
        $resp = $this->httpRequest('GET', '/v3/stt/detail', null, null, $queryParams);
        return $resp;
    }

    public function getTracking ($q = '') {
        $queryParams = [
            'q' => (empty($q) ? '' : $q)
        ];
        $resp = $this->httpRequest('GET', '/v3/stt/track', null, null, $queryParams);
        return $resp;
    }

    public function createBooking ($orderNo, $clientCode, $userType, $externalNumber, $trackingNo, $packageId, $orderNoTag, $packageDate, $productType, $serviceType,
        $commodityType, $noOfPieces, $grossWeight, $volumeWeight, $codAmount, $shipperName, $pickupAddress, $pickupLocation, $pickupPhone, $pickupEmail, $receiverName,
        $receiverAddress, $receiverLocation, $receiverPhone, $receiverEmail
    ) {
        $pruebaXml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<ORDER_DETAILS></ORDER_DETAILS>
XML;
        $xml = new \SimpleXMLElement($pruebaXml);
        $xml->addChild('ORDER_NO', $orderNo);
        $details = $xml->addChild('PACKAGE_DETAILS');
        $details->addChild('CLIENT_CODE', $clientCode);
        $details->addChild('UserType', $userType);
        $details->addChild('EXTERNALNUMBER', $externalNumber);
        $details->addChild('TRACKING_NO', $trackingNo);
        $details->addChild('PACKAGE_ID', $packageId);
        $details->addChild('ORDER_NO_TAG', $orderNoTag);
        $details->addChild('PACKAGE_DATE', $packageDate);
        $details->addChild('PRODUCT_TYPE', $productType);
        $details->addChild('SERVICE_TYPE', $serviceType);
        $details->addChild('COMMODITY_TYPE', $commodityType);
        $details->addChild('NO_OF_PIECES', $noOfPieces);
        $details->addChild('GROSS_WEIGHT', $grossWeight);
        $details->addChild('VOLUME_WEIGHT', $volumeWeight);
        $details->addChild('CODAMOUNT', $codAmount);
        $details->addChild('SHIPPERNAME', $shipperName);
        $details->addChild('PICK_UP_ADDRESS', $pickupAddress);
        $details->addChild('PICK_UP_LOCATION', $pickupLocation);
        $details->addChild('PICK_UP_PHONE', $pickupPhone);
        $details->addChild('PICK_UP_EMAIL', $pickupEmail);
        $details->addChild('RECEIVER_NAME', $receiverName);
        $details->addChild('RECEIVER_ADDRESS', $receiverAddress);
        $details->addChild('RECEIVER_LOCATION', $receiverLocation);
        $details->addChild('RECEIVER_PHONE', $receiverPhone);
        $details->addChild('RECEIVER_EMAIL', $receiverEmail);
        // Header('Content-type: text/xml');
        // print($xml->asXML());

        $headers['Content-Type'] = 'text/xml; charset=UTF8';
        $this->client = new Client([
            'base_uri' => 'http://lionparcelbookingapi.bookmycargo.net',
        ]);
        $resp = $this->httpRequest('POST', '/BookingService.svc/ProcessOrder2', null, null, null, $xml->asXML());
        return $resp;
    }

    public function httpRequest($method, $url, $headers = [], $bodyReq = null, $queryParams = null, $body = null) {
        $headers['Authorization'] = 'Basic '.$this->authorization;
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'headers' => $headers,
                'query' => $queryParams,
                'body' => $body
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp);
        } catch (\Exception $e) {
            if (empty($e->getResponse())) {
                $resp = 'Connection Error';
            } else {
                $resp = $e->getResponse()->getBody(true);
                $resp = json_decode($resp);   
            }
        }
        return $resp;
    }

}
