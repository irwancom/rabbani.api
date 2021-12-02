<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Admin extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('admin_model');
        //$this->load->library('wa');
        $this->load->library('sms');

        $this->load->helper(array('form', 'url'));
    }

    function index_post() {
        
    }

    public function verfyAccount($keyCode = '', $secret = '') {
        $data = array(
            "keyCodeStaff" => $keyCode,
            "secret" => $secret
        );
        // $this->db->select('c.namestore, a.*');
        // $this->db->Join('store as c', 'c.idstore = a.idstore', 'left');
        $query = $this->db->get_where('apiauth_staff as a', $data)->result();
        return $query;
    }

    public function token_response() {
        $this->response(array('status' => 502, 'error' => 'true','message' => 'Token tidak boleh salah'));
    }

    public function duplicate_response() {
        $this->response(array('status' => 502, 'error' => 'true','message' => 'Sudah Ada Data Di Database'));
    }

    public function empty_response() {
        $this->response(array('status' => 502, 'error' => 'true','message' => 'Data Input Kosong'));
    }

    //CRUD CATEGORY
    function category_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('catName')
            );
            $data = $this->admin_model->CataddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('catName'),
                $this->input->post('idCat')
            );
            $data = $this->admin_model->CatupdateData($data);
        } elseif ($pg == 'delete') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idCat')
            );
            $data = $this->admin_model->CatdeleteData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->dataCategory($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
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
            $data = $this->admin_model->ParentidcategoryaddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('catName'),
                $this->input->post('idCat'),
                $this->input->post('parentidcategory')
            );
            $data = $this->admin_model->ParentidcategoryupdateData($data);
        } elseif ($pg == 'delete') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('parentidcategory')
            );
            $data = $this->admin_model->parentCatdeleteData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->dataCategory($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    //END CRUD CATEGORY
    //CRUD PRODUCT

    public function search_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('search')
            );
            $data = $this->admin_model->searchProduct($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function searchuser_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('search')
            );
            $data = $this->admin_model->searchUser($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function searchtransaction_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('search')
            );
            $data = $this->admin_model->searchTransaction($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function searchstore_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('search')
            );
            $data = $this->admin_model->searchStore($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function product_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('desc'),
                $this->input->post('descditails'),
                $this->input->post('data')
            );
