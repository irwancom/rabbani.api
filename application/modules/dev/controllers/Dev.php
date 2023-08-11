<?php

use Service\Handler;

class Dev extends CI_Controller {

    private $serviceHandler;

    public function __construct() {
        parent::__construct();

        $this->load->model('MainModel');
        $this->serviceHandler = new Handler($this->MainModel);
    }
    
    public function index() {
        echo 'dev';
    }

    // digital ocean upload
    public function do_upload()
    {
        $result = upload_image('image', 10);
        return $this->returnJSON($result);
    }

    // digital ocean delete
    public function do_delete () {
        $result = delete_from_cloud($this->input->post('filename'));
        return $this->returnJSON($result);
    }

    public function moota_callback () {
        $payload = file_get_contents("php://input");
        $payload = json_decode($payload, true);

        $data = [
            'fromcall' => 'MOOTA_TRANSACTIONS',
            'dataJson' => json_encode($payload),
            'dateTime' => date('Y-m-d H:i:s')
        ];

        $action = $this->MainModel->insert('logcallback', $data);
        $handler = $this->serviceHandler->onMootaCallback($payload);
        $payload['data'] = $handler;
        return $this->returnJSON($payload);
    }

    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

    public function php() {

        phpinfo();
    }

}
