<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';


use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\Order\UseCases\UpdateOrderDetail;

class Order_detail extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Orders');
        $this->presenter = new Presenter;
    }



    // GET
    // ===========================================================================

    public function index_get($order_code, $id) {
        return $this->_detail($order_code, $id);
    }

    private function _detail($order_code, $id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $condition = [
            'order_code' => $order_code,
            'id_auth' => $user->id_auth
        ];
        
        if (!$order = $this->Orders->detailByFields($condition)) {
            $error = failed_format(404, ['order' => 'error.order.global.not_found']);
            return $this->response($error, 404);
        }

        if (!$detail = $this->Orders->detailItemByFields(['id_order_detail' => $id], true)) {
            $error = failed_format(404, ['order_detail' => 'error.order_detail.global.not_found']);
            return $this->response($error, 404);
        }

        $detail = (array)$detail;
        $detail['order'] = $order;
        
        $this->response(success_format($detail), 200);
    }


    // POST
    // ===========================================================================

    public function index_post($id_product, $id) {
        if ($id) return $this->_update($id_product, $id);
        $this->_store($id_product);
    }

    
    // private function _store($id_product) {
    //     if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        
    //     $data = $this->input->post();

    //     $data['id_auth']    = $user->id_auth;
    //     $data['id_product'] = $id_product;

    //     $store = new StoreProductDetail($this->Products);
    //     $store->execute($data, $this->presenter);

    //     if ($this->presenter->hasError()) {
    //         $errors = $this->presenter->errors;
    //         $this->response(failed_format(403, $errors));
    //     }

    //     $data = $this->presenter->data;
    //     $this->response(success_format($data), 200);
    // }

    /**
     * Update Order
     */
    private function _update($order_code, $id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $data = $this->input->post();
        
        $data['id_auth']    = $user->id_auth;
        $data['order_code'] = $order_code;

        $data['id_order_detail'] = $id;

        $update = new UpdateOrderDetail($this->Orders);
        $update->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $detail = (array)$data['detail'];
        $detail['order'] = $data['order'];
        $this->response(success_format($detail, 'success.order.global.successfully_updated'));
    }


    // DELETE
    // ===========================================================================

    public function index_delete($id_product, $id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
       
        if (!$detail = $this->Orders->detailItemByFields(['id_order_detail' => $id], true)) {
            $error = failed_format(404, ['order_detail' => 'error.order_detail.global.not_found']);
            return $this->response($error, 404);
        }

        
        if ($result = $this->Orders->deleteDetail($id, $user->id_auth)) {
            $message = 'success.order_detail.global.successfuly_delete_item';
            return $this->response(success_format(['success' => true], $message), 200);
        }

        $error = failed_format(
                    402, 
                    ['order_detail', 'error.order_detail.global.failed_to_delete_order_detail']
                );
        return $this->response($error, 402);
    }

}