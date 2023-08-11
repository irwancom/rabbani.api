<?php

class Mutasi extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('moota');
        $this->load->Model('MainModel');
    }
    
    public function index() {
        echo 'moota';
    }

    public function getProfile () {
        $resp = null;
        $resp = $this->moota->getProfile();

        $data = [
            'success' => true,
            'mootaResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getBalance () {
        $resp = null;
        $resp = $this->moota->getBalance(2);

        $data = [
            'success' => true,
            'mootaResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getBank () {
        $resp = null;
        $resp = $this->moota->getBank();

        $data = [
            'success' => true,
            'mootaResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getBankDetail () {
        $resp = null;
        $resp = $this->moota->getBankDetail(1);

        $data = [
            'success' => true,
            'mootaResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getThisMonthMutation () {
        $resp = null;
        $resp = $this->moota->getThisMonthMutation(1);

        $data = [
            'success' => true,
            'mootaResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getRecentMutation () {
        $resp = null;
        $resp = $this->moota->getRecentMutation(1);

        $data = [
            'success' => true,
            'mootaResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getMutationByAmount () {
        $resp = null;
        $resp = $this->moota->getMutationByAmount(1, 1000);

        $data = [
            'success' => true,
            'mootaResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getMutationByDescription () {
        $resp = null;
        $resp = $this->moota->getMutationByDescription(1, 'Transfer');

        $data = [
            'success' => true,
            'mootaResponse' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function callback () {
        $payload = file_get_contents("php://input");
        $payload = json_decode($payload, true);

        $data = [
            'fromcall' => 'MOOTA',
            'dataJson' => json_encode($payload),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $action = $this->MainModel->insert('logcallback', $data);
        return $this->returnJSON($payload);
    }

    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

}
