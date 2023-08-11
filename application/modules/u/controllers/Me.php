
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\UserHandler;
use Library\WablasService;

class Me extends REST_Controller {

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

        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getUserEntity();

        $this->response($result->format(), $result->getStatusCode());
    }

    public function banks_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getBanks();

        $this->response($result->format(), $result->getStatusCode());
    }

    public function notifications_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getNotifications();

        $this->response($result->format(), $result->getStatusCode());
    }

    public function last_view_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getLastViewProduct();

        $this->response($result->format(), $result->getStatusCode());
    }

    public function bank_profile_get ($bankDetail = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId = $auth->data['id'];
        $existUser = $this->db->select(['id','bank_name','bank_account_number','bank_account_name'])->from('users')->where(['id'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$existUser || is_null($existUser)){
             $this->delivery->addError(400, 'Account not found or no longer available'); $this->response($this->delivery->format());
        }

        $countBank = $this->db->from('user_banks')->where(['bank_user'=>$userId,'deleted_at'=>NULL])->count_all_results();
        if($countBank==0){
            if($existUser['bank_account_number'] && !empty($existUser['bank_account_number']) && !is_null($existUser['bank_account_number'])){
                $newBank = array();
                $newBank['bank_user'] = $existUser['id'];
                $newBank['bank_name'] = $existUser['bank_name'];
                $newBank['bank_account_name'] = $existUser['bank_account_name'];
                $newBank['bank_account_number'] = $existUser['bank_account_number'];
                $newBank['created_at'] = date('Y-m-d H:i:s');
                $newBank['updated_at'] = date('Y-m-d H:i:s');
                $upBank = $this->db->insert('user_banks', $newBank);
            }
        }

        $payload = $this->input->get();
        $sort = array('name'=>'bank_name','account_name'=>'bank_account_name','account_number'=>'bank_account_number');
        $orderBy = 'created_at'; $orderVal = 'DESC';
        $argsBank = ['bank_id','bank_name','bank_account_name','bank_account_number'];
        $filter = array('bank_user'=>$userId,'deleted_at'=>NULL);

        $isDetail = ($bankDetail && !empty($bankDetail) && !is_null($bankDetail)) ? $bankDetail : false;
        if($isDetail){
            $isBank = $this->db->from('user_banks')->where($filter)->group_start();
            $isBank = $isBank->where('bank_id', $isDetail)->or_where('bank_account_number', $isDetail)->group_end()->get()->row_array();
            if(!$isBank || is_null($isBank)){
                $this->delivery->addError(400, 'Bank not found or no longer available'); $this->response($this->delivery->format());
            }
            $this->delivery->data = $isBank;
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

        $result = $this->db->select($argsBank)->from('user_banks')->where($filter);
        foreach($sort as $k_sort=>$srt){
             if(isset($payload[$k_sort]) && $payload[$k_sort] && !empty($payload[$k_sort]) && !is_null($payload[$k_sort])){
                $result = $result->like([$srt=>$payload[$k_sort]]);
            }
        }
        $countData = $result->count_all_results('', false);
        if($countData==0){
            $this->delivery->addError(400, 'Bank not found or not yet available'); $this->response($this->delivery->format());
        }
        $result = $result->order_by($orderBy, $orderVal);

        $payload['sort_by'] = $orderBy;
        $payload['sort_value'] = $orderVal;
        $payload['limit'] = (isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data'])) ? $payload['data'] : '';
        $forPager = $this->wooh_support->pagerData($payload, $countData, [], ['sort'=>$orderBy]);
        $pagination = $forPager['data'];
        $result = $result->limit($pagination['limit'], $forPager['offset']);

        $resData = $result->get()->result_array();
        $banks = array('result'=>$resData);
        foreach($pagination as $k_pg=>$pg){ $banks[$k_pg] = $pg; }

        $this->delivery->data = $banks;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function bank_profile_post ($bankId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $userId = $auth->data['id'];
        $existUser = $this->db->select(['id','bank_name','bank_account_number','bank_account_name'])->from('users')->where(['id'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$existUser || is_null($existUser)){
             $this->delivery->addError(400, 'Account not found or no longer available'); $this->response($this->delivery->format());
        }

        $isDetail = ($bankId && !empty($bankId) && !is_null($bankId)) ? $bankId : false;
        if($isDetail){
            $isBank = $this->db->from('user_banks')->where(['bank_id'=>$isDetail,'bank_user'=>$userId,'deleted_at'=>NULL])->get()->row_array();
            if(!$isBank || is_null($isBank)){
                $this->delivery->addError(400, 'Bank not found or no longer available'); $this->response($this->delivery->format());
            }
        }

        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $bankList = $handler->getBanks();
        $bankData = $bankList->data;

        $payload = $this->input->post();
        if (!isset($payload['bank_name']) || empty($payload['bank_name']) || !in_array(strtolower($payload['bank_name']), $bankData)) {
            $this->delivery->addError(400, 'Bank name is required'); $this->response($this->delivery->format());
        }
        $payload['bank_name'] = strtolower($payload['bank_name']);
        
        if (!isset($payload['bank_account_name']) || empty($payload['bank_account_name']) || is_null($payload['bank_account_name'])) {
            $this->delivery->addError(400, 'Bank account name is required'); $this->response($this->delivery->format());
        }
        if (!isset($payload['bank_account_number']) || empty($payload['bank_account_number']) || is_null($payload['bank_account_number'])) {
            $this->delivery->addError(400 ,'Bank account number is required'); $this->response($this->delivery->format());
        }

        $argsCheck = array('bank_user'=>$userId,'bank_account_number'=>$payload['bank_account_number'],'deleted_at'=>NULL);
        if($isDetail){
            $argsCheck['bank_id !='] = $isDetail;
        }
        $banks = $this->db->from('user_banks')->where($argsCheck)->get()->row_array();
        if($banks && !is_null($banks)){
            $this->delivery->addError(400, 'The bank with that number is already available in your account'); $this->response($this->delivery->format());
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');
        if(!$isDetail){
            $payload['bank_user'] = $existUser['id'];
            $payload['created_at'] = date('Y-m-d H:i:s');
            $upBank = $this->db->insert('user_banks', $payload);
            $filterBank = $payload;
        }else{
            $upBank = $this->db->set($payload)->where(['bank_id'=>$isDetail])->update('user_banks');
            $filterBank = ['bank_id'=>$isDetail];
        }
        
        $argsBank = ['bank_id','bank_name','bank_account_name','bank_account_number'];
        $thisBank = $this->db->select($argsBank)->from('user_banks')->where($filterBank)->get()->row_array();
        $this->delivery->data = $thisBank;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function bank_profile_delete ($bankId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $userId = $auth->data['id'];
        $existUser = $this->db->select(['id','bank_name','bank_account_number','bank_account_name'])->from('users')->where(['id'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$existUser || is_null($existUser)){
             $this->delivery->addError(400, 'Account not found or no longer available'); $this->response($this->delivery->format());
        }

        if(!$bankId || empty($bankId) || is_null($bankId)){
            $this->delivery->addError(400, 'Bank ID is required'); $this->response($this->delivery->format());
        }

        $isBank = $this->db->from('user_banks')->where(['bank_id'=>$bankId,'bank_user'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$isBank || is_null($isBank)){
            $this->delivery->addError(400, 'Bank not found or no longer available'); $this->response($this->delivery->format());
        }

        $dateNow = date('Y-m-d H:i:s');
        $upBank = $this->db->set(['deleted_at'=>$dateNow])->where(['bank_id'=>$isBank['bank_id']])->update('user_banks');
        $this->delivery->data = 'The bank was successfully removed from the account';
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function update_bank_profile_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = [
            'bank_name' => $this->input->post('bank_name'),
            'bank_account_number' => $this->input->post('bank_account_number'),
            'bank_account_name' => $this->input->post('bank_account_name')
        ];
        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->updateBankProfile($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_profile_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId = $auth->data['id'];
        $userPhone = $auth->data['phone'];

        $payload = array();
        $postData = $this->input->post();
        if(isset($postData['username'])){
            if(!$postData['username'] || empty($postData['username']) || is_null($postData['username'])){
                $this->delivery->addError(400, 'Username is required if changed'); $this->response($this->delivery->format());
            }
            $existUsername = $this->db->from('users')->where(['username'=>$postData['username'],'id !='=>$userId,'deleted_at'=>NULL])->get()->row_array();
            if ($existUsername && !is_null($existUsername)) {
                $this->delivery->addError(400, 'Username already taken'); $this->response($this->delivery->format());
            }
            $payload['username'] = $postData['username'];
        }
        if(isset($postData['first_name'])){
            if(!$postData['first_name'] || empty($postData['first_name']) || is_null($postData['first_name'])){
                $this->delivery->addError(400, 'Name is required if changed'); $this->response($this->delivery->format());
            }
            $payload['first_name'] = $postData['first_name'];
        }
        if(isset($postData['birthdate'])){
            if(!$postData['birthdate'] || empty($postData['birthdate']) || is_null($postData['birthdate']) || empty(strtotime($postData['birthdate']))){
                $this->delivery->addError(400, 'Birthdate is required if changed'); $this->response($this->delivery->format());
            }
            $payload['birthdate'] = date('Y-m-d', strtotime($postData['birthdate']));
        }

        $changePhone = false;
        if(isset($postData['phone'])){
            if(!$postData['phone'] || empty($postData['phone']) || is_null($postData['phone'])){
                $this->delivery->addError(400, 'Phone is required if changed'); $this->response($this->delivery->format());
            }
            $phone = getFormattedPhoneNumber($postData['phone']);
            $existPhone = $this->db->from('users')->where(['phone'=>$phone,'id !='=>$userId,'deleted_at'=>NULL])->get()->row_array();
            if ($existPhone && !is_null($existPhone)) {
                $this->delivery->addError(400, 'Phone already taken'); $this->response($this->delivery->format());
            }
            $payload['phone'] = $phone;
            $changePhone = ($phone==$userPhone) ? false : $phone;
        }

        if(isset($postData['email'])){
            if(!$postData['email'] || empty($postData['email']) || is_null($postData['email'])){
                $this->delivery->addError(400, 'Email is required if changed'); $this->response($this->delivery->format());
            }
            $existEmail = $this->db->from('users')->where(['email'=>$postData['email'],'id !='=>$userId,'deleted_at'=>NULL])->get()->row_array();
            if ($existEmail && !is_null($existEmail)) {
                $this->delivery->addError(400, 'Email already taken'); $this->response($this->delivery->format());
            }
            $payload['email'] = $postData['email'];
        }

        if(isset($postData['gender'])){
            if($postData['gender']!='0' && $postData['gender']!='1'){
                $this->delivery->addError(400, 'Gender is required if changed'); $this->response($this->delivery->format());
            }
            $payload['gender'] = intval($postData['gender']);
        }

        //$birthdate = $this->input->post('birthdate');

        //if (empty($birthdate) || empty(strtotime($birthdate))) {
            //$this->delivery->addError(400, 'Invalid birthdate format');
            //$this->response($this->delivery->format(), $this->delivery->getStatusCode());
        //}
        //$birthdate = date('Y-m-d', strtotime($birthdate));

        //$payload = [
            //'birthdate' => $birthdate
        //];
        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->update($payload);

        if($changePhone){
            $upData = $this->db->set(['phone_number'=>$changePhone])->where(['phone_number'=>$userPhone])->update('member_digitals');
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_profile_picture_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->updateProfilePicture($this->input->post());

        $this->response($result->format(), $result->getStatusCode());   
    }

    public function onesignal_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->createOneSignalPlayerId($this->input->post('player_id'));

        $this->response($result->format(), $result->getStatusCode());
    }

    public function onesignal_test_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $title = $this->input->post('title');
        $message = $this->input->post('message');
        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->sendPushNotif($title, $message);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function close_account_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $handler = new UserHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->closeAccount();

        $this->response($result->format(), $result->getStatusCode());
    }


    function reset_password_get() { 
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId = $auth->data['id'];
        $existUser = $this->db->select(['id','username','phone','email','password'])->from('users')->where(['id'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$existUser || is_null($existUser)){
             $this->delivery->addError(400, 'Account not found or no longer available'); $this->response($this->delivery->format());
        }

        $newPassword = generateRandomString(6);
        $password = password_hash($newPassword, PASSWORD_DEFAULT);
        $upPassword = $this->db->set(['password'=>$password])->where(['id'=>$existUser['id']])->update('users');

        //$notifType = $this->input->post('notif_type');
        $message = 'Your new password is _*'.$newPassword.'*_';
        $wablasConfig = $this->wooh_support->wablasConfig();
        $wablas = new WablasService($wablasConfig['domain'], $wablasConfig['token']);
        $notifAction = $wablas->sendMessage($existUser['phone'], $message);

        $this->delivery->data = $notifAction;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    function update_password_post() { 
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId = $auth->data['id'];
        $existUser = $this->db->select(['id','username','phone','email','password'])->from('users')->where(['id'=>$userId,'deleted_at'=>NULL])->get()->row_array();
        if(!$existUser || is_null($existUser)){
             $this->delivery->addError(400, 'Account not found or no longer available'); $this->response($this->delivery->format());
        }

        $payload = $this->input->post();
        $passwordNow = false;
        if(isset($payload['password']) && $payload['password'] && !empty($payload['password']) && !is_null($payload['password'])){
            $passwordNow = $payload['password'];
        }
        if(!$passwordNow){
            $this->delivery->addError(400, 'Current password is required'); $this->response($this->delivery->format());
        }

        $passwordNew = false;
        if(isset($payload['new_password']) && $payload['new_password'] && !empty($payload['new_password']) && !is_null($payload['new_password'])){
            $passwordNew = $payload['new_password'];
        }
        if(!$passwordNew){
            $this->delivery->addError(400, 'New password is required'); $this->response($this->delivery->format());
        }

        $passwordNewConfirm = false;
        if(isset($payload['new_password_confirm']) && $payload['new_password_confirm'] && !empty($payload['new_password_confirm']) && !is_null($payload['new_password_confirm'])){
            $passwordNewConfirm = $payload['new_password_confirm'];
        }
        if(!$passwordNewConfirm || ($passwordNewConfirm!=$passwordNew)){
            $this->delivery->addError(400, 'New password confirmation does not match'); $this->response($this->delivery->format());
        }

        if (!password_verify($passwordNow, $existUser['password'])) {
            $this->delivery->addError(400, 'The current password does not match'); $this->response($this->delivery->format());
        }

        $setPassword = password_hash($passwordNew, PASSWORD_DEFAULT);
        $upPassword = $this->db->set(['password'=>$setPassword])->where(['id'=>$existUser['id']])->update('users');
        $this->delivery->data = 'Password updated successfully';
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }



}
