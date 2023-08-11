<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\JNEService;
use Service\CLM\Handler\ShippingLocationHandler;
use Service\CLM\Handler\ShippingPriceHandler;

class Shipping extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function filterJneLocation ($locations = [], $code = false, $search = false) {
        $resData = array(); $k_result = 0;
        foreach($locations as $k_detail=>$detail){
            $detail = array_change_key_case($detail, CASE_LOWER);
            $lowerValue = array_map('strtolower', $detail);

            $foundResult = ($code) ? (($lowerValue['city_code']==$code) ? true : false) : true;
            $foundResult = ($foundResult && $search) 
                            ? ( ($lowerValue['city_name']==$search || str_contains($lowerValue['city_name'], $search))?true:false ) 
                            : $foundResult;

            if($foundResult){
                $resData[$k_result] = $detail; $k_result = $k_result+1;
            }
        }
        return array('result'=>$resData);
    }

    public function destination_get ($cityCode = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $payload = $this->input->get();
        $sort = array('code'=>'city_code','name'=>'city_name');
        $orderBy = 'city_code'; $orderVal = 'DESC';

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $select = ['jne_destination.city_code','jne_destination.city_name'];
        $filter = array('jne_destination.deleted_at'=>NULL);

        $isDetail = ($cityCode && !empty($cityCode) && !is_null($cityCode)) ? $cityCode : false;
        if($isDetail || (isset($payload['code']) && $payload['code'] && !empty($payload['code']) && !is_null($payload['code']))){
            $filter['jne_destination.city_code'] = ($isDetail) ? $isDetail : $payload['code'];
        }

        $result = $this->db->select($select)->from('jne_destination')->where($filter);
        if(isset($payload['q']) && $payload['q'] && !empty($payload['q']) && !is_null($payload['q'])){
            $result = $result->like(['jne_destination.city_name'=>$payload['q']]);
        }

        $countData = $result->count_all_results('', false);
        if($countData==0){
            $this->delivery->addError(400, 'Destination not found'); $this->response($this->delivery->format());
        }

        $result = $result->order_by('jne_destination.'.$orderBy, $orderVal);
        $destination = [];
        if($isDetail){
            $destination = $result->get()->row_array();
        }else{
            $pagination = false;
            if(isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data'])){
                $payload['sort_by'] = $orderBy;
                $payload['sort_value'] = $orderVal;
                $payload['limit'] = $payload['data'];
                $forPager = $this->wooh_support->pagerData($payload, $countData, [], ['sort'=>$orderBy]);
                $pagination = $forPager['data'];
                $result = $result->limit($pagination['limit'], $forPager['offset']);
            }

            $resData = $result->get()->result_array();
            $destination = array('result'=>$resData);
            if($pagination && !is_null($pagination)){
                foreach($pagination as $k_pg=>$pg){ $destination[$k_pg] = $pg; }
            }
        }

        $this->delivery->data = $destination;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function destination_jne_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $jneService = new JNEService();
        $jneService->setEnv('production');
        $destination = $jneService->getDestination();
        
        if(isset($destination['error']) && !empty($destination['error'])){
            $this->delivery->addError(400, $destination['error']); $this->response($this->delivery->format());
        }
        if(!isset($destination['detail']) || empty($destination['detail']) || is_null($destination['detail'])){
            $this->delivery->addError(400, 'Destination not found'); $this->response($this->delivery->format());
        }

        $payload = $this->input->get();
        $readySearch = (isset($payload['q']) && $payload['q'] && !empty($payload['q']) && !is_null($payload['q'])) ? strtolower($payload['q']) : false;
        $filterCode = (isset($payload['code']) && $payload['code'] && !empty($payload['code']) && !is_null($payload['code'])) ? strtolower($payload['code']) : false;

        $resData = $this->filterJneLocation($destination['detail'], $filterCode, $readySearch);
        if(!$resData['result'] || is_null($resData['result'])){
            $this->delivery->addError(500, 'Destination not found'); $this->response($this->delivery->format());
        }

        $this->delivery->data = $resData;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function origin_get ($cityCode = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $payload = $this->input->get();
        $sort = array('code'=>'city_code','name'=>'city_name');
        $orderBy = 'city_code'; $orderVal = 'DESC';

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $select = ['jne_origin.city_code','jne_origin.city_name'];
        $filter = array('jne_origin.deleted_at'=>NULL);

        $isDetail = ($cityCode && !empty($cityCode) && !is_null($cityCode)) ? $cityCode : false;
        if($isDetail || (isset($payload['code']) && $payload['code'] && !empty($payload['code']) && !is_null($payload['code']))){
            $filter['jne_origin.city_code'] = ($isDetail) ? $isDetail : $payload['code'];
        }

        $result = $this->db->select($select)->from('jne_origin')->where($filter);
        if(isset($payload['q']) && $payload['q'] && !empty($payload['q']) && !is_null($payload['q'])){
            $result = $result->like(['jne_origin.city_name'=>$payload['q']]);
        }

        $countData = $result->count_all_results('', false);
        if($countData==0){
            $this->delivery->addError(400, 'Origin not found'); $this->response($this->delivery->format());
        }

        $result = $result->order_by('jne_origin.'.$orderBy, $orderVal);
        $origin = [];
        if($isDetail){
            $origin = $result->get()->row_array();
        }else{
            $pagination = false;
            if(isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data'])){
                $payload['sort_by'] = $orderBy;
                $payload['sort_value'] = $orderVal;
                $payload['limit'] = $payload['data'];
                $forPager = $this->wooh_support->pagerData($payload, $countData, [], ['sort'=>$orderBy]);
                $pagination = $forPager['data'];
                $result = $result->limit($pagination['limit'], $forPager['offset']);
            }
            $resData = $result->get()->result_array();
            $origin = array('result'=>$resData);
            if($pagination && !is_null($pagination)){
                foreach($pagination as $k_pg=>$pg){ $origin[$k_pg] = $pg; }
            }
        }
        $this->delivery->data = $origin;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function origin_jne_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }
        
        $jneService = new JNEService();
        $jneService->setEnv('production');
        $origin = $jneService->getOrigin();
        if(isset($origin['error']) && !empty($origin['error'])){
            $this->delivery->addError(400, $origin['error']); $this->response($this->delivery->format());
        }
        if(!isset($origin['detail']) || empty($origin['detail']) || is_null($origin['detail'])){
            $this->delivery->addError(400, 'Origin not found'); $this->response($this->delivery->format());
        }

        $payload = $this->input->get();
        $readySearch = (isset($payload['q']) && $payload['q'] && !empty($payload['q']) && !is_null($payload['q'])) ? strtolower($payload['q']) : false;
        $filterCode = (isset($payload['code']) && $payload['code'] && !empty($payload['code']) && !is_null($payload['code'])) ? strtolower($payload['code']) : false;

        $resData = $this->filterJneLocation($origin['detail'], $filterCode, $readySearch);
        if(!$resData['result'] || is_null($resData['result'])){
            $this->delivery->addError(500, 'Origin not found'); $this->response($this->delivery->format());
        }

        $this->delivery->data = $resData;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function ongkir_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $payload = $this->input->post();
        if(!isset($payload['origin']) || empty($payload['origin']) || is_null($payload['origin'])){
            $this->delivery->addError(400, 'Origin is required');
            $this->response($this->delivery->format());
        }
        if(!isset($payload['destination']) || empty($payload['destination']) || is_null($payload['destination'])){
            $this->delivery->addError(400, 'Destination is required');
            $this->response($this->delivery->format());
        }
        if(!isset($payload['weight']) || empty($payload['weight']) || is_null($payload['weight']) || !is_numeric($payload['weight'])){
            $this->delivery->addError(400, 'Weight is required');
            $this->response($this->delivery->format());
        }
        $isWeight = intval($payload['weight']);
        if($isWeight <= 0){
            $this->delivery->addError(400, 'Weight is not match');
            $this->response($this->delivery->format());
        }

        $jneService = new JNEService();
        $jneService->setEnv('production');
        $ongkir = $jneService->getTariff($payload['origin'], $payload['destination'], intval($isWeight/1000));

        $foundServiceJne = true;
        if(isset($ongkir['error']) && !empty($ongkir['error'])){
            $foundServiceJne = false;
        }

        $serviceOptions = null;
        if($foundServiceJne && isset($ongkir['price']) && !empty($ongkir['price']) && !is_null($ongkir['price'])){
            $serviceOptions = $ongkir['price'];
        }else{
            $handlerPriceInternal = new ShippingPriceHandler($this->MainModel);
            $destinationData = $this->db->select(['city_code','city_name'])->from('jne_destination')->where(['city_code'=>$payload['destination']])->get()->row_array();
            $originData = $this->db->select(['city_code','city_name'])->from('jne_origin')->where(['city_code'=>$payload['origin']])->get()->row_array();
            if(!$originData || is_null($originData)){
                $originData = $this->db->select(['city_code','city_name'])->from('jne_destination')->where(['city_code'=>$payload['origin']])->get()->row_array();
            }
            if($destinationData && $originData){
                $serviceOptions = $handlerPriceInternal->getOngkirRabbani($destinationData, $originData, intval($isWeight/1000));
            }
        }

        if(!$serviceOptions || empty($serviceOptions) || is_null($serviceOptions)){
            $this->delivery->addError(400, 'Price shipping not found'); $this->response($this->delivery->format());
        }

        $this->delivery->data = array('result'=>$serviceOptions);
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function track_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }
        $noAwb = $this->input->post('awb');
        if(!isset($noAwb) || !$noAwb || empty($noAwb) || is_null($noAwb)){
            $this->delivery->addError(400, 'No AWB is required'); $this->response($this->delivery->format());
        }

        $jneService = new JNEService();
        $jneService->setEnv('production');
        $tracking = $jneService->getTraceTracking($noAwb);

        if(isset($tracking['error']) && $tracking['error'] && !empty($tracking['error'])){
            $this->delivery->addError(400, $tracking['error']); $this->response($this->delivery->format());
        }
        if(isset($tracking['status']) && !$tracking['status']){
            $this->delivery->addError(400, 'Failed load tracking shipping'); $this->response($this->delivery->format());
        }

        $this->delivery->data = $tracking;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function origin_from_district_get($districtId = null) {
        return $this->_ordes_from_district('origin', $districtId);
    }

    public function destination_from_district_get($districtId = null) {
        return $this->_ordes_from_district('destination', $districtId);
    }

    public function _ordes_from_district($isType = null, $districtId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }
        $handler = new ShippingLocationHandler($this->MainModel);

        $secondType = ($isType=='origin') ? 'destination' : 'origin';
        $result = $handler->originDestiFromDistrict($isType, $districtId);
        if(!$result->data || empty($result->data) || is_null($result->data)){
            $result = $handler->originDestiFromDistrict($secondType, $districtId);
        }

        $this->delivery->data = $result->data;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function destination_from_subdistrict_get($subDistrictId = null) {
        return $this->_ordes_from_subdistrict('destination', $subDistrictId);
    }

    public function origin_from_subdistrict_get($subDistrictId = null) {
        return $this->_ordes_from_subdistrict('origin', $subDistrictId);
    }

    public function _ordes_from_subdistrict($isType = null, $subDistrictId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }
        $handler = new ShippingLocationHandler($this->MainModel);

        $secondType = ($isType=='origin') ? 'destination' : 'origin';
        $result = $handler->originDestiFromSubDistrict($isType, $subDistrictId);
        if(!$result->data || empty($result->data) || is_null($result->data)){
            $result = $handler->originDestiFromSubDistrict($secondType, $subDistrictId);
        }

        $this->delivery->data = $result->data;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
