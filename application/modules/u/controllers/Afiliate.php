
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\UserHandler;
use Service\MemberDigital\MemberDigitalHandler;

class Afiliate extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
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
        $userId = $auth->data['id'];
        $phoneUser = $auth->data['phone'];
        $afiliate = $this->db->from('member_digitals')->where(['phone_number'=>$phoneUser, 'deleted_at'=>NULL])->get()->row_array();
        if(!$afiliate || is_null($afiliate)){
            $this->delivery->addError(400, 'The user has not been registered in affiliate'); $this->response($this->delivery->format());
        }
        $this->delivery->data = $afiliate;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userData = $auth->data;
        $userId = $userData['id'];
        $userAuth = $userData['id_auth'];
        $userPhone = $userData['phone'];
        $existUser = $this->db->from('users')->where(['id'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$existUser || is_null($existUser)){
            $this->delivery->addError(400, 'Account not found or not registered'); $this->response($this->delivery->format());
        }
        $afiliate = $this->db->from('member_digitals')->where(['id_auth_api'=>$userAuth, 'phone_number'=>$userPhone])->order_by('id','DESC')->get()->row_array();
        $isNew = ($afiliate && !is_null($afiliate)) ? false : true;

        $payload = $this->input->post();
        $sendData = array('updated_at'=>date('Y-m-d H:i:s'),'deleted_at'=>NULL);

        //name//
        $name = $existUser['first_name'];
        if(isset($payload['name']) && $payload['name'] && !empty($payload['name']) && !is_null($payload['name'])){
            $name = $payload['name'];
        }
        if(!$name || empty($name) || is_null($name)){
            $this->delivery->addError(400, 'Name is required'); $this->response($this->delivery->format());
        }
        $sendData['name'] = $name;

        //birthday//
        $birthday = $existUser['birthdate'];
        if(isset($payload['birthdate']) && !empty($payload['birthdate']) && !is_null($payload['birthdate'])){
            $birthday = $payload['birthdate'];
        }
        if(!$birthday || !$this->wooh_support->validationDate($birthday, 'Y-m-d')){
            $this->delivery->addError(400, 'Birthdate is required'); $this->response($this->delivery->format());
        }
        $sendData['birthday'] = date('Y-m-d', strtotime($birthday));

        //gender//
        $gender = $existUser['gender'];
        $gender = ($gender==1) ? 'male' : ( ($gender==0) ? 'female' : '' );
        if(isset($payload['gender']) && $payload['gender'] && !empty($payload['gender'])){
            $gender = $payload['gender'];
        }
        if(!$gender || ($gender!='male' && $gender!='female')){
            $this->delivery->addError(400, 'Gender is required (male/female)'); $this->response($this->delivery->format());
        }
        $sendData['gender'] = $gender;

        //bank//
        $bankName = ''; $bankAccountNumber = ''; $bankAccountName = '';
        $bank = $this->db->from('user_banks')->where(['bank_user'=>$userId,'deleted_at'=>NULL])->order_by('bank_id', 'ASC')->get()->row_array();
        if($bank && !is_null($bank)){
            $bankName = $bank['bank_name']; $bankAccountNumber = $bank['bank_account_number']; $bankAccountName = $bank['bank_account_name'];
        }
        if(isset($payload['bank_name']) && $payload['bank_name'] && !empty($payload['bank_name']) && !is_null($payload['bank_name'])){
            $bankName = $payload['bank_name'];
        }
        if(isset($payload['bank_account_number']) && $payload['bank_account_number'] && !empty($payload['bank_account_number']) && !is_null($payload['bank_account_number'])){
            $bankAccountNumber = $payload['bank_account_number'];
        }
        if(isset($payload['bank_account_name']) && $payload['bank_account_name'] && !empty($payload['bank_account_name']) && !is_null($payload['bank_account_name'])){
            $bankAccountName = $payload['bank_account_name'];
        }

        if(!$bankName || empty($bankName) || is_null($bankName)){
            $this->delivery->addError(400, 'Bank name is required'); $this->response($this->delivery->format());
        }
        if(!$bankAccountNumber || empty($bankAccountNumber) || is_null($bankAccountNumber)){
            $this->delivery->addError(400, 'Bank account number is required'); $this->response($this->delivery->format());
        }
        if(!$bankAccountName || empty($bankAccountName) || is_null($bankAccountName)){
            $this->delivery->addError(400, 'Bank account name is required'); $this->response($this->delivery->format());
        }
        $sendData['bank_name'] = $bankName;
        $sendData['bank_account_number'] = $bankAccountNumber;
        $sendData['bank_account_name'] = $bankAccountName;

        //address//
        $selectAddress = ['user_address.address as address','provinces.name as province_name','districts.nama as city_name'];
        $cekAddress = $this->db->select($selectAddress)->from('user_address')->where(['user_address.user_id'=>$userId,'user_address.deleted_at'=>NULL]);
        $cekAddress = $cekAddress->join('provinces','provinces.id=user_address.province_id','left');
        $cekAddress = $cekAddress->join('districts','districts.id_kab=user_address.districts_id','left');
        $cekAddress = $cekAddress->order_by('main_address', 'DESC')->get()->row_array();
        $existAddress = ($cekAddress && !is_null($cekAddress)) ? $cekAddress : false;

        $address = ($existAddress) ? $existAddress['address'] : '';
        if(isset($payload['address']) && $payload['address'] && !empty($payload['address']) && !is_null($payload['address'])){
            $address = $payload['address'];
        }
        $province = ($existAddress) ? $existAddress['province_name'] : '';
        if(isset($payload['province']) && $payload['province'] && !empty($payload['province']) && !is_null($payload['province'])){
            $province = $payload['province'];
        }
        $city = ($existAddress) ? $existAddress['city_name'] : '';
        if(isset($payload['city']) && $payload['city'] && !empty($payload['city']) && !is_null($payload['city'])){
            $city = $payload['city'];
        }

        $sendData['address'] = $address;
        $sendData['province'] = $province;
        $sendData['city'] = $city;

        $wablasReceiver = '62895383334783';
        if(isset($payload['wablas_receiver']) && !empty($payload['wablas_receiver']) && !is_null($payload['wablas_receiver'])){
            $wablasReceiver = $payload['wablas_receiver'];
        }
        $sendData['wablas_phone_number_receiver'] = $wablasReceiver;

        if(!$isNew){
            $upData = $this->db->set($sendData)->where(['id'=>$afiliate['id']])->update('member_digitals');
            $afiliate = $this->db->from('member_digitals')->where(['id'=>$afiliate['id']])->get()->row_array();
        }else{
            $sendData['id_auth_api'] = $userAuth;
            $sendData['phone_number'] = $userPhone;
            $sendData['created_at'] = date('Y-m-d H:i:s');
            $upData = $this->db->insert('member_digitals', $sendData);
            $afiliate = $this->db->from('member_digitals')->where($sendData)->get()->row_array();
        }

        $resultId = $afiliate['id'];
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->generateMemberDigitalAttribute($resultId);
        $resultData = ($result->hasErrors()) ? $afiliate : $result->data;

        $this->delivery->data = $resultData;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function history_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId = $auth->data['id'];
        $userPhone = $auth->data['phone'];
        $payload = $this->input->get();
        
        $afiliate = $this->db->from('member_digitals')->where(['phone_number'=>$userPhone, 'deleted_at'=>NULL])->get()->row_array();
        $payload['id_member_digital'] = $afiliate['id'];

        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $transactionsResult = $handler->getMemberDigitalTransactions($payload);
        $transactionsData = $transactionsResult->data;

        if (!empty($transactionsData['result'])) {
            foreach ($transactionsData['result'] as $transaction) {
                $transaction->description = sprintf('Komisi dari pembelanjaan %s sebesar %s', $transaction->referred_member_digital_name ,toRupiahFormat($transaction->amount));
            }
        }

        $this->delivery->data = $transactionsData;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }


//=============================== END LINE ===============================//
}
