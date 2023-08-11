<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;

class Voucher extends REST_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function index_get ($voucherId = NULL) {
        $secret = $this->input->get_request_header('X-Token-Secret');

        $payload = $this->input->get();
        $sort = array('id'=>'id','code'=>'code','name'=>'name','created'=>'created_at');
        $orderBy = 'created_at'; $orderVal = 'DESC';

        if(isset($payload['sort_by']) && $payload['sort_by'] && !empty($payload['sort_by']) && !is_null($payload['sort_by'])){
            $payload['order_by'] = $payload['sort_by'];
        }
        if(isset($payload['sort_value']) && $payload['sort_value'] && !empty($payload['sort_value']) && !is_null($payload['sort_value'])){
            $payload['order_value'] = $payload['sort_value'];
        }
        if(isset($payload['limit']) && $payload['limit'] && !empty($payload['limit']) && !is_null($payload['limit'])){
            $payload['data'] = $payload['limit'];
        }

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $filter = array('vouchers.deleted_at'=>NULL);
        $isDetail = ($voucherId && !empty($voucherId) && !is_null($voucherId)) ? $voucherId : false;
        $readyId = (isset($payload['id']) && $payload['id'] && !empty($payload['id']) && !is_null($payload['id'])) ? $payload['id'] : false;
        $readyCode = (isset($payload['code']) && $payload['code'] && !empty($payload['code']) && !is_null($payload['code'])) ? $payload['code'] : false;

        if($isDetail || $readyId){
            $forCekDetail = ($isDetail) ? ( ($this->wooh_support->isNomor($isDetail)) ? 'id' : 'code' ) : 'id';
            $filter['vouchers.'.$forCekDetail] = ($isDetail) ? $isDetail : $readyId;
        }
        if($readyCode){
            $filter['vouchers.code'] = $readyCode;
        }

        if(isset($payload['current_time']) && strlen($payload['current_time'])>0 && $this->wooh_support->validationDate($payload['current_time'])){
            $filter['start_time <='] = $payload['current_time'];
            $filter['end_time >='] = $payload['current_time'];
        }

        $result = $this->db->from('vouchers')->where($filter);
        if(isset($payload['q']) && $payload['q'] && !empty($payload['q']) && !is_null($payload['q'])){
            $result = $result->like(['vouchers.name'=>$payload['q']]);
        }

        $countData = $result->count_all_results('', false);
        if($countData==0){
            $this->delivery->addError(400, 'Voucher not found'); $this->response($this->delivery->format());
        }

        $result = $result->order_by('vouchers.'.$orderBy, $orderVal);
        $vouchers = [];
        if($isDetail){
            $vouchers = $result->get()->row_array();
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
            $vouchers = array('result'=>$resData);
            if($pagination && !is_null($pagination)){
                foreach($pagination as $k_pg=>$pg){ $vouchers[$k_pg] = $pg; }
            }
        }

        $this->delivery->data = $vouchers;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

//============================================== END LINE ============================================================//
}
