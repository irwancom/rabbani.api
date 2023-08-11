<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;

class Notification extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function purchaseStatusList(){
        $status = array('waiting_payment','open','in_process','in_shipment','delivered','completed','canceled','payment_expired');
        return $status;
    }

    public function purchase_status_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $userId = $auth->data['id'];
        $statusList = $this->purchaseStatusList();
        $result = array();
        foreach($statusList as $k_status=>$status){
            $result[$status] = $this->db->select('user_id','status')->from('orders')->where(['user_id'=>$userId,'status'=>$status,'deleted_at'=>NULL])->count_all_results();
        }
        
        $this->delivery->data = $result;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());  
    }



//============================================= END LINE =============================================//
}
