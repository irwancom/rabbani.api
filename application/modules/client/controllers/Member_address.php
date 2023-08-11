<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Andri\Engine\Shared\Presenter;
use Andri\Engine\Client\Domain\MemberAddress\UseCases\ListMemberAddress;
use Andri\Engine\Client\Domain\MemberAddress\UseCases\StoreMemberAddress;
use Andri\Engine\Client\Domain\MemberAddress\UseCases\UpdateMemberAddress;
use Service\CLM\Handler\ShippingLocationHandler;

class Member_address extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('MemberAddresses');
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
        $this->presenter = new Presenter;
    }

    public function index_get($addressId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId = $auth->data['id'];
        $handlerShippingLoc = new ShippingLocationHandler($this->MainModel);

        $payload = $this->input->get();
        $sort = array('name'=>'address_name');
        $orderBy = 'created_at'; $orderVal = 'DESC';
        $argsAddress = $this->MemberAddresses->handleSelect();
        $filter = array('user_address.user_id'=>$userId,'user_address.deleted_at'=>NULL);

        $isDetail = ($addressId && !empty($addressId) && !is_null($addressId)) ? $addressId : false;
        if($isDetail){
            $filterDetail = $filter;
            $filterDefault = ($addressId=='default') ? true : false;
            $fieldData = ($filterDefault) ? 'main_address' : 'id';
            $fieldValue = ($filterDefault) ? 1 : intval($addressId);
            $filterDetail['user_address.'.$fieldData] = $fieldValue;
            $isAddress = $this->db->from('user_address')->where($filterDetail)->get()->row_object();

            if(!$isAddress || is_null($isAddress)){
                if($filterDefault){
                    $isAddress = $this->db->from('user_address')->where($filter)->order_by($orderBy, $orderVal)->get()->row_object();
                }
            }
            if(!$isAddress || is_null($isAddress)){
                $this->delivery->addError(400, 'Address not found or no longer available'); $this->response($this->delivery->format());
            }

            $setDestination = null;
            if($isAddress->sub_district_id && !empty($isAddress->sub_district_id) && !is_null($isAddress->sub_district_id)){
                $cekDesti = $handlerShippingLoc->destinationFromSubdistrict($isAddress->sub_district_id);
                $setDestination = $cekDesti->data;
            }
            if(!$setDestination || is_null($setDestination)){
                if($isAddress->districts_id && !empty($isAddress->districts_id) && !is_null($isAddress->districts_id)){
                    $cekDesti = $handlerShippingLoc->originDestiFromDistrict('destination', $isAddress->districts_id);
                    $setDestination = $cekDesti->data;
                }
            }
            $isAddress->destination = $setDestination;
            if($setDestination && !is_null($setDestination)){
                $thisDestination = $setDestination[0];
                $isAddress->city_name = $thisDestination->city_name;
                $isAddress->city_code = $thisDestination->city_code;
            }

            $this->delivery->data = $isAddress;
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $result = $this->db->select($argsAddress)->from('user_address')->where($filter);
        foreach($sort as $k_sort=>$srt){
             if(isset($payload[$k_sort]) && $payload[$k_sort] && !empty($payload[$k_sort]) && !is_null($payload[$k_sort])){
                $result = $result->like([$srt=>$payload[$k_sort]]);
            }
        }
        $result = $result->join('provinces', 'provinces.id=user_address.province_id', 'left');
        $result = $result->join('districts', 'districts.id_kab=user_address.districts_id', 'left');
        $result = $result->join('sub_district', 'sub_district.id_kec=user_address.sub_district_id', 'left');
        $result = $result->join('urban_village', 'urban_village.id_kel=user_address.urban_village_id', 'left');

        $countData = $result->count_all_results('', false);
        if($countData==0){
            $this->delivery->addError(400, 'Address not found or not yet available'); $this->response($this->delivery->format());
        }
        $result = $result->order_by('main_address', 'DESC');
        $result = $result->order_by($orderBy, $orderVal);

        $payload['sort_by'] = $orderBy;
        $payload['sort_value'] = $orderVal;
        $payload['limit'] = (isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data'])) ? $payload['data'] : '';
        $forPager = $this->wooh_support->pagerData($payload, $countData, [], ['sort'=>$orderBy]);
        $pagination = $forPager['data'];
        $result = $result->limit($pagination['limit'], $forPager['offset']);
        $resData = $result->get()->result_object();

        foreach($resData as $addr){
            $setDestination = null;
            if($addr->sub_district_id && !empty($addr->sub_district_id) && !is_null($addr->sub_district_id)){
                $cekDesti = $handlerShippingLoc->destinationFromSubdistrict($addr->sub_district_id);
                $setDestination = $cekDesti->data;
            }
            if(!$setDestination || is_null($setDestination)){
                if($addr->districts_id && !empty($addr->districts_id) && !is_null($addr->districts_id)){
                    $cekDesti = $handlerShippingLoc->originDestiFromDistrict('destination', $addr->districts_id);
                    $setDestination = $cekDesti->data;
                }
            }
            $addr->destination = $setDestination;
            if($setDestination && !is_null($setDestination)){
                $thisDestination = $setDestination[0];
                $addr->city_name = $thisDestination->city_name;
                $addr->city_code = $thisDestination->city_code;
            }
        }

        $addresses = array('result'=>$resData);
        foreach($pagination as $k_pg=>$pg){ $addresses[$k_pg] = $pg; }

        $this->delivery->data = $addresses;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function index_v1_get($id = null) {
        if ($id)
            return $this->_detail($id);
        $this->_index();
    }

    // GET
    // ===========================================================================

    private function _index() {
        if (!$user = validate_token_user())
            return $this->response(failed_format(403), 403);

        $list = new ListMemberAddress($this->MemberAddresses);
        $options = $_GET;
        $options['user_id'] = $user->id;
        $list->execute($options, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $result = $this->presenter->data;
        $data = $result['data'];
        $totalItem = $result['totalItem'];
        $totalPage = $result['totalPage'];

        $this->response(success_format($data, '', $totalItem, $totalPage), 200);
    }

    private function _detail($id) {

        if (!$user = validate_token_user())
            return $this->response(failed_format(403), 403);

        $filterDefault = ($id=='default') ? true : false;
        $fieldData = ($filterDefault) ? 'main_address' : 'id';
        $fieldValue = ($filterDefault) ? 1 : intval($id);
        $memberAddress = $this->MemberAddresses->detailByFields([
            'user_id' => $user->id,
            $fieldData => $fieldValue
        ]);

        if($filterDefault && !$memberAddress){
            $memberAddress = $this->MemberAddresses->detailByFields([
                'user_id' => $user->id,
                'deleted_at' => NULL
            ]);
        }

        if (!$memberAddress)
        return $this->response(failed_format(404, ['error.member_address.global.not_found']));
        $this->response(success_format($memberAddress), 200);
    }

    // POST
    // ===========================================================================

    public function index_post($id = null, $endpoint = null) {
        if ($id)
            return $this->_update($id);
        $this->_store();
    }

    private function _set_default_location_address($data = []) {
        $handlerShippingLoc = new ShippingLocationHandler($this->MainModel);
        $result = array(); $foundName = false; $foundCode = false;
        if(isset($data['city_name']) && $data['city_name'] && !empty($data['city_name']) && !is_null($data['city_name'])){
            $result['city_name'] = $data['city_name'];
            $foundName = true;
        }
        if(isset($data['city_code']) && $data['city_code'] && !empty($data['city_code']) && !is_null($data['city_code'])){
            $result['city_code'] = $data['city_code'];
            $foundCode = true;
        }

        if(!$foundName || !$foundCode){
            $setDestination = null;
            if(isset($data['sub_district_id']) && $data['sub_district_id'] && !empty($data['sub_district_id']) && !is_null($data['sub_district_id'])){
                $cekDesti = $handlerShippingLoc->destinationFromSubdistrict($data['sub_district_id']);
                $setDestination = $cekDesti->data;
            }

            if(!$setDestination || is_null($setDestination)){
                if(isset($data['districts_id']) && $data['districts_id'] && !empty($data['districts_id']) && !is_null($data['districts_id'])){
                    $cekDesti = $handlerShippingLoc->originDestiFromDistrict('destination', $data['districts_id']);
                    $setDestination = $cekDesti->data;
                }
            }

            if($setDestination && !is_null($setDestination)){
                $isDestination = $setDestination[0];
                $result['city_code'] = $isDestination->city_code;
                $result['city_name'] = $isDestination->city_name;
            }
        }
        return $result;
    }

    private function _store() {
        if (!$user = validate_token_user())
            return $this->response(failed_format(403), 403);
        $data = $this->input->post();
        if(isset($data['district_id'])){
            if($data['district_id'] && !empty($data['district_id']) && !is_null($data['district_id'])){
                $data['districts_id'] = $data['district_id'];
            }
            unset($data['district_id']);
        }
        if(isset($data['default'])){
            if($data['default']=='1' || $data['default']=='0'){
                $data['main_address'] = intval($data['default']);
            }
            unset($data['default']);
        }

        $cekLocation = $this->_set_default_location_address($data);
        if(isset($cekLocation['city_name'])){
            $data['city_name'] = $cekLocation['city_name'];
        }
        if(isset($cekLocation['city_code'])){
            $data['city_code'] = $cekLocation['city_code'];
        }
        if(!isset($data['urban_village_id']) || !$data['urban_village_id'] || empty($data['urban_village_id']) || is_null($data['urban_village_id'])){
            $data['urban_village_id'] = 0;
        }
        
        $data['user_id'] = $user->id;
        $listMemberAddress = new StoreMemberAddress($this->MemberAddresses);
        $listMemberAddress->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        if($data->main_address==1){
            $upMainAddress = $this->db->set(['main_address'=>0])->where(['user_id'=>$user->id,'id !='=>$data->id])->update('user_address');
        }
        $this->response(success_format($data), 200);
    }

    private function _update($id) {
        if (!$user = validate_token_user())
            return $this->response(failed_format(403), 403);
        $data = $this->input->post();

        if(isset($data['district_id'])){
            if($data['district_id'] && !empty($data['district_id']) && !is_null($data['district_id'])){
                $data['districts_id'] = $data['district_id'];
            }
            unset($data['district_id']);
        }
        if(isset($data['default'])){
            if($data['default']=='1' || $data['default']=='0'){
                $data['main_address'] = intval($data['default']);
            }
            unset($data['default']);
        }

        $cekLocation = $this->_set_default_location_address($data);
        if(isset($cekLocation['city_name'])){
            $data['city_name'] = $cekLocation['city_name'];
        }
        if(isset($cekLocation['city_code'])){
            $data['city_code'] = $cekLocation['city_code'];
        }
        
        $data['user_id'] = $user->id;
        $data['id'] = $id;
        $data['id_auth'] = $user->id_auth;

        $updateMemberAddress = new UpdateMemberAddress($this->MemberAddresses);
        $updateMemberAddress->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;

        if($data->main_address==1){
            $existOtherMain = $this->db->from('user_address')->where(['user_id'=>$user->id,'id !='=>$data->id,'main_address'=>1])->count_all_results();
            if($existOtherMain>0){
                $upMainAddress = $this->db->set(['main_address'=>0])->where(['user_id'=>$user->id,'id !='=>$data->id])->update('user_address');
            }
        }
        $this->response(success_format($data, 'success.member_address.global.successfully_updated'));
    }

    // DELETE
    // ===========================================================================

    public function index_delete($addressId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $userId = $auth->data['id'];
        if(!$addressId || empty($addressId) || is_null($addressId)){
            $this->delivery->addError(400, 'Address ID is required'); $this->response($this->delivery->format());
        }

        $address = $this->db->from('user_address')->where(['id'=>$addressId,'user_id'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$address || is_null($address)){
            $this->delivery->addError(400, 'Address not found or no longer available'); $this->response($this->delivery->format());
        }

        $dateNow = date('Y-m-d H:i:s');
        $upAddress = $this->db->set(['deleted_at'=>$dateNow])->where(['id'=>$address['id']])->update('user_address');
        $this->delivery->data = 'The address was successfully removed from the account';
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function index_v1_delete($id, $endpoint = null) {
        $this->_delete_member_address($id);
    }

    public function _delete_member_address($id) {
        if (!$user = validate_token_user())
            return $this->response(failed_format(403), 403);

        $memberAddress = $this->MemberAddresses->detailByFields([
            'user_id' => $user->id,
            'id' => (int) $id
        ]);

        if (!$memberAddress) {
            $errors = failed_format(404, ['member_address' => 'error.member_address.global.not_found']);
            return $this->response($errors, 404);
        }

        $result = $this->MemberAddresses->update($memberAddress, ['deleted_at' => date('Y-m-d h:i:s')]);
        if ($result) {
            return $this->response(
                            success_format(
                                    ['success' => true], 'success.member_address.global.successfully_deleted'
                            )
            );
        }

        $errors = failed_format(401, ['member_address' => 'error.member_address.global.failed_to_delete']);
        return $this->response($errors, 401);
    }

}
