
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\WishlistHandler;

class Wishlist extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new WishlistHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->getWishlists($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function wishlist_post ($idProduct) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = [
            'is_wishlist' => 1,
        ];
        $handler = new WishlistHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->createWishlist($idProduct, $payload);

        $this->response($result->format(), $result->getStatusCode());   
    }

    public function unwishlist_post ($idProduct = null) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        if(!$idProduct || empty($idProduct) || is_null($idProduct)){
            $this->delivery->addError(400, 'Product is required');
            $this->response($this->delivery->format());
        }

        $isPost = $this->input->post();
        $singleRemove = ($idProduct=='all' || $idProduct=='opsi') ? false : true;
        $multiRemove = (isset($isPost['product']) && $isPost['product'] && !empty($isPost['product']) && !is_null($isPost['product'])) ? $isPost['product'] : false;
        if($idProduct=='opsi' && !$multiRemove){
            $this->delivery->addError(400, 'Product opsi is required');
            $this->response($this->delivery->format());
        }

        //$payload = ['is_wishlist' => 0];
        $handler = new WishlistHandler($this->MainModel);
        $handler->setUser($auth->data);

        if($singleRemove){
            $result = $handler->createWishlist($idProduct, ['is_wishlist' => 0]);
        }else{
            $result = $handler->handleRemoveWishlist($idProduct, $multiRemove);
        }
        
        $this->response($result->format(), $result->getStatusCode());   
    }

}
