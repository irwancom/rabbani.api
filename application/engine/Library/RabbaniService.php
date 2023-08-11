<?php
namespace Library;

use GuzzleHttp\Client;

class RabbaniService {

    private $client;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://rsys.systems',
        ]);
    }


    public function generateVoucherWeekend ($noVoucher) {
        $innerPayload = [
            'token' => 'M00zwEyiemojKR9ilsECaE81QUjpdfNP5Bdp',
            'no_voucher' => $noVoucher,
        ];
        $payload['data'] = json_encode($innerPayload);
        $headers['Content-Type'] = 'application/json';
        $resp = $this->httpRequest('POST', '/back_end/microservices/wha/voucherweekend', null, null, null, $payload);
        return $resp;
    }

    public function httpRequest($method, $url, $headers = [], $bodyReq = null, $queryParams = null, $body = null) {
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'headers' => $headers,
                'query' => $queryParams,
                'form_params' => $body,
                'connect_timeout' => 10,
                'timeout' => 10,
                'verify' => false,
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
