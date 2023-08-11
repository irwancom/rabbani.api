
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\FlashsaleHandler;

class Flashsale extends REST_Controller {

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

        $filters = $this->input->get();
        if (empty($this->input->get('current_time'))) {
            $filters['current_time'] = date('Y-m-d H:i:s'); 
        }
        $handler = new FlashsaleHandler($this->MainModel);
        $result = $handler->getFlashsales($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

}
