
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\SearchKeywordHandler;
use Service\CLM\Handler\ProductHandler;

class Search_keyword extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_popular_get () {
        /* $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        } */

        $filters['flag'] = 1;
        $filters['live'] = true;
        $filters['order_key'] = 'search_keywords.publish_until, search_keywords.count';
        $filters['order_value'] = 'DESC';
        $filters['data'] = $this->input->get('data');
        $handler = new SearchKeywordHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getSearchKeywords($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function similir_search_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);

        $payload = $this->input->get();
        $search = (isset($payload['q']) && $payload['q'] && !empty($payload['q']) && !is_null($payload['q'])) ? $payload['q'] : '';

        $history = null;
        if(isset($auth->data) && $auth->data && !empty($auth->data)){
            $userId = $auth->data['id'];
            $history = $this->db->from('search_keyword_histories')->where(['deleted_at'=>NULL,'user_id'=>$userId]);
            if($search && !empty($search)){
                $history = $history->like(['text'=>$search]);
            }
            $limitHistory = 5;
            if(isset($payload['data_history']) && $payload['data_history'] && !empty($payload['data_history']) && !is_null($payload['data_history']) && is_numeric($payload['data_history'])){
                $limitData = intval($payload['data_history']);
                $limitHistory = ($limitData <= 0) ? $limitHistory : $limitData;
            }
            $history = $history->order_by('created_at', 'DESC')->limit($limitHistory)->get()->result_array();
        }

        $popular = $this->db->from('search_keywords')->where(['deleted_at'=>NULL]);
        if($search && !empty($search)){
            $popular = $popular->like(['text'=>$search]);
        }
        $limitPopular = 5;
        if(isset($payload['data_popular']) && $payload['data_popular'] && !empty($payload['data_popular']) && !is_null($payload['data_popular']) && is_numeric($payload['data_popular'])){
            $limitData = intval($payload['data_popular']);
            $limitPopular = ($limitData <= 0) ? $limitPopular : $limitData;
        }
        $popular = $popular->order_by('count', 'DESC')->limit($limitPopular)->get()->result_array();

        $handler = new ProductHandler($this->MainModel);
        $filters = [
            'q' => $search,
            'order_key' => 'RAND()',
            'is_recommended' => 1,
            'data' => $this->input->get('data_recomend'),
            'published' => 1,
        ];
        
        $handler->setShowProductImages(true);
        $recomend = $handler->getProducts($filters);
        $recomend = $recomend->data['result'];
        
        $result = array('history'=>$history, 'popular'=>$popular, 'recomendation'=>$recomend);
        $this->delivery->data = $result;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

}
