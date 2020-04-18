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
                $this->input->post('password')
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
                $this->input->post('data')
            );
//            $this->wa->SendWa('08986002287', 'haloooo 1');
            $data = $this->admin_model->transactionupdateData($data);
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
                $this->input->post('idstore'),
                $this->input->post('id_prov'),
                $this->input->post('id_city'),
                $this->input->post('id_dis'),
                $this->input->post('id_vill'),
                $this->input->post('namestore'),
                $this->input->post('addrstore'),
                $this->input->post('phonestore')
            );
            $data = $this->admin_model->StoreaddData($data);
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
                $this->input->post('phonestore')
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
                $this->input->post('idtransaction')
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

    //END CRUD PRODUCT
}
