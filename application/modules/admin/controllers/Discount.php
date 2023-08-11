<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\Discount\UseCases\ListDiscount;
use Andri\Engine\Admin\Domain\Discount\UseCases\StoreDiscount;
use Andri\Engine\Admin\Domain\Discount\UseCases\UpdateDiscount;


class Discount extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Discounts');
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

        $list = new ListDiscount($this->Discounts);
        $options = $_GET;
        $options['id_auth'] = $user->id_auth;
        $list->execute($options, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $totalItem = 0;
        $totalPage = 1;
        
        $this->response(success_format($data, '', $totalItem, $totalPage), 200);
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
    


    // POST
    // ===========================================================================

    public function index_post($id = null) {
        if ($id) return $this->_update($id);
        $this->_store();
    }

    private function _store() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;

        $list = new StoreDiscount($this->Discounts);
        $list->execute($data, $this->presenter);

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

        $data['id_discount'] = $id;
        $data['id_auth']   = $user->id_auth;

        $updateCat = new UpdateDiscount($this->Discounts);
        $updateCat->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.discount.global.successfully_updated'));
    }


    // DELETE
    // ===========================================================================

    public function index_delete($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $discount = $this->Discounts->detailByFields([
            'id_auth' => $user->id_auth,
            'id_discount' => (int)$id
        ]);

        if (!$discount) {
            $errors = failed_format(404, ['discount' => 'error.discount.global.not_found']);
            return $this->response($errors, 404);
        }
        
        $result = $this->Discounts->update($discount, ['deleted_at' => date('Y-m-d h:i:s')]);
        if ($result) {
            return $this->response(
                        success_format(
                            ['success' => true], 
                            'success.discount.global.successfully_deleted'
                        )
                    );    
        }

        $errors = failed_format(401, ['discount' => 'error.discount.global.failed_to_delete']);
        return $this->response($errors, 401);
    }


}
