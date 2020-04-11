<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Sensus extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('sensus_model');
        $this->load->library('wa');

        $this->load->helper(array('form', 'url'));
    }

    function index_post() {
        
    }

    function prov_get() {
        $data = $this->sensus_model->get_prov();

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function city_get($id = '') {
        $data = $this->sensus_model->get_city($id);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function districts_get($id = '') {
        $data = $this->sensus_model->get_districts($id);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function village_get($id = '') {
        $data = $this->sensus_model->get_village($id);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function people_post() {
        $data = array(
            'id_prov' => $this->input->post('id_prov'),
            'id_city' => $this->input->post('id_city'),
            'id_dis' => $this->input->post('id_dis'),
            'id_vill' => $this->input->post('id_vill'),
            'address' => $this->input->post('address'),
            'rt' => $this->input->post('rt'),
            'rw' => $this->input->post('rw'),
            'name' => $this->input->post('name'),
            'phone' => $this->input->post('phone'),
            'email' => $this->input->post('email'),
            'costumer' => $this->input->post('costumer'),
            'question1' => $this->input->post('question1'),
            'question2' => $this->input->post('question2'),
            'question3' => $this->input->post('question3')
        );
        $data = $this->sensus_model->addPeople($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function searchVillage_post() {
        $data = array(
            'nameVill' => $this->input->post('key')
        );
        $data = $this->sensus_model->searchVillage($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function searchDistricts_post() {
        $data = array(
            'nameDis' => $this->input->post('key')
        );
        $data = $this->sensus_model->searchDistricts($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function searchCity_post() {
        $data = array(
            'nameCity' => $this->input->post('key')
        );
        $data = $this->sensus_model->searchCity($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function katalog_post($pg = '') {
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
                $this->input->post('username'),
                $this->input->post('hp'),
            );

            $data = $this->sensus_model->catalog($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function dataStaffStore_get() {
        $data = $this->sensus_model->dataStaffStore();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function reseller_post($pg = '') {
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
                $this->input->post('data')
            );

            $data = $this->sensus_model->reseller($data);
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

}
