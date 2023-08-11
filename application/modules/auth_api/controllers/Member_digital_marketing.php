<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberDigital\MemberDigitalMarketingHandler;
use Service\MemberDigital\MemberDigitalHandler;

class Member_digital_marketing extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function standings_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberDigitalMarketingHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalMarketingStandings($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function standing_post ($level) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberDigitalMarketingHandler($this->MainModel, $auth->data);
        $payload['level'] = (int)$level;
        $result = $handler->createMemberDigitalMarketingStandings($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function debit_post ($slug) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $memberResult = $handler->getMemberDigital(['iden' => $slug]);
        if ($memberResult->hasErrors()) {
            $this->response($memberResult->format(), $memberResult->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberDigitalMarketingHandler($this->MainModel, $auth->data);
        $result = $handler->debitMemberDigitalBalance($memberResult->data, $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function refund_post ($slug) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $memberResult = $handler->getMemberDigital(['iden' => $slug]);
        if ($memberResult->hasErrors()) {
            $this->response($memberResult->format(), $memberResult->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberDigitalMarketingHandler($this->MainModel, $auth->data);
        $result = $handler->refundMemberDigitalBalance($memberResult->data, $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function affiliator_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberDigitalMarketingHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalAffiliator($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function remove_affiliate_post ($slug) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $memberResult = $handler->getMemberDigital(['iden' => $slug]);
        if ($memberResult->hasErrors()) {
            $this->response($memberResult->format(), $memberResult->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberDigitalMarketingHandler($this->MainModel, $auth->data);
        $result = $handler->removeAffiliate($memberResult->data);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function join_affiliate_post ($slug) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $affiliatorReferralCode = $this->input->post('affiliator_referral_code');

        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $memberResult = $handler->getMemberDigital(['iden' => $slug]);
        $affiliatorResult = $handler->getMemberDigital(['referral_code' => $affiliatorReferralCode]);

        $payload = $this->input->post();
        $handler = new MemberDigitalMarketingHandler($this->MainModel, $auth->data);
        $result = $handler->joinAffiliate($memberResult->data, $affiliatorResult->data);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

}
