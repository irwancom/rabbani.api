<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require_once APPPATH . 'libraries/redis/Redis.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\JNEService;
use Service\CLM\Handler\ShippingLocationHandler;
use Library\TripayGateway;

class Developer extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
        $this->redis = new \CI_Predis\Redis();
        //$this->load->driver('cache');
    }

    public function handleCheckSecretDev($secret = '') {
        if(!$secret || empty($secret) || is_null($secret)){
           return false;
        }
        if($secret!='rabbanideveloper1992'){
            return false;
        }
        return true;
    }

    public function payment_channel_post() {
        $tripay = new TripayGateway;
        $payload = $this->input->post();
        if(isset($payload['mode']) && $payload['mode'] && !empty($payload['mode']) && !is_null($payload['mode'])){
            $tripay->setEnv($payload['mode']);
        }
        if(isset($payload['merchant']) && $payload['merchant'] && !empty($payload['merchant']) && !is_null($payload['merchant'])){
            $tripay->setMerchantCode($payload['merchant']);
        }
        if(isset($payload['key']) && $payload['key'] && !empty($payload['key']) && !is_null($payload['key'])){
            $tripay->setApiKey($payload['key']);
        }
        if(isset($payload['private']) && $payload['private'] && !empty($payload['private']) && !is_null($payload['private'])){
            $tripay->setPrivateKey($payload['private']);
        }
        
        $payCode = '';
        if(isset($payload['code']) && $payload['code'] && !empty($payload['code']) && !is_null($payload['code'])){
            $payCode = $payload['code'];
        }

        $tripayResult = $tripay->channelPembayaran($payCode);
       // $tripayResult = $tripayResult->data;
        $this->delivery->data = $tripayResult;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }
    
    public function ordes_from_district_get($isType = null, $districtId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $cekSecret = $this->handleCheckSecretDev($secret);
        if(!$cekSecret){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }
        $handler = new ShippingLocationHandler($this->MainModel);
        $result = $handler->originDestiFromDistrict($isType, $districtId);

        $this->delivery->data = $result->data;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function destination_from_subdistrict_get($subDistrictId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $cekSecret = $this->handleCheckSecretDev($secret);
        if(!$cekSecret){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $handler = new ShippingLocationHandler($this->MainModel);
        $result = $handler->destinationFromSubdistrict($subDistrictId);

        $this->delivery->data = $result->data;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function origin_from_subdistrict_get($subDistrictId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $cekSecret = $this->handleCheckSecretDev($secret);
        if(!$cekSecret){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $handler = new ShippingLocationHandler($this->MainModel);
        $result = $handler->originFromSubdistrict($subDistrictId);

        $this->delivery->data = $result->data;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function redis_get() {
        // Using the default_server configuration
        echo $this->redis->ping();
        //echo $this->redis->getServerConnected()->ping();

        // Specifying hosts
        //$this->redis = new \CI_Predis\Redis(['serverName' => 'host_tls']);
        //echo $this->redis->ping();
        //echo $this->redis->getServerConnected()->ping();

        // Connect to another server
        //$this->redis->connect('another_instance_example');
        //echo $this->redis->ping();
        //echo $this->redis->getServersCollection()->getServer('another_instance_example')->ping();

        //get data
        //$data = $this->redis->get('coba');
        
        //set data
        //$tes = $this->redis->set('coba', json_encode(['dummy'=>'ok']));

        //delete data
        //$this->redis->delete('dummy');

        //print_r(json_encode($data, true));die;
    }

     public function cart_store_awb_get($secret = '') {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $cekSecret = $this->handleCheckSecretDev($secret);
        if(!$cekSecret){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }

        $carts = $this->db->from('cart_stores')->where(['crst_shipment !='=>NULL])->get()->result_array();
        $result = array(); $no = 0;
        foreach($carts as $k_cart=>$cart){
            $shipment = json_decode($cart['crst_shipment'], true);
            if(isset($shipment['tracking'])){
                $result[$no] = $cart;
                $no++;
            }
        }
        $this->delivery->data = $result;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function qrcode_get() {
        $this->load->helper('url');
        $this->load->library('Qr_code');
        $payload = $this->input->get();

        $pathFile = 'assets/'; $dirFile = 'images/';
        $dataQr = '';
        //$dataQr = //(isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data'])) ? $payload['data'] : base_url();
        $generateName = 'Qrcode';
        $nameFile = $generateName.'.png';

        //$config['cacheable']    = true;
        //$config['cachedir']     = './'.$pathFile;
        //$config['errorlog']     = './'.$pathFile;
        //$config['imagedir']     = './'.$pathFile.$dirFile;
        //$config['quality']      = true;
        //$config['size']         = '1024';
        //$config['black']        = array(224,255,255);
        //$config['white']        = array(70,130,180);
        //$this->qr_code->initialize($config);

        $this->qr_code->initialize();
        $params['data'] = $dataQr;
        $params['level'] = 'H';
        $params['size'] = 10;
        $params['padding'] = 1;
        $params['mode'] = 'develop';
        //$params['savename'] = FCPATH.$config['imagedir'].$nameFile;

        ob_start();
        $this->qr_code->generate($params);
        $result_qr_content_in_png = ob_get_contents();
        ob_end_clean();

        if(isset($params['savename']) && $params['savename'] && !empty($params['savename']) && !is_null($params['savename'])){
            $qrUrl = base_url('/'.$pathFile.$dirFile.$nameFile);
        }else{
            $result_qr_content_in_base64 =  base64_encode($result_qr_content_in_png);
            $qrUrl = 'data:image/jpeg;base64,'.$result_qr_content_in_base64;
        }

        $this->delivery->data = $qrUrl;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }


    public function qc_handle_get($secret = '') {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $cekSecret = $this->handleCheckSecretDev($secret);
        if(!$cekSecret){
            $this->delivery->addError(400, 'Unauthorized'); $this->response($this->delivery->format());
        }
        //$lists = $this->db->from('qc_orders')->limit(50, 0)->get()->result_array();

        $this->delivery->data = 'ok';
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());

    }

}
