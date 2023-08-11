<?php

class Payment extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('xendit');
    }
    
    public function index() {
        echo 'pay';
    }

    public function getPaymentChannels() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $resp = $this->xendit->getPaymentChannels();

        $data = [
            'success' => true,
            'xenditResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function createVirtualAccount() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $externalId = $this->input->post('external_id');
        $bankCode = $this->input->post('bank_code');
        $name = $this->input->post('name');
        $virtualAccountNumber = $this->input->post('virtual_account_number');

        $resp = $this->xendit->createVirtualAccount( $externalId, $bankCode, $name, $virtualAccountNumber);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getVirtualAccount($id) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $resp = $this->xendit->getVirtualAccount( $id);


        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function createInvoice() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $externalId = $this->input->post('external_id');
        $payerEmail = $this->input->post('payer_email');
        $description = $this->input->post('description');
        $amount = $this->input->post('amount');
        $shouldSendEmail = $this->input->post('should_send_email');
        $paymentMethods = $this->input->post('payment_methods');
        $currency = $this->input->post('currency');
        $invoiceDuration = $this->input->post('invoice_duration');

        $resp = $this->xendit->createInvoice( $externalId, $payerEmail, $description, $amount, $shouldSendEmail, $paymentMethods, $currency, $invoiceDuration);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getInvoice($invoiceId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $resp = $this->xendit->getInvoice( $invoiceId);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function expireInvoice($invoiceId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $resp = $this->xendit->expireInvoice( $invoiceId);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function createDisbursement() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $externalId = $this->input->post('external_id');
        $bankCode = $this->input->post('bank_code');
        $accountHolderName = $this->input->post('account_holder_name');
        $accountNumber = $this->input->post('account_number');
        $description = $this->input->post('description');
        $amount = $this->input->post('amount');

        $resp = $this->xendit->createDisbursement( $externalId, $bankCode, $accountHolderName, $accountNumber, $description, $amount);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getDisbursementById($disbursementId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $resp = $this->xendit->getDisbursementById( $disbursementId);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getDisbursementByExternalId($externalId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $resp = $this->xendit->getDisbursementByExternalId( $externalId);
        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function createQRCode() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $externalId = $this->input->post('external_id');
        $type = $this->input->post('type');
        $callbackUrl = $this->input->post('callback_url');
        $amount = $this->input->post('amount');

        $resp = $this->xendit->createQRCode( $externalId, $type, $callbackUrl, $amount);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getQRCodeByExternalId($externalId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $resp = $this->xendit->getQRCodeByExternalId( $externalId);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function createPaymentOvo() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $externalId = $this->input->post('external_id');
        $amount = $this->input->post('amount');
        $phone = $this->input->post('phone');

        $resp = $this->xendit->createPaymentOvo( $externalId, $amount, $phone);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function createPaymentDana() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $externalId = $this->input->post('external_id');
        $amount = $this->input->post('amount');
        $callbackUrl = $this->input->post('callback_url');
        $redirectUrl = $this->input->post('redirect_url');
        $expirationDate = $this->input->post('expiration_date');

        $resp = $this->xendit->createPaymentDana( $externalId, $amount, $callbackUrl, $redirectUrl, $expirationDate);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function createPaymentLinkAja() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $externalId = $this->input->post('external_id');
        $phone = $this->input->post('phone');
        $items = $this->input->post('items');
        $amount = $this->input->post('amount');
        $callbackUrl = $this->input->post('callback_url');
        $redirectUrl = $this->input->post('redirect_url');

        $resp = $this->xendit->createPaymentLinkAja( $externalId, $phone, $amount, $items, $callbackUrl, $redirectUrl);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getPaymentStatus($externalId, $ewalletType) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $resp = $this->xendit->getPaymentStatus( $externalId, $ewalletType);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function createFixedPaymentCode() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);
        
        $externalId = $this->input->post('external_id');
        $retailOutletName = $this->input->post('retail_outlet_name');
        $name = $this->input->post('name');
        $expectedAmount = $this->input->post('expected_amount');
        $paymentCode = $this->input->post('payment_code');
        $expirationDate = $this->input->post('expiration_date');
        $isSingleUse = $this->input->post('is_single_use');

        $resp = $this->xendit->createFixedPaymentCode( $externalId, $retailOutletName, $name, $expectedAmount, $paymentCode, $expirationDate, $isSingleUse);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function updateFixedPaymentCode($fixedPaymentCodeId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $expectedAmount = $this->input->post('expected_amount');
        $name = $this->input->post('name');
        $expirationDate = $this->input->post('expiration_date');

        $resp = $this->xendit->updateFixedPaymentCode( $fixedPaymentCodeId, $expectedAmount, $name, $expirationDate);

        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getFixedPaymentCode($fixedPaymentCodeId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $resp = $this->xendit->getFixedPaymentCode( $fixedPaymentCodeId);
        $data = [
            'success' => true,
            'xenditResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

}
