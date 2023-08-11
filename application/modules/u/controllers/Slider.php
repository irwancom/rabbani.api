
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\SliderHandler;

class Slider extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_get () {
        $filters = $this->input->get();
        if (empty($this->input->get('current_time'))) {
            $filters['current_time'] = date('Y-m-d H:i:s'); 
        }
        $handler = new SliderHandler($this->MainModel);
        $result = $handler->getSliders($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

}
