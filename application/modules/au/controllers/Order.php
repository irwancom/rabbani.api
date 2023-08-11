
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\OrderHandler;
use Service\CLM\Handler\UserHandler;
use Service\CLM\Handler\CartHandler;
use Library\JNEService;
use Service\CLM\Handler\StoreHandler;

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

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new OrderHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getOrders($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($orderCode) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }


        $handler = new OrderHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getOrder(['slug' => $orderCode]);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function check_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();

        $userHandler = new UserHandler($this->MainModel);
        $userHandler->setAdmin($auth->data);
        $filters = [];
        $filters['users.id'] = $payload['user_id'];
        $cartValues = [];
        foreach ($payload['order_detail'] as $orderDetail) {
            $value = new \stdClass;
            $value->id_product_detail = $orderDetail['id_product_detail'];
            $value->qty = $orderDetail['qty'];
            $cartValues[] = $value;
        }

        $userResult = $userHandler->getUserEntity($filters);

        $orderHandler = new OrderHandler($this->MainModel);
        $orderHandler->setUseCartFromAdmin(true);
        $orderHandler->setAdminCart($cartValues);
        $orderHandler->setUser((array)$userResult->data);
        $result = $orderHandler->check($payload['user_address_id'], $payload['shipment_code'], $payload['payment_method_code'], '');

        $this->response($result->format(), $result->getStatusCode());
    }

    public function create_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();

        $userHandler = new UserHandler($this->MainModel);
        $userHandler->setAdmin($auth->data);
        $filters = [];
        $filters['users.id'] = $payload['user_id'];
        $cartValues = [];
        foreach ($payload['order_detail'] as $orderDetail) {
            $value = new \stdClass;
            $value->id_product_detail = $orderDetail['id_product_detail'];
            $value->qty = $orderDetail['qty'];
            $cartValues[] = $value;
        }

        $userResult = $userHandler->getUserEntity($filters);

        $orderHandler = new OrderHandler($this->MainModel);
        $orderHandler->setUseCartFromAdmin(true);
        $orderHandler->setAdminCart($cartValues);
        $orderHandler->setUser((array)$userResult->data);
        $result = $orderHandler->purchase($payload['user_address_id'], $payload['shipment_code'], $payload['payment_method_code'], '');

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $filters = [
            'id_order' => $id
        ];
        $handler = new OrderHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $handler->setSendNotif(true);
        $result = $handler->updateOrder($payload, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_to_in_process_post ($orderCode) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = [
            'status' => OrderHandler::ORDER_STATUS_IN_PROCESS,
            'in_process_at' => date('Y-m-d H:i:s')
        ];

        $filters = [
            'order_code' => $orderCode
        ];
        $handler = new OrderHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $handler->setSendNotif(true);
        $result = $handler->updateOrder($payload, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_to_in_shipment_post ($orderCode) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        if (empty($this->input->post('no_awb'))) {
            $this->delivery->addError(400, 'AWB Number is required');
            $this->response($this->delivery->format());
        }

        $payload = [
            'status' => OrderHandler::ORDER_STATUS_IN_SHIPMENT,
            'no_awb' => $this->input->post('no_awb'),
        ];

        $filters = [
            'order_code' => $orderCode
        ];
        $handler = new OrderHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $handler->setSendNotif(true);
        $result = $handler->updateOrder($payload, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function store_list_get ($storeId = null, $orderCode = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);

        $isDetail = ($orderCode && !empty($orderCode) && !is_null($orderCode)) ? true : false;
        $result = $handler->getOrderStore($storeId, $orderCode, $payload, $isDetail);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function store_order_get ($orderCode = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->get();
        $handler = new StoreHandler($this->MainModel);
        $handler->setAdmin($auth->data);

        $isDetail = ($orderCode && !empty($orderCode) && !is_null($orderCode)) ? true : false;
        $result = $handler->getOrderStore(null, $orderCode, $payload, $isDetail, true);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function store_update_post ($orderId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        if(!$orderId || empty($orderId) || is_null($orderId)){
            $this->delivery->addError(400, 'Order ID is required'); $this->response($this->delivery->format());
        }
        $handler = new OrderHandler($this->MainModel);
        $payload = $this->input->post();
        if(!isset($payload['store_id']) || !$payload['store_id'] || empty($payload['store_id']) || is_null($payload['store_id'])){
            $this->delivery->addError(400, 'Store ID is required'); $this->response($this->delivery->format());
        }
        $storeId = $payload['store_id'];

        if(!isset($payload['order_code']) || !$payload['order_code'] || empty($payload['order_code']) || is_null($payload['order_code'])){
            $this->delivery->addError(400, 'Order code is required'); $this->response($this->delivery->format());
        }
        $orderCode = $payload['order_code'];

        $handlerStore = new StoreHandler($this->MainModel);
        $handlerStore->setAdmin($auth->data);
        $getOrder = $handlerStore->getOrderStore($storeId, $orderCode);
        if ($getOrder->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $order = $getOrder->data;
        if($order->id!=$orderId){
            $this->delivery->addError(400, 'Order ID does not match the data store and order code.'); $this->response($this->delivery->format());
        }

        $detailStore = $order->store_detail;
        $storeLocation = null;
        if($detailStore && !empty($detailStore) && !is_null($detailStore)){
            if(isset($detailStore->location) && $detailStore->location && !empty($detailStore->location) && !is_null($detailStore->location)){
                $storeLocation = $detailStore->location;
            }
        }
        $detailAddress = $order->user_address_detail;
        $shipment = $order->shipment;
        $summary = $order->detail;
        $products = $order->products;

        $status = (isset($payload['status']) && $payload['status'] && !empty($payload['status']) && !is_null($payload['status'])) ? $payload['status'] : null;
        $note = (isset($payload['note']) && $payload['note'] && !empty($payload['note']) && !is_null($payload['note'])) ? $payload['note'] : null;
        $trackType = (isset($payload['track_type']) && ($payload['track_type']=='auto' || $payload['track_type']=='manual')) ? $payload['track_type'] : null;
        $trackNo = (isset($payload['track_nomor']) && $payload['track_nomor'] && !empty($payload['track_nomor']) && !is_null($payload['track_nomor'])) ? $payload['track_nomor'] : null;

        $existTrack = (isset($shipment->tracking) && $shipment->tracking) ? $shipment->tracking : null;
        $existTrackType = ($existTrack && !is_null($existTrack)) ? $existTrack->type : null;
        $existTrackNomor = ($existTrack && !is_null($existTrack)) ? $existTrack->awb_nomor : null;

        $sendData = array();
        $sendData['updated_at'] = date('Y-m-d H:i:s');
        $sendData['crst_note'] = $note;
        
        $listStatus = $handler->listStatusOrderStore();
        $lastStatus  = $order->order_status;
        $lastStatusDetail = (isset($listStatus[$lastStatus])) ? $listStatus[$lastStatus] : null;

        $upStatus = false;
        if($status && !is_null($status)){
            $cekStatus = array_search($status, array_column($listStatus, 'slug'));
            if($cekStatus && intval($cekStatus)>1 && $cekStatus!=$lastStatus){
                if($lastStatus=='waiting_payment'){
                    $this->delivery->addError(400, 'Order is waiting payment.'); $this->response($this->delivery->format());
                }
                if($lastStatus=='payment_expired'){
                    $this->delivery->addError(400, 'Order is payment expired.'); $this->response($this->delivery->format());
                }
                if($lastStatus=='canceled' || $lastStatus=='deleted'){
                    $this->delivery->addError(400, 'Order is canceled or deleted.'); $this->response($this->delivery->format());
                }
                $detailStatus = $listStatus[$cekStatus];
                $sendData['crst_status'] = intval($cekStatus);
                $upStatus = true;
            }
        }

        $upTrackType = false; $upTrackNomor = false;
        if($trackType && !is_null($trackType)){
            $upTrackType = $trackType;
            $forUpTrackNomor = $existTrackNomor;
            if($trackType=='manual'){
                if($trackNo!=$existTrackNomor){
                    if(!$trackNo || is_null($trackNo)){
                        $this->delivery->addError(400, 'Track nomor is required for update.'); $this->response($this->delivery->format());
                    }
                    $forUpTrackNomor = $trackNo;
                }
            }else{
                if($existTrackType=='auto' && $existTrackNomor && !is_null($existTrackNomor) && !empty($existTrackNomor)){
                    $this->delivery->addError(400, 'Generate awb is already available for the transaction.'); $this->response($this->delivery->format());
                }else{
                    if($detailStore->is_central!=1){
                        $this->delivery->addError(400, 'Generate awb only for online store.'); $this->response($this->delivery->format());
                    }

                    if(!$detailStore || empty($detailStore) || is_null($detailStore)){
                        $this->delivery->addError(400, 'Unable to generate awb for the store.'); $this->response($this->delivery->format());
                    }
                    if(!$shipment || empty($shipment) || is_null($shipment)){
                        $this->delivery->addError(400, 'Shipping data not found in the transaction.'); $this->response($this->delivery->format());
                    }
                    if(!isset($shipment->service) || !$shipment->service || empty($shipment->service) || is_null($shipment->service)){
                        $this->delivery->addError(400, 'Shipping service not available in the transaction.'); $this->response($this->delivery->format());
                    }

                    $orderNote = ($order->note && !empty($order->note) && !is_null($order->note)) ? $order->note : '';
                    $orderNote = $order->order_code.' - '.$orderNote;

                    $jneService = new JNEService();
                    $jneService->setEnv('production');
                    $tracking = $jneService->generateAirwayBill(
                        'BDO000',//($shipment && !is_null($shipment)) ? $shipment->origin->city_code : 'BDO000', //branch
                        '10950700',//($detailAddress->phone_number) ? $detailAddress->phone_number : $order->user_phone, //cust
                        $order->order_id, //orderId
                        $detailStore->name, //shipperName
                        $detailStore->address, //shipperAddr1
                        $detailStore->address, //shipperAddr2
                        $detailStore->address, //shipperAddr3
                        ($storeLocation && !is_null($storeLocation)) ? $storeLocation->district_name : $shipment->origin->city_name, //shipperCity
                        ($storeLocation && !is_null($storeLocation)) ? $storeLocation->province_name : $shipment->origin->city_name, //shipperRegion
                        '12345', //shipperZip
                        '', //shipperPhone
                        ($detailAddress->received_name) ? $detailAddress->received_name : $order->user_name, //receiverName
                        $detailAddress->address, //receiverAddr1
                        $detailAddress->address, //receiverAddr2
                        $detailAddress->address, //receiverAddr3
                        ($shipment && !is_null($shipment)) ? $shipment->destination->city_name : $detailAddress->address, //receiverCity
                        ($shipment && !is_null($shipment)) ? $shipment->destination->city_name : $detailAddress->address, //receiverRegion
                        ($detailAddress->post_code) ? $detailAddress->post_code : '12345', //receiverZip
                        ($detailAddress->phone_number) ? $detailAddress->phone_number : $order->user_phone, //receiverPhone
                        $order->detail['qty'], //qty
                        $order->detail['weight'], //weight
                        $orderNote, //goodsDesc
                        $order->detail['final_total'], //goodsValue
                        1, //goodsType
                        '', //inst
                        'N', //insFlag
                        $shipment->origin->city_code, //origin
                        $shipment->destination->city_code, //desti
                        $shipment->service->service_display, //service
                        'N', //codFlag
                        '', //codAmount
                    );

                    if(!$tracking || empty($tracking) || is_null($tracking)){
                        $this->delivery->addError(400, 'Failed generate awb system.'); $this->response($this->delivery->format());
                    }
                    
                    if(isset($tracking['detail'])){
                        if(isset($tracking['detail'][0]) && isset($tracking['detail'][0]['status'])){
                            if(!$tracking['detail'][0]['status'] || $tracking['detail'][0]['status']=='error' || $tracking['detail'][0]['status']=='Error' || $tracking['detail'][0]['status']=='failed'){
                                $reason = (isset($tracking['detail'][0]['reason'])) ? $tracking['detail'][0]['reason'] : '';
                                $this->delivery->addError(400, 'Failed generate awb system, '.$reason); $this->response($this->delivery->format());
                            }else if($tracking['detail'][0]['status'] || $tracking['detail'][0]['status']=='sukses' || $tracking['detail'][0]['status']=='Sukses' || $tracking['detail'][0]['status']=='success'){
                                $cnote_no = (isset($tracking['detail'][0]['cnote_no'])) ? $tracking['detail'][0]['cnote_no'] : null;
                                if((!$cnote_no || empty($cnote_no) || is_null($cnote_no)) && isset($tracking['detail'][0]['airwaybill'])){
                                    $cnote_no = $tracking['detail'][0]['airwaybill'];
                                }
                                if(!$cnote_no || empty($cnote_no) || is_null($cnote_no)){
                                    $this->delivery->addError(400, 'Failed generate awb system, airwaybill number is not available.'); $this->response($this->delivery->format());
                                }
                                $forUpTrackNomor = $cnote_no;
                            }
                        }
                    }
                }
            }
            if($forUpTrackNomor && !is_null($forUpTrackNomor) && $forUpTrackNomor!=$existTrackNomor){
                $upTrackNomor = $forUpTrackNomor;
            }
        }

        if($upTrackType && $upTrackNomor){
            $shipment->tracking = array('type'=>$upTrackType,'awb_nomor'=>$upTrackNomor);
            $sendData['crst_shipment'] = json_encode($shipment, true);
            $sendData['crst_awb_no'] = $upTrackNomor;
            if(!$upStatus){
                $upToShipment = true;
                if($lastStatusDetail && !is_null($lastStatusDetail)){
                    $upToShipment = ($lastStatusDetail['slug']=='by_order' || $lastStatusDetail['slug']=='in_process') ? true : false;
                }
                if($upToShipment){
                    $sendData['crst_status'] = 3;
                }
            }
        }
        $update = $this->db->set($sendData)->where(['crst_id'=>$orderId,'crst_store'=>$storeId,'crst_order'=>$orderCode])->update('cart_stores');

        $this->delivery->data = 'ok';
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function summary_marketplace_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->get();
        $handler = new OrderHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $statusList = $handler->listStatusOrder();

        $sources = array('shopee'=>[],'tokopedia'=>[],'lazada'=>[]);

        $filters = array('deleted_at'=>NULL);
        if(isset($payload['date_from']) && $payload['date_from'] && !empty($payload['date_from']) && !is_null($payload['date_from']) && strtotime($payload['date_from'])){
            $filters['DATE(created_at) >='] = date('Y-m-d H:i:s', strtotime($payload['date_from']));
        }
        if(isset($payload['date_to']) && $payload['date_to'] && !empty($payload['date_to']) && !is_null($payload['date_to']) && strtotime($payload['date_to'])){
            $filters['DATE(created_at) <='] = date('Y-m-d H:i:s', strtotime($payload['date_to']));
        }

        $result = array(); $numberRes = 0;
        foreach($sources as $k_src=>$source){
            $result[$numberRes]['source'] = $k_src;
            $result[$numberRes]['count'] = array();
            foreach($statusList as $status){
                $condition = $filters;
                $condition['order_source'] = $k_src;
                $condition['status'] = $status['slug'];
                $result[$numberRes]['count'][$status['slug']] = $this->db->from('orders')->where($condition)->count_all_results();
            }
            $numberRes = $numberRes+1;
        }

        $this->delivery->data = $result;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

//======================================= END LINE ======================================= //
}
