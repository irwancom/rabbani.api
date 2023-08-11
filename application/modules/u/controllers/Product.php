
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\ProductHandler;
use Service\CLM\Handler\ProductDetailHandler;
use Service\CLM\Handler\SearchKeywordHandler;
use Service\CLM\Handler\OrderHandler;

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
        $filters = $this->input->get();
        $handler = new ProductHandler($this->MainModel);
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        $userId = null;
        if (!empty($auth->data)) {
            $handler->setUser($auth->data);
            $userId = $auth->data['id'];
        }
        $filters['published'] =  (isset($filters['published']) && ($filters['published']==1 || $filters['published']==0)) ? $filters['published'] : 1;
        //$filters['published'] = 1;
        $result = $handler->getProducts($filters);

        if (isset($filters['q']) && !empty($filters['q'])) {
            $text = $filters['q'];
            $searchKeywordHandler = new SearchKeywordHandler($this->MainModel);
            $payload = [
                'text' => $text,
                'user_id' => $userId,
            ];
            $keywordResult = $searchKeywordHandler->createSearchKeyword($payload);
        }
        $this->response($result->format(), $result->getStatusCode());        
    }

    public function detail_get ($idProduct) {
        $handler = new ProductHandler($this->MainModel);
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if (!empty($auth->data)) {
            $handler->setUser($auth->data);
        }
        $result = $handler->getProduct(['q' => $idProduct]);
        if(isset($result->data) && $result->data && !empty($result->data) && !is_null($result->data)){
            $result->data->product_vouchers = $handler->handleListProductVoucher($result->data->id_product);
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function variant_get ($idProductDetail) {
        $handler = new ProductDetailHandler($this->MainModel);
        $handler->useCalculator();
        $result = $handler->getProductDetail(['id_product_detail' => $idProductDetail]);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function view_post ($idProduct) {
        $payload = [
            'id_product' => $idProduct,
            'user_id' => null
        ];

        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $payload['user_id'] = null;
        } else {
            $authData = $auth->data;
            $payload['user_id'] = $authData['id'];
        }
        

        $filters = $this->input->get();
        $handler = new ProductHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->viewProduct($payload['id_product'], $payload['user_id']);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function recommend_get () {
        $filters = $this->input->get();
        $handler = new ProductHandler($this->MainModel);
        $filters = [
            'order_key' => 'RAND()',
            'is_recommended' => 1,
            'data' => $this->input->get('data'),
            'published' => (isset($filters['published']) && ($filters['published']==1 || $filters['published']==0)) ? $filters['published'] : 1,
            'format' => (isset($filters['format'])) ? $filters['format'] : '',
            'location_id' => (isset($filters['location_id'])) ? $filters['location_id'] : '',
            'store_code' => (isset($filters['store_code'])) ? $filters['store_code'] : '',
            'stock' => (isset($filters['stock'])) ? $filters['stock'] : '',
            'stock_type' => (isset($filters['stock_type'])) ? $filters['stock_type'] : '',
        ];
        
        $handler->setShowProductImages(true);
        $result = $handler->getProducts($filters);
        $this->response($result->format(), $result->getStatusCode());  
    }

    public function review_v1_get ($q) {

        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);

        $filters = $this->input->get();
        $handler = new ProductHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getProductReview($q, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function review_get ($productId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        $handleOrder = new OrderHandler($this->MainModel);
        $detailBy = (is_numeric($productId)) ? 'id_product' : 'product_slug';
        $product = $this->db->select(['id_product','product_slug'])->from('product')->where([$detailBy=>$productId])->get()->row_array();
        if(!$product || is_null($product)){
            $this->delivery->addError(400, 'Product not found or not available'); $this->response($this->delivery->format());
        }
        $productId = $product['id_product'];

        $payload = $this->input->get();
        $sort = array('rate'=>'rate');
        $orderBy = 'created_at'; $orderVal = 'DESC';
        $select = [
            'product_rates.id',
            'product_rates.order_code',
            'product_rates.id_product',
            'product_rates.id_product_detail',
            'product_rates.message',
            'product_rates.rate',
            'users.id as user_id',
            'users.first_name',
            'users.picImage',
            'product_rates.created_at',
            'product_rates.updated_at',
        ];
        $filter = array('product_rates.id_product'=>$productId,'product_rates.deleted_at'=>NULL);

        $productDetailId = false;
        if(isset($payload['product_detail']) && !empty($payload['product_detail']) && !is_null($payload['product_detail'])){
            $productDetailId = $payload['product_detail'];
            $filter['product_rates.id_product_detail'] = $productDetailId;
        }
        if(isset($payload['rate']) && !empty($payload['rate']) && !is_null($payload['rate'])){
            $filter['product_rates.rate'] = $payload['rate'];
        }

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderVal;
        }

        $result = $this->db->select($select)->from('product_rates')->where($filter);
        $result = $result->join('orders', 'orders.order_code = product_rates.order_code','left')->join('users', 'users.id = orders.user_id','left');

        $countData = $result->count_all_results('', false);
        if($countData==0){
            $this->delivery->addError(400, 'Review not found or not yet available'); $this->response($this->delivery->format());
        }
        $result = $result->order_by($orderBy, $orderVal);

        $payload['sort_by'] = $orderBy;
        $payload['sort_value'] = $orderVal;
        $payload['limit'] = (isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data'])) ? $payload['data'] : '';
        $forPager = $this->wooh_support->pagerData($payload, $countData, [], ['sort'=>$orderBy]);
        $pagination = $forPager['data'];
        $result = $result->limit($pagination['limit'], $forPager['offset']);

        $resData = $result->get()->result();
        $argsMedia = array('id_product'=>$productId,'deleted_at'=>NULL);
        foreach($resData as $result){
            $argsMedia['order_code'] = $result->order_code;
            if($productDetailId){
                $argsMedia['id_product_detail'] = $productDetailId;
            }
            $result->media = $this->db->select('image_url')->from('product_rate_images')->where($argsMedia)->get()->result_array();

            $resultOrderDetail = null;
            $existOrder = $handleOrder->getOrder(['order_code' => $result->order_code]);
            if($existOrder && $existOrder->data && !empty($existOrder->data) && !is_null($existOrder->data)){
                $orderDetails = $existOrder->data->order_details;
                if($orderDetails && !empty($orderDetails) && !is_null($orderDetails)){
                    if($result->id_product_detail && !empty($result->id_product_detail) && !is_null($result->id_product_detail)){
                        $keyDetail = array_search($result->id_product_detail, array_column($orderDetails, 'id_product_detail'));
                        if($keyDetail!==FALSE){
                            $isOrderDetail = $orderDetails[$keyDetail];
                            $resultOrderDetail = $isOrderDetail->product_detail_variable;
                        }
                    }
                }
            }
            $result->product_detail_variable = $resultOrderDetail;
        }

        $reviews = array('result'=>$resData);
        foreach($pagination as $k_pg=>$pg){ $reviews[$k_pg] = $pg; }

        $this->delivery->data = $reviews;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function review_summary_get ($productId = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);

        if(!$productId || empty($productId) || is_null($productId)){
            $this->delivery->addError(400, 'Product ID is required'); $this->response($this->delivery->format());
        }

        $payload = $this->input->get();
        $productDetailId = false;
        if(isset($payload['product_detail']) && !empty($payload['product_detail']) && !is_null($payload['product_detail'])){
            $productDetailId = $payload['product_detail'];
        }

        $listRate = array();
        $slcData = array('product_rates.order_code','product_rates.id_product');
        $filterRate = array('product_rates.id_product'=>$productId,'product_rates.deleted_at'=>NULL);
        $joinImage = 'product_rate_images.id_product = product_rates.id_product and product_rate_images.order_code = product_rates.order_code';
        if($productDetailId){
            $slcData[] = 'product_rates.id_product_detail';
            $filterRate['product_rates.id_product_detail'] = $productDetailId;
            $joinImage = $joinImage.' and product_rate_images.id_product_detail = product_rates.id_product_detail';
        }
        
        for($rt=1;$rt<=5;$rt++){
            $filterThisRate = $filterRate;
            $filterThisRate['product_rates.rate'] = $rt;
            $resultCount = $this->db->select($slcData)->from('product_rates')->where($filterThisRate);
            $countData = $resultCount->count_all_results('', false);

            $listRate[$rt] = array();
            $listRate[$rt]['label'] = $rt.' Bintang';
            $listRate[$rt]['count'] = $countData;

            $cekMedia = $resultCount->join('product_rate_images', $joinImage)->group_by('product_rates.order_code');
            $listRate[$rt]['image'] = $cekMedia->count_all_results();
        }
        
        $cekMedia = $this->db->select($slcData)->from('product_rates')->where($filterRate)->join('product_rate_images', $joinImage);
        $cekMedia = $cekMedia->group_by('product_rates.order_code')->count_all_results();
        $listRate['image'] = array('label'=>'Dengan Media','count'=>$cekMedia);

        $this->delivery->data = $listRate;
        $this->response($this->delivery->format(), $this->delivery->getStatusCode());
    }

    public function review_reply_get ($rateId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);

        $filters = $this->input->get();
        $handler = new ProductHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getProductReviewReply($rateId, $filters);

        $this->response($result->format(), $result->getStatusCode());
    }

}
