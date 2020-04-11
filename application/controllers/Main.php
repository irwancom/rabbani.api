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
        $this->load->library('wa');

        $this->load->helper(array('form', 'url'));
    }

    function index_post() {
        
    }

    function category_get() {

        $data = $this->main_model->category();
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

    public function productDetails_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('idProduct')
            );
            $data = $this->main_model->ditailsGetData($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
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
        } else {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('secret'),
                $this->input->post('dataOrders')
            );

            $data = $this->main_model->addOrders($data);
        }
        if ($data) {
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
                $this->input->post('pass')
            );
            $data = $this->main_model->login($data);
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

    public function banner_get($type = '') {

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
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('idcart')
            );
            $data = $this->main_model->updatecart($data);
        } else {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('voucher'),
            );

            $data = $this->main_model->cart($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
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
            $data = $this->main_model->delcart($data);
            $data = $this->main_model->addcart($data);
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

}
