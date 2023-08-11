
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\OrderHandler;
use Service\CLM\Handler\OrderInfoHandler;
use Service\CLM\Handler\CLMHandler;
use Library\DigitalOceanService;

class Order extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function check_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->check($this->input->post('user_address_id'), $this->input->post('shipment_code'), $this->input->post('payment_method_code'), $this->input->post('referral_code'), $this->input->post('voucher_code'), $this->input->post('donation'));
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $infoHandler = new OrderInfoHandler($this->MainModel);
        $infoHandler->setOrderResult($result->data);
        $result->data->infos = $infoHandler->create();

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->purchase($this->input->post('user_address_id'), $this->input->post('shipment_code'), $this->input->post('payment_method_code'), $this->input->post('referral_code'), $this->input->post('voucher_code'), $this->input->post('donation'));

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_to_completed_post ($orderCode) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = [
            'status' => OrderHandler::ORDER_STATUS_COMPLETED,
            'is_completed' => 1,
            'completed_at' => date('Y-m-d H:i:s')
        ];

        $filters = [
            'order_code' => $orderCode
        ];
        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->updateOrder($payload, $filters);

        $clmHandler = new CLMHandler($this->MainModel);
        $clmResult = $clmHandler->handle($orderCode);

        $this->response($clmResult->format());
    }

    public function update_to_completed_by_store_post ($orderCode) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $explode = explode("-", $orderCode);
        if (count($explode) >= 3) {
            $orderCode = sprintf('%s-%s', $explode[0], $explode[1]);
        }

        $payload = [
            'status' => OrderHandler::ORDER_STATUS_COMPLETED,
            'is_completed' => 1,
            'completed_at' => date('Y-m-d H:i:s')
        ];

        $filters = [
            'order_code' => $orderCode
        ];
        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->updateOrder($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format());
        }

        $clmHandler = new CLMHandler($this->MainModel);
        $clmResult = $clmHandler->handle($orderCode);

        $this->response($clmResult->format());
    }

    public function recheck_payment_method_post ($idOrder) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $paymentMethodCode = $this->input->post('payment_method_code');
        $idOrder = (int)$idOrder;

        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getOrder(['id_order' => $idOrder]);
        $order = $result->data;
        if (empty($order)) {
            $this->delivery->addError(400, 'Order is required');
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }

        $result = $handler->recheckPaymentMethod($order, $paymentMethodCode);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function recreate_payment_method_post ($idOrder) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $paymentMethodCode = $this->input->post('payment_method_code');
        $idOrder = (int)$idOrder;

        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getOrder(['id_order' => $idOrder]);
        $order = $result->data;
        if (empty($order)) {
            $this->delivery->addError(400, 'Order is required');
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }

        $result = $handler->recreatePaymentMethod($order, $paymentMethodCode);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function cancel_post ($idOrder) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $idOrder = (int)$idOrder;

        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getOrder(['id_order' => $idOrder]);
        $order = $result->data;
        if (empty($order)) {
            $this->delivery->addError(400, 'Order is required');
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }

        if ($order->status != OrderHandler::ORDER_STATUS_WAITING_PAYMENT) {
            $this->delivery->addError(400, 'Cant cancel order. Only valid when payment is exists.');
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }

        $payload = [
            'status' => OrderHandler::ORDER_STATUS_CANCELED,
            'canceled_at' => date('Y-m-d H:i:s')
        ];

        $filters = [
            'id_order' => $idOrder
        ];
        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->updateOrder($payload, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($orderCode) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }


        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getOrder(['order_code' => $orderCode]);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new OrderHandler($this->MainModel, $auth->data);
        $handler->setUser($auth->data);
        $result = $handler->getOrders($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function rate_post ($orderCode) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->rateOrder($orderCode, $payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function rate_review_post ($orderCode, $productId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $handler = new OrderHandler($this->MainModel);
        $handler->setUser($auth->data);
        $cekOrder = $handler->getOrder(['order_code' => $orderCode]);
        if(!$cekOrder || !$cekOrder->data || empty($cekOrder->data) || is_null($cekOrder->data)){
            $this->delivery->addError(400, 'Transaction not found'); $this->response($this->delivery->format());
        }
        $orderData = $cekOrder->data;
        $orderDetails = $orderData->order_details;

        $productDetailId = $this->input->post('product_detail');
        $productDetailId = ($productDetailId && !empty($productDetailId) && !is_null($productDetailId)) ? $productDetailId : NULL;

        $detailItem = NULL;
        foreach($orderDetails as $orderDetail){
            $foundItem = false;
            if($orderDetail->id_product==$productId){
                if(!$productDetailId || is_null($productDetailId)){
                    $foundItem = true;
                }else{
                    if($orderDetail->id_product_detail==$productDetailId){
                        $foundItem = true;
                    }
                }
            }
            if($foundItem){
                $detailItem = $orderDetail; break;
            }
        }

        if(!$detailItem || is_null($detailItem)){
            $this->delivery->addError(400, 'Product not found in the transaction'); $this->response($this->delivery->format());
        }

        $rate = $this->input->post('rate');
        if(strlen($rate)==0 || !is_numeric($rate)){
            $this->delivery->addError(400, 'Rate is required'); $this->response($this->delivery->format());
        }
        $rate = intval($rate);
        if($rate < 0 || $rate > 5){
            $this->delivery->addError(400, 'Rate not match (0-5)'); $this->response($this->delivery->format());
        }
        $message = $this->input->post('message');

        $postMedia = array();
        if(isset($_FILES['media']) && $this->wooh_support->readyFileUpload($_FILES['media'])){
            $forMedia = $_FILES['media'];
            $isTmp = $_FILES['media']['tmp_name'];
            if(!is_array($isTmp)){
                $forMedia = array();
                $forMedia['name'][0] =  $_FILES['media']['name'];
                $forMedia['type'][0] =  $_FILES['media']['type'];
                $forMedia['tmp_name'][0] =  $_FILES['media']['tmp_name'];
                $forMedia['error'][0] =  isset($_FILES['media']['error']) ? $_FILES['media']['error'] : 0;
                $forMedia['size'][0] =  isset($_FILES['media']['size']) ? $_FILES['media']['size'] : 0;
            }
            $noUpload = 0;
            foreach($forMedia['tmp_name'] as $k_media=>$media){
                $noMedia = $k_media+1;
                $isFile = [
                    'name' => $forMedia['name'][$k_media],
                    'type' => $forMedia['type'][$k_media],
                    'tmp_name' => $forMedia['tmp_name'][$k_media],
                    'size' => $forMedia['size'][$k_media],
                ];
                $validationUpload = $this->wooh_support->validationUpload('media', $isFile, ['image']);
                if(!$validationUpload['success']){
                    $this->delivery->addError(400, $validationUpload['msg'].' (File '.$noMedia.': '.$forMedia['name'][$k_media].')'); $this->response($this->delivery->format());
                }
                $_FILES['media_'.$k_media] = $isFile;
                $uploadMedia = new DigitalOceanService();
                $resultUploadMedia = $uploadMedia->upload($_FILES['media_'.$k_media], 'media_'.$k_media);
                if($resultUploadMedia && is_array($resultUploadMedia) && isset($resultUploadMedia['cdn_url'])){
                    $resultUploadMedia['file_type'] = $validationUpload['detail']['type'];
                    $postMedia[$noUpload] = $resultUploadMedia;
                    $noUpload = $noUpload+1;
                }
            }
        }

        $sendRate = array();
        $sendRate['order_code'] = $orderCode;
        $sendRate['id_product'] = $productId;
        $sendRate['id_product_detail'] = $productDetailId;
        $sendRate['message'] = $message;
        $sendRate['rate'] = $rate;
        $sendRate['created_at'] = date('Y-m-d H:i:s');
        $sendRate['updated_at'] = date('Y-m-d H:i:s');
        $upRate = $this->db->insert('product_rates', $sendRate);

        if($postMedia && !empty($postMedia) && !is_null($postMedia)){
            foreach($postMedia as $media){
                $payloadImages = [
                    'order_code' => $orderCode,
                    'id_product' => $productId,
                    'id_product_detail' => $productDetailId,
                    'image_url' => $media['cdn_url'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $action = $this->db->insert('product_rate_images', $payloadImages);
            }
        }

        $argsReview = array('order_code'=>$orderCode,'id_product'=>$productId,'deleted_at'=>NULL);
        if($productDetailId && !is_null($productDetailId)){
            $argsReview['id_product_detail'] = $productDetailId;
        }
        $review = $this->db->from('product_rates')->where($argsReview)->get()->row_array();
        $review['media'] = $this->db->select('image_url')->from('product_rate_images')->where($argsReview)->get()->result_array();

        $result = $orderData;
        $result->order_details = $detailItem;
        $result->order_review = $review;
        $this->delivery->data = $result;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
