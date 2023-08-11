<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Dashboard extends REST_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('Dashbords');
        $this->load->model('Products');
        $this->load->model('Orders');
    }

    function index_get() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $total_product = $this->Products->totalItem(['id_auth' => $user->id_auth]);
        $total_order = $this->Orders->totalItem(['id_auth' => $user->id_auth]);
        
        $data = compact('total_product', 'total_order');
        $this->response(success_format($data));
    }
    

}
