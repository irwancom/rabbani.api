<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Main extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('main_model');
        $this->load->model('pay_model');
        $this->load->library('email');
        $this->load->library('sms');
		$this->load->library('otp');
        $this->load->library('xendit');

        $this->load->helper(array('form', 'url'));
    }

    function index_post() {
        
    }

    function logIp_post($pg = '') {
        if ($pg == 'dev') {
            $data = $this->main_model->logIp($this->input->post('ipAddress'));
        } else {
            $data = $this->main_model->logIp($this->input->post('ipAddress'));
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function category_get() {

        $data = $this->main_model->category();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function banner_get() {
        $data = $this->main_model->banner();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	


    public function product_get($page = '') {
        $data = $this->main_model->getData($page);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function product2_get($page = '') {
        $data = $this->main_model->getDatarandom($page);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function product3_get($page = '') {
        $data = $this->main_model->getDatasimiliar($page);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	public function similiar_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('idproduct')
            );

            $data = $this->main_model->similiar($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	
	   public function product4_get($page = '') {
        $data = $this->main_model->getDataproduct($page);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 public function product5_get($page = '') {
        $data = $this->main_model->getDataproductrandom($page);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 public function getproductrandom_get($page = '') {
        $data = $this->main_model->getproductrandom($page);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 public function getDataproduct200_get($page = '') {
        $data = $this->main_model->getDataproduct200($page);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	public function getproductcat_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('cat'),
                $this->input->post('hal')
            );

            $data = $this->main_model->getproductcat($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	


    public function productByCat_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('cat')
            );

            $data = $this->main_model->getDataByCat($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function address2_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('data')
            );

            $data = $this->main_model->address2($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function address3_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('data')
            );

            $data = $this->main_model->address3($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function district_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('city'),
				//$this->input->post('district')
            );

            $data = $this->main_model->district($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	public function district2_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			
			$data = array(
                $this->input->post('keyCode'),
              
            );
          

            $data = $this->main_model->district2($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	 public function districtnew_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                //$this->input->post('city'),
				$this->input->post('district')
            );

            $data = $this->main_model->districtnew($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	
	
	public function districtditails_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                
                $this->input->post('district')
            );

            $data = $this->main_model->districtditails($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function city_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('city')
            );

            $data = $this->main_model->city($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function city3_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('province')
            );

            $data = $this->main_model->city3($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function province_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('data')
            );

            $data = $this->main_model->province($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function province2_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode')
                    // $this->input->post('province')
            );

            $data = $this->main_model->province2($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function province3_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('province')
            );

            $data = $this->main_model->province3($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function productDetails_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('idProduct'),
				$this->input->post('ip'),
				$this->input->post('idauthuser')
            );
            $data = $this->main_model->ditailsGetData($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	public function productDetailsnew_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('idProduct'),
				$this->input->post('ip'),
				$this->input->post('idauthuser')
            );
            $data = $this->main_model->productDetailsnew($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function productDetailsV2_get($data = '') {
        $data = $this->main_model->ditailsGetDataV2($data);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function detailsSize_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('idProduct')
            );
            $data = $this->main_model->ditailsSize($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    function orders_post($mp = '') {
        if ($mp == 'mp') {
            $data = array(
                // $this->input->post('keyCode'),
                //$this->input->post('secret'),
                $this->input->post('dataOrders')
            );

            $data = $this->main_model->addOrdersByMp($data);
        } elseif ($mp == 'status') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('id')
            );
            $data = $this->main_model->orderStatus($data);
        } elseif ($mp == 'detials') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idTransaction')
            );
            $data = $this->main_model->detailsOrders($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('dataOrders')
            );
            if ($mp == 'v2') {
                $data = $this->main_model->addOrders($data, 2);
				
            } else {
                $data = $this->main_model->addOrders($data);
            }
        }
        if ($data) {
            if ($mp == 'v2') {
                $this->pay_model->createVa($data['dataTransaction']['noInvoice'], $this->input->post('keyCode'));
            }
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 function orders1_post($mp = '') {
        if ($mp == 'new') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('dataOrders')
            );

            $data = $this->main_model->addOrdersnew($data);
        } elseif ($mp == 'status') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('id')
            );
            $data = $this->main_model->orderStatus($data);
        } elseif ($mp == 'detials') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idTransaction')
            );
            $data = $this->main_model->detailsOrders($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('dataOrders')
            );
            if ($mp == 'v2') {
                $data = $this->main_model->addOrders1($data, 2);
			
            } else {
                $data = $this->main_model->addOrders1($data);
            }
        }
        if ($data) {
            if ($mp == 'v2') {
                $this->pay_model->createVa($data['dataTransaction']['noInvoice'], $this->input->post('keyCode'));
            
            }
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function orders2_post($mp = '') {
        if ($mp == 'new') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('dataOrders')
            );

            $data = $this->main_model->addOrdersnew($data);
        } elseif ($mp == 'status') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('id')
            );
            $data = $this->main_model->orderStatus($data);
        } elseif ($mp == 'detials') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idTransaction')
            );
            $data = $this->main_model->detailsOrders($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('dataOrders')
            );
            if ($mp == 'v2') {
                $data = $this->main_model->addOrders2($data, 2);
			
            } else {
                $data = $this->main_model->addOrders2($data);
            }
        }
        if ($data) {
            if ($mp == 'v2') {
                $this->pay_model->createVa($data['dataTransaction']['noInvoice'], $this->input->post('keyCode'));
            
            }
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function user_post($pg = '') {
        if ($pg == 'login') {
            $data = array(
                //$this->input->post('keyCodeStaff'),
                //$this->input->post('secret'),
                $this->input->post('name'),
                $this->input->post('email'),
                $this->input->post('password'),
                $this->input->post('hp')
                    // $this->input->post('foto')
            );
            $data = $this->main_model->UseraddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('firstname'),
                $this->input->post('lastname'),
                $this->input->post('username'),
                $this->input->post('password'),
                $this->input->post('email'),
                $this->input->post('hp'),
                $this->input->post('idauthuser')
            );
            $data = $this->main_model->UserupdateData($data);
        } elseif ($pg == 'register') {
            $data = array(
                $this->input->post('hp')
            );
            $data = $this->main_model->register($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idauthuser')
            );

            $data = $this->main_model->dataUser($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function search_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('search')
            );
            $data = $this->main_model->search($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	


    public function login_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('hp'),
                //$this->input->post('pass')
            );
            $data = $this->main_model->login($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	
    public function loginotp_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('otp')
                //$this->input->post('pass')
            );
            $data = $this->main_model->loginotp($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function register_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('username'),
                $this->input->post('password'),
                $this->input->post('namadepan'),
                $this->input->post('namabelakang'),
                $this->input->post('nomorwa'),
                $this->input->post('email')
            );
            $data = $this->main_model->register($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	public function newregister_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('nomerHP'),
               
            );
            $data = $this->main_model->newregister($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function otp_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('hp'),
            );
            $data = $this->main_model->otp($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function historytrans_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idauth')
            );
            $data = $this->main_model->historytrans($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function historytransdetail_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                // $this->input->post('noInvoice')
            );
            $data = $this->main_model->historytransdetail($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function userimage_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'images/';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            // $config['max_size'] = 100;
            // $config['max_width'] = 1024;
            // $config['max_height'] = 768;

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->main_model->userimage($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCode'),
                    //$this->input->post('secret'),
                    // $this->input->post('idauthuser'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->main_model->userimage($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function staffpic_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'images/';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            // $config['max_size'] = 100;
            // $config['max_width'] = 1024;
            // $config['max_height'] = 768;

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->main_model->userimage($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idproduct'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->main_model->userimage($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function banner2_get($type = '') {

        $data = $this->main_model->dataBanner($type);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	


    function cart_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCode'),
                //$this->input->post('secret'),
                $this->input->post('dataOrders')
            );

            $data = $this->main_model->addcart($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idcart')
            );
            $data = $this->main_model->delcart($data);
        } elseif ($pg == 'new') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('dataOrders')
            );
            $data = $this->main_model->addcartnew($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('voucher')
            );

            $data = $this->main_model->cart($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 public function cartnew_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                    //$this->input->post('idstore')
            );
            $data = $this->main_model->cartnew($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	

    function address_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('data')
            );
            $data = $this->main_model->addressUseradd($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('data')
            );
            $data = $this->main_model->addressUserupdate($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idpeople')
            );
            $data = $this->main_model->addressUserdel($data);
        } elseif ($pg == 'ditail') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idpeople')
            );
            $data = $this->main_model->addressditail($data);
        } else {
            $data = array(
                $this->input->post('keyCode')
            );

            $data = $this->main_model->address($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function whishlist_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('data')
            );

            $data = $this->main_model->addwhishlist($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idcart')
            );
            $data = $this->main_model->delwhishlist($data);
        
        } elseif ($pg == 'view') {
            $data = array(
                $this->input->post('keyCode'),
            );
            $data = $this->main_model->whishlistview($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
            );

            $data = $this->main_model->whishlist($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function store_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                    //$this->input->post('idstore')
            );
            $data = $this->main_model->store($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	 public function storedetails_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idcity')
            );
            $data = $this->main_model->storedetails($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function faq_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                    //$this->input->post('idstore')
            );
            $data = $this->main_model->faq($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function quest_post() {
        $data = array(
            $this->input->post('cat')
        );

        $data = $this->main_model->quest($data);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	function flashsale_get() {

        $data = $this->main_model->getflashsale();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	  public function voucher_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('vouchercode')
            );

            $data = $this->main_model->voucher($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	public function vouchernew_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('vouchercode')
            );

            $data = $this->main_model->vouchernew($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	function promo_get() {

        $data = $this->main_model->getpromo();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 public function forget_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('hp'),
				//$this->input->post('pass')
            );

            $data = $this->main_model->forget($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	 public function password_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('otp'),
				$this->input->post('pass')
            );

            $data = $this->main_model->password($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	  function getblog_get() {

        $data = $this->main_model->getblog();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 function comment_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
               
                $this->input->post('keyCode'),
                $this->input->post('idproduct'),
                $this->input->post('comment'),
				$this->input->post('star')
                
                   
            );
            $data = $this->main_model->addcomment($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idcomment'),
                $this->input->post('comment'),
				$this->input->post('star')
               
            );
            $data = $this->main_model->updatecomment($data);
        } elseif ($pg == 'register') {
            $data = array(
                $this->input->post('hp')
            );
            $data = $this->main_model->register($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
				$this->input->post('idproduct')
              
            );

            $data = $this->main_model->getcomment($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	
	 function review_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
               
                $this->input->post('keyCode'),
                $this->input->post('idproduct'),
                $this->input->post('star')
                
                   
            );
            $data = $this->main_model->addreview($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idreview'),
                $this->input->post('star')
               
            );
            $data = $this->main_model->updatereview($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
				$this->input->post('idproduct')
              
            );

            $data = $this->main_model->getreview($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	function freegift_get() {

        $data = $this->main_model->freegift();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 public function cekfreegift_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('idproduct'),
				//$this->input->post('pass')
            );

            $data = $this->main_model->cekfreegift($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	
	
	function affiliate_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
               
                $this->input->post('keyCode'),
                $this->input->post('discount')
               // $this->input->post('star')
                 
                   
            );
            $data = $this->main_model->addaffiliate($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idreview'),
                $this->input->post('star')
               
            );
            $data = $this->main_model->updatereview($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
				//$this->input->post('idproduct')
              
            );

            $data = $this->main_model->affiliate($data); 
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	public function archery_get() {

        $data = $this->main_model->archery();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 public function addarchery_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data')
            );
            $data = $this->main_model->addarchery($data);
            if ($data) {
                $this->response($data, 200); 
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    } 
	
	public function reviewaff_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                    //$this->input->post('idstore')
            );
            $data = $this->main_model->reviewaff($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	public function email_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->main_model->email($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	public function test_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->main_model->test($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function newproduct_get($page = '') {
        $data = $this->main_model->newproduct($page);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
}
