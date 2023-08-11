<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';


use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\Product\UseCases\ListProduct;
use Andri\Engine\Admin\Domain\Product\UseCases\StoreProduct;
use Andri\Engine\Admin\Domain\Product\UseCases\UpdateProduct;
use Service\CLM\Handler\ProductHandler;
use Service\Delivery;
use Service\Validator;

class Product extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Products');
        $this->load->model('Categorys');
        $this->load->model('MainModel');
        $this->presenter = new Presenter;
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }



    // GET
    // ===========================================================================

    public function index_get($id = null) {
        if ($id) return $this->_detail($id);
        $this->_index();
    }


    private function _index() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $list = new ListProduct($this->Products);
        $options = $_GET;
        $options['id_auth'] = $user->id_auth;
        $list->execute($options, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $result = $this->presenter->data;
        $data = $result['data'];
        $totalItem = $result['totalItem'];
        $totalPage = $result['totalPage'];
        
        $this->response(success_format($data, '', $totalItem, $totalPage), 200);
    }


    private function _detail($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $result = $this->Products->detailByFields(['id_product' => $id], true);
        if (!$result) {
            $error = failed_format(404, ['product' => 'error.product.global.not_found']);
            return $this->response($error, 404);
        }
        
        $this->response(success_format($result), 200);
    }


    // POST
    // ===========================================================================

    public function index_post($id = null, $endpoints = null) {
        if ($endpoints == 'add_image') $this->add_image_product($id);
        if ($endpoints == 'map') $this->_map($id);
        if ($id) return $this->_update($id);
        $this->_store();
    }

    private function _store() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;

        $store = new StoreProduct($this->Products, $this->Categorys);
        $store->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data), 200);
    }


    private function _update($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $data = $this->input->post();

        $data['id_product'] = $id;
        $data['id_auth']   = $user->id_auth;

        $update = new UpdateProduct($this->Products, $this->Categorys);
        $update->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.product.global.successfully_updated'));
    }

    private function add_image_product ($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $condition = [
            'id_product' => (int)$id
        ];

        if (!$result = $this->Products->detailByFields($condition))
            return $this->response(failed_format(404, ['product' => 'error.product.global.not_found']));

        if (isset($_FILES['image']['name']) && empty($_FILES['image']['name'])) {
            $error = failed_format(404,
                ['product' => 'error.product.global.image_error']
            );
            return $this->response($error);
        }

        $image = upload_image('image');
        if ($image) {
            $data = [
                'id_product' => (int)$id,
                'image_path' => $image['cdn_url'],
                'host_path' => $image['cdn_url'],
                'urlimg' => $image['cdn_url']
            ];
            if ($result = $this->Products->storeImage($data)) {
                $message = success_format(
                                [
                                    'success' => true,
                                    'id_product_image' => $result
                                ], 
                                'success.product.global.image_successfully_uploaded'
                            );
                return $this->response($message);
            }
        }

        $error = failed_format(402, 
                        ['product' => 'error.product.global.failed_to_upload_image']
                );
        return $this->response($error);
    }

    private function _map($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $filters = $this->input->get();
        $handler = new ProductHandler($this->MainModel);
        $result = $handler->map($id);

        $this->response($result->format(), $result->getStatusCode());
    }


    // DELETE
    // ===========================================================================

    public function index_delete($id, $endpoint = null) {

        switch($endpoint) {
        
            case 'remove_image':
                return $this->_remove_image_product($id);

        }

        return $this->_delete_product($id);
    }

    public function _delete_product($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $product = $this->Products->detailByFields([
            'id_auth' => $user->id_auth,
            'id_product' => $id
        ]);

        if (!$product) {
            $error = failed_format(404, ['product' => 'error.product.global.not_found']);
            return $this->response($error, 404);
        }

        $data['id_product'] = $id;
        $data['deleted_at'] = date('Y-m-d h:i:s');
        
        $result = $this->Products->update($product, $data);
        if ($result) {
            return $this->response(
                        success_format(
                            ['success' => true], 
                            'success.product.global.successfully_deleted'
                        )
                    );
        }

        $error = failed_format(401, ['product' => 'error.product.global.failed_to_delete']);
        return $this->response($error, 401);
    }

    public function _remove_image_product ($id) {
        parse_str($this->input->raw_input_stream, $data);
        $condition = [
            'id_product' => (int)$id
        ];

        if (!$product = $this->Products->detailByFields($condition)) {
            $error = failed_format(404, ['product_detail' => 'error.product.global.not_found']);
            return $this->response($error, 404);
        }

        if (!isset($data['image_id']))
            return $this->response(failed_format(402, ['category' => 'error.category.image_id.is_required']), 402);

        $condition['id_product_image'] = (int)$data['image_id'];
        if (!$result = $this->Products->deleteImageImage($condition)) {
            $error = failed_format(402, ['category' => 'error.category.global.failed_to_remove']);
            return $this->response($error, 402);
        }

        return $this->response(success_format(['status' => 'success']));
    }


}