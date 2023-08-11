
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\ProductHandler;
use Library\DigitalOceanService;

class Product extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new ProductHandler($this->MainModel);
        $result = $handler->getProducts($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new ProductHandler($this->MainModel);
        $filters = [
            'id_product' => $id
        ];
        $result = $handler->updateProduct($payload, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function review_reply_post ($rateId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        if(!$rateId || empty($rateId) || is_null($rateId)){
            $this->delivery->addError(400, 'Review ID is required'); $this->response($this->delivery->format());
        }

        $payload = $this->input->post();
        $cekRate = $this->db->from('product_rates')->where(['id'=>$rateId,'deleted_at'=>NULL])->get()->row_array();
        if(!$cekRate || is_null($cekRate)){
            $this->delivery->addError(400, 'Review not found'); $this->response($this->delivery->format());
        }

        $sendData = array();
        $sendData['rate_reply_rates'] = $rateId;
        $sendData['rate_reply_by'] = 'admin';
        $sendData['rate_reply_auth'] = $auth->data['id'];
        $sendData['rate_reply_message'] = $this->input->post('message');

        $postMedia = array();
        if(isset($_FILES['media']) && $this->wooh_support->readyFileUpload($_FILES['media'])){
            $forMedia = $_FILES['media'];
            $isTmp = $_FILES['media']['tmp_name'];
            if(!is_array($isTmp)){
                $forMedia = array();
                $forMedia['name'][0] =  $_FILES['media']['name'];
                $forMedia['type'][0] =  $_FILES['media']['type'];
                $forMedia['tmp_name'][0] =  $_FILES['media']['tmp_name'];
                $forMedia['error'][0] =  isset($_FILES['media']['error']) ? $_FILES['media']['error'] : 0;
                $forMedia['size'][0] =  isset($_FILES['media']['size']) ? $_FILES['media']['size'] : 0;
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
                $validationUpload = $this->wooh_support->validationUpload('media', $isFile, ['image']);
                if(!$validationUpload['success']){
                    $this->delivery->addError(400, $validationUpload['msg'].' (File '.$noMedia.': '.$forMedia['name'][$k_media].')'); $this->response($this->delivery->format());
                }
                $_FILES['media_'.$k_media] = $isFile;
                $uploadMedia = new DigitalOceanService();
                $resultUploadMedia = $uploadMedia->upload($_FILES['media_'.$k_media], 'media_'.$k_media);
                if($resultUploadMedia && is_array($resultUploadMedia) && isset($resultUploadMedia['cdn_url'])){
                    $resultUploadMedia['file_type'] = $validationUpload['detail']['type'];
                    $postMedia[$noUpload] = $resultUploadMedia;
                    $noUpload = $noUpload+1;
                }
            }
        }
        if($postMedia && !empty($postMedia) && !is_null($postMedia)){
            $sendData['rate_reply_file'] = json_encode($postMedia, true);
        }
        $sendData['created_at'] = date('Y-m-d H:i:s');
        $sendData['updated_at'] = date('Y-m-d H:i:s');
        $upData = $this->db->insert('product_rate_reply', $sendData);

        $select =[
            'product_rate_reply.rate_reply_id as reply_id',
            'product_rate_reply.rate_reply_by as reply_by',
            'product_rate_reply.rate_reply_auth as reply_auth',
            'product_rate_reply.rate_reply_message as reply_message',
            'admins.first_name as reply_name',
            'admins.username as reply_pic',
            'product_rate_reply.created_at as reply_created',
            'product_rate_reply.rate_reply_file as reply_media',
            'admins.last_name as admin_last_name',
        ];

        $reply = $this->db->select($select)->from('product_rate_reply')->where($sendData)->join('admins', 'admins.id = product_rate_reply.rate_reply_auth', 'left')->get()->row_array();
        $reply['reply_name'] = $reply['reply_name'].' '.$reply['admin_last_name'];
        $reply['reply_pic'] = null;
        $returnMedia = null;
        if($postMedia && !empty($postMedia) && !is_null($postMedia)){
            $returnMedia = array();
            foreach($postMedia as $k_med=>$isMedia){
                $returnMedia[$k_med]['type'] = $isMedia['file_type'];
                $returnMedia[$k_med]['path'] = $isMedia['cdn_url'];
            }
        }
        $reply['reply_media'] = $returnMedia;
        unset($reply['admin_last_name']);
        $this->delivery->data = $reply;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function review_reply_delete ($replyId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        if(!$replyId || empty($replyId) || is_null($replyId)){
            $this->delivery->addError(400, 'Reply ID is required'); $this->response($this->delivery->format());
        }

        $cekReply = $this->db->from('product_rate_reply')->where(['rate_reply_id'=>$replyId,'deleted_at'=>NULL])->get()->row_array();
        if(!$cekReply || is_null($cekReply)){
            $this->delivery->addError(400, 'Reply not found'); $this->response($this->delivery->format());
        }
        $delData = $this->db->set(['deleted_at'=>date('Y-m-d H:i:s')])->where(['rate_reply_id'=>$replyId])->update('product_rate_reply');
        return $this->wooh_support->resData('success', $this->wooh_support->notifMsg('success-remove')); die;
    }

}
