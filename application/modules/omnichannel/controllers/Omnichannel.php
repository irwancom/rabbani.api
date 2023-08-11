<?php

class Omnichannel extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('session');
        $this->load->library('jubelio');
    }

    public function index() {
        $body = $this->jubelio->login();

        if (isset($body->token)) {
            // $this->session->set_userdata('token', $body->token);
            $file = fopen('jubelio_token.txt', 'w');
            fwrite($file, $body->token);
            fclose($file);
        }
        $data = [
            'success' => true,
            'jubelioResponse ' => $body
        ];
        return $this->returnJSON($data);
    }

    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
    }

}
