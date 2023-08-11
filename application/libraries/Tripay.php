<?php

use GuzzleHttp\Client;

class Tripay {

    private $instance;
    private $apiKey;
    private $client;

    const TYPE_API_KEY = 'TRIPAY_API_KEY';

    public function __construct() {

        $this->instance = &get_instance();
        $this->instance->load->model('MainModel');

        $this->client = new Client([
            'base_uri' => 'https://tripay.co.id',
        ]);
    }

    public function kategoriProdukPembelian() {
        $this->authenticate();
        $resp = $this->httpRequest('POST', '/api/v2/pembelian/category/');
        return $resp;
    }

    public function operatorProdukPembelian() {
        $this->authenticate();
        $resp = $this->httpRequest('POST', '/api/v2/pembelian/operator/');
        return $resp;
    }

    public function produkPembelian() {
        $this->authenticate();
        $resp = $this->httpRequest('POST', '/api/v2/pembelian/produk/');
        return $resp;
    }

    public function detailProdukPembelian($code) {
        $this->authenticate();
        $bodyReq = [
            'code' => $code
        ];

        $resp = $this->httpRequest('POST', '/api/v2/pembelian/produk/cek', $bodyReq);
        return $resp;
    }

    public function kategoriProdukPembayaran() {
        $this->authenticate();
        $resp = $this->httpRequest('POST', '/api/v2/pembayaran/category/');
        return $resp;
    }

    public function operatorProdukPembayaran() {
        $this->authenticate();
        $resp = $this->httpRequest('POST', '/api/v2/pembayaran/operator/');
        return $resp;
    }

    public function produkPembayaran() {
        $this->authenticate();
        $resp = $this->httpRequest('POST', '/api/v2/pembayaran/produk/');
        return $resp;
    }

    public function detailProdukPembayaran($code) {
        $this->authenticate();
        $bodyReq = [
            'code' => $code
        ];

        $resp = $this->httpRequest('POST', '/api/v2/pembayaran/produk/cek', $bodyReq);
        return $resp;
    }

    public function ordersPurchase($bodyReq) {
//        print_r($code);
        $this->authenticate();
//        $bodyReq = [
//            'inquiry' => $code['inquiry'], // 'PLN' untuk pembelian PLN Prabayar, atau 'I' (i besar) untuk produk lainnya
//            'code' => $code['code'], // kode produk
//            'phone' => $code['phone'], // nohp pembeli
//            'no_meter_pln' => $code['no_meter_pln'], // khusus untuk pembelian token PLN prabayar
//            'api_trxid' => $code['api_trxid'], // ID transaksi dari server Anda. (tidak wajib, maks. 25 karakter)
//            'pin' => '7909', // pin member
//        ];
//        $bodyReq = [$code];

        $resp = $this->httpRequest('POST', '/api/v2/transaksi/pembelian', $bodyReq);
        return $resp;
    }
    
    public function payBill($code) {
        $this->authenticate();
        $bodyReq = [$code];

        $resp = $this->httpRequest('POST', '/api/v2/transaksi/pembayaran', $bodyReq);
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null) {
        $resp = null;

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey
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

    public function authenticate() {
        $this->apiKey = 'WPYKay0lW1zX6YgetNYkp7kqQLtqayO4';
    }

}
