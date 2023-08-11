
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\CategoryHandler;

class Category extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->library('Wooh_support');
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function index_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');

        $filters = $this->input->get();
        $handler = new CategoryHandler($this->MainModel);
        $result = $handler->getCategories($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($categoryId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');

        if(!$categoryId || empty($categoryId) || is_null($categoryId)){
            $this->delivery->addError(400, 'Categori ID or slug is required'); $this->response($this->delivery->format());
        }
        $select = [
            'category.id_category',
            'category.id_parent',
            'category.category_name',
            'category.category_slug',
            'category.image_path',
            'category.status',
        ];

        $filters = $this->input->get();
        $detailFilter = ($this->wooh_support->isNomor($categoryId)) ? 'id_category' : 'category_slug';
        $filterStatus = (isset($filters['status']) && ($filters['status']=='1' || $filters['status']=='0')) ? intval($filters['status']) : 1;
        $args = [
            'category.'.$detailFilter => $categoryId,
            'category.status' => $filterStatus,
            'category.deleted_at' => NULL,
        ];
        $category = $this->db->select($select)->from('category')->where($args)->get()->row_array();
        if(!$category || is_null($category)){
            $this->delivery->addError(400, 'Categori not found'); $this->response($this->delivery->format());
        }

        $this->delivery->data = $category;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
