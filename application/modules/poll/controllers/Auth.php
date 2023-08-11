
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Poll\PollHandler;
use \libphonenumber\PhoneNumberUtil;

class Auth extends REST_Controller {

    private $validator;
    private $delivery;
    private $pollHandler;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function otp_send_post () {
        $phoneNumber = getFormattedPhoneNumber($this->input->post('phone_number'));
        $password = $this->input->post('password');
        $cpassword = $this->input->post('cpassword');

        $payload = [
            'password' => $password,
            'cpassword' => $cpassword
        ];

        $pollHandler = new PollHandler($this->MainModel);
        $result = $pollHandler->authorizeSendOTP($phoneNumber, $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function otp_submit_post () {
        $phoneNumber = getFormattedPhoneNumber($this->input->post('phone_number'));
        $otp = $this->input->post('otp');
        $password = $this->input->post('password');
        $cpassword = $this->input->post('cpassword');
        $payload = [
            'password' => $password,
            'cpassword' => $cpassword
        ];

        $pollHandler = new PollHandler($this->MainModel);
        $result = $pollHandler->authorizeSubmitOTP($phoneNumber, $otp, $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function login_post () {
        $phoneNumber = getFormattedPhoneNumber($this->input->post('phone_number'));
        $password = $this->input->post('password');

        $pollHandler = new PollHandler($this->MainModel);
        $result = $pollHandler->login($phoneNumber, $password);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function forgot_password_post () {
        $phoneNumber = getFormattedPhoneNumber($this->input->post('phone_number'));

        $pollHandler = new PollHandler($this->MainModel);
        $result = $pollHandler->forgotPassword($phoneNumber);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

}
