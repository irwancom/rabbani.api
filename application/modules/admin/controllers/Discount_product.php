<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\Discount\UseCases\ListDiscountProduct;


class Discount_product extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Discounts');
        $this->load->model('Products');
        $this->presenter = new Presenter;
    }



    // GET
    // ===========================================================================

    public function index_get($id = null) {
        if ($id) return $this->_detail($id);
        $this->_index();
    }


    private function _index() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        
        $list = new ListDiscountProduct($this->Discounts);
        $options = $_GET;
        $options['id_auth'] = $user->id_auth;
        $list->execute($options, $this->presenter);

        $result = $this->presenter->data;
        
        $this->response(success_format($result), 200);
    }


    private function _detail($id) {
        
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $letter = $this->Discounts->detailByFields([
            'id_discount' => (int)$id
        ]);

        if (!$letter) {
            $errors = failed_format(404, ['discount' => 'error.discount.global.not_found']);
            return $this->response($errors, 404);
        }

        $this->response(success_format($letter), 200);
    }
    


    public function index_post() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $data = $this->input->post();

        if (!isset($data['id_product_detail'])) {
            return $this->response(failed_format(402, ['id_product_detail' => 'error.discount_product.id_product_detail.is_required']));
        }

        if (!isset($data['id_discount'])) {
            return $this->response(failed_format(402, ['id_product_detail' => 'error.discount_product.id_product_detail.is_required']));
        }


        $product = $this->Products->detailItemByFields([
            'id_product_detail' => $data['id_product_detail']
        ]);
        if (!$product) {
            return $this->response(failed_format(402, ['product' => 'error.product.global.not_found']));
        }



        $discount = $this->Discounts->detailByFields([
            'id_auth'       => $user->id_auth,
            'id_discount'   => $data['id_discount']
        ]);
        if (!$discount) {
            return $this->response(failed_format(402, ['discount' => 'error.discount.global.not_found']));
        }



        if ($this->Discounts->storeProduct($data)) {
            return $this->response(
                    success_format(
                        ['success' => true], 
                        'success.discount.global.successfully_store_product_to_discount'
                    )
                );    
        }
        $errors = failed_format(402, ['discount' => 'error.discount.global.failed_to_store']);
        return $this->response($errors, 402);
    }



    // DELETE
    // ===========================================================================

    public function index_delete($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        if ($this->Discounts->deleteProduct($id)) {
            return $this->response(
                    success_format(
                        ['success' => true], 
                        'success.discount.global.successfully_deleted_discount_product_item'
                    )
                );    
        }

        $errors = failed_format(402, ['discount' => 'error.discount.global.failed_to_delete']);
        return $this->response($errors, 402);
    }


}