//            print_r($data);
//            exit;
            $data = $this->admin_model->productAddData($data, TRUE);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->admin_model->productUpdateData($data);
        } elseif ($pg == 'delete') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idproduct')
            );
            $data = $this->admin_model->productDeleteData($data);
        } elseif ($pg == 'v2') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );
            $data = $this->admin_model->productGetData_v2($data);
        } elseif ($pg == 'details') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idProduct')
            );
            $data = $this->admin_model->productGetDetails_v2($data);
        } elseif ($pg == 'update_v2') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idproduct'),
                $this->input->post('productName'),
                $this->input->post('descr'),
                $this->input->post('descr_en'),
                $this->input->post('descrDitails'),
                $this->input->post('descrDitails_en'),
                $this->input->post('delproduct'),
                $this->input->post('idcategory'),
                $this->input->post('weight')
            );
            $data = $this->admin_model->productUpdate_v2($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('page')
            );
            $data = $this->admin_model->productGetData($data);
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
                $this->input->post('level'),
                //$this->input->post('status'),
                $this->input->post('idstore'),
                $this->input->post('name'),
                $this->input->post('phone'),
                $this->input->post('username'),
                $this->input->post('password')
            );
            $data = $this->admin_model->StaffaddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('level'),
                $this->input->post('status'),
                $this->input->post('name'),
                $this->input->post('phone'),
                $this->input->post('username'),
                $this->input->post('password'),
                $this->input->post('idstore'),
                $this->input->post('idauthstaff')
            );
            $data = $this->admin_model->StaffupdateData($data);
        } elseif ($pg == 'profile') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('name'),
                $this->input->post('password'),
				$this->input->post('phone'),
                $this->input->post('staffemail')
            );
            $data = $this->admin_model->profile($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->dataStaff($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function uploadImageV2_post($pg = '') {
        if ($pg == 'delete') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idpimages'),
                $this->input->post('imageName')
            );
            $data = $this->admin_model->productUpload_v2($data, 'del');
        } elseif ($pg == 'dataCollor') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idProduct')
            );
            $data = $this->admin_model->productUpload_v2($data, 'dataCollor');
        } elseif ($pg == 'imagesDetailsProductDel') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idpimagesdetails'),
                $this->input->post('imageName')
            );
            $data = $this->admin_model->productUpload_v2($data, 'imagesDetailsProductDel');
        } elseif ($pg == 'imagesDetailsProduct') {
            $config['upload_path'] = 'img';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->admin_model->productUpload_v2($data, 'imagesDetailsProduct');
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idproduct'),
                    $this->input->post('collor'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->productUpload_v2($data, 'imagesDetailsProduct');
            }
        } else {
            $config['upload_path'] = 'img';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->admin_model->productUpload_v2($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idproduct'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->productUpload_v2($data);
            }
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
            $config['upload_path'] = 'img';
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
                $data = $this->admin_model->staffPic($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idproduct'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->staffPic($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    function user_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->admin_model->UseraddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->admin_model->UserupdateData($data);
        } elseif ($pg == 'ditails') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idauthuser')
            );
            $data = $this->admin_model->UserditailsData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('page')
            );

            $data = $this->admin_model->dataUser($data);
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
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->admin_model->addressUseradd($data);
        } elseif ($pg == 'details') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idpeople')
            );
            $data = $this->admin_model->addressdetails($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->admin_model->addressUserUpdate($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idauthuser')
            );

            $data = $this->admin_model->addressUser($data);
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
            $data = $this->admin_model->ditailsAddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data'),
                $this->input->post('idpditails')
            );
            $data = $this->admin_model->ditailsupdateData($data);
        } elseif ($pg == 'delete') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idpditails')
            );
            $data = $this->admin_model->ditailsdeleteData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idproduct')
            );

            $data = $this->admin_model->ditailsGetData($data);
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
                $this->input->post('data')
            );
            $data = $this->admin_model->addOrders($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data'),
				//$this->input->post('time')
            );
