<?php

use GuzzleHttp\Client;

class Jubelio {

    private $instance;
    private $client;
    private $authorization;
    private $email;
    private $password;

    const TYPE_EMAIL = 'JUBELIO_EMAIL';
    const TYPE_PASSWORD = 'JUBELIO_PASSWORD';

    public function __construct() {

        $this->instance = &get_instance();
        $this->instance->load->model('MainModel');

        $this->client = new Client([
            'base_uri' => 'https://api.jubelio.com',
        ]);
    }

    public function authenticate() {
        $this->email = 'ofirnetwork@gmail.com';
        $this->password = 'admin123';
    }

    public function login() {
        $this->authenticate();
        $body = null;

        try {
            $response = $this->client->request('POST', '/login', [
                'json' => [
                    'email' => $this->email,
                    'password' => $this->password
                ]
            ]);

            $body = $response->getBody();
            $body = json_decode($body, true);
        } catch (\Exception $e) {
            $body = null;
            if (!empty($e->getResponse())) {
                $body = $e->getResponse()->getBody(true);
                $body = json_decode($body);
            }
            $resp = [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $body
            ];
        }

        return $body;
    }

    public function addStockAdjustments($token, $items, $note, $locationId, $isOpeningBalance) {
        $resp = null;
        $headers = [
            'Authorization' => $token
        ];

        $jubBody = [
            'item_adj_id' => 0,
            'item_adj_no' => '[auto]',
            'transaction_date' => gmdate('Y-m-d\TH:i:s\Z'),
            'note' => $note,
            'location_id' => (int) $locationId,
            'is_opening_balance' => $isOpeningBalance,
            'items' => $items
        ];

        $resp = $this->httpRequest('POST', '/inventory/adjustments/', $headers, $jubBody);
        return $resp;
    }

    public function editStockAdjustments($authorization, $itemAdjId, $itemAdjNo, $items, $note, $locationId, $isOpeningBalance) {
        $body = null;

        $jubBody = [
            'item_adj_id' => (int) $itemAdjId,
            // 'transaction_date' 		=> '2020-06-27T05:11:47.872Z',
            'item_adj_no' => $itemAdjNo,
            'note' => $note,
            'location_id' => (int) $locationId,
            'is_opening_balance' => $isOpeningBalance,
            'items' => $items
        ];

        try {
            $response = $this->client->request('POST', '/inventory/adjustments/', [
                'headers' => $authorization,
                'json' => $jubBody
            ]);

            $body = $response->getBody();
            $body = json_decode($body);
        } catch (\Exception $e) {
            $body = $e->getResponse()->getBody(true);
            $body = json_decode($body);
        }

        return $body;
    }

    public function getSalesOrders($authorization, $page, $pageSize) {
        $body = null;
        $resp = null;

        try {
            $response = $this->client->request('GET', '/sales/orders/', [
                'headers' => $authorization,
                'query' => [
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'sortBy' => 'transaction_date',
                    'sortDirection' => 'DESC'
                ]
            ]);

            $body = $response->getBody();
            $body = json_decode($body);

            $orders = $body->data;
            $resp['totalCount'] = $body->totalCount;
            foreach ($orders as $order) {
                $resp['data'][] = $this->getSalesOrdersById($authorization, $order->salesorder_id);
            }
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
            $resp = json_decode($resp);
        }

        return $resp;
    }

    public function getReadyToPick ($token, $page = 1 , $data = 150) {
        $resp = null;
        $headers = [
            'Authorization' => $token
        ];

        $resp = $this->httpRequest('GET', sprintf('/sales/orders/ready-to-pick/?page=%s&pageSize=%s', $page, $data), $headers);
        return $resp;
    }

    public function getProductGroup ($token, $itemGroupId) {
        $resp = null;
        $headers = [
            'Authorization' => $token
        ];
        $resp = $this->httpRequest('GET', '/inventory/items/group/'.$itemGroupId, $headers);
        return $resp;
    }

    public function getSalesOrder($token, $salesOrderId) {
        $resp = null;
        $headers = [
            'Authorization' => $token
        ];
        $resp = $this->httpRequest('GET', '/sales/orders/' . $salesOrderId, $headers);
        return $resp;
    }

