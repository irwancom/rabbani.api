<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Presenter;
use Andri\Engine\Auth\UseCases\Login;
use Service\Delivery;
use Service\Entity;
use Service\Validator;
use Service\CLM\Handler\UserHandler;
use Library\WablasService;
use Library\SMSService;
use Library\TripayGateway;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

class Oauth extends REST_Controller {

    const EMAIL_FROM = 'Admin <no-reply@1itmedia.co.id>';
    const EMAIL_DOMAIN = 'mg.1itmedia.co.id';
    const EMAIL_KEY = 'key-0d89204653627cc8cbba67684cfff390';
    const WABLAS_DOMAIN = 'https://solo.wablas.com';
    const WABLAS_TOKEN = 'CZrRIT5qo1GNYdiFXySxc0oW4oINZ5WZmLi40HlHHAushg4S1GlSfnSTHQfJEQgs';
    private $delivery;
    private $sms;

    public function __construct() {
        parent::__construct();

        $this->load->model('MainModel');
        $this->load->library('Mailgun');
        // $this->load->library('Sms');
        $this->load->model('Auth');
        $this->load->library('Wooh_support');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
        $this->entity = new Entity;
        $this->sms = new SMSService;
    }

    function index_get() {
        echo 'access denied';
        exit;
    }

// USER
    function index_post() {
        $presenter = new Presenter;
        $login = new Login($this->Auth);
        $login->execute($this->input->post(), $presenter);
        
        if ($presenter->hasError()) {
            $errors = array_map(function($error) {
                return $error[0];
            }, array_values($presenter->errors));

            $this->response([
                'status' => 'failed',
                'error' => $errors[0],
                'code' => 401
                    ], 401);
        }

        $data = $presenter->data;
        $this->response(success_format($data), 200);
    }

    function user_post($sl='') {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        $phoneNumber = getFormattedPhoneNumber($username);
        if (!$phoneNumber) {
            $phoneNumber = $username;
        }

        $args = [
            'email' => $username,
            'username' => $username,
            'phone' => $phoneNumber
        ];
        $orWhere[] = $args;

        $where = null;
        if($sl=='res'){
            $where = ['is_reseller'=>1];
        }else{
            $where = ['is_reseller'=>0];
        }
        
        $existsUser = $this->MainModel->findOne('users', $where, $orWhere);
        $dataExistsUser = $this->MainModel->findOne('users', null, $orWhere);

        if (empty($existsUser)) {
            if (!empty($dataExistsUser)) {
                $data = [
                    'success' => false,
                    'code' => 404,
                    'data' => [
                        'uname' => $dataExistsUser->username,
                        'is_reseller' => $dataExistsUser->is_reseller,
                        'message' => 'Username active on reseller.'
                    ],
                    'message' => 'Username not exist.'
                ];
            }else{
                $data = [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Username not exist.'
                ];
            }
            return $this->returnJSON($data);
        }
        if (!password_verify($password, $existsUser->password)) {
            $data = [
                'success' => false,
                'code' => 400,
                'message' => 'Username or password incorrect.'
            ];
            return $this->returnJSON($data);
        }

        $secret  = $existsUser->secret;
        if($sl == 'skip_secret'){
            $secret  = $existsUser->secret;
        }else{
            if (empty($existsUser->secret)) {
                $secret = $this->createSecret($username, $password);
                $updateData = [
                    'secret' => $secret,
                ];
                $action = $this->MainModel->update('users', $updateData, ['id' => $existsUser->id]);
            } else {
                $secret = $existsUser->secret;
            }
        }
        
        $data = [
            'data' => [
                'uname' => $existsUser->username,
                'phone' => $existsUser->phone,
                'email' => $existsUser->email,
                'first_name' => $existsUser->first_name,
                'last_name' => $existsUser->last_name,
                'name' => sprintf("%s %s", $existsUser->first_name, $existsUser->last_name),
                // 'born' => $existsUser->born,
                'secret' => $secret,
                // 'is_reseller' => $existsUser->is_reseller
            ],
            'code' => 200
        ];
        return $this->returnJSON($data);
    }

