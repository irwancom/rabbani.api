<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Entity;
use Library\DigitalOceanService;
use DigitalOcean\SpacesConnect;
use GuzzleHttp\Client;

class Drive extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function connectedCDN ($option = []) {
        $config = $this->wooh_support->driveConfig();
        $connect = new \SpacesConnect($config['key'], $config['secret'], $config['space'], $config['region']);
        return $connect;
    }

    public function buildFileCDN ($file = []) {
        $config = $this->wooh_support->driveConfig();
        $result = [
            'original_name' => $file['orig_name'],
            'file_name' => $file['file_name'],
            'file_ext' => $file['file_ext'],
            'cdn_url' => sprintf('%s/%s', $config['link'], $file['file_name']),
        ];
        return $result;
    }

    public function index_post () {
        $this->delivery->addError(400, 'Fitur not ready'); $this->response($this->delivery->format());die;
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $headers = $this->input->request_headers();
        $connect = $this->connectedCDN();

        $payload = $this->input->post();
        $folder = (isset($payload['folder']) && $payload['folder'] && !empty($payload['folder']) && !is_null($payload['folder'])) ? $payload['folder'] : '';

        $postMedia = array();
        if(isset($_FILES['file']) && $this->wooh_support->readyFileUpload($_FILES['file'])){
            $forMedia = $_FILES['file'];
            $isTmp = $_FILES['file']['tmp_name'];
            if(!is_array($isTmp)){
                $forMedia = array();
                $forMedia['name'][0] =  $_FILES['file']['name'];
                $forMedia['type'][0] =  $_FILES['file']['type'];
                $forMedia['tmp_name'][0] =  $_FILES['file']['tmp_name'];
                $forMedia['error'][0] =  isset($_FILES['file']['error']) ? $_FILES['file']['error'] : 0;
                $forMedia['size'][0] =  isset($_FILES['file']['size']) ? $_FILES['file']['size'] : 0;
            }
            $noUpload = 0;
            foreach($forMedia['tmp_name'] as $k_media=>$media){
                $noMedia = $k_media+1;
                $isFile = [
                    'name' => $forMedia['name'][$k_media],
                    'type' => $forMedia['type'][$k_media],
                    'tmp_name' => $forMedia['tmp_name'][$k_media],
                    'size' => $forMedia['size'][$k_media],
                ];
                $validationUpload = $this->wooh_support->validationUpload('media', $isFile, ['image','video','document']);
                if(!$validationUpload['success']){
                    $this->delivery->addError(400, $validationUpload['msg'].' (File '.$noMedia.': '.$forMedia['name'][$k_media].')'); $this->response($this->delivery->format());
                }

                $namePath = $this->wooh_support->randomString(5);
                $_FILES['file_'.$k_media.'_'.$namePath] = $isFile;
                $getPath = $this->wooh_support->uploadConfig($folder, 'file_'.$k_media.'_'.$namePath);
                if($getPath){
                    //$upload = $connect->UploadFile($getPath['full_path'], "public", $getPath['file_name'], mime_content_type($getPath['full_path']));
                    $upload = false;
                    if($upload){
                        $postMedia[$noUpload] = $this->buildFileCDN($getPath);
                        $noUpload = $noUpload+1;
                        unlink($getPath['full_path']);
                    }
                }
            }
        }

        $this->delivery->data = array('result'=>$postMedia);
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