    public function getSalesOrdersById($authorization, $orderId) {
        $body = null;

        try {
            $responseDetail = $this->client->request('GET', '/sales/orders/' . $orderId, [
                'headers' => $authorization
            ]);

            $bodyDetail = $responseDetail->getBody();
            $bodyDetail = json_decode($bodyDetail);

            $items = $bodyDetail->items;
            $body['transaction'] = $bodyDetail;

            foreach ($items as $item) {
                $item->salesorder_id = $bodyDetail->salesorder_id;
                $body['transaction_item'][] = $item;
            }
        } catch (\Exception $e) {
            $body = $e->getResponse()->getBody(true);
            $body = json_decode($body);
        }

        return $body;
    }

    public function getAllProductStock($token, $queryParams = null) {
        $resp = null;
        $headers = [
            'Authorization' => $token
        ];
        $resp = $this->httpRequest('GET', '/inventory/', $headers, null, $queryParams);
        return $resp;
    }

    public function getProduct($token, $id) {
        $resp = null;
        $headers = [
            'Authorization' => $token
        ];
        $resp = $this->httpRequest('GET', '/inventory/items/'. $id, $headers, null);
        return $resp;
    }

    public function getProductbySku($token, $sku) {
        $resp = null;
        $headers = [
            'Authorization' => $token
        ];
        $resp = $this->httpRequest('GET', '/inventory/items/by-sku/' . $sku, $headers);
        return $resp;
    }

    public function getItems ($token) {
        $resp = null;
        $headers = [
            'Authorization' => $token
        ];

        $resp = $this->httpRequest('GET', '/inventory/items/', $headers);
        return $resp;
    }

    public function createSalesOrder ($token, $payload) {
        
        $data = [
            'salesorder_id' => 0,
            'salesorder_no' => sprintf('%s-%s','SIM', time()),
            'contact_id' => 1,
            'customer_name' => (isset($payload['customer_name']) ? $payload['customer_name'] : 0),
            'transaction_date' => gmdate('Y-m-d\TH:i:s\Z'),
            'is_tax_included' => (isset($payload['is_tax_included']) ? $payload['is_tax_included'] : 0),
            'note' => (isset($payload['note']) ? $payload['note'] : ''),
            'sub_total' => (isset($payload['sub_total']) ? $payload['sub_total'] : 0),
            'total_disc' => (isset($payload['total_disc']) ? $payload['total_disc'] : 0),
            'total_tax' => (isset($payload['total_tax']) ? $payload['total_tax'] : 0),
            'grand_total' => (isset($payload['grand_total']) ? $payload['grand_total'] : 0),
            'ref_no' => (isset($payload['ref_no']) ? $payload['ref_no'] : ''),
            'location_id' => -1,
            'source' => 1,
            // 'is_canceled' => false,
            // 'is_paid' => true,
            'cancel_reason' => '',
            'cancel_reason_detail' => '',
            'channel_status' => 'pending',
            'shipping_cost' => (isset($payload['shipping_cost']) ? $payload['shipping_cost'] : 0),
            'insurance_cost' => (isset($payload['insurance_cost']) ? $payload['insurance_cost'] : 0),
            'shipping_full_name' => (isset($payload['shipping_full_name']) ? $payload['shipping_full_name'] : ''),
            'shipping_phone' => (isset($payload['shipping_phone']) ? $payload['shipping_phone'] : ''),
            'shipping_address' => (isset($payload['shipping_address']) ? $payload['shipping_address'] : ''),
            'shipping_area' => (isset($payload['shipping_area']) ? $payload['shipping_area'] : ''),
            'shipping_city' => (isset($payload['shipping_city']) ? $payload['shipping_city'] : ''),
            'shipping_province' => (isset($payload['shipping_province']) ? $payload['shipping_province'] : ''),
            'shipping_post_code' => (isset($payload['shipping_post_code']) ? $payload['shipping_post_code'] : ''),
            'shipping_country' => 'INDONESIA',
            'add_disc' => 0,
            'add_fee' => 0,
            'salesmen_id' => null,
            'store_id' => null,
            'service_fee' => (isset($payload['service_fee']) ? $payload['service_fee'] : 0),
            'payment_method' => null,
        ];

        if (isset($payload['salesorder_id']) && !empty($payload['salesorder_id'])) {
            $data['salesorder_id'] = $payload['salesorder_id'];
        }
        if (isset($payload['salesorder_no']) && !empty($payload['salesorder_no'])) {
            $data['salesorder_no'] = $payload['salesorder_no'];
        }

        if (isset($payload['is_canceled'])) {
            $data['is_canceled'] = $payload['is_canceled'];
        }

        if (isset($payload['is_paid'])) {
            $data['is_paid'] = $payload['is_paid'];
        }

        $items = [];
        if (isset($payload['items'])) {
            $items = $payload['items'];
        }
        foreach ($items as $item) {
            $add = [
                'salesorder_detail_id' => 0,
                'item_id' => (isset($item['item_id']) ? $item['item_id'] : ''),
                'serial_no' => (isset($item['serial_no']) ? $item['serial_no'] : ''),
                'description' => (isset($item['description']) ? $item['description'] : ''),
                'tax_id' => 1,
                'price' => (isset($item['price']) ? $item['price'] : 0),
                'unit' => 'Buah',
                'qty_in_base' => (isset($item['qty_in_base']) ? $item['qty_in_base'] : 0),
                'disc' => (isset($item['disc']) ? $item['disc'] : 0),
                'disc_amount' => (isset($item['disc_amount']) ? $item['disc_amount'] : 0),
                'tax_amount' => (isset($item['tax_amount']) ? $item['tax_amount'] : 0),
                'amount' => (isset($item['amount']) ? $item['amount'] : 0),
                'location_id' => -1,
                'shipper' => (isset($item['shipper']) ? $item['shipper'] : 0),
                'channel_order_detail_id' => null
            ];
            $data['items'][] = $add;
        }

        $headers = [
            'Authorization' => $token
        ];

        $resp = null;
        $resp = $this->httpRequest('POST', '/sales/orders/', $headers, $data);
        if (isset($resp['success']) && $resp['success'] == false) {

        } else {
            $data['salesorder_id'] = $resp['id'];
            $resp = $data;
        }
        return $resp;
    }

