<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Sellercenter extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('sellercenter_model');

        $this->load->helper(array('form', 'url'));
    }

    function index_post() {
        
    }

    function parentidcategory_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('catName'),
                //$this->input->post('idCat'),
                $this->input->post('parentidcategory')
            );
            $data = $this->sellercenter_model->ParentidcategoryaddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('catName'),
                $this->input->post('idCat'),
                $this->input->post('parentidcategory')
            );
            $data = $this->sellercenter_model->ParentidcategoryupdateData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->sellercenter_model->dataCategory($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    //END CRUD CATEGORY
    //CRUD PRODUCT
    public function product_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->sellercenter_model->productAddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->sellercenter_model->productUpdateData($data);
        } else {
            $data = array(
//                $this->input->post('idStore'),
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );
            $data = $this->sellercenter_model->productGetData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function staff_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                // $this->input->post('idstore'),
                $this->input->post('level'),
                //$this->input->post('status'),
                $this->input->post('name'),
                $this->input->post('phone'),
                $this->input->post('username'),
                $this->input->post('password')
            );
            $data = $this->sellercenter_model->StaffaddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                //$this->input->post('idstore'),
                $this->input->post('level'),
                $this->input->post('name'),
                // $this->input->post('username'),
                $this->input->post('password'),
                $this->input->post('status')
            );
            $data = $this->sellercenter_model->StaffupdateData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->sellercenter_model->dataStaff($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function ditails_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data'),
                $this->input->post('idproduct')
            );
            $data = $this->sellercenter_model->ditailsAddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
                    //$this->input->post('idproduct')
            );
            $data = $this->sellercenter_model->ditailsupdateData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idproduct')
            );

            $data = $this->sellercenter_model->ditailsGetData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function transaction_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data'),
            );
            $data = $this->sellercenter_model->addOrders($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('name'),
                // $this->input->post('email'),
                // $this->input->post('password'),
                // $this->input->post('hp'),
                $this->input->post('data')
            );
            $data = $this->sellercenter_model->transactionupdateData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->sellercenter_model->transactionGetData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function store_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idstore'),
                $this->input->post('id_prov'),
                $this->input->post('id_city'),
                $this->input->post('id_dis'),
                $this->input->post('id_vill'),
                $this->input->post('namestore'),
                $this->input->post('addrstore'),
                $this->input->post('phonestore'),
                $this->input->post('pic')
            );
            $data = $this->sellercenter_model->StoreaddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idstore'),
                $this->input->post('id_prov'),
                $this->input->post('id_city'),
                $this->input->post('id_dis'),
                $this->input->post('id_vill'),
                $this->input->post('namestore'),
                $this->input->post('addrstore'),
                $this->input->post('phonestore'),
                $this->input->post('pic')
            );
            $data = $this->sellercenter_model->StoreupdateData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->sellercenter_model->dataStore($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function transactiondetails_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                // $this->input->post('idstore'),
                // $this->input->post('id_prov'),
                // $this->input->post('id_city'),
                // $this->input->post('id_dis'),
                // $this->input->post('id_vill'),
                // $this->input->post('namestore'),
                // $this->input->post('addrstore'),
                // $this->input->post('phonestore'),
                $this->input->post('data'),
                $this->input->post('idtransaction')
            );
            $data = $this->sellercenter_model->transactiondetailsAddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data'),
                // $this->input->post('id_prov'),
                // $this->input->post('id_city'),
                // $this->input->post('id_dis'),
                // $this->input->post('id_vill'),
                // $this->input->post('namestore'),
                // $this->input->post('addrstore'),
                // $this->input->post('phonestore'),
                $this->input->post('idtransactiondetails')
            );
            $data = $this->sellercenter_model->transactiondetailsUpdateData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
            );

            $data = $this->sellercenter_model->transactiondetailsGetData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function productimages_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                // $this->input->post('idstore'),
                // $this->input->post('id_prov'),
                // $this->input->post('id_city'),
                // $this->input->post('id_dis'),
                // $this->input->post('id_vill'),
                // $this->input->post('namestore'),
                // $this->input->post('addrstore'),
                // $this->input->post('phonestore'),
                $this->input->post('data'),
                    //$this->input->post('idtransaction')
            );
            $data = $this->sellercenter_model->productimagesAddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data'),
                    // $this->input->post('id_prov'),
                    // $this->input->post('id_city'),
                    // $this->input->post('id_dis'),
                    // $this->input->post('id_vill'),
                    // $this->input->post('namestore'),
                    // $this->input->post('addrstore'),
                    // $this->input->post('phonestore'),
                    //$this->input->post('idtransactiondetails')
            );
            $data = $this->sellercenter_model->productimagesUpdateData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                    // $this->input->post('idtransaction')
            );

            $data = $this->sellercenter_model->productimagesGetData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function staffpic_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = './file/img/';
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
                $data = $this->sellercenter_model->staffPic($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idproduct'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->sellercenter_model->staffPic($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function staffpicadd_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = './file/img/';
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
                $data = $this->sellercenter_model->staffPicadd($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    //$this->input->post('idproduct'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->sellercenter_model->staffPicadd($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function uploadpic_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = './file/img/';
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
                $data = $this->sellercenter_model->uploadPic($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idproduct'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->sellercenter_model->uploadPic($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function delPic_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                //$this->input->post('idproduct')
                $this->input->post('idpimages')
            );
            $data = $this->sellercenter_model->delPic($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function uploadpicditails_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = './file/img/';
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
                $data = $this->sellercenter_model->uploadPicditails($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idpditails'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->sellercenter_model->uploadPicditails($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function delPicditails_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idpditails'),
                $this->input->post('idpimagesdetails')
            );
            $data = $this->sellercenter_model->delPicditails($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function dashboard_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );
            $data = $this->sellercenter_model->dashboard($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function statuspay_post($pg = '') {
        if ($pg == 'pay') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
            );
            $data = $this->sellercenter_model->statuspaypayData($data);
        } elseif ($pg == 'cancel') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
                    // $this->input->post('idproduct')
            );
            $data = $this->sellercenter_model->statuspaycancelData($data);
        } elseif ($pg == 'refund') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
            );
            $data = $this->sellercenter_model->statuspayrefund($data);
        } else {
            $data = array(
//                $this->input->post('idStore'),
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                    //$this->input->post('idproduct')
            );
            $data = $this->sellercenter_model->statuspayData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function status_post($pg = '') {
        if ($pg == 'proses') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
            );
            $data = $this->sellercenter_model->statusprosesData($data);
        } elseif ($pg == 'sending') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
            );
            $data = $this->sellercenter_model->statussendingData($data);
        } else {
            $data = array(
//                $this->input->post('idStore'),
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                    //$this->input->post('idproduct')
            );
            $data = $this->sellercenter_model->statusGetData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    //END CRUD PRODUCT
}
