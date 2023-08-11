<?php

use GuzzleHttp\Client;

class Xendit {

    private $instance;
    private $client;
    private $apiKey;

    const TYPE_API_KEY = 'XENDIT_API_KEY';

    public function __construct() {
        $this->instance = &get_instance();
        $this->instance->load->model('MainModel');

        $this->client = new Client([
            'base_uri' => 'https://api.xendit.co',
        ]);


        $this->availableVirtualAccountBankCodes = [
            'MANDIRI',
            'BNI',
            'BRI',
            'PERMATA',
            'BCA',
            'SAHABAT_SAMPPOERNA'
        ];
    }

    public function getPaymentChannels() {
        $this->authenticate();
        $resp = null;
        $resp = $this->httpRequest('GET', '/payment_channels');
        return $resp;
    }

    public function createVirtualAccount( $externalId, $bankCode, $name, $virtualAccountNumber = null) {
        $this->authenticate();
        $resp = null;

        if (!in_array($bankCode, $this->availableVirtualAccountBankCodes)) {
            $resp = [
                'message' => 'Bank code not available.'
            ];

            return $resp;
        }

        $bodyReq = [
            'external_id' => $externalId,
            'bank_code' => $bankCode,
            'name' => $name
        ];

        if (!empty($virtualAccountNumber)) {
            $bodyReq['virtual_account_number'] = $virtual_account_number;
        }


        $resp = $this->httpRequest('POST', '/callback_virtual_accounts', $bodyReq);
        return $resp;
    }

    public function getVirtualAccount( $id) {
        $this->authenticate();
        $resp = null;
        $resp = $this->httpRequest('GET', '/callback_virtual_accounts/' . $id);
        return $resp;
    }

    public function createInvoice( $externalId, $payerEmail, $description, $amount, $shouldSendEmail = false, $paymentMethods = array(), $currency = null, $invoiceDuration = null) {
        $this->authenticate();
        $resp = null;

        $bodyReq = [
            'external_id' => $externalId,
            'payer_email' => $payerEmail,
            'description' => $description,
            'amount' => $amount
        ];

        if (!empty($shouldSendEmail)) {
            $bodyReq['should_send_email'] = $shouldSendEmail;
        }
        if (!empty($paymentMethods)) {
            $bodyReq['payment_methods'] = $paymentMethods;
        }
        if (!empty($currency)) {
            $bodyReq['currency'] = $currency;
        }
        if (!empty($invoiceDuration)) {
            $bodyReq['invoice_duration'] = $invoiceDuration;
        }


        $resp = $this->httpRequest('POST', '/v2/invoices', $bodyReq);
        return $resp;
    }

    public function getInvoice( $invoiceId) {
        $this->authenticate();
        $resp = null;
        $resp = $this->httpRequest('GET', '/v2/invoices/' . $invoiceId);
        return $resp;
    }

    public function expireInvoice( $invoiceId) {
        $this->authenticate();
        $resp = null;
        $resp = $this->httpRequest('POST', '/v2/invoices/' . $invoiceId . '/expire!');
        return $resp;
    }

    public function createDisbursement( $externalId, $bankCode, $accountHolderName, $accountNumber, $description, $amount, $emailTo = array()) {
        $this->authenticate();
        $resp = null;

        $bodyReq = [
            'external_id' => $externalId,
            'bank_code' => $bankCode,
            'account_holder_name' => $accountHolderName,
            'account_number' => $accountNumber,
            'description' => $description,
            'amount' => $amount
        ];

        if (!empty($emailTo)) {
            $bodyReq['email_to'] = $emailTo;
        }


        $resp = $this->httpRequest('POST', '/disbursements', $bodyReq);
        return $resp;
    }

    public function getDisbursementById( $disbursementId) {
        $this->authenticate();
        $resp = null;
        $resp = $this->httpRequest('GET', '/disbursements/' . $disbursementId);
        return $resp;
    }

    public function getDisbursementByExternalId( $externalId) {
        $this->authenticate();
        $resp = null;
        $resp = $this->httpRequest('GET', '/disbursements?external_id=' . $externalId);
        return $resp;
    }

    public function createQRCode( $externalId, $type, $callbackUrl, $amount) {
        $this->authenticate();
        $resp = null;

        $bodyReq = [
            'external_id' => $externalId,
            'type' => $type,
            'callback_url' => $callbackUrl,
            'amount' => $amount
        ];

        $resp = $this->httpRequest('POST', '/qr_codes', $bodyReq);

        return $resp;
    }

