<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\MemberDigital\MemberDigitalHandler;

class Member_digital extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitals($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($iden) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters['iden'] = $iden;
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigital($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        if (empty($result->data)) {
            $result->addError(400, 'Member is required');
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->createMemberDigital($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->updateMemberDigitals($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function restore_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = [
            'deleted_at' => null,
        ];
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $handler->existsValidation = false;
        $result = $handler->updateMemberDigitals($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function generate_attribute_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->generateMemberDigitalAttribute($id);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->deleteMemberDigitals($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function transaction_post ($slug) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->createMemberDigitalTransaction($slug, $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function transactions_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalTransactions($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function transaction_update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->updateMemberDigitalTransactions($payload, $filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function transaction_delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $filters = ['id' => $id];
        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->deleteMemberDigitalTransactions($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function send_batch_wablas_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->sendBatchWablas($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function send_wablas_post ($slug) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $message = $this->input->post('message');
        $additionalMessage = $this->input->post('additional_message');
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $memberResult = $handler->getMemberDigital(['iden' => $slug]);
        if ($memberResult->hasErrors()) {
            $this->response($memberResult->format(), $memberResult->getStatusCode());
        }

        $result = $handler->sendWablasToMember($memberResult->data, $message, $additionalMessage);
        if ($result->hasErrors()) {
            $this->createLog(json_encode($this->input->post()));
            $this->response($result->format(), $result->getStatusCode());
        }
        
        $this->createLog(json_encode($this->input->post()));

        $this->response($result->format(), $result->getStatusCode());
    }

    public function notify_transaction_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->notifyTransactions();
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    /**
     * Jalankan setiap 1 jam sekali
     **/
    public function notify_finish_get () {
        $handler = new MemberDigitalHandler($this->MainModel);
        $result = $handler->notifyFinish();
        $this->response($result->format(), $result->getStatusCode());
    }

    public function vouchers_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->getMemberDigitalVouchers($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function createLog ($payload) {
        $data = [
            'fromcall' => 'inbound_member_digital_wablas',
            'dataJson' => $payload,
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $this->MainModel->insert('logcallback', $data);
    }

    public function kyc_check_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->validateKyc($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $resultOTP = $handler->handleSendOTP($payload);
        if ($resultOTP->hasErrors()) {
            $this->response($resultOTP->format(), $resultOTP->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function kyc_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $payload = $this->input->post();
        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $result = $handler->handleKyc($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

}
