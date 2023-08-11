<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Product extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Products');
    }

    public function index_get($id = null) {
        if(!$user = validate_token_user()) return $this->response(failed_format(403), 403);

        $product = $this->Products->detailByFields([
            'id_product' => $id,
            'id_auth' => $user->id_auth
        ], true);
        
        if (!$product) {
            $error = failed_format(404, ['product' => 'error.product.global.not_found']);
            return $this->response($error, 404);
        }

        $this->response(success_format($product), 200);
    }

}
