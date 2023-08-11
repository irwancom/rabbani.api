<?php

class Logistic extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('shipper');
    }

    public function index() {
        echo 'sdx';
    }

    public function getCountries() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->getCountries($idAuth);

        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getProvinces() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->getProvinces($idAuth);

        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getCities() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $origin = $this->input->post('origin');
        $province = $this->input->post('province');

        $resp = $this->shipper->getCities($idAuth, $origin, $province);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getSuburbs($city) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->getSuburbs($idAuth, $city);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getAreas($suburb) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->getAreas($idAuth, $suburb);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function searchAllLocation ($substring) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->searchAllLocation($idAuth, $substring);
        $data = [
            'success' => true,
            'shipperResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getDomesticRates() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $o = $this->input->post('o');
        $d = $this->input->post('d');
        $l = $this->input->post('l');
        $w = $this->input->post('w');
        $h = $this->input->post('h');
        $wt = $this->input->post('wt');
        $v = $this->input->post('v');
        $type = $this->input->post('type');
        $cod = $this->input->post('cod');
        $order = $this->input->post('order');
        $originCoord = $this->input->post('originCoord');
        $destinationCoord = $this->input->post('destinationCoord');

        $resp = $this->shipper->getDomesticRates($idAuth,$o, $d, $l, $w, $h, $wt, $v, $type, $cod, $order, $originCoord, $destinationCoord);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function domesticOrderCreation() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $o = $this->input->post('o');
        $d = $this->input->post('d');
        $l = $this->input->post('l');
        $w = $this->input->post('w');
        $h = $this->input->post('h');
        $wt = $this->input->post('wt');
        $v = $this->input->post('v');
        $rateId = $this->input->post('rateID');
        $consigneeName = $this->input->post('consigneeName');
        $consigneePhoneNumber = $this->input->post('consigneePhoneNumber');
        $consignerName = $this->input->post('consignerName');
        $consignerPhoneNumber = $this->input->post('consignerPhoneNumber');
        $originAddress = $this->input->post('originAddress');
        $originDirection = $this->input->post('originDirection');
        $destinationAddress = $this->input->post('destinationAddress');
        $destinationDirection = $this->input->post('destinationDirection');
        $items = $this->input->post('itemName');
        $contents = $this->input->post('contents');
        $useInsurance = $this->input->post('useInsurance');
        $externalId = $this->input->post('externalId');
        $paymentType = $this->input->post('paymentType');
        $packageType = $this->input->post('packageType');
        $cod = $this->input->post('cod');
        $originCoord = $this->input->post('originCoord');
        $destinationCoord = $this->input->post('destinationCoord');

        $resp = $this->shipper->domesticOrderCreation($idAuth, $o, $d, $l, $w, $h, $wt, $v, $rateId, $consigneeName, $consigneePhoneNumber, $consignerName, $consignerPhoneNumber, $originAddress, $originDirection, $destinationAddress, $destinationDirection, $items, $contents, $useInsurance, $externalId, $paymentType, $packageType, $cod, $originCoord, $destinationCoord);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getTrackingId($id) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->getTrackingId($idAuth, $id);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function orderActivation($orderId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $active = $this->input->post('active');
        $agentId = $this->input->post('agentId');

        $resp = $this->shipper->orderActivation($idAuth, $orderId, $active, $agentId);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function orderDetail($orderId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->orderDetail($idAuth, $orderId);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function orderUpdate($orderId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $l = $this->input->post('l');
        $w = $this->input->post('w');
        $h = $this->input->post('h');
        $wt = $this->input->post('wt');

        $resp = $this->shipper->orderUpdate($idAuth, $orderId, $l, $w, $h, $wt);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function orderCancellation($orderId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->orderCancellation($idAuth, $orderId);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function pickupRequest() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $orderIds = $this->input->post('orderIds');
        $datePickup = $this->input->post('datePickup');
        $agentId = $this->input->post('agentId');

        $resp = $this->shipper->pickupRequest($idAuth, $orderIds, $datePickup, $agentId);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function cancelPickup() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $orderIds = $this->input->post('orderIds');

        $resp = $this->shipper->cancelPickup($idAuth, $orderIds);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getAgentsBySuburb($suburbId) {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->getAgentsBySuburb($idAuth, $suburbId);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getAllTrackingStatus() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $resp = $this->shipper->getAllTrackingStatus($idAuth);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function generateAwbNumber() {
        if (!$user = validate_token())
            return $this->returnJSON(['status' => 'failed', 'message' => [], 'code' => 403], 403);

        $idAuth = $user->id_auth;

        $eid = $this->input->post('eid');
        $oid = $this->input->post('oid');

        $resp = $this->shipper->generateAwbNumber($idAuth, $eid, $oid);
        $data = [
            'success' => true,
            'shipperResponse ' => $resp
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
