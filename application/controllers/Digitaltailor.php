<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Digitaltailor extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('digitaltailor_model');
        //$this->load->library('wa');
        $this->load->library('sms');

        $this->load->helper(array('form', 'url'));
    }

    function index_post() {
        
    }

    public function tailor_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->digitaltailor_model->addtailor($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

     function viewtailor_get() {
        $data = $this->digitaltailor_model->viewtailor();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function usertailor_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('data')
            );
            $data = $this->digitaltailor_model->adduser($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('data')
            );
            $data = $this->digitaltailor_model->updateuser($data);
        } elseif ($pg == 'disable') {
            $data = array(
                $this->input->post('iduser')
            );
            $data = $this->digitaltailor_model->disableuser($data);
        } elseif ($pg == 'ditails') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('iduser')
            );
            $data = $this->digitaltailor_model->ditailsuser($data);
        } else {
            $data = array(
                $this->input->post('page')
            );

            $data = $this->digitaltailor_model->datauser($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function UserUpdatePhoto_post() {
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
            //$this->upload->initialize($config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                ($data);
                $data = $this->digitaltailor_model->UserUpdatePhoto($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('keyCode'),
                   
                    
                    
                    $data,
                    $config['upload_path']
                );
                $data = $this->digitaltailor_model->UserUpdatePhoto($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function useraddress_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('keyCode'),
                $this->input->post('data')
                    //$this->input->post('idstore')
            );
            $data = $this->digitaltailor_model->useraddress($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

     public function logintailor_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->digitaltailor_model->logintailor($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    public function forgettailor_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('hp'),
                    //$this->input->post('idstore')
            );
            $data = $this->digitaltailor_model->forgettailor($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

    function order_post($pg = '') {
        if ($pg == 'add') {
            $data = array(
                $this->input->post('keycode'),
                $this->input->post('data')
            );
            $data = $this->digitaltailor_model->addorder($data);
        } elseif ($pg == 'update') {
            $data = array(
                $this->input->post('data')
            );
            $data = $this->digitaltailor_model->updateuser($data);
        } elseif ($pg == 'disable') {
            $data = array(
                $this->input->post('iduser')
            );
            $data = $this->digitaltailor_model->disableuser($data);
        } elseif ($pg == 'ditails') {
            $data = array(
                $this->input->post('idtransaction')
            );
            $data = $this->digitaltailor_model->ditailorders($data);
        } else {
            $data = array(
                $this->input->post('page')
            );

            $data = $this->digitaltailor_model->dataorder($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

     function product_post($pg = '') {
        if ($pg == 'del') {
            $data = array(
                $this->input->post('idproduct')
            );
            $data = $this->digitaltailor_model->productDeleteData($data);
        } elseif ($pg == 'cat') {
            $data = array(
                $this->input->post('cat')
            );
            $data = $this->digitaltailor_model->productcategory($data);
        } elseif ($pg == 'disable') {
            $data = array(
                $this->input->post('idproduct')
            );
            $data = $this->digitaltailor_model->productDisableData($data);
        } elseif ($pg == 'ditails') {
            $data = array(
                $this->input->post('idproduct')
            );
            $data = $this->digitaltailor_model->ditailproduct($data);
        } else {
            $data = array(
               $this->input->post('keyCodeStaff'),
               $this->input->post('secret'),
            );

            $data = $this->digitaltailor_model->dataproduct($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }
    

    public function productAddData_post() {
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
            //$this->upload->initialize($config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->digitaltailor_model->productAddData($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('data'),
                    
                    $data,
                    $config['upload_path']
                );
                $data = $this->digitaltailor_model->productAddData($data);
            }
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

     public function productditailsadd_post() {
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
            //$this->upload->initialize($config);
            if (!$this->upload->do_upload('filePic')) {
                $error = array('error' => $this->upload->display_errors());

                $data = array(
                    $error
                );
                // dd($data);
                $data = $this->digitaltailor_model->productditailsadd($data);
            } else {
                $data = array('upload_data' => $this->upload->data());

                $data = array(
                    $this->input->post('data'),
                    
                    $data,
                    $config['upload_path']
                );
                $data = $this->digitaltailor_model->productditailsadd($data);
            }
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

      function viewbalapsarung_get() {
        $data = $this->digitaltailor_model->viewbalapsarung();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }


 public function addbalapsarung_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->digitaltailor_model->addbalapsarung($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }

     function viewsedekah_get() {
        $data = $this->digitaltailor_model->viewsedekah();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

     public function addsedekah_post() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = array(
                $this->input->post('data'),
                    //$this->input->post('idstore')
            );
            $data = $this->digitaltailor_model->addsedekah($data);
            if ($data) {
                $this->response($data, 200);
            } else {
                $this->response(array('status' => 'fail', 502));
            }
        }
    }
    
	



    //END CRUD 
}
