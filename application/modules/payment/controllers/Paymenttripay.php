<?php

use Service\Handler;
use Service\CLM\Handler\OrderHandler;

class Paymenttripay extends CI_Controller {

    private $serviceHandler;
    private $clmOrderHandler;

    public function __construct() {
        parent::__construct();

        $this->load->library('tripaypayment');
        $this->load->Model('MainModel');
        $this->serviceHandler = new Handler($this->MainModel);
        $this->clmOrderHandler = new OrderHandler($this->MainModel);
    }
    
    public function index() {
        echo 'tripay payment';
    }

    public function channelPembayaran() {
        $resp = null;
        $resp = $this->tripaypayment->channelPembayaran();

        $data = [
            'success' => true,
            'paymentResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function requestTransaksi () {
        $payload = $this->input->post();
        $method = $payload['method'];
        $merchantRef = $payload['merchant_ref'];
        $amount = $payload['amount'];
        $customerName = $payload['customer_name'];
        $customerEmail = $payload['customer_email'];
        $customerPhone = $payload['customer_phone'];
        $orderItems = $payload['order_items'];
        $expiredTime = $payload['expired_time'];

        $resp = $this->tripaypayment->requestTransaksi( $method, $merchantRef, $amount, $customerName, $customerEmail, $customerPhone, $orderItems, $expiredTime);
        $data = [
            'success' => true,
            'tripayResponse' => $resp
        ];
        return $this->returnJSON($data);

    }

    public function callback () {
        $payload = file_get_contents("php://input");
        $payload = json_decode($payload, true);

        $data = [
            'fromcall' => 'PAYMENT_TRIPAY',
            'dataJson' => json_encode($payload),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $action = $this->MainModel->insert('logcallback', $data);
        $result = null;
        if ($result = $this->clmOrderHandler->onTripayCallback($payload)) {
            
        } else {
            $result = $this->serviceHandler->onTripayCallback($payload);
        }
        $payload['data'] = $result;

        return $this->returnJSON($payload);
    }

    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

}