    function user_register_post () {
        $username = $this->input->post('username');
        $email = $this->input->post('email');
        $phone = $this->input->post('phone');
        $password = $this->input->post('password');
        $cpassword = $this->input->post('cpassword');
        $firstName = $this->input->post('first_name');
        $lastName = $this->input->post('last_name');
        $googleVerificationPayload = $this->input->post('google_verification_payload');

        $forms = [
            'username',
            // 'email',
            'phone',
            'password',
            'cpassword',
            'first_name',
        ];

        /* foreach ($forms as $form) {
            if (empty($this->input->post($form))) {
                $this->delivery->addError(404, 'Missing required parameters.');
                $this->response($this->delivery->format());
            }
        } */

        if (empty($username)) {
            $this->delivery->addError(404, 'Missing required parameters.');
            $this->response($this->delivery->format());
        }

        if (empty($email) && empty($phone)) {
            $this->delivery->addError(404, 'Missing required parameters.');
            $this->response($this->delivery->format());
        }

        if (empty($password)) {
            $this->delivery->addError(404, 'Missing required parameters.');
            $this->response($this->delivery->format());
        }

        if (empty($cpassword)) {
            $this->delivery->addError(404, 'Missing required parameters.');
            $this->response($this->delivery->format());
        }

        if (empty($firstName)) {
            $this->delivery->addError(404, 'Missing required parameters.');
            $this->response($this->delivery->format());
        }

        if (empty($email)) {
            $username = getFormattedPhoneNumber($phone);
            $phone = $username;
        }

        $args = [
            'username' => $username
        ];
        $existUsername = $this->MainModel->findOne('users', $args);
        if (!empty($existUsername)) {
            $this->delivery->addError(400, 'Username already taken');
            $this->response($this->delivery->format());
        }

        if (!empty($email)) {
            $args = [
                'email' => $email
            ];
            $existsEmail = $this->MainModel->findOne('users', $args);
            if (!empty($existsEmail)) {
                $this->delivery->addError(409, 'Email already taken');
                $this->response($this->delivery->format());
            }
        }

        if (!empty($phone)) {
            $args = [
                'phone' => $phone
            ];
            $existsPhone = $this->MainModel->findOne('users', $args);
            if (!empty($existsPhone)) {
                $this->delivery->addError(409, 'Phone already taken');
                $this->response($this->delivery->format());
            }
        }

        if ($password != $cpassword || strlen($password) < 5) {
            $this->delivery->addError(409, 'Password is not correct');
            $this->response($this->delivery->format());
        }

        try {
            $secret = $this->createSecret($username, $password);
            $newUser = [
                'id_auth' => 1,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $email,
                'phone' => $phone,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'created_on' => time(),
                'secret' => $secret,
            ];
            if (!empty($googleVerificationPayload)) {
                $newUser['google_verification_payload'] = json_encode($googleVerificationPayload);
            }

            $action = $this->MainModel->insert('users', $newUser);
            $data = [
                'message' => 'Success create new user',
                'username' => $newUser['username'],
                'email' => $newUser['email'],
                'phone' => $newUser['phone'],
                'secret' => $secret,
            ];

            // update referral code
            $userHandler = new UserHandler($this->MainModel);
            $user = new \stdClass;
            $user->id = $action;
            $user->first_name = $newUser['first_name'];
            $payload = [
                'referral_code' => $userHandler->generateReferralCode($user)
            ];
            $referralCodeAction = $this->MainModel->update('users', $payload, ['id' => $action]);
            $this->delivery->data = $data;
            $this->response($this->delivery->format());
        } catch (\Exception $e) {
            $this->delivery->addError(500, $e->getMessage());
            $this->response($this->delivery->format());
        }

        $this->response($this->delivery->format());

    }

    function check_post() {
        $username = $this->input->post('username');
        $args = [
            'email' => $username,
            'username' => $username,
            'phone' => getFormattedPhoneNumber($username),
        ];

        $orWhere[] = $args;
        $existsUser = $this->MainModel->findOne('users', null, $orWhere);
        if (empty($existsUser)) {
            $data = [
                'success' => false,
                'code' => 404,
                'message' => 'Username or password incorrect.'
            ];
            return $this->returnJSON($data);
        }
        $data = [
            'success' => true,
            'data' => 'ok'
        ];
        return $this->returnJSON($data);
    }

    function otp_post() { //for login//
        $username = $this->input->post('username');
        $otpType = $this->input->post('otp_type');
        $args = [
            'email' => $username,
            'username' => $username,
            'phone' => getFormattedPhoneNumber($username),
        ];

        $otpTypes = ['whatsapp','sms','email'];
        //new code//
        $otpType = (isset($otpType) && in_array($otpType, $otpTypes)) ? $otpType : 'whatsapp';

        //if (!in_array($otpType, $otpTypes)) {
            //$data = [
                //'success' => false,
                //'code' => 400,
                //'message' => 'OTP type is required'
            //];
            //return $this->returnJSON($data);
        //}
        $orWhere[] = $args;
        $existsUser = $this->MainModel->findOne('users', null, $orWhere);

        if (empty($existsUser)) {
            $data = [
                'success' => false,
                'code' => 404,
                'message' => 'Username or password incorrect.'
            ];
            return $this->returnJSON($data);
        }

        $otp = generateRandomDigit(6);
        $message = sprintf('Kode OTP: %s', $otp);
        $currentDate = date('Y-m-d H:i:s');
        $futureDate = strtotime($currentDate) + (60 * 5);
        $expiredAt = date('Y-m-d H:i:s', $futureDate);

        $newOtpData = [
            'id_user' => $existsUser->id,
            'otp' => $otp,
            'used_at' => null,
            'expired_at' => $expiredAt,
            'created_at' => $currentDate,
            'updated_at' => $currentDate,
            'deleted_at' => $currentDate
        ];
        $action = $this->MainModel->insert('otp', $newOtpData);


        $notifAction = null;
        $wablas = new WablasService(self::WABLAS_DOMAIN, self::WABLAS_TOKEN);
        switch ($otpType) {
            case 'whatsapp':
                $notifAction = $wablas->sendMessage($existsUser->phone, $message);
                break;
            case 'sms':
                $notifAction = $this->sms->send($existsUser->phone, $message);
                break;
            case 'email':
                $notifAction = $this->mailgun->send(self::EMAIL_KEY, self::EMAIL_DOMAIN, self::EMAIL_FROM, $existsUser->email, 'OTP', $message);
                break;
        }

        $extras = [
            'notify' => [
                'type' => $otpType,
                'data' => $notifAction,
            ]
        ];

        // $secret = $this->createSecret ($username, $password);
        // $updateData = [
        //     'secret' => $secret,
        // ];
        // $action = $this->MainModel->update('users', $updateData, ['id' => $existsUser->id]);
        $data = [
            'data' => [
                'uname' => $existsUser->username,
                'phone' => $existsUser->phone,
                'email' => $existsUser->email,
            // 'secret' => $secret
            ],
            'extras' => $extras,
            'code' => 200
        ];
        return $this->returnJSON($data);
    }

