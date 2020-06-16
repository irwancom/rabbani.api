<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Xendit {

//https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=00020101021226690017COM.TELKOMSEL.WWW011893600911002414220002152003260414220010303UME51450015ID.OR.GPNQR.WWW02150000000000000000303UME520454995802ID5920Placeholder%20merchant6007Jakarta61061234566238011571bN7pPte7y1MAp071571bN7pPte7y1MAp53033605405100006304B8AF
//    https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=00020101021226690017COM.TELKOMSEL.WWW011893600911002414220002152003260414220010303UME51450015ID.OR.GPNQR.WWW02150000000000000000303UME520454995802ID5920Placeholder%20merchant6007Jakarta61061234566238011571bN7pPte7y1MAp071571bN7pPte7y1MAp53033605405100006304B8AF
    function veryfy() {
        $keyCode = 'xnd_production_SYWhZCQMV4HOrURXyxpESmWmpxu3MqqNKqkgfCJNgsOldCpszCHRT7AB3WxPgpC:';
//        $keyCode = 'xnd_development_lL4cBPuIytpSzKqHXtJBXYvn10bwncJUNBvbqZlJa2mKyMovciccNUsV3wADdY:';
        $data = base64_encode($keyCode);
        return $data;
    }

    function getListVa() {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.xendit.co/available_virtual_account_banks",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $this->veryfy()
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    function getListPayment() {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.xendit.co/payment_channels",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $this->veryfy()
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    function createVa($data = '') {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.xendit.co/callback_virtual_accounts",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
//            CURLOPT_POSTFIELDS => '{"external_id": "VA_fixed-123456778","bank_code": "MANDIRI","name": "Steve Wozniak"}',
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Basic " . $this->veryfy()
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    function addBalanceToVa($id = '', $amount = '') {
        $curl = curl_init();

        curl_setopt_array($curl, array(
//            CURLOPT_URL => "https://api.xendit.co/callback_virtual_accounts/external_id=VA_fixed-123456778/simulate_payment",
            CURLOPT_URL => "https://api.xendit.co/callback_virtual_accounts/external_id=" . $id . "/simulate_payment",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_POSTFIELDS => '{"amount": 50000}', "virtual_account_number": "isi VA number","is_closed": true,"expected_amount": "isi amount"
            CURLOPT_POSTFIELDS => '{"amount": ' . $amount . '}',
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Basic " . $this->veryfy()
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    function walletOvo() {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.xendit.co/ewallets",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"external_id":"ovo-ewallet-08986002287","amount":"8888","phone":"08986002287","ewallet_type":"OVO"}',
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "x-api-version: 2020-02-01",
                "Authorization: Basic " . $this->veryfy(),
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    function walletDana() {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.xendit.co/ewallets",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"external_id":"dana-ewallet-test-1234","amount":"1001","expiration_date":"2020-12-20T00:00:00.000Z","callback_url":"https://my-shop.com/callbacks","redirect_url":"https://my-shop.com/home","ewallet_type":"DANA"}',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $this->veryfy(),
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    function walletLinkAja() {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.xendit.co/ewallets",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{
	"external_id": "linkaja-ewallet-test-123",
    "phone": "089911111111",
    "amount": 300000,
    "items": [
      {
        "id": "123123",
        "name": "Phone Case",
        "price": 100000,
        "quantity": 1
      },
      {
        "id": "345678",
        "name": "Powerbank",
        "price": 200000,
        "quantity": 1
      }
    ],
    "callback_url": "https://my-shop.com/callbacks",
    "redirect_url": "https://xendit.co/",
    "ewallet_type": "LINKAJA"
}',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . $this->veryfy(),
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

}
