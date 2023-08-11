<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\User\UseCases\ListUser;
use Andri\Engine\Admin\Domain\User\UseCases\DetailUser;
use Andri\Engine\Admin\Domain\User\UseCases\StoreUser;
use Andri\Engine\Admin\Domain\User\UseCases\UpdateUser;
use Andri\Engine\Admin\Domain\User\UseCases\PatchUser;



class User extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Users');
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

        $list = new ListUser($this->Users);
        $list->execute([], $this->presenter);

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

        $detail = new DetailUser($this->Users);
        $detail->execute((int)$id, $this->presenter);

        if ($this->presenter->hasError()) {
            $error = ['user' => 'error.user.global.user_not_found'];
            return $this->response(failed_format(404, $error));
        }

        $user = $this->presenter->data;
        $this->response(success_format($user), 200);
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

        $listCat = new StoreUser($this->Users);
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

        $data['id_auth_user'] = $id;
        $data['id_auth']   = $user->id_auth;

        $updateCat = new UpdateUser($this->Users);
        $updateCat->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = [
            'success' => true,
            'user' => $this->presenter->data
        ];
        
        $this->response(success_format($data, 'success.user.successfully_updated'));
    }


    // DELETE
    // ===========================================================================

    public function index_delete($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $user = $this->Users->detailByFields([
            'id_auth' => $user->id_auth,
            'id_auth_user' => $id
        ]);

        if (!$user) {
            $errors = failed_format(404, ['user' => 'error.user.global.not_found']);
            return $this->response($errors, 404);
        }
        
        $result = $this->Users->update($user, [
            'deleted_at' => date('Y-m-d h:i:s')
        ]);

        if ($result) {
            return $this->response(
                        success_format(
                            ['success' => true], 
                            'success.user.global.successfully_deleted'
                        )
                    );
        }

        $errors = failed_format(401, ['user' => 'error.user.global.failed_to_delete']);
        return $this->response($errors, 401);
    }



    // PATCH
    // ===========================================================================

    public function index_patch($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $user = $this->Users->detailBy('id_auth_user', $id);
        if (!$user) {
            $error = failed_format(404, ['user' => "error.user.global.not_found"]);
            return $this->response($error, 404);
        }

        parse_str($this->input->raw_input_stream, $data);

        $patch = new PatchUser($this->Users);
        $patch->execute($user, $data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            return $this->response(failed_format(422, $errors), 422);
        }

        $result = $this->presenter->data;
        $this->response(success_format($result, 'success.user.global.successfully_updated'), 200);
    }

}
