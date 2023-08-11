<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\XenditService;

class Xendit extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function payment_channels_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getPaymentChannels();
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function available_virtual_account_banks_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getAvailableVirtualAccountBanks();
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_virtual_account_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $externalId = $this->input->post('external_id');
        $bankCode = $this->input->post('bank_code');
        $name = $this->input->post('name');
        $virtualAccountNumber = $this->input->post('virtual_account_number');
        $isSingleUse = stringToBool($this->input->post('is_single_use'));
        $isClosed = stringToBool($this->input->post('is_closed'));
        $expectedAmount = $this->input->post('expected_amount');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->createVirtualAccount( $externalId, $bankCode, $name, $virtualAccountNumber, $isSingleUse, $isClosed, $expectedAmount);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_virtual_account_get ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getVirtualAccount($id);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_invoice_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $externalId = $this->input->post('external_id');
        $payerEmail = $this->input->post('payer_email');
        $description = $this->input->post('description');
        $amount = $this->input->post('amount');
        $shouldSendEmail = $this->input->post('should_send_email');
        $paymentMethods = $this->input->post('payment_methods');
        $currency = $this->input->post('currency');
        $invoiceDuration = $this->input->post('invoice_duration');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->createInvoice( $externalId, $payerEmail, $description, $amount, $shouldSendEmail, $paymentMethods, $currency, $invoiceDuration);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_invoice_get ($invoiceId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getInvoice($invoiceId);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function expire_invoice_post ($invoiceId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }
        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->expireInvoice($invoiceId);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_disbursement_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $externalId = $this->input->post('external_id');
        $bankCode = $this->input->post('bank_code');
        $accountHolderName = $this->input->post('account_holder_name');
        $accountNumber = $this->input->post('account_number');
        $description = $this->input->post('description');
        $amount = $this->input->post('amount');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->createDisbursement($externalId, $bankCode, $accountHolderName, $accountNumber, $description, $amount);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_disbursement_by_id_get ($disbursementId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getDisbursementById($disbursementId);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_disbursement_by_external_id_get ($disbursementExternalId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getDisbursementByExternalId($disbursementExternalId);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_qr_code_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $externalId = $this->input->post('external_id');
        $type = $this->input->post('type');
        $callbackUrl = $this->input->post('callback_url');
        $amount = $this->input->post('amount');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->createQRCode($externalId, $type, $callbackUrl, $amount);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_qr_code_by_external_id_get ($qrCodeExternalId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getQRCodeByExternalId($qrCodeExternalId);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_payment_ovo_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $externalId = $this->input->post('external_id');
        $amount = $this->input->post('amount');
        $phone = $this->input->post('phone');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->createPaymentOvo($externalId, $amount, $phone);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_payment_dana_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $externalId = $this->input->post('external_id');
        $amount = $this->input->post('amount');
        $callbackUrl = $this->input->post('callback_url');
        $redirectUrl = $this->input->post('redirect_url');
        $expirationDate = $this->input->post('expiration_date');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->createPaymentDana($externalId, $amount, $callbackUrl, $redirectUrl, $expirationDate);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_payment_link_aja_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $externalId = $this->input->post('external_id');
        $phone = $this->input->post('phone');
        $items = $this->input->post('items');
        $amount = $this->input->post('amount');
        $callbackUrl = $this->input->post('callback_url');
        $redirectUrl = $this->input->post('redirect_url');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->createPaymentLinkAja($externalId, $phone, $amount, $items, $callbackUrl, $redirectUrl);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_payment_status_get ($qrCodeExternalId, $ewalletType) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getPaymentStatus($qrCodeExternalId, $ewalletType);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function create_fixed_payment_code_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $externalId = $this->input->post('external_id');
        $retailOutletName = $this->input->post('retail_outlet_name');
        $name = $this->input->post('name');
        $expectedAmount = $this->input->post('expected_amount');
        $paymentCode = $this->input->post('payment_code');
        $expirationDate = $this->input->post('expiration_date');
        $isSingleUse = $this->input->post('is_single_use');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->createFixedPaymentCode($externalId, $retailOutletName, $name, $expectedAmount, $paymentCode, $expirationDate, $isSingleUse);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function update_fixed_payment_code_post ($fixedPaymentCodeId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $expectedAmount = $this->input->post('expected_amount');
        $name = $this->input->post('name');
        $expirationDate = $this->input->post('expiration_date');

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->updateFixedPaymentCode($fixedPaymentCodeId, $expectedAmount, $name, $expirationDate);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

    public function get_fixed_payment_code_get ($fixedPaymentCodeId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $payload = $this->input->post();
        $validHeaders = $this->validator->validateIntegrationHeader(Entity::SERVICE_XENDIT, $headers);
        if ($validHeaders->hasErrors()) {
            $this->response($validHeaders->format());
        }

        $xendit = new XenditService();
        $xendit->setApiKey($headers['X-Xendit-Api-Key']);
        $action = $xendit->getFixedPaymentCode($fixedPaymentCodeId);
        $this->delivery->data = $action;
        $this->response($this->delivery->format());
    }

}
