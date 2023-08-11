<?php
namespace Library;
use GuzzleHttp\Client;

class XenditService {

    private $instance;
    private $client;
    private $apiKey;

    const TYPE_API_KEY = 'XENDIT_API_KEY';

    public function __construct() {
        $this->client = new Client([
            'base_uri' => 'https://api.xendit.co',
        ]);

        $this->apiKey = 'xnd_development_BfgBECyMgQ06oPDSi1qGIzriOBMqGfYgn9OjpvumNpWXPDvBLF95APPf66';
        $this->authorization = [
            $this->apiKey,
            ''
        ];

        $this->availableVirtualAccountBankCodes = [
            'MANDIRI',
            'BNI',
            'BRI',
            'PERMATA',
            'BCA',
            'SAHABAT_SAMPPOERNA'
        ];
    }

    public function setApiKey ($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getPaymentChannels() {
        $resp = null;
        $resp = $this->httpRequest('GET', '/payment_channels');
        return $resp;
    }

    public function getAvailableVirtualAccountBanks() {
        $resp = null;
        $resp = $this->httpRequest('GET', '/available_virtual_account_banks');
        return $resp;
    }

    public function createVirtualAccount( $externalId, $bankCode, $name, $virtualAccountNumber = null, $isSingleUse = false, $isClosed = false, $expectedAmount = 0) {
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
            'name' => $name,
            'is_single_use' => $isSingleUse,
            'is_closed' => $isClosed
        ];

        if (!empty($virtualAccountNumber)) {
            $bodyReq['virtual_account_number'] = $virtual_account_number;
        }

        if (!empty($expectedAmount)) {
            $bodyReq['expected_amount'] = $expectedAmount;
        }


        $resp = $this->httpRequest('POST', '/callback_virtual_accounts', $bodyReq);
        return $resp;
    }

    public function getVirtualAccount( $id) {
        $resp = null;
        $resp = $this->httpRequest('GET', '/callback_virtual_accounts/' . $id);
        return $resp;
    }

    public function createInvoice( $externalId, $payerEmail = null, $description = null, $amount, $shouldSendEmail = false, $paymentMethods = array(), $currency = null, $invoiceDuration = null) {
        $resp = null;

        $bodyReq = [
            'external_id' => $externalId,
            'amount' => $amount
        ];

        if (!empty($payerEmail)) {
            $bodyReq['payer_email'] = $payerEmail;
        }
        if (!empty($description)) {
            $bodyReq['description'] = $description;
        }
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
        $resp = null;
        $resp = $this->httpRequest('GET', '/v2/invoices/' . $invoiceId);
        return $resp;
    }

    public function expireInvoice( $invoiceId) {
        $resp = null;
        $resp = $this->httpRequest('POST', '/invoices/' . $invoiceId . '/expire!');
        return $resp;
    }

    public function createDisbursement( $externalId, $bankCode, $accountHolderName, $accountNumber, $description, $amount, $emailTo = array()) {
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
        $resp = null;
        $resp = $this->httpRequest('GET', '/disbursements/' . $disbursementId);
        return $resp;
    }

    public function getDisbursementByExternalId( $externalId) {
        $resp = null;
        $resp = $this->httpRequest('GET', '/disbursements?external_id=' . $externalId);
        return $resp;
    }

    public function createQRCode( $externalId, $type, $callbackUrl, $amount) {
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
        $resp = null;
        $resp = $this->httpRequest('GET', '/qr_codes/' . $externalId);
        return $resp;
    }

    public function createPaymentOvo( $externalId, $amount, $phone) {
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
        $queryParams = http_build_query(array(
            'external_id' => $externalId,
            'ewallet_type' => $ewalletType
        ));
        $resp = $this->httpRequest('GET', '/ewallets?' . $queryParams);
        return $resp;
    }

    public function createFixedPaymentCode( $externalId, $retailOutletName, $name, $expectedAmount, $paymentCode, $expirationDate, $isSingleUse) {
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
        $resp = null;
        $resp = $this->httpRequest('GET', '/fixed_payment_code/' . $fixedPaymentCodeId);
        return $resp;
    }

    /*     * ********************** */

    public function httpRequest($method, $url, $bodyReq = null) {
        $resp = null;

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'Authorization' => sprintf('Basic %s', base64_encode($this->apiKey.':'))
                ],
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

}
