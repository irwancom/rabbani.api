<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\NewsLetter\UseCases\ListNewsLetter;
use Andri\Engine\Admin\Domain\NewsLetter\UseCases\StoreNewsLetter;
use Andri\Engine\Admin\Domain\NewsLetter\UseCases\UpdateNewsLetter;


class Newsletter extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Newsletters');
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

        $listCat = new ListNewsLetter($this->Newsletters);
        $listCat->execute([], $this->presenter);

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

        $letter = $this->Newsletters->detailByFields([
            'id_letter' => (int)$id
        ]);

        if (!$letter) {
            $errors = failed_format(404, ['newsletter' => 'error.newsletter.global.not_found']);
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

        $listCat = new StoreNewsLetter($this->Newsletters);
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

        $data['id_letter'] = $id;
        $data['id_auth']   = $user->id_auth;

        $updateCat = new UpdateNewsLetter($this->Newsletters);
        $updateCat->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.newsletter.global.successfully_updated'));
    }


    // DELETE
    // ===========================================================================

    public function index_delete($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $letter = $this->Newsletters->detailByFields([
            'id_auth' => $user->id_auth,
            'id_letter' => $id
        ]);

        if (!$letter) {
            $errors = failed_format(404, ['newsletter' => 'error.newsletter.global.not_found']);
            return $this->response($errors, 404);
        }
        
        $result = $this->Newsletters->delete($id);
        if ($result) {
            return $this->response(
                        success_format(
                            ['success' => true], 
                            'success.newsletter.global.successfully_deleted'
                        )
                    );
        }

        $errors = failed_format(401, ['newsletter' => 'error.newsletter.global.failed_to_delete']);
        return $this->response($errors, 401);
    }


}