//            $this->wa->SendWa('08986002287', 'haloooo 1');
            $data = $this->admin_model->transactionupdateData($data);
		    
        } elseif ($pg == 'report') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('date')
            );

            $data = $this->admin_model->reporttransaction($data);
		 } elseif ($pg == 'fu') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('date')
                
            );

            $data = $this->admin_model->futransaction($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('page')
            );

            $data = $this->admin_model->transactionGetData($data);
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
                $this->input->post('id_prov'),
                $this->input->post('id_city'),
                $this->input->post('namestore'),
                $this->input->post('addrstore'),
                $this->input->post('phonestore'),
                $this->input->post('wa')
            );
            $data = $this->admin_model->StoreaddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idstore'),
                $this->input->post('id_prov'),
                $this->input->post('id_city'),
                $this->input->post('namestore'),
                $this->input->post('addrstore'),
                $this->input->post('phonestore'),
                $this->input->post('wa')
            );
            $data = $this->admin_model->StoreupdateData($data);
        } elseif ($pg == 'ditails') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idstore')
            );
            $data = $this->admin_model->dataStoreditails($data);
        } elseif ($pg == 'delete') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idstore')
            );
            $data = $this->admin_model->storedeleteData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('page')
            );

            $data = $this->admin_model->dataStore($data);
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
                $this->input->post('data')
            );
            $data = $this->admin_model->transactiondetailsAddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->admin_model->transactiondetailsUpdateData($data);
		 } elseif ($pg == 'details') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('nomer')
            );
            $data = $this->admin_model->transactiondetails($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
            );

            $data = $this->admin_model->transactiondetailsGetData($data);
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
                $this->input->post('data'),
            );
            $data = $this->admin_model->productimagesAddData($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data'),
            );
            $data = $this->admin_model->productimagesUpdateData($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
            );

            $data = $this->admin_model->productimagesGetData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function uploadpic_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'img';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            //$config['max_size'] = 100;
            //$config['max_width'] = 700;
            // $config['max_height'] = 700;

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                $data = $this->admin_model->uploadPic($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idproduct'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->uploadPic($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function imagecat_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'img';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            //$config['max_size'] = 100;
            //$config['max_width'] = 700;
            //$config['max_height'] = 700;

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->admin_model->imagecat($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idcat'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->imagecat($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function imagecat2_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'img';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            //$config['max_size'] = 100;
            //$config['max_width'] = 700;
            //$config['max_height'] = 700;

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->admin_model->imagecat2($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idcat'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->imagecat2($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function imagesubcat_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'img';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            //$config['max_size'] = 100;
            //$config['max_width'] = 700;
            //$config['max_height'] = 700;

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->admin_model->imagesubcat($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('parentidcategory'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->imagesubcat($data);
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
                //$this->input->post('idproduct'),
                $this->input->post('idpimages')
            );
            $data = $this->admin_model->delPic($data);
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
            $config['upload_path'] = 'img';
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
                $data = $this->admin_model->uploadPicditails($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('data'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->uploadPicditails($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function addpicditails_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'img';
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
                $data = $this->admin_model->addPicditails($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idpditails'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->addPicditails($data);
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
                $this->input->post('idpimagesdetails')
            );
            $data = $this->admin_model->delPicditails($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function banner_post($pg = '') {
        if ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idbanner')
            );
            $data = $this->admin_model->bannerdel($data);
        } elseif ($pg == 'cancel') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
                    // $this->input->post('idproduct')
            );
            $data = $this->admin_model->statuspaycancelData($data);
        } elseif ($pg == 'refund') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
            );
            $data = $this->admin_model->statuspayrefund($data);
        } else {
            $data = array(
//                $this->input->post('idStore'),
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                    //$this->input->post('idproduct')
            );
            $data = $this->admin_model->banner($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function addbanner_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'img';
            $config['encrypt_name'] = true;
            $config['use_storage_service'] = true;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            //$config['max_width'] = 0;
            //$config['max_height'] = 0;

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                //($data);
                // $data = $this->admin_model->banneradd($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('data'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->banneradd($data);
            }
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
            $data = $this->admin_model->dashboard($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function feed_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );
            $data = $this->admin_model->feeddata($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function latestorder_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );
            $data = $this->admin_model->latestorder($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function latestproduct_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );
            $data = $this->admin_model->latestproduct($data);
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
                $this->input->post('idtransaction'),
                $this->input->post('status')
            );
            $data = $this->admin_model->statuspaypayData($data);
        } elseif ($pg == 'cancel') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction'),
            );
            $data = $this->admin_model->statuspaycancelData($data);
        } elseif ($pg == 'refund') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction')
            );
            $data = $this->admin_model->statuspayrefund($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );
            $data = $this->admin_model->statuspayData($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 function discount_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('discount'),
				$this->input->post('start'),
				$this->input->post('end')
            );
            $data = $this->admin_model->adddiscount($data);
        } elseif ($pg == 'product') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idproduct'),
				 $this->input->post('discount')
            );
            $data = $this->admin_model->productdiscount($data);
		 } elseif ($pg == 'ditails') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idpditails'),
				 $this->input->post('discount')
            );
            $data = $this->admin_model->ditailsdiscount($data);
        } elseif ($pg == 'category') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idcategory'),
				 $this->input->post('discount')
            );
            $data = $this->admin_model->categorydiscount($data);
		 } elseif ($pg == 'flashsale') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('start'),
				$this->input->post('end'),
                $this->input->post('idproduct'),
				$this->input->post('discount'),
				$this->input->post('limit')
            );
            $data = $this->admin_model->flashsale($data);
		 } elseif ($pg == 'delflashsale') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('idflashsale')
			
            );
            $data = $this->admin_model->delflashsale($data);
		 } elseif ($pg == 'getflashsale') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('start'),
				$this->input->post('end'),
                $this->input->post('idproduct'),
				$this->input->post('discount')
            );
            $data = $this->admin_model->getflashsale($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->discount($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 function terms_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('sk'),
               
            );
            $data = $this->admin_model->addterms($data);
        } elseif ($pg == 'draft') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idterms')
                
            );
            $data = $this->admin_model->termsdraft($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->dataterms($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

 function shorturl_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('urlname'),
				$this->input->post('urltarget')
               
            );
            $data = $this->admin_model->addshorturl($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idshort')
                
            );
            $data = $this->admin_model->delshorturl($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->shorturl($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 function cod_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
			$this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('kecamatan'),
                
            );
            $data = $this->admin_model->addcod($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('idcod'),
				$this->input->post('kecamatan'),
                
            );
            $data = $this->admin_model->updatecod($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idcod')
            );
            $data = $this->admin_model->delcod($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->datacod($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	public function po_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );
            $data = $this->admin_model->po($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	
	
	
	 function jualcepat_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('start'),
				$this->input->post('end'),
                $this->input->post('idproduct'),
				$this->input->post('discount'),
				$this->input->post('limit'),
               
            );
            $data = $this->admin_model->jualcepat($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idshort')
                
            );
            $data = $this->admin_model->delshorturl($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->getflashsale($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	function voucher_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('startdate'),
				$this->input->post('enddate'),
                $this->input->post('vouchercode'),
				$this->input->post('voucherdisc'),
				$this->input->post('minorder'),
               
            );
            $data = $this->admin_model->addvoucher($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idvoucher')
                
            );
            $data = $this->admin_model->delvoucher($data);
		 } elseif ($pg == 'ditails') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('vouchercode')
                
            );
            $data = $this->admin_model->ditailsvoucher($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
            );

            $data = $this->admin_model->getvoucher($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	function promo_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('start'),
				$this->input->post('end'),
                $this->input->post('idproduct'),
				//$this->input->post('discount'),
				$this->input->post('limit')
               
            );
            $data = $this->admin_model->addpromo($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idpromo')
                
            );
            $data = $this->admin_model->delpromo($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->getpromo($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	
	function blog_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('data')
				
               
            );
            $data = $this->admin_model->addblog($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idblog')
                
            );
            $data = $this->admin_model->delblog($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->getblog($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 public function uploadPicblog_post() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $config['upload_path'] = 'img';
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
                $data = $this->admin_model->uploadPicblog($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCodeStaff'),
                    $this->input->post('secret'),
                    $this->input->post('idblog'),
                    $data,
                    $config['upload_path']
                );
                $data = $this->admin_model->uploadPicblog($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
	
	

	
	function comment_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('data')
				
               
            );
            $data = $this->admin_model->addblog($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idcomment')
                
            );
            $data = $this->admin_model->delcomment($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->getcomment($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	
	function freegift_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('data')
				
               
            );
            $data = $this->admin_model->addfreegift($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idfreegift')
                
            );
            $data = $this->admin_model->delfreegift($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->getfreegift($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	
	
	function reseller_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('data')
				
               
            );
            $data = $this->admin_model->addreseller($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idreseller')
                
            );
            $data = $this->admin_model->delreseller($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
            );

            $data = $this->admin_model->getreseller($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	
	
    function allorder_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                
				$this->input->post('no_pesanan'),
				$this->input->post('tanggal'),
				$this->input->post('pelanggan'),
				$this->input->post('sumber'),
				$this->input->post('detailproduk')
				
               
            );
            $data = $this->admin_model->addallorder($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('noresi')
                
                
            );
            $data = $this->admin_model->updateallorder($data);
		 } elseif ($pg == 'sku') {
            $data = array(
                $this->input->post('noresi'),
				$this->input->post('sku')
              
                
            );
            $data = $this->admin_model->skuallorder($data);
        } else {
            $data = array(
                $this->input->post('noresi')
                
            );
            $data = $this->admin_model->allorder($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	 function qcorder_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                
				$this->input->post('data'),
				$this->input->post('detailorder')
               
            );
            $data = $this->admin_model->addqcorder($data);
        } elseif ($pg == 'resi') {
            $data = array(
              $this->input->post('noresi')
                
                
            );
            $data = $this->admin_model->resiqcorder($data);
		 } elseif ($pg == 'pos') {
            $data = array(
                $this->input->post('noresi'),
				$this->input->post('sku')
              
                
            );
            $data = $this->admin_model->skuallorder($data);
        } else {
            $data = array(
                $this->input->post('day')
                
            );
            $data = $this->admin_model->qcorder($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	
	 function posview_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                
				$this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('idorder'),
				//$this->input->post('time')
                 
            );
            $data = $this->admin_model->addposview($data);
        } elseif ($pg == 'pos') {
            $data = array(
              $this->input->post('keyCodeStaff'),
              $this->input->post('secret'),
			  $this->input->post('date'),
		      $this->input->post('time')
                
                
            );
            $data = $this->admin_model->insertposview($data);
		 } elseif ($pg == 'update') {
            $data = array(
              $this->input->post('keyCodeStaff'),
              $this->input->post('secret'),
			  $this->input->post('idorder')
              
                
            );
            $data = $this->admin_model->updateposview($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('date'),
				$this->input->post('time')
                
            );
            $data = $this->admin_model->posview($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	
	
	 function feemp_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                
				$this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('mp'),
				$this->input->post('feemp')
                 
            );
            $data = $this->admin_model->addfeemp($data);
        } elseif ($pg == 'update') {
            $data = array(
              $this->input->post('keyCodeStaff'),
              $this->input->post('secret'),
			  $this->input->post('mp'),
			  $this->input->post('feemp'),
			  $this->input->post('idfeemp')
                
                
            );
            $data = $this->admin_model->updatefeemp($data);
		 } elseif ($pg == 'delete') {
            $data = array(
              $this->input->post('keyCodeStaff'),
              $this->input->post('secret'),
			  $this->input->post('idfeemp')
              
                
            );
            $data = $this->admin_model->deletefeemp($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret')
                
            );
            $data = $this->admin_model->feemp($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }



	 function video_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                
				$this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				$this->input->post('name'),
				$this->input->post('link')
                 
            );
            $data = $this->admin_model->addvideo($data);
        } elseif ($pg == 'update') { 
            $data = array(
              $this->input->post('keyCodeStaff'),
              $this->input->post('secret'),
			  $this->input->post('mp'),
			  $this->input->post('feemp'),
			  $this->input->post('idfeemp')
                
                
            );
            $data = $this->admin_model->updatevideo($data);
		 } elseif ($pg == 'delete') {
            $data = array(
              $this->input->post('keyCodeStaff'),
              $this->input->post('secret'),
			  $this->input->post('idvideo')
              
                
            );
            $data = $this->admin_model->deletevideo($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
				
                
            );
            $data = $this->admin_model->video($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }


 public function dataimage_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
              
            );
            $data = $this->admin_model->dataimage($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }


    public function statussending_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idtransaction'),
                $this->input->post('status'),
              
            );
            $data = $this->admin_model->statussending($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
    
    function vouchernew_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('data')
            );
            $data = $this->admin_model->voucheradd($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('catName'),
                $this->input->post('idCat')
            );
            $data = $this->admin_model->CatupdateData($data);
        } elseif ($pg == 'del') {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),
                $this->input->post('idvoucher')
            );
            $data = $this->admin_model->voucherdel($data);
        } else {
            $data = array(
                $this->input->post('keyCodeStaff'),
                $this->input->post('secret'),

            
        );
        $data = $this->admin_model->vouchernew($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
	

    public function awbcod_post() { 
       
        $dataz = $this->input->post('data');
        $datay = json_decode($dataz);
          // print_r($dataz);exit;
        $verify = $this->verfyAccount($datay->keyCodeStaff, $datay->secret);
          
            if (!empty($verify)) {
 
                $this->db->set('trackingCode', $datay->awb);
                $this->db->where('noInvoice', $datay->noInvoice);
                $supdate = $this->db->update('transaction');
            } else {
                    return $this->token_response();
            }
        
        if ($supdate) {
            $this->response(array('status' => 202, 'error' => false,'totalData' => count($supdate),'data' => $supdate));
             
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function addnewproduct_post() {
        
        $dataz = $this->input->post('data');
        $datay = json_decode($dataz);
        // print_r($datay);exit;
        $verify = $this->verfyAccount($datay->keyCodeStaff, $datay->secret);
          
        if (!empty($verify)) {
           
               $this->db->where('skuProduct',$datay->skuProduct);
        $cek = $this->db->get_where('product')->result();
           // print_r($cek);exit;
            if(empty($cek)) {
                $datax = array(
                    'dateCreate' => date('Y-m-d'),
                    'timeCreate' => date('H:i:s'),
                    'idstore' => 1,
                    'idcategory' => $datay->idcategory,
                    'skuProduct' => $datay->skuProduct,
                    'productName' => $datay->productName,
                    'descr' => $datay->descr,
                    'descrDitails' => $datay->descrDitails,
                    'delproduct' => $datay->delproduct,
            );
             // print_r($datax);exit;      
            $this->db->insert('product', $datax);
            $insert_id = $this->db->insert_id();

                foreach($datay->productDitails as $q){
                    // print_r($q);exit;
                    $datam = array(
                        'idproduct' => $insert_id,
                        'skuPditails' => $q->skuPditails,
                        'size' => $q->size,
                        'collor' => $q->collor,
                        'weight' => $q->weight,
                        'price' => $q->price,
                        'realprice' => $q->realprice,
                        'priceDiscount' => $q->priceDiscount,
                        'stock' => $q->stock,
                        'delproductditails' => $q->delproductditails,
                    );
                    // print_r($datam);exit;
                    $this->db->insert('product_ditails', $datam);
                }
                $this->db->where('skuProduct',$datay->skuProduct);
                $datap = $this->db->get_where('product')->result();
                $this->db->where('idproduct',$datap[0]->idproduct);
                $datad = $this->db->get_where('product_ditails')->result();

                $dataq= array(
                        'Product' => $datap,
                        'product details' => $datad
                    );

            } else {
                return $this->duplicate_response();
            }
        } else {
                return $this->token_response();
        }

            
                   
        if ($dataq) {
            $this->response(array('status' => 202, 'error' => false,'totalData' => count($dataq),'data' => $dataq));
        } else {
            $this->response(array('status' => 'fail', 502));
        }     
    }

    public function addimgdetail_post() { 
        header('Content-Type: application/json');
         if ($_SERVER['REQUEST_METHOD'] == 'POST') {
             $config['upload_path'] = 'img';
             $config['encrypt_name'] = true;
             $config['use_storage_service'] = true;
             $config['allowed_types'] = 'gif|jpg|png|jpeg';
            //  $config['allowed_types'] = 'svg';
 
             //$config['max_size'] = 100;
             //$config['max_width'] = 700;
             // $config['max_height'] = 700;
 
             $this->load->library('upload', $config);
             if (!$this->upload->do_upload('filePic')) {
                 $error = array('error' => $this->upload->display_errors());
 
                 $data = array(
                     $error
                 );
                  print_r($data);exit; 
             } else {
                 $data = array('upload_data' => $this->upload->data());
 
                 $data = array(
                     $this->input->post('data'),
                     $data,
                     $config['upload_path']
                 );
              } 
                 
            
             
            $datay = json_decode($data[0]);
            if (!empty($datay->idproduct) || !empty($datay->idpditails) || !empty($data[1]['upload_data']['file_url']) || !empty($data[1]['upload_data']['file_name']) || !empty($data[2])) {
             $verify = $this->verfyAccount($datay->keyCodeStaff, $datay->secret);
           // print_r($verify);exit;
                 if (!empty($verify)) {
                     $this->db->where('idpditails',$datay->idpditails);
                     $cek = $this->db->get_where('product_images_ditails')->result();
                     if(!empty($cek)) {
                         return $this->duplicate_response();
                     } else {
                         $datax = array(
                             'idproduct' => $datay->idproduct,
                             'idpditails' => $datay->idpditails,
                             'collor' => $datay->collor,
                             'urlImage' => $data[1]['upload_data']['file_url'],
                             'dir' => $data[2],
                             'imageFile' => $data[1]['upload_data']['file_name'],
                             'size' => $data[1]['upload_data']['file_size'],
                             'type' => $data[1]['upload_data']['image_type']
                         );
                         $this->db->insert('product_images_ditails', $datax);
                     }
                
                 
                 } else {
                     return $this->token_response();
                 }
            } else {   
                return $this->empty_response();
            }
             
         
         if ($datax) {
             $this->response(array('status' => 202, 'error' => false,'totalData' => count($datax),'data' => $datax));
              
         } else {
             $this->response(array('status' => 'fail', 502));
         }
     }
 }

 public function kitalog_post() {
        
     
    $this->db->Join('kitalog_images as b', 'b.idkitalog = a.idkitalog');
    $datax = $this->db->get_where('kitalog as a')->result();

   if ($datax) {
          $this->response(array('status' => 202, 'error' => false,'totalData' => count($datax),'data' => $datax));
           
      } else {
          $this->response(array('status' => 'fail', 502));
      }

   }

   public function addkitalog_post() {

    $dataz = $this->input->post('data');
    $datay = json_decode($dataz);
    // print_r($datay);exit;
        $datax = array(
            'title' => $datay->title,
            'urlpdf' => $datay->url,
            'pdfFile' => $datay->pdfname,
                            
        );
                    // print_r($datax);exit;
        $this->db->insert('kitalog', $datax);
        $this->db->select('idkitalog,title,urlpdf');
        $this->db->where('title',$datay->title);
        $datax = $this->db->get_where('kitalog')->result();

    if ($datax) {
        $this->response(array('status' => 202, 'error' => false,'totalData' => count($datax),'data' => $datax));
             
    } else {
            $this->response(array('status' => 'fail', 502));
    }
    
}


public function imgkitalog_post() {
     
    $dataz = $this->input->post('data');
    $datay = json_decode($dataz);
// print_r($datay);exit;

    if (!empty($datay->idkitalog) || !empty($datay->urlimg) || !empty($datay->filename)) {
        $datax = array(
            'idkitalog' => $datay->idkitalog,
            'urlImage' => $datay->urlimg,
            'imageFile' => $datay->filename,
                        
        );
                // print_r($datax);exit;
        $this->db->insert('kitalog_images', $datax);
        $this->db->where('idkitalog',$datay->idkitalog);
        $this->db->select('idkitalog,urlImage,imageFile');
        $datax = $this->db->get_where('kitalog_images')->result();
    } else {
        return $this->empty_response();
    }
  
 

    if ($datax) {
      $this->response(array('status' => 202, 'error' => false,'totalData' => count($datax),'data' => $datax));
       
    } else {
      $this->response(array('status' => 'fail', 502));
    }


}

public function delkitalog_post() {

    $dataz = $this->input->post('idkitalog');
    // $datay = json_decode($dataz);
     // print_r($dataz);exit;
      
        $this->db->where('idkitalog',$dataz);           
        $datax = $this->db->delete('kitalog');

    if ($datax) {
        $this->response(array('status' => 202, 'error' => false,'totalData' => count($datax),'data' => $datax));
             
    } else {
            $this->response(array('status' => 'fail', 502));
    }
    
}
	
	
	 
	
	



    //END CRUD PRODUCT
}
