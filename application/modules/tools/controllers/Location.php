
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;

class Location extends REST_Controller {

    private $validator;
    private $delivery;
    private $pollHandler;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_provinsi_get () {

        $name = $this->input->get('name');
        $argsProvince = [];
        if (!empty($name)) {
            $argsProvince['name'] = [
                'condition' => 'like',
                'value' => $name
            ];
        }
        $findProvince = $this->MainModel->find('provinces', $argsProvince);
        $this->delivery->data = $findProvince;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function list_kabupaten_get () {

        $name = $this->input->get('name');
        $idProv = $this->input->get('id_prov');
        $argsKabupaten = [];
        if (!empty($name)) {
            $argsKabupaten['nama'] = [
                'condition' => 'like',
                'value' => $name
            ];
        }
        if (!empty($idProv)) {
            $argsKabupaten['id_prov'] = $idProv;
        }
        $locs = $this->MainModel->find('districts', $argsKabupaten);
        $this->delivery->data = $locs;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function list_kecamatan_get () {

        $name = $this->input->get('name');
        $idKab = $this->input->get('id_kab');
        $args = [];
        if (!empty($name)) {
            $args['nama'] = [
                'condition' => 'like',
                'value' => $name
            ];
        }
        if (!empty($idKab)) {
            $args['id_kab'] = $idKab;
        }
        $locs = $this->MainModel->find('sub_district', $args);
        $this->delivery->data = $locs;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function list_kelurahan_get () {

        $name = $this->input->get('name');
        $idKec = $this->input->get('id_kec');
        $args = [];
        if (!empty($name)) {
            $args['nama'] = [
                'condition' => 'like',
                'value' => $name
            ];
        }
        if (!empty($idKec)) {
            $args['id_kec'] = $idKec;
        }
        $locs = $this->MainModel->find('urban_village', $args);
        $this->delivery->data = $locs;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
