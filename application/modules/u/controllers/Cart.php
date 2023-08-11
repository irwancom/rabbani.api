
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\CartHandler;
use Service\CLM\Handler\StoreHandler;
use Library\JNEService;
use Service\CLM\Handler\ShippingLocationHandler;
use Service\CLM\Handler\ShippingPriceHandler;

class Cart extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function index_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $payload = $this->input->get();

        $handler = new CartHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getCart();
        $result->data->checkout_by_store = $handler->getCartStoreCheckout($auth->data['id'], $payload);

        $this->response($result->format(), $result->getStatusCode());        
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $payload = $this->input->post();
        $qty = (isset($payload['qty']) && $payload['qty'] && is_numeric($payload['qty'])) ? intval($payload['qty']) : '';
        //$qty = (int)$this->input->post('qty');

        $store = null;
        $vaidationStore = (isset($payload['source']) && $payload['source']!='website') ? false : true;
        if($vaidationStore){
            $conditionStore = array('deleted_at'=>NULL);
            if(isset($payload['store_id']) && $payload['store_id'] && is_numeric($payload['store_id'])){
                $conditionStore['id'] = $payload['store_id'];
            }else{
                $conditionStore['is_central'] = 1;
            }
            $store = $this->db->select(['id','code'])->from('stores')->where($conditionStore)->get()->row_array();
            if(!$store || is_null($store)){
                $this->delivery->addError(400, 'Store not found or not ready'); $this->response($this->delivery->format());
            }
        }

        $handler = new CartHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->addToCart($this->input->post('id_product_detail'), $qty, $store);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function modify_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        //update//
        $params = $this->input->post();
        $types = array('qty','note','checkout','store');
        $type = (isset($params['type']) && in_array($params['type'], $types)) ? $params['type'] : 'qty';
        $qty = (isset($params['qty'])) ? (int)$params['qty'] : 0;
        $storeId = (isset($params['store_id'])) ? (int)$params['store_id'] : 0;

        $handler = new CartHandler($this->MainModel);
        $handler->setUser($auth->data);

        $singleModify = (isset($params['all'])) ? ( ($params['all']=='1') ? false : true ) : true;
        if($singleModify){
            $result = $handler->modifyCart($this->input->post('id_product_detail'), $qty, $type, $params, $storeId); //update//
        }else{
            $result = $handler->modifyAllCart($type, $params); //update//
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function remove_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CartHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->removeFromCart($this->input->post('id_product_detail'), $this->input->post('store_id'));

        $this->response($result->format(), $result->getStatusCode());   
    }

    public function store_item_get ($skuCode = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        if(!$skuCode || empty($skuCode) || is_null($skuCode)){
            $this->delivery->addError(400, 'SKU code is required'); $this->response($this->delivery->format());
        }
        $handlerStore = new StoreHandler($this->MainModel);

        $selectStores = [
            'store_product_detail_stocks.stock','stores.id','stores.id_kab','stores.subdistrict_id',
            'stores.name','stores.address','stores.code'
        ];
        $filterStores = [
            'store_product_detail_stocks.sku_code'=>$skuCode,
            'store_product_detail_stocks.stock !='=>NULL,
            'store_product_detail_stocks.stock >'=>0,
            'store_product_detail_stocks.deleted_at'=>NULL,
            'stores.deleted_at'=>NULL
        ];
        $joinStores = ['stores' => 'stores.code = store_product_detail_stocks.store_code'];
        $stores = $this->db->select($selectStores)->from('store_product_detail_stocks');
        $stores = $stores->join('stores','stores.code = store_product_detail_stocks.store_code');
        $stores = $stores->where($filterStores)->get()->result_object();
        foreach($stores as $store){
            $store->location = $handlerStore->handleLocationStore($store);
        }

        $this->delivery->data = $stores;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function store_sync_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId =  $auth->data['id'];
        $currentDate = date('Y-m-d H:i:s');

        $payload = $this->input->post();
        if(!isset($payload['store_id']) || !$payload['store_id'] || empty($payload['store_id']) || is_null($payload['store_id'])){
            $this->delivery->addError(400, 'Store ID is reguired'); $this->response($this->delivery->format());
        }
        $storeId = $payload['store_id'];

        $handler = new CartHandler($this->MainModel);
        $handler->setUser($auth->data);
        $cart = $handler->getCart()->data;
        if(!$cart || empty($cart) || is_null($cart)){
            $this->delivery->addError(400, 'Cart not found'); $this->response($this->delivery->format());
        }
        $cartCheckout = $cart->checkout_by_store;
        if(!$cartCheckout || empty($cartCheckout) || is_null($cartCheckout)){
            $this->delivery->addError(400, 'Cart store for checkout not found'); $this->response($this->delivery->format());
        }

        $existStore = $this->db->from('cart_stores')->where(['crst_user'=>$userId,'crst_order'=>NULL,'crst_store'=>$storeId,'crst_status'=>0,'deleted_at'=>NULL])->get()->row_array();
        if(!$existStore || is_null($existStore)){
            $this->delivery->addError(400, 'Store not found in cart'); $this->response($this->delivery->format());
        }
        $isStore = json_decode($existStore['crst_store_detail'], true);

        $addressId = ($existStore) ? $existStore['crst_address'] : null;
        if(isset($payload['address_id']) && $payload['address_id'] && !empty($payload['address_id']) && !is_null($payload['address_id'])){
            $addressId = $payload['address_id'];
        }

        $address = $this->db->from('user_address')->where(['id'=>$addressId,'user_id'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$address || is_null($address)){
            $this->delivery->addError(400, 'Address not found or not available'); $this->response($this->delivery->format());
        }

        $handlerShippingLoc = new ShippingLocationHandler($this->MainModel);
        $setDestination = null;
        if($address['sub_district_id'] && !empty($address['sub_district_id']) && !is_null($address['sub_district_id'])){
            $cekDesti = $handlerShippingLoc->destinationFromSubdistrict($address['sub_district_id']);
            $setDestination = $cekDesti->data;
        }
        if(!$setDestination || is_null($setDestination)){
            if($address['districts_id'] && !empty($address['districts_id']) && !is_null($address['districts_id'])){
                $cekDesti = $handlerShippingLoc->originDestiFromDistrict('destination', $address['districts_id']);
                $setDestination = $cekDesti->data;
            }
        }

        $shipmentCode = null; $destinationData = null;
        if(isset($payload['shipment_code']) && $payload['shipment_code'] && !empty($payload['shipment_code']) && !is_null($payload['shipment_code'])){
            if(!$setDestination || empty($setDestination) || is_null($setDestination)){
                $this->delivery->addError(400, 'No delivery destinations found that match the address'); $this->response($this->delivery->format());
            }

            $shipmentCode = $payload['shipment_code'];
            $destinationData = $setDestination[0];
        }

        $upData = array('updated_at'=>$currentDate);
        $upData['crst_address'] = $addressId;
        $upData['crst_address_detail'] = json_encode($address, true);

        $dataShipment = null; $costShipping = 0;
        if($shipmentCode && !is_null($shipmentCode)){
            if(!isset($isStore['location']) || !$isStore['location'] || empty($isStore['location']) || is_null($isStore['location'])){
                $this->delivery->addError(400, 'Store location not found or not yet available'); $this->response($this->delivery->format());
            }
            $storeLocation = $isStore['location'];
            if(!isset($storeLocation['origin']) || !$storeLocation['origin'] || empty($storeLocation['origin']) || is_null($storeLocation['origin'])){
                $this->delivery->addError(400, 'Store location origin not found or not yet available'); $this->response($this->delivery->format());
            }
            $originData = $storeLocation['origin'][0];

            $foundShipping = null; $setWeight = intval($existStore['crst_weight']/1000);
            if($shipmentCode=='flat' || $shipmentCode=='FLAT'){
                $handlerPriceInternal = new ShippingPriceHandler($this->MainModel);
                $serviceOptions = $handlerPriceInternal->getOngkirRabbani((Array)$destinationData, $originData, $setWeight);
            }else{
                $jneService = new JNEService();
                $jneService->setEnv('production');
                $ongkir = $jneService->getTariff($originData['city_code'], $destinationData->city_code, $setWeight);
                if(isset($ongkir['error']) && !empty($ongkir['error'])){
                    $this->delivery->addError(400, $ongkir['error']); $this->response($this->delivery->format());
                }
                $serviceOptions = $ongkir['price'];
            }

            foreach($serviceOptions as $shipService){
                if($shipService['service_display']==$shipmentCode){
                    $costShipping = $shipService['price'];
                    $foundShipping = $shipService; break;
                }
            }
            if(!$foundShipping || is_null($foundShipping)){
                $this->delivery->addError(400, 'No delivery services found according to the code shipment'); $this->response($this->delivery->format());
            }

            $dataShipment = array();
            $dataShipment['destination'] = $destinationData;
            $dataShipment['origin'] = $originData;
            $dataShipment['service'] = $foundShipping;
            $dataShipment['service_options'] = $serviceOptions;
        }

        $upData['crst_shipping'] = $costShipping;
        $upData['crst_shipment'] = ($dataShipment && !is_null($dataShipment)) ? json_encode($dataShipment, true) : $dataShipment;
        $upCart = $this->db->set($upData)->where(['crst_id'=>$existStore['crst_id']])->update('cart_stores');

        $cart->checkout_by_store = $handler->getCartStoreCheckout($userId);
        $this->delivery->data = $cart;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
