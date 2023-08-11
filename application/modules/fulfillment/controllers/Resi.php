<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Fulfillment\FulfillmentOrderHandler;
use GuzzleHttp\Client;



class Resi extends REST_Controller {

    private $validator;
    private $delivery;
    private $client;
    private $authorization;
    private $token;

    public function __construct() {
        parent::__construct();
        $this->load->library('jubelio');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
        $this->client = new Client([
            'base_uri' => 'https://api.jubelio.com',
        ]);
    }

    public function get_get() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $salesOrderIds = json_decode($this->input->get('salesorder_ids'));
        $fulfillmentOrderHandler = new FulfillmentOrderHandler($this->MainModel, $auth->data);
        $result = $fulfillmentOrderHandler->getOrders(['salesorder_ids' => $salesOrderIds]);

        $lazadaIds = [];
        $nonLazadaIds = [];
        for ($i = 0; $i < count($result->data); $i++) {
            $order = $result->data[$i];
            if (substr($order['salesorder_no'], 0, 2) == 'LZ') {
                $lazadaIds[] = $order['salesorder_id'];
            } else {
                $nonLazadaIds[] = $order['salesorder_id'];
            }
        }

        $this->authenticate();

        $headers = [
            'Authorization' => $this->token
        ];

        $lazadaDocUrl = null;
        if (count($lazadaIds) > 0) {
            $response = $this->client->request('GET', '/lazada/get-document/', [
                'headers' => $headers,
                'query' => [
                    'ids' => '[' . implode(',', $lazadaIds) . ']',
                    'document_type' => 'shippingLabel',
                    'store_id' => 7971,
                    'title' => 'Shipping Label Lazada'
                ]
            ]);
    
            $body = $response->getBody();
            $contents = $body->getContents();
            preg_match('/src="([^"]+)"/', $contents, $match);
            $lazadaDocUrl = $match[1];
        }

        $nonLazadaDocUrl = null;
        if (count($nonLazadaIds) > 0) {
            $response = $this->client->request('GET', '/reports/shipping-label/', [
                'headers' => $headers,
                'query' => [
                    'tz' => 'Asia%2FJakarta',
                    'ids' => $nonLazadaIds,
                ]
            ]);
            $body = $response->getBody();
            $body = json_decode($body);
            $nonLazadaDocUrl = $body->url;
        }


        
        $this->response([
            'data' => [
                'shippingLabelUrl' => $nonLazadaDocUrl,
                'lazadaShippingLabelUrl' => $lazadaDocUrl,
            ],
        ], 200);
    }


    public function export_post2() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [100, 150],
            'orientation' => 'P',
            'margin-top' => 0,
            'default_font' => 'san-serif',
            'default_font_size' => 9,
        ]);

        $filters = $this->input->get();
        $handler = new FulfillmentOrderHandler($this->MainModel, $auth->data);
        $result = $handler->getFulfilledOrders($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();

        $printDate = date('d/m/Y H.i.s');

        for ($i = 0; $i < count($result->data); $i++) {
            $order = $result->data[$i];

            if (!isset($order['tracking_number']) || !isset($order['invoice_no'])) {
                continue;
            }

            $trackingNo = $order['tracking_number'];
            $trackingBarcodeSrc = 'data:image/png;base64,' . base64_encode($generator->getBarcode($trackingNo, $generator::TYPE_CODE_128, 2, 50));
    
            $invoiceNo = $order['invoice_no'];
            $invoiceBarcodeSrc = 'data:image/png;base64,' . base64_encode($generator->getBarcode($invoiceNo, $generator::TYPE_CODE_128, 1, 50));

            $orderDate = date('d/m/Y H.i.s', strtotime($order['transaction_date']));
            $pickId = $order['fulfillment_order_id'];
            $to = $order['shipping_full_name'];
            $storeName = $order['store_name'];
            $shippingPhone = $order['shipping_phone'];
            $shippingCost = $order['shipping_cost'];
            $shippingAddress = $order['shipping_address'];
            $shippingArea = $order['shipping_area'];
            $shippingCity = $order['shipping_city'];
            $shippingProvince = $order['shipping_province'];
            $shippingPostCode = $order['shipping_post_code'];
            $insuranceCost = $order['insurance_cost'];
            $grandTotal = $order['grand_total'];
            $weight = $order['weight'];
            $paymentMethod = $order['payment_method'];
            $sourceName = $order['source_name'];
            $courier = $order['courier'];
            $orderItems = $order['transaction_items'];
            

            $mpdf->WriteHTML($this->load->view('resi', [
                'barcode' => [
                    'no' => $trackingNo,
                    'src' => $trackingBarcodeSrc
                ],
                'invoice' => [
                    'no' => $invoiceNo,
                    'src' => $invoiceBarcodeSrc
                ],
                'orderDate' => $orderDate,
                'printDate' => $printDate,
                'pickId' => $pickId,
                'to' => $to,
                'storeName' => $storeName,
                'shippingPhone' => $shippingPhone,
                'shippingCost' => $shippingCost,
                'shippingAddress' => $shippingAddress,
                'shippingArea' => $shippingArea,
                'shippingCity' => $shippingCity,
                'shippingProvince' => $shippingProvince,
                'shippingPostCode' => $shippingPostCode,
                'insuranceCost' => $insuranceCost,
                'grandTotal' => $grandTotal,
                'weight' => $weight,
                'orderItems' => $orderItems,
                'paymentMethod' => $paymentMethod,
                'courier' => $courier,
                'sourceName' => $sourceName,
            ], true));
            $mpdf->addPage();
        }
        $mpdf->Output('Daftar_Resi.pdf', 'I');
    }

    public function authenticate () {
        $this->token = file_get_contents('internal/jubelio_token.txt');
        $this->authorization = [
            'Authorization' => $this->token
        ];

        if (empty($this->token)) {
            $data = [
                "success" => false,
                "message" => "Please provide Jubelio token."
            ];
            return $this->returnJSON($data);
        }

    }


}
