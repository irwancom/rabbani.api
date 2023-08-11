<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';


use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\Product\UseCases\ListProductDetail;
use Andri\Engine\Admin\Domain\Product\UseCases\StoreProductDetail;
use Andri\Engine\Admin\Domain\Product\UseCases\UpdateProductDetail;

class Product_detail extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Products');
        $this->presenter = new Presenter;
    }



    // GET
    // ===========================================================================

    public function index_get($id_product = null, $id = null) {
        if ($id_product && $id)
            return $this->_detail($id_product, $id);
        return $this->_index();
    }

    private function _index() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        
        $list = new ListProductDetail($this->Products);

        $options            = $_GET;
        $options['id_auth'] = $user->id_auth;

        $list->execute($options, $this->presenter);

        $data      = $this->presenter->data['data'];
        $totalItem = $this->presenter->data['totalItem'];
        $totalPage = $this->presenter->data['totalPage'];

        $this->response(success_format($data, '', $totalItem, $totalPage), 200);
    }

    private function _detail($id_product, $id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $product = $this->Products->detailByFields([
            'id_product' => $id_product,
            'id_auth' => $user->id_auth
        ]);
        
        if (!$product) {
            $error = failed_format(404, ['product' => 'error.product.global.not_found']);
            return $this->response($error, 404);
        }

        $result = $this->Products->detailItemByFields(['id_product_detail' => $id], true);
        if (!$result) {
            $error = failed_format(404, ['product_detail' => 'error.product_detail.global.not_found']);
            return $this->response($error, 404);
        }
        
        $this->response(success_format($result), 200);
    }


    // POST
    // ===========================================================================

    public function index_post($id_product, $id, $endpoints = null) {
        if ($endpoints == 'add_image') $this->add_image($id_product, $id);

        if ($id) return $this->_update($id_product, $id);
        $this->_store($id_product);
    }

    
    private function _store($id_product) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        
        $data = $this->input->post();

        $data['id_auth']    = $user->id_auth;
        $data['id_product'] = $id_product;

        $store = new StoreProductDetail($this->Products);
        $store->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data), 200);
    }

    /**
     * Update Product
     */
    private function _update($id_product, $id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $data = $this->input->post();
        
        $data['id_auth']    = $user->id_auth;
        $data['id_product'] = $id_product;

        $data['id_product_detail'] = $id;

        $update = new UpdateProductDetail($this->Products);
        $update->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            return $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.product.global.successfully_updated'));
    }


    public function add_image($id_product, $id_product_detail) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $condition = [
            'id_product_detail' => (int)$id_product_detail
        ];

        if (!$detail = $this->Products->detailItemByFields($condition))
            return $this->response(failed_format(404, ['product_detail' => 'error.product_detail.global.not_found']));

        if (isset($_FILES['image']['name']) && empty($_FILES['image']['name'])) {
            $error = failed_format(404,
                ['product' => 'error.product_detail.global.image_error']
            );
            return $this->response($error);
        }

        $image = upload_image('image');
        if ($image) {
            $data['id_auth'] = $user->id_auth;
            $data['id_product'] = $id_product;
            $data['id_product_detail'] = $id_product_detail;
            $data['image_path'] = $image['cdn_url'];
            $data['host_path'] = $image['cdn_url'];

            $update = new UpdateProductDetail($this->Products);
            $update->execute($data, $this->presenter);

            if ($this->presenter->hasError()) {
                $errors = $this->presenter->errors;
                return $this->response(failed_format(403, $errors));
            }

            $data = $this->presenter->data;
            return $this->response(success_format($data, 'success.product_detail.global.successfully_updated'));
            /* $data = [
                'id_product_detail' => (int)$id_product_detail,
                'image_path' => $image['full_path']
            ];
            if ($result = $this->Products->storeImage($data)) {
                $message = success_format(
                                [
                                    'success' => true,
                                    'id_product_image' => $result
                                ], 
                                'success.product_detail.global.image_successfully_uploaded'
                            );
                return $this->response($message);
            } */
        }

        $error = failed_format(402, 
                        ['product' => 'error.product_detail.global.failed_to_upload_image']
                );
        return $this->response($error);
    }


    // DELETE
    // ===========================================================================

    public function index_delete($id_product, $id, $endpoint = null) {

        switch($endpoint) {
        
            case 'remove_image':
                return $this->_remove_image($id_product, $id);

        }

        return $this->_delete_product_detail($id_product, $id);
    }

    private function _delete_product_detail($id_product, $id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $condition = [
            'id_product' => (int)$id_product,
            'id_product_detail' => (int)$id
        ];

        if (!$detail = $this->Products->detailItemByFields($condition)) {
            $error = failed_format(404, ['product_detail' => 'error.product_detail.global.not_found']);
            return $this->response($error, 404);
        }

        if ($result = $this->Products->deleteItem($condition)) {
            return $this->response(
                        success_format(
                            ['success' => true], 
                            'success.product_detail.global.successfully_deleted'
                        )
                    );
        }

        $error = failed_format(401, ['product_detail' => 'error.product_detail.global.failed_to_delete']);
        return $this->response($error, 401);
    }

    public function _remove_image($id_product, $id_product_detail) {

        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $condition = [
            'id_product_detail' => (int)$id_product_detail
        ];

        if (!$detail = $this->Products->detailItemByFields($condition))
            return $this->response(failed_format(404, ['product_detail' => 'error.product_detail.global.not_found']));

        $data['id_auth'] = $user->id_auth;
        $data['id_product'] = $id_product;
        $data['id_product_detail'] = $id_product_detail;
        $data['image_path'] = null;

        $update = new UpdateProductDetail($this->Products);
        $update->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            return $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        return $this->response(success_format($data, 'success.product_detail.global.successfully_updated'));
    }
 

}