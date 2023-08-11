
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\FlashsaleHandler;
use Library\DigitalOceanService;

class Flashsale extends REST_Controller {

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
        $handler = new FlashsaleHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getFlashsales($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function index_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        if(isset($payload['category']) && $payload['category'] && !empty($payload['category']) && !is_null($payload['category'])){
            $category = $this->db->from('flashsale_categories')->where(['fscat_id'=>$payload['category'],'fscat_status <'=>2])->get()->row_array();
            if(!$category || is_null($category)){
                return $this->wooh_support->resData('not_found', $this->wooh_support->notifNotFoundData('flashsale category'));die;
            }
            $payload['flashsale_category'] = $payload['category'];
            unset($payload['category']);
        }
        $handler = new FlashsaleHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->createFlashsale($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new FlashsaleHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteFlashsale($id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function category_get($forDetail = NULL) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        if($forDetail && !empty($forDetail) && !is_null($forDetail)){
            $forCekDetail = ($this->wooh_support->isNomor($forDetail)) ? 'id' : 'slug';
            $category = $this->db->from('flashsale_categories')->where(['fscat_'.$forCekDetail=>$forDetail,'fscat_status <'=>2])->get()->row_array();
            if(!$category || is_null($category)){
                return $this->wooh_support->resData('not_found', $this->wooh_support->notifNotFoundData('flashsale category'));die;
            }
            return $this->wooh_support->resData('success', 'Flashsale category found', $category); die;
        }
        $getData = $this->input->get();
        $conditionData = array('fscat_status <'=>2);
        if(isset($getData['status']) && strlen($getData['status'])>0 && ($getData['status']==1 || $getData['status']==0)){
            $conditionData['fscat_status'] = $getData['status'];
        }
        $thisData = $this->db->from('flashsale_categories')->where($conditionData);

        $forSearch = array('id','title','slug','created','updated');
        foreach($forSearch as $k_src=>$search){
            if(isset($getData[$search]) && $getData[$search] && !empty($getData[$search]) && strlen($getData[$search])>0){
                $thisData = $thisData->like(['fscat_'.$search=>$getData[$search]]);
            }
        }

        $countData = $thisData->count_all_results('', false);
        if($countData==0){
            return $this->wooh_support->resData('empty', $this->wooh_support->notifNoData('flashsale category')); die;
        }

        $forPager = $this->wooh_support->pagerData($getData, $countData, $forSearch, ['sort'=>'created']);
        $thisData = $thisData->order_by('fscat_'.$forPager['sort_by'], $forPager['sort_value']);
        $categories = $thisData->limit($forPager['data']['limit'], $forPager['offset'])->get()->result_array();
        return $this->wooh_support->resData('success', 'Flashsale category found', $categories, $forPager['data'], $getData, true); die;
    }

    public function category_post() {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $postData = $this->input->post();
        if(!$postData || is_null($postData)){
            return $this->wooh_support->resData('failed', $this->wooh_support->notifMsg('no-data'));die;
        }
        $isNew = (isset($postData['id']) && $postData['id'] && !empty($postData['id']) && !is_null($postData['id'])) ? false : true;
        $action = ($isNew) ? 'add' : 'edit';

        $categoryId = false;
        if(!$isNew){
            $category = $this->db->select('fscat_id')->from('flashsale_categories')->where(['fscat_id'=>$postData['id'],'fscat_status <'=>2])->get()->row_array();
            if(!$category || is_null($category)){
                return $this->wooh_support->resData('not_found', $this->wooh_support->notifNotFoundData('flashsale category'));die;
            }
            $categoryId = $category['fscat_id'];
        }
        $validationData = $this->wooh_support->validationNeedData($postData, ['title']);
        if(!$validationData['success']){
            return $this->wooh_support->resData('bad', $validationData['msg']);die;
        }

        $forcekTitle = array('fscat_id !='=>$categoryId,'fscat_title'=>strtoupper($postData['title']),'fscat_status <'=>2);
        $cekTitle = $this->db->select('fscat_title')->from('flashsale_categories')->where($forcekTitle)->get()->row_array();
        if($cekTitle && !is_null($cekTitle)){
            return $this->wooh_support->resData('bad', $this->wooh_support->notifUseData('title'));die;
        }

        $updatePic = false;
        if(isset($_FILES['image']) && $this->wooh_support->readyFileUpload($_FILES['image'])){
            $validationUpload = $this->wooh_support->validationUpload('image', $_FILES['image'], ['image']);
            if(!$validationUpload['success']){
                return $this->wooh_support->resData('not_support', $validationUpload['msg']); die;
            }
            $uploadPic = new DigitalOceanService();
            $updatePic = $uploadPic->upload($postData, 'image');
            if(!$updatePic || !is_array($updatePic) || !isset($updatePic['cdn_url'])){
                return $this->wooh_support->resData('not_allowed', 'Failed upload image'); die;
            }
        }

        $sendData = array();
        $sendData['fscat_title'] = strtoupper($postData['title']);
        $sendData['fscat_slug'] = $this->wooh_support->stringToSlug($postData['title']);
        $sendData['fscat_status'] = (isset($postData['status']) && $postData['status']==1) ? 1 : 0;
        $sendData['fscat_updated'] = date('Y-m-d H:i:s');
        $sendData['updated_at'] = date('Y-m-d H:i:s');
        if($updatePic && !empty($updatePic) && !is_null($updatePic)) $sendData['fscat_pic'] = json_encode($updatePic, true);
        if($isNew){
            $sendData['fscat_created'] = date('Y-m-d H:i:s');
            $sendData['created_at'] = date('Y-m-d H:i:s');
            $upData = $this->db->insert('flashsale_categories', $sendData);
        }else{
            $upData = $this->db->set($sendData)->where(['fscat_id'=>$categoryId])->update('flashsale_categories');
        }
        if(!$upData){
            return $this->wooh_support->resData('failed', $this->wooh_support->notifMsg('error-'.$action));die;
        }
        $category = $this->db->from('flashsale_categories')->where($sendData)->get()->row_array();
        return $this->wooh_support->resData('success', $this->wooh_support->notifMsg('success-'.$action), $category); die;
    }

    public function category_delete($categoryId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        if(!$categoryId || empty($categoryId) || is_null($categoryId)){
            return $this->wooh_support->resData('failed', $this->wooh_support->notifMsg('no-data'));die;
        }

        $category = $this->db->from('flashsale_categories')->where(['fscat_id'=>$categoryId])->get()->row_array();
        if(!$category || is_null($category)){
            return $this->wooh_support->resData('not_found', $this->wooh_support->notifNotFoundData('flashsale category'));die;
        }

        $delData = $this->db->set(['fscat_status'=>2,'deleted_at'=>date('Y-m-d H:i:s')])->where(['fscat_id'=>$category['fscat_id']])->update('flashsale_categories');
        return $this->wooh_support->resData('success', $this->wooh_support->notifMsg('success-remove')); die;
    }

//============================================== END LINE ============================================================//
}
