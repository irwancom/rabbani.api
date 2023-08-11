<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\FlashSale\UseCases\ListFlashSale;
use Andri\Engine\Admin\Domain\FlashSale\UseCases\StoreFlashSale;
use Andri\Engine\Admin\Domain\FlashSale\UseCases\UpdateFlashSale;


class Flashsale extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Flashsales');
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

        $list = new ListFlashSale($this->Flashsales);
        $options = $_GET;

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

        $letter = $this->Flashsales->detailByFields([
            'id_flash_sale' => (int)$id
        ]);

        if (!$letter) {
            $errors = failed_format(404, ['flashsale' => 'error.flashsale.global.not_found']);
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

        $listCat = new StoreFlashSale($this->Flashsales, $this->Products);
        $listCat->execute($data, $this->presenter);

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

        $data['id_flash_sale'] = $id;
        $data['id_auth']   = $user->id_auth;

        $update = new UpdateFlashSale($this->Flashsales);
        $update->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.flashsale.global.successfully_updated'));
    }




    // DELETE
    // ===========================================================================

    public function index_delete($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $flashsale = $this->Flashsales->detailByFields([
            'id_auth' => $user->id_auth,
            'id_flash_sale' => (int)$id
        ]);

        if (!$flashsale) {
            $errors = failed_format(404, ['flashsale' => 'error.flashsale.global.not_found']);
            return $this->response($errors, 404);
        }
        
        $result = $this->Flashsales->update($flashsale, ['deleted_at' => date('Y-m-d h:i:s')]);
        if ($result) {
            return $this->response(
                        success_format(
                            ['success' => true], 
                            'success.flashsale.global.successfully_deleted'
                        )
                    );    
        }

        $errors = failed_format(401, ['flashsale' => 'error.flashsale.global.failed_to_delete']);
        return $this->response($errors, 401);
    }


}