    public function setAsPaid ($token, $salesOrderIds) {
        $data = [
            'ids' => $salesOrderIds
        ];
        $headers = [
            'Authorization' => $token
        ];
        $resp = null;
        $resp = $this->httpRequest('POST', '/sales/orders/set-as-paid', $headers, $data);
        return $resp;
    }

    public function saveAirwayBill ($token, $salesOrderId, $trackingNo, $shipper) {
        $data = [
            'salesorder_id' => $salesOrderId,
            'tracking_no' => $trackingNo,
            'shipper' => $shipper
        ];
        $headers = [
            'Authorization' => $token
        ];
        $resp = null;
        $resp = $this->httpRequest('POST', '/sales/orders/save-airwaybill/', $headers, $data);
        return $resp;
    }

    public function createInvoicePayment ($token, $salesOrderId) {
        $data = [
            'salesorder_id' => $salesOrderId
        ];
        $headers = [
            'Authorization' => $token
        ];
        $resp = null;
        $resp = $this->httpRequest('POST', '/sales/packlists/create-invoice', $headers, $data);
        return $resp;
    }

    public function createPickList ($token, $salesOrderIds, $items) {
        foreach ($items as $key => $item) {
            $items[$key]['picklist_detail_id'] = 12091209102;
        }
        $data = [
            'picklist_id' => 0,
            'picklist_no' => '[auto]',
            'is_completed' => true,
            'salesorderIds' => [
                $salesOrderIds
            ],
            'items' => $items
        ];
        $headers = [
            'Authorization' => $token
        ];
        $resp = null;
        $resp = $this->httpRequest('POST', '/sales/picklists/', $headers, $data);
        return $resp;
    }

    public function saveReceivedDate ($token, $salesOrderId, $receivedDate) {
        $data = [
            'salesorder_id' => $salesOrderId,
            'received_date' => $receivedDate
        ];
        $headers = [
            'Authorization' => $token
        ];
        $resp = null;
        $resp = $this->httpRequest('POST', '/sales/orders/save-received-date/', $headers, $data);
        return $resp;
    }

    public function setAsComplete ($token, $salesOrderIds) {
        $data = [
            'ids' => $salesOrderIds
        ];
        $headers = [
            'Authorization' => $token
        ];
        $resp = null;
        $resp = $this->httpRequest('POST', '/sales/orders/mark-as-complete', $headers, $data);
        return $resp;
    }

    public function httpRequest($method, $url, $headers = null, $bodyReq = null, $queryParams = null) {
        $resp = null;
        try {
            $response = $this->client->request($method, $url, [
                'headers' => $headers,
                'json' => $bodyReq,
                'connect_timeout' => 15,
                'query' => $queryParams
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp, true);
        } catch (\Exception $e) {
            $body = null;
            if (!empty($e->getResponse())) {
                $body = $e->getResponse()->getBody(true);
                $body = json_decode($body);
            }
            $resp = [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $body
            ];
        }

        return $resp;
    }

}
