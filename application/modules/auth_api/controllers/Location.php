<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Service\CLM\Handler\ShippingLocationHandler;

class Location extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function province_get($provinceId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $isDetail = false; $payload = $this->input->get();
        $sort = array('id'=>'id','name'=>'name');
        $orderBy = 'name'; $orderVal = 'ASC';

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $select = ['provinces.id as province_id','provinces.name as province_name'];
        $filter = array('provinces.deleted_at'=>NULL);
        if($provinceId && !empty($provinceId) && !is_null($provinceId)){
            $isDetail = true; $filter['provinces.id'] = $provinceId;
        }

        $result = $this->db->select($select)->from('provinces')->where($filter);
        if(isset($payload['name']) && $payload['name'] && !empty($payload['name']) && !is_null($payload['name'])){
            $result = $result->like(['provinces.name'=>$payload['name']]);
        }
        $result = $result->order_by('provinces.'.$orderBy, $orderVal)->get();
        $provinces = ($isDetail) ? $result->row_array() : $result->result_array();

        if(!$provinces || is_null($provinces)){
            $this->delivery->addError(400, 'Provinces not found'); $this->response($this->delivery->format());
        }

        $resData = ($isDetail) ? $provinces :  array('result'=>$provinces);
        $this->delivery->data = $resData;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function district_get($districtId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $isDetail = false; $payload = $this->input->get();
        $sort = array('id'=>'id_kab','name'=>'nama');
        $orderBy = 'nama'; $orderVal = 'ASC';

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $select = [
            'districts.id_kab as district_id', 'districts.nama as district_name',
            'provinces.id as province_id','provinces.name as province_name'
        ];

        $filter = array('districts.deleted_at'=>NULL);
        if($districtId && !empty($districtId) && !is_null($districtId)){
            $isDetail = true; $filter['districts.id_kab'] = $districtId;
        }
        if(isset($payload['province']) && $payload['province'] && !empty($payload['province']) && !is_null($payload['province'])){
            $filter['districts.id_prov'] = $payload['province'];
        }

        $result = $this->db->select($select)->from('districts')->where($filter);
        if(isset($payload['name']) && $payload['name'] && !empty($payload['name']) && !is_null($payload['name'])){
            $result = $result->like(['districts.nama'=>$payload['name']]);
        }
        $result = $result->join('provinces', 'provinces.id = districts.id_prov', 'left');

        $result = $result->order_by('districts.'.$orderBy, $orderVal)->get();
        $districts = ($isDetail) ? $result->row_object() : $result->result_object();

        if(!$districts || is_null($districts)){
            $this->delivery->addError(400, 'Districts not found'); $this->response($this->delivery->format());
        }

        $handlerShippingLoc = new ShippingLocationHandler($this->MainModel);
        if($isDetail){
            $cekDesti = $handlerShippingLoc->originDestiFromDistrict('destination', $districts->district_id);
            $districts->destination = $cekDesti->data;
        }else{
            foreach($districts as $dist){
                $cekDesti = $handlerShippingLoc->originDestiFromDistrict('destination', $dist->district_id);
                $dist->destination = $cekDesti->data;
            }
        }

        $resData = ($isDetail) ? $districts :  array('result'=>$districts);
        $this->delivery->data = $resData;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function subdistrict_get($subDistrictId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $isDetail = false; $payload = $this->input->get();
        $sort = array('id'=>'id_kec','name'=>'nama');
        $orderBy = 'nama'; $orderVal = 'ASC';

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $select = [
            'sub_district.id_kec as sub_district_id', 'sub_district.nama as sub_district_name',
            'districts.id_kab as district_id','districts.nama as district_name',
            'provinces.id as province_id','provinces.name as province_name'
        ];

        $filter = array('sub_district.deleted_at'=>NULL);
        if($subDistrictId && !empty($subDistrictId) && !is_null($subDistrictId)){
            $isDetail = true; $filter['sub_district.id_kec'] = $subDistrictId;
        }
        if(isset($payload['district']) && $payload['district'] && !empty($payload['district']) && !is_null($payload['district'])){
            $filter['sub_district.id_kab'] = $payload['district'];
        }
        if(isset($payload['province']) && $payload['province'] && !empty($payload['province']) && !is_null($payload['province'])){
            $filter['provinces.id'] = $payload['province'];
        }

        $result = $this->db->select($select)->from('sub_district')->where($filter);
        if(isset($payload['name']) && $payload['name'] && !empty($payload['name']) && !is_null($payload['name'])){
            $result = $result->like(['sub_district.nama'=>$payload['name']]);
        }
        $result = $result->join('districts', 'districts.id_kab = sub_district.id_kab', 'left');
        $result = $result->join('provinces', 'provinces.id = districts.id_prov', 'left');
        $result = $result->order_by('sub_district.'.$orderBy, $orderVal)->get();
        $sub_districts = ($isDetail) ? $result->row_object() : $result->result_object();

        if(!$sub_districts || is_null($sub_districts)){
            $this->delivery->addError(400, 'Sub districts not found'); $this->response($this->delivery->format());
        }

       $handlerShippingLoc = new ShippingLocationHandler($this->MainModel);
        if($isDetail){
            $cekDesti = $handlerShippingLoc->destinationFromSubdistrict($sub_districts->sub_district_id);
            if(!$cekDesti->data || is_null($cekDesti->data)){
                $cekDesti = $handlerShippingLoc->originDestiFromDistrict('destination', $sub_districts->district_id);
            }
            $sub_districts->destination = $cekDesti->data;
        }else{
            foreach($sub_districts as $sub_dist){
                $cekDesti = $handlerShippingLoc->destinationFromSubdistrict($sub_dist->sub_district_id);
                if(!$cekDesti->data || is_null($cekDesti->data)){
                    $cekDesti = $handlerShippingLoc->originDestiFromDistrict('destination', $sub_dist->district_id);
                }
                $sub_dist->destination = $cekDesti->data;
            }
        }

        $resData = ($isDetail) ? $sub_districts :  array('result'=>$sub_districts);
        $this->delivery->data = $resData;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function village_get($villageId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAll($secret);
        if(!$auth || empty($auth) || is_null($auth)){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $isDetail = false; $payload = $this->input->get();
        $sort = array('id'=>'id_kel','name'=>'nama');
        $orderBy = 'nama'; $orderVal = 'ASC';

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $select = [
            'urban_village.id_kel as village_id', 'urban_village.nama as village_name',
            'sub_district.id_kec as sub_district_id', 'sub_district.nama as sub_district_name',
            'districts.id_kab as district_id','districts.nama as district_name',
            'provinces.id as province_id','provinces.name as province_name'
        ];

        $filter = array('urban_village.deleted_at'=>NULL);
        if($villageId && !empty($villageId) && !is_null($villageId)){
            $isDetail = true; $filter['urban_village.id_kel'] = $villageId;
        }
        if(isset($payload['subdistrict']) && $payload['subdistrict'] && !empty($payload['subdistrict']) && !is_null($payload['subdistrict'])){
            $filter['urban_village.id_kec'] = $payload['subdistrict'];
        }
        if(isset($payload['district']) && $payload['district'] && !empty($payload['district']) && !is_null($payload['district'])){
            $filter['districts.id_kab'] = $payload['district'];
        }
        if(isset($payload['province']) && $payload['province'] && !empty($payload['province']) && !is_null($payload['province'])){
            $filter['provinces.id'] = $payload['province'];
        }

        $result = $this->db->select($select)->from('urban_village')->where($filter);
        if(isset($payload['name']) && $payload['name'] && !empty($payload['name']) && !is_null($payload['name'])){
            $result = $result->like(['urban_village.nama'=>$payload['name']]);
        }
        $result = $result->join('sub_district', 'sub_district.id_kec = urban_village.id_kec', 'left');
        $result = $result->join('districts', 'districts.id_kab = sub_district.id_kab', 'left');
        $result = $result->join('provinces', 'provinces.id = districts.id_prov', 'left');
        $result = $result->order_by('urban_village.'.$orderBy, $orderVal)->get();
        $villages = ($isDetail) ? $result->row_array() : $result->result_array();

        if(!$villages || is_null($villages)){
            $this->delivery->addError(400, 'Village not found'); $this->response($this->delivery->format());
        }

        $resData = ($isDetail) ? $villages :  array('result'=>$villages);
        $this->delivery->data = $resData;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }


//======================================= END LINE =======================================//
}