    public function otp_verify_post() {
        $username = $this->input->post('username');
        $otp = $this->input->post('otp');
        $args = [
            'email' => $username,
            'username' => $username,
            'phone' => getFormattedPhoneNumber($username),
        ];
        $orWhere[] = $args;
        $existsUser = $this->MainModel->findOne('users', null, $orWhere);

        if (empty($existsUser)) {
            $data = [
                'success' => false,
                'code' => 404,
                'message' => 'Username or password incorrect.'
            ];
            return $this->returnJSON($data);
        }

        $availableOtp = $this->MainModel->findLastOtpByUser($existsUser->id, $otp);
        $currentTime = date('Y-m-d H:i:s');

        //($availableOtp->otp != $otp || $availableOtp->used_at != null || $availableOtp->expired_at < $currentTime)//
        if ($availableOtp->otp != $otp || $availableOtp->used_at != null) {
            $data = [
                'success' => false,
                'code' => 400,
                'message' => 'OTP incorrect'
            ];
            return $this->returnJSON($data);
        }

        $updateData = [
            'used_at' => $currentTime
        ];
        $action = $this->MainModel->update('otp', $updateData, ['id' => $availableOtp->id]);

        $secret = $existsUser->secret;
        if (empty($existsUser->secret)) {
            $secret = $this->createSecret($username, $otp);
        }
        $updateData = [
            'secret' => $secret,
        ];
        $action = $this->MainModel->update('users', $updateData, ['id' => $existsUser->id]);
        $data = [
            'data' => [
                'uname' => $existsUser->username,
                'phone' => $existsUser->phone,
                'email' => $existsUser->email,
                'secret' => $secret
            ],
            'code' => 200
        ];
        return $this->returnJSON($data);
    }

// AUTH USER
    function oauth_register_post () {
        $corp = $this->input->post('corp');
        $uname = $this->input->post('uname');
        $email = $this->input->post('email');
        $phone = $this->input->post('phone');
        $password = $this->input->post('password');
        $cpassword = $this->input->post('cpassword');
        $name = $this->input->post('name');
        $born = $this->input->post('born');

        $forms = [
            'corp',
            'uname',
            'email',
            'phone',
            'password',
            'cpassword',
            'name',
            'born',
        ];

        foreach ($forms as $form) {
            if (empty($this->input->post($form))) {
                $this->delivery->addError(404, 'Missing required parameters.');
                $this->response($this->delivery->format());
            }
        }

        $args = [
            'uname' => $uname
        ];
        $existUsername = $this->MainModel->findOne('auth_user', $args);
        if (!empty($existUsername)) {
            $this->delivery->addError(409, 'Username already taken');
            $this->response($this->delivery->format());
        }

        $args = [
            'email' => $email
        ];
        $existsEmail = $this->MainModel->findOne('auth_user', $args);
        if (!empty($existsEmail)) {
            $this->delivery->addError(409, 'Email already taken');
            $this->response($this->delivery->format());
        }

        $args = [
            'phone' => $phone
        ];
        $existsPhone = $this->MainModel->findOne('auth_user', $args);
        if (!empty($existsPhone)) {
            $this->delivery->addError(409, 'Phone already taken');
            $this->response($this->delivery->format());
        }

        if ($password != $cpassword || strlen($password) < 5) {
            $this->delivery->addError(409, 'Password is not correct');
            $this->response($this->delivery->format());
        }
        
        try {

            $args = [
                'corp' => strtoupper($corp)
            ];
            $existsAuth = $this->MainModel->findOne('auth_api', $args);
            $idAuth = null;
            if (empty($existsAuth)) {
                $newCorp = [
                    'parentidauth' => 0,
                    'balance' => 0,
                    'corp' => strtoupper($corp),
                    'disc' => 0,
                ];
                $actionCorp = $this->MainModel->insert('auth_api', $newCorp);
                $idAuth = $actionCorp;
            } else {
                $idAuth = $existsAuth->id_auth;
            }


            $newAuth = [
                'id_auth' => $idAuth,
                'uname' => $uname,
                'paswd' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $email,
                'phone' => $phone,
                'name' => $name,
                'born' => $born,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $action = $this->MainModel->insert('auth_user', $newAuth);

            $sendEmail = $this->mailgun->send(self::EMAIL_KEY, self::EMAIL_DOMAIN, self::EMAIL_FROM, $email, 'Subject', 'Text');
            $sendSms = $this->sms->send($phone, 'Text');
            $data = [
                'message' => 'Success create new auth user',
                'extras' => [
                    'sms' => $sendSms,
                    'email' => $sendEmail
                ]
            ];
            $this->delivery->data = $data;
            $this->response($this->delivery->format());
        } catch (\Exception $e) {
            $this->delivery->addError(500, $e->getMessage());
            $this->response($this->delivery->format());
        }
    }

    function user_forgot_password_post () {
        $username = $this->input->post('username');
        $args = [
            'email' => $username,
            'phone' => getFormattedPhoneNumber($username),
        ];
        $orWhere[] = $args;
        $existsUser = $this->MainModel->findOne('users', null, $orWhere);
        if(!$existsUser){
            $this->delivery->addError(404, 'Account not found'); $this->response($this->delivery->format());
        }

        $currentDate = date('Y-m-d H:i:s');
        $readyForgot = $existsUser->forgotten_password_code;
        $readyTime = $existsUser->forgotten_password_time;
        if($readyForgot && !empty($readyForgot) && !is_null($readyForgot)){
            if($readyTime && !empty($readyTime) && !is_null($readyTime) && $this->wooh_support->validationDate($readyTime)){
                if($readyTime > $currentDate){
                    $this->delivery->addError(404, 'Kode verification has been sent. Try again in 15 minutes.'); $this->response($this->delivery->format());
                }
            }
        }

        $otp = generateRandomDigit(6);
        $futureDate = strtotime($currentDate) + (60 * 15);
        $expiredAt = date('Y-m-d H:i:s', $futureDate);

        $forgotData = array();
        $forgotData['forgotten_password_code'] = $otp;
        $forgotData['forgotten_password_time'] = $expiredAt;
        
        try {
            $forgotData['secret'] = null;
            $action = $this->db->set($forgotData)->where(['id'=>$existsUser->id])->update('users');
            $sendEmail = null;
            if($existsUser->email && !empty($existsUser->email) && !is_null($existsUser->email)){
                $msgEmail = 'Your kode for reset password is <b>'.$otp.'</b>';
                $sendEmail = $this->mailgun->send(self::EMAIL_KEY, self::EMAIL_DOMAIN, self::EMAIL_FROM, $existsUser->email, 'Forgot Password', $msg);
            }
            $sendMessage = null;
            if($existsUser->phone && !empty($existsUser->phone) && !is_null($existsUser->phone)){
                $msgPhone = 'Your kode for reset password is _*'.$otp.'*_';
                $wablas = new WablasService(self::WABLAS_DOMAIN, self::WABLAS_TOKEN);
                $sendMessage = $wablas->sendMessage($existsUser->phone, $msgPhone);
            }
            
            //$sendSms = $this->sms->send($existsUser->phone, 'Your new password is '. $newPassword);
            $result = array();
            $result['code_verification'] = $otp;
            $result['expired_time'] = $expiredAt;
            $result['result'] = array();
            $result['result']['email'] = $sendEmail;
            $result['result']['whatsapp'] = $sendMessage;

            $this->delivery->data = $result;
            $this->response($this->delivery->format());
        } catch (\Exception $e) {
            $this->delivery->addError(500, 'Internal Server Error');
            $this->delivery->addError(500, $e->getMessage());
            $this->response($this->delivery->format());
        }
    }

    function user_reset_password_post () {
        $username = $this->input->post('username');
        $otp = $this->input->post('otp');
        $args = [
            'email' => $username,
            'phone' => getFormattedPhoneNumber($username),
        ];
        $orWhere[] = $args;
        $existsUser = $this->MainModel->findOne('users', null, $orWhere);
        if(!$existsUser){
            $this->delivery->addError(404, 'Account not found'); $this->response($this->delivery->format());
        }

        $currentDate = date('Y-m-d H:i:s');
        $readyForgot = $existsUser->forgotten_password_code;
        $readyTime = $existsUser->forgotten_password_time;
        if(!$readyForgot || empty($readyForgot) || is_null($readyForgot)){
            $this->delivery->addError(404, 'Kode verification not found. Please request again.'); $this->response($this->delivery->format());
        }
        if(!$readyTime || empty($readyTime) || is_null($readyTime) || !$this->wooh_support->validationDate($readyTime)){
            $this->delivery->addError(404, 'Kode verification not available. Please request again.'); $this->response($this->delivery->format());
        }
        if($readyTime < $currentDate){
            $this->delivery->addError(404, 'Kode verification expired. Please request again.'); $this->response($this->delivery->format());
        }
        if($readyForgot!=$otp){
            $this->delivery->addError(404, 'Kode verification not match.'); $this->response($this->delivery->format());
        }

        $password = $this->input->post('password');
        if(!$password || empty($password) || is_null($password)){
            $this->delivery->addError(400, 'New password is required'); $this->response($this->delivery->format());
        }

        $upData = array();
        $upData['password'] = password_hash($password, PASSWORD_DEFAULT);
        $upData['forgotten_password_code'] = NULL;
        $upData['forgotten_password_time'] = NULL;
        $upData['secret'] = null;

        $upPassword = $this->db->set($upData)->where(['id'=>$existsUser->id])->update('users');
        $this->delivery->data = 'Password updated successfully';
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    function oauth_forgot_post () {
        $username = $this->input->post('username');

        $args = [
            'email' => $username,
            'phone' => $username,
            'uname' => $username
        ];
        $orWhere[] = $args;
        $existsUser = $this->MainModel->findOne('auth_user', null, $orWhere);
        

        if (empty($existsUser)) {
            $this->delivery->addError(404, 'Missing required parameters.');
            $this->response($this->delivery->format());
        }

        $newPassword = generateRandomString(6);
        $newData = [
            'paswd' => password_hash($newPassword, PASSWORD_DEFAULT)
        ];
        $args = [
            'id_auth_user' => $existsUser->id_auth_user
        ];
        try {
            $action = $this->MainModel->update('auth_user', $newData, $args);
            $sendEmail = $this->mailgun->send(self::EMAIL_KEY, self::EMAIL_DOMAIN, self::EMAIL_FROM, $existsUser->email, 'Reset Password', 'Your new password is '.$newPassword);
            $sendSms = $this->sms->send($existsUser->phone, 'Your new password is '. $newPassword);
            $data = [
                'email' => $sendEmail,
                'sms' => $sendSms
            ];
            $this->delivery->data = $data;
            $this->response($this->delivery->format());
        } catch (\Exception $e) {
            $this->delivery->addError(500, 'Internal Server Error');
            $this->delivery->addError(500, $e->getMessage());
            $this->response($this->delivery->format());
        }

    }

    function admin_post() {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        $args = [
            'email' => $username,
            'username' => $username
        ];
        $orWhere[] = $args;
        $existsUser = $this->MainModel->findOne('admins', null, $orWhere);

        if (empty($existsUser)) {
            $data = [
                'success' => false,
                'code' => 404,
                'message' => 'Username or password incorrect.'
            ];
            return $this->returnJSON($data);
        }

        if (!password_verify($password, $existsUser->password)) {
            $data = [
                'success' => false,
                'code' => 400,
                'message' => 'Username or password incorrect.'
            ];
            return $this->returnJSON($data);
        }

        $secret = $this->createSecret($username, $password);
        $updateData = [
            'secret' => $secret,
        ];
        $action = $this->MainModel->update('admins', $updateData, ['id' => $existsUser->id]);
        $adminStore = null;
        if(isset($existsUser->admin_store) && $existsUser->admin_store && !empty($existsUser->admin_store) && !is_null($existsUser->admin_store)){
            $adminStore = $this->MainModel->findOne('stores', ['stores.id'=>$existsUser->admin_store]);
        }
        $data = [
            'data' => [
                'id' => $existsUser->id,
                'id_auth' => $existsUser->id_auth,
                'uname' => $existsUser->username,
                'phone' => $existsUser->phone,
                'email' => $existsUser->email,
                'secret' => $secret,
                'store' => $existsUser->admin_store,
                'store_detail' => $adminStore
            ],
            'code' => 200
        ];
        return $this->returnJSON($data);
    }

    function admin_register_post () {
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        $cpassword = $this->input->post('cpassword');
        $email = $this->input->post('email');
        $firstName = $this->input->post('first_name');
        $lastName = $this->input->post('last_name');
        $company = $this->input->post('company');
        $phone = $this->input->post('phone');
        $idAuth = $this->input->post('id_auth');
        $inventoryRole = $this->input->post('inventory_role');
        $forms = [
            'username',
            'password',
            'cpassword',
            'email',
            'first_name',
            'last_name',
            'company',
            'phone',
            'id_auth',
            'inventory_role'
        ];

        foreach ($forms as $form) {
            if (empty($this->input->post($form))) {
                $this->delivery->addError(404, 'Missing required parameters.');
                $this->response($this->delivery->format());
            }
        }

        $args = [
            'username' => $username
        ];
        $existUsername = $this->MainModel->findOne('admins', $args);
        if (!empty($existUsername)) {
            $this->delivery->addError(409, 'Username already taken');
            $this->response($this->delivery->format());
        }

        $args = [
            'email' => $email
        ];
        $existsEmail = $this->MainModel->findOne('admins', $args);
        if (!empty($existsEmail)) {
            $this->delivery->addError(409, 'Email already taken');
            $this->response($this->delivery->format());
        }

        $args = [
            'id_auth' => $idAuth
        ];
        $existsAuth = $this->MainModel->findOne('auth_api', $args);
        if (empty($existsAuth)) {
            $this->delivery->addError(409, 'Auth not found');
            $this->response($this->delivery->format());
        }


        if ($password != $cpassword || strlen($password) < 5) {
            $this->delivery->addError(409, 'Password is not correct');
            $this->response($this->delivery->format());
        }

        /* $defaultServices = [
            [
                'type' => Entity::TYPE_HOSTING,
                'fee' => 0,
                'is_active' => true
            ],
            [
                'type' => Entity::TYPE_DOMAIN,
                'fee' => 0,
                'is_active' => true
            ],
        ]; */
        // $defaultServices = json_encode($defaultServices);
        $secret = $this->createSecret($username, $password);
        // $services = $this->entity->format($defaultServices);
        $newAdmin = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => $company,
            'phone' => $phone,
            'secret' => $secret,
            'id_auth' => $idAuth,
            'inventory_role' => $inventoryRole
            // 'services' => json_encode($services)
        ];
        
        try {
            $action = $this->MainModel->insert('admins', $newAdmin);
            $data = [
                'secret' => $secret,
                'message' => 'Success create new admin'
            ];
            $this->delivery->data = $data;
            $this->response($this->delivery->format());
        } catch (\Exception $e) {
            $this->delivery->addError(500, $e->getMessage());
            $this->response($this->delivery->format());
        }


    }

    function userProfile_get() {
        $token = $this->input->get_request_header('X-Token-Secret');
        $idDevice = $this->input->get_request_header('X-idDevice');
        $typeIdDevice = $this->input->get_request_header('X-typeIdDevice');
        $dataVerify = $this->MainModel->verfyUser($token);
        if ($dataVerify['status'] == 200) {
            $data['status'] = 200;
            $data['data'] = $this->Auth->getProfileUser($token, $idDevice, $typeIdDevice);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    function updateProfile_post() {
        $token = $this->input->get_request_header('X-Token-Secret');
        $dataVerify = $this->MainModel->verfyUser($token);
        if ($dataVerify['status'] == 200) {
            $data = $this->Auth->updateProfileUser(
                    $dataVerify['dataSecret'][0]->id, $this->input->post('first_name'), $this->input->post('last_name'), $this->input->post('email'), $this->input->post('phone'), $this->input->post('company'), $this->input->post('pin'));
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        } else {
            $this->response(array('status' => 'access denied', 403));
        }
    }

    /*     * ************************************************************************************** */

    function test_post () {
        print_r(isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');
        die();
    }

    function reseller_check_post () {

        $payload = $this->input->post();
        $handler = new UserHandler($this->MainModel);
        // $handler->setAdmin($auth->data);
        $result = $handler->validateReseller($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $resultOTP = $handler->handleSendOTP($result->data);
        if ($resultOTP->hasErrors()) {
            $this->response($resultOTP->format(), $resultOTP->getStatusCode());
        }
        $this->delivery->data = 'ok';

        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    function reseller_payment_methods_get () {
        $tripay = new TripayGateway;
        // $tripay->setEnv('development');
        $tripay->setEnv('production');
        $tripay->setMerchantCode('T13840');
        $tripay->setApiKey('XW1h01alwrID3xpwcqV2jIa9lUFxV9o89fQMwso2');
        $tripay->setPrivateKey('CNvlC-sMN5n-14mnA-EtIq5-pC7Z8');
        // gunakan amount sebelum dicharge fee (customer_fee)
        $tripayRequest = $tripay->channelPembayaran();
        $this->delivery->data = $tripayRequest->data;

        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    function reseller_post () {

        $payload = $this->input->post();
        $handler = new UserHandler($this->MainModel);
        // $handler->setAdmin($auth->data);
        $result = $handler->registerReseller($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    function fa_otp_post () { //for register//
        $phoneNumber = $this->input->post('username');

        $payload = [
            'phone' => $phoneNumber,
        ];
        $userHandler = new UserHandler($this->MainModel);
        $result = $userHandler->handleSendOTP($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    function fa_signup_post () {
        $phoneNumber = $this->input->post('username');
        $otp = $this->input->post('otp');

        $payload = [
            'phone' => $phoneNumber,
            'otp' => $otp,
        ];
        $userHandler = new UserHandler($this->MainModel);
        $result = $userHandler->handleValidOTP($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        
        $phone = getFormattedPhoneNumber($phoneNumber);
        $password = generateRandomString(6);
        $_POST['phone'] = $phone;
        $_POST['email'] = null;
        $_POST['username'] = $phone;
        $_POST['password'] = $password;
        $_POST['cpassword'] = $password;
        $_POST['first_name'] = $phone;
        $registerAction = $this->user_register_post();

        $this->response($result->format(), $result->getStatusCode());
    }

    public function returnJSON($data, $statusCode = 200) {
        if (isset($data['code'])) {
            $statusCode = $data['code'];
        }
        $this->response($data, $statusCode);
    }

    public function createSecret($username, $password) {
        return md5($username . time()) . md5(time() . $password);
    }

    function user_check_post() { //for check account//
        $username = $this->input->post('username');
        $select = ['id','email','username','phone','first_name','last_name','can_order','is_reseller'];
        $args = [
            'email' => $username,
            'username' => $username,
            'phone' => getFormattedPhoneNumber($username),
        ];
        $orWhere[] = $args;
        $existsUser = $this->MainModel->findOne('users', null, $orWhere, null, $select);

        if (!$existsUser || empty($existsUser) || is_null($existsUser)) {
            $this->delivery->addError(400, 'Account not found'); $this->response($this->delivery->format());
        }
        $this->delivery->data = $existsUser;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    function user_by_google_post () {
        $clientId = $this->input->post('client_id');
        $credential = $this->input->post('credential');
        $client = new Google_Client(['client_id' => $clientId]);
        $payload = $client->verifyIdToken($credential);
        $result = new Delivery;

        if ($payload) {
            $email = $payload['email'];
            $name = $payload['name'];
            $firstName = $payload['given_name'];
            $lastName = $payload['family_name'];
            $args = [
                'email' => $email,
            ];
            
            $existsUser = $this->MainModel->findOne('users', $args);
            if (empty($existsUser)) {
                // register
                $password = generateRandomString(6);
                $_POST['phone'] = null;
                $_POST['email'] = $email;
                $_POST['username'] = $email;
                $_POST['password'] = $password;
                $_POST['cpassword'] = $password;
                $_POST['first_name'] = $firstName;
                $_POST['last_name'] = $lastName;
                $_POST['google_verification_payload'] = $payload;
                $registerAction = $this->user_register_post();
            } else {
                // login
                $secret = $this->createSecret($existsUser->username, $existsUser->password);
                $updateData = [
                    'secret' => $secret,
                    'google_verification_payload' => json_encode($payload),
                ];
                $action = $this->MainModel->update('users', $updateData, ['id' => $existsUser->id]);
                $data = [
                    'data' => [
                        'uname' => $existsUser->username,
                        'phone' => $existsUser->phone,
                        'email' => $existsUser->email,
                        'first_name' => $existsUser->first_name,
                        'last_name' => $existsUser->last_name,
                        'name' => sprintf("%s %s", $existsUser->first_name, $existsUser->last_name),
                        // 'born' => $existsUser->born,
                        'secret' => $secret,
                        // 'is_reseller' => $existsUser->is_reseller
                    ],
                    'code' => 200
                ];
                return $this->returnJSON($data);
            }

        } else {
            $result->addError(400, 'Invalid token');
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    function google_get () {
        $this->load->view('delete');
    }

    function apple_get () {
        $this->load->view('apple');
    }

    function user_by_apple_post () {
        $payload = $this->input->post();
        $idToken = $payload['id_token'];
        $code = $payload['code'];
        $teamId = 'CK7N92JL48';
        $serviceId = 'id.botfood.signinservice';
        $redirectUri = 'https://api.1itmedia.co.id/oauth/google';

        $applePayload = explode('.', $idToken);
        if (count($applePayload) < 2) {
            $this->delivery->addError(400, 'Invalid ID Token');
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }
        $headerPayload = $applePayload[0];
        $bodyPayload = $applePayload[1];
        $headerApple = (JWT::jsonDecode(JWT::urlsafeB64Decode($headerPayload)));
        $bodyApple = (JWT::jsonDecode(JWT::urlsafeB64Decode($bodyPayload)));

        $jwsData = [
            'iat' => time(),
            'exp' => time() + 86400*180,
            'iss' => $teamId,
            'aud' => 'https://appleid.apple.com',
            'sub' => $serviceId,
        ];

        $privKey = file_get_contents('key.txt');

        $clientSecret = JWT::encode($jwsData, $privKey, 'ES256', '8FNXLW76G3');

        $result = [
            'header' => $headerApple,
            'body' => $bodyApple,
            'client_secret' => $clientSecret,
        ];

        $client = new Client([
            'base_uri' => 'https://appleid.apple.com',
        ]);
        $appleGenerate = [
            'client_id' => $serviceId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ];
        $resp = null;
        try {
            $response = $client->request('POST', '/auth/token', [
                'form_params' => $appleGenerate,
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp);
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
            $resp = json_decode($resp);
            $resp->isError = true;
            $this->delivery->addError(400, 'Error when authorize to apple');
        }
        if ($this->delivery->hasErrors()) {
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }

        if (isset($resp->access_token) && !empty($resp->access_token)) {
            $finalResult = [
                'apple_client_request' => $result,
                'apple_auth_response' => $resp
            ];

            $email = $bodyApple->email;
            $name = $bodyApple->email;
            $firstName = $bodyApple->email;
            $lastName = $bodyApple->email;
            $args = [
                'email' => $email,
            ];
            
            $existsUser = $this->MainModel->findOne('users', $args);
            if (empty($existsUser)) {
                // register
                /* $password = generateRandomString(6);
                $_POST['phone'] = null;
                $_POST['email'] = $email;
                $_POST['username'] = $email;
                $_POST['password'] = $password;
                $_POST['cpassword'] = $password;
                $_POST['first_name'] = $firstName;
                $_POST['last_name'] = $lastName;
                $_POST['google_verification_payload'] = $payload;
                $registerAction = $this->user_register_post(); */
                $finalResult['action'] = 'register';
            } else {
                // login
                /* $secret = $this->createSecret($existsUser->username, $existsUser->password);
                $updateData = [
                    'secret' => $secret,
                    'google_verification_payload' => json_encode($payload),
                ];
                $action = $this->MainModel->update('users', $updateData, ['id' => $existsUser->id]);
                $data = [
                    'data' => [
                        'uname' => $existsUser->username,
                        'phone' => $existsUser->phone,
                        'email' => $existsUser->email,
                        'first_name' => $existsUser->first_name,
                        'last_name' => $existsUser->last_name,
                        'name' => sprintf("%s %s", $existsUser->first_name, $existsUser->last_name),
                        // 'born' => $existsUser->born,
                        'secret' => $secret,
                        // 'is_reseller' => $existsUser->is_reseller
                    ],
                    'code' => 200
                ];
                return $this->returnJSON($data); */
                $finalResult['action'] = 'login';
            }



            $this->delivery->data = $finalResult;
        } else {
            $this->delivery->addError(400, 'Invalid format when processing apple request');
        }

        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function qr_user_get() {
        $this->load->library(['Qr_code','Client_info']);
        $payload = $this->input->get();
        $userInfo = $this->client_info->get_info();
        $myIp = $userInfo['ip'];
        if(isset($payload['myip']) && $payload['myip'] && !empty($payload['myip']) && !is_null($payload['myip'])){
            $myIp = $payload['myip'];
        }

        $generateData = $this->wooh_support->codeData('QR', 5);
        $generateData = $this->wooh_support->generateSecret($generateData);
        $setData = implode('-', str_split($generateData, 8));
        
        //$params['data'] = '';
        $params['level'] = 'H';
        $params['size'] = 5;
        $params['padding'] = 1;
        $params['mode'] = 'develop';
        $params['url'] = 'auths/login?data='.$setData;

        ob_start();
        $this->qr_code->generate($params);
        $qrImage = ob_get_contents();
        ob_end_clean();

        $qrEncode =  base64_encode($qrImage);
        $qrUrl = 'data:image/jpeg;base64,'.$qrEncode;

        $filter = [
            'verif_type'=>'qr_login',
            'verif_ip'=>$myIp,
            'verif_os'=>$userInfo['os']['code'],
            'verif_browser'=>$userInfo['browser']['code'],
            'verif_device'=>$userInfo['device']['code'],
            'verif_status'=>0,
            'deleted_at'=>NULL,
        ];
        $existHistory = $this->db->from('verifications')->where($filter)->get()->row_object();
        $currentDate = date('Y-m-d H:i:s');

        $expiredType = 'second'; $expiredValue = 60;
        $expiredDate = date('Y-m-d H:i:s', strtotime('+'.$expiredValue.' '.$expiredType, strtotime($currentDate)));

        $sendData = array();
        $sendData['verif_user'] = NULL;
        $sendData['verif_data'] = $generateData;
        $sendData['verif_expired'] = $expiredDate;
        $sendData['updated_at'] = $currentDate;
        $sendData['verif_confirm'] = NULL;

        if($existHistory && !is_null($existHistory)){
            $upData = $this->db->set($sendData)->where(['verif_id'=>$existHistory->verif_id])->update('verifications');
        }else{
            $sendData['verif_type'] = 'qr_login';
            $sendData['verif_ip'] = $myIp;
            $sendData['verif_os'] = $userInfo['os']['code'];
            $sendData['verif_browser'] = $userInfo['browser']['code'];
            $sendData['verif_device'] = $userInfo['device']['code'];
            $sendData['verif_info'] = json_encode($userInfo, true);
            $sendData['verif_status'] = 0;
            $sendData['created_at'] = $currentDate;
            $upData = $this->db->insert('verifications', $sendData);
        }

        $result = array();
        $result['code'] = $setData;
        $result['expired_date'] = $expiredDate;
        $result['expired_type'] = $expiredType;
        $result['expired_value'] = $expiredValue;
        $result['qr'] = $qrUrl;

        $this->delivery->data = $result;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function qr_user_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $payload = $this->input->post();
        if(!isset($payload['code']) || !$payload['code'] || empty($payload['code']) || is_null($payload['code'])){
            $this->delivery->addError(400, 'failed'); $this->response($this->delivery->format());
        }
        if(isset($payload['secret']) && $payload['secret'] && !empty($payload['secret']) && !is_null($payload['secret'])){
            $secret = $payload['secret'];
        }

        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId = $auth->data['id'];

        $code = str_replace('-', '', $payload['code']);
        $currentDate = date('Y-m-d H:i:s');
        $filter = ['verif_type'=>'qr_login','verif_data'=>$code,'deleted_at'=>NULL];

        $existCode = $this->db->from('verifications')->where($filter)->get()->row_object();
        if(!$existCode || is_null($existCode)){
            $this->delivery->addError(400, 'not_valid'); $this->response($this->delivery->format());
        }
        if($existCode->verif_status==1){
            $this->delivery->addError(400, 'used'); $this->response($this->delivery->format());
        }

        $expiredDate = $this->wooh_support->validationDate($existCode->verif_expired);
        if($expiredDate === true){
            $expiredDate = date('Y-m-d H:i:s', strtotime($existCode->verif_expired));
            if($expiredDate < $currentDate){
                $this->delivery->addError(400, 'expired'); $this->response($this->delivery->format());
            }
        }

        $upData = array();
        $upData['verif_user'] = $userId;
        $upData['verif_status'] = 1;
        $upData['updated_at'] = $currentDate;
        $verifData = $this->db->set($upData)->where(['verif_id'=>$existCode->verif_id])->update('verifications');

        $this->delivery->data = 'confirmation';
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function qr_user_check_post() {
        $payload = $this->input->post();
        if(!isset($payload['code']) || !$payload['code'] || empty($payload['code']) || is_null($payload['code'])){
            $this->delivery->addError(400, 'failed'); $this->response($this->delivery->format());
        }
        $code = str_replace('-', '', $payload['code']);

        $filter = [
            'verifications.verif_type'=>'qr_login','verifications.verif_data'=>$code,
            'verifications.verif_status'=>1,'verifications.verif_user !='=>NULL,
            'verifications.deleted_at'=>NULL
        ];
        $selectVerif = [
            'verifications.*',
            'users.username as uname',
            'users.phone as phone',
            'users.email as email',
            'users.first_name as first_name',
            'users.last_name as last_name',
            'users.secret as secret',
        ];
        $existCode = $this->db->select($selectVerif)->from('verifications')->where($filter);
        $existCode = $existCode->join('users','users.id=verifications.verif_user');
        $existCode = $existCode->get()->row_object();
        if($existCode && !is_null($existCode) && $existCode->secret){
            $result = array();
            $result['uname'] = $existCode->uname;
            $result['phone'] = $existCode->phone;
            $result['email'] = $existCode->email;
            $result['first_name'] = $existCode->first_name;
            $result['last_name'] = $existCode->last_name;
            $result['name'] = sprintf("%s %s", $existCode->first_name, $existCode->last_name);
            $result['secret'] = $existCode->secret;

            $this->delivery->data = $result;
            $this->response($this->delivery->format(), $this->delivery->getStatusCode());
        }else{
            $this->delivery->addError(400, 'not_confirm'); $this->response($this->delivery->format());
        }
    }

//================================ END LINE ================================//
}