    public function getQRCodeByExternalId( $externalId) {
        $this->authenticate();
        $resp = null;
        $resp = $this->httpRequest('GET', '/qr_codes/' . $externalId);
        return $resp;
    }

    public function createPaymentOvo( $externalId, $amount, $phone) {
        $this->authenticate();
        $resp = null;

        $bodyReq = [
            'external_id' => $externalId,
            'amount' => $amount,
            'phone' => $phone,
            'ewallet_type' => 'OVO'
        ];

        $resp = $this->httpRequest('POST', '/ewallets', $bodyReq);
        return $resp;
    }

    public function createPaymentDana( $externalId, $amount, $callbackUrl, $redirectUrl, $expirationDate) {
        $this->authenticate();
        $resp = null;

        $bodyReq = [
            'external_id' => $externalId,
            'amount' => $amount,
            'callback_url' => $callbackUrl,
            'redirect_url' => $redirectUrl,
            'ewallet_type' => 'DANA'
        ];

        if (!empty($expirationDate)) {
            $bodyReq['expiration_date'] = $expirationDate;
        }

        $resp = $this->httpRequest('POST', '/ewallets', $bodyReq);
        return $resp;
    }

    public function createPaymentLinkAja( $externalId, $phone, $amount, $items, $callbackUrl, $redirectUrl) {
        $this->authenticate();
        $resp = null;

        $bodyReq = [
            'external_id' => $externalId,
            'phone' => $phone,
            'amount' => $amount,
            'items' => $items,
            'callback_url' => $callbackUrl,
            'redirect_url' => $redirectUrl,
            'ewallet_type' => 'LINKAJA'
        ];

        $resp = $this->httpRequest('POST', '/ewallets', $bodyReq);
        return $resp;
    }

    public function getPaymentStatus( $externalId, $ewalletType) {
        $this->authenticate();
        $queryParams = http_build_query(array(
            'external_id' => $externalId,
            'ewallet_type' => $ewalletType
        ));
        $resp = $this->httpRequest('GET', '/ewallets?' . $queryParams);
        return $resp;
    }

    public function createFixedPaymentCode( $externalId, $retailOutletName, $name, $expectedAmount, $paymentCode, $expirationDate, $isSingleUse) {
        $this->authenticate();
        $resp = null;
        $bodyReq = [
            'external_id' => $externalId,
            'retail_outlet_name' => $retailOutletName,
            'name' => $name,
            'expected_amount' => $expectedAmount
        ];

        if (!empty($paymentCode)) {
            $bodyReq['payment_code'] = $paymentCode;
        }
        if (!empty($expirationDate)) {
            $bodyReq['expiration_date'] = $expirationDate;
        }
        if (!empty($isSingleUse)) {
            $bodyReq['is_single_use'] = $isSingleUse;
        }

        $resp = $this->httpRequest('POST', '/fixed_payment_code', $bodyReq);
        return $resp;
    }

    public function updateFixedPaymentCode( $fixedPaymentCodeId, $expectedAmount, $name, $expirationDate) {
        $this->authenticate();
        $resp = null;
        $bodyReq = [];

        if (!empty($expectedAmount)) {
            $bodyReq['expected_amount'] = $expectedAmount;
        }
        if (!empty($name)) {
            $bodyReq['name'] = $name;
        }
        if (!empty($expirationDate)) {
            $bodyReq['expiration_date'] = $expirationDate;
        }

        $resp = $this->httpRequest('PATCH', '/fixed_payment_code/' . $fixedPaymentCodeId, $bodyReq);
        return $resp;
    }

    public function getFixedPaymentCode( $fixedPaymentCodeId) {
        $this->authenticate();
        $resp = null;
        $resp = $this->httpRequest('GET', '/fixed_payment_code/' . $fixedPaymentCodeId);
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null) {
        $resp = null;

        try {
            $response = $this->client->request($method, $url, [
                'auth' => $this->authorization,
                'json' => $bodyReq
            ]);

            $resp = $response->getBody();
            $resp = json_decode($resp);
        } catch (\Exception $e) {
            $resp = $e->getResponse()->getBody(true);
            $resp = json_decode($resp);
        }

        return $resp;
    }

    public function authenticate() {
        $this->apiKey = 'xnd_development_BfgBECyMgQ06oPDSi1qGIzriOBMqGfYgn9OjpvumNpWXPDvBLF95APPf66';
        $this->authorization = [
            $this->apiKey,
            ''
        ];
    }

}
