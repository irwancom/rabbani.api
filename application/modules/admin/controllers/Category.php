<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\Category\UseCases\ListCategory;
use Andri\Engine\Admin\Domain\Category\UseCases\StoreCategory;
use Andri\Engine\Admin\Domain\Category\UseCases\UpdateCategory;
use Andri\Engine\Admin\Domain\Category\UseCases\PatchCategory;
use Andri\Engine\Admin\Exceptions\CategoryNotFoundException;

class Category extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();
        $this->load->library('wooh_support'); 
        $this->load->model('Categorys');
        $this->presenter = new Presenter;
    }

    public function index_get($id = null) {
        if ($id)
            return $this->_detail($id);
        $this->_index();
    }

    // GET
    // ===========================================================================

    private function _index() {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $list = new ListCategory($this->Categorys);
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

        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $category = $this->Categorys->detailByFields([
            'id_auth' => $user->id_auth,
            'id_category' => (int) $id
        ]);

        if (!$category)
            return $this->response(failed_response(404, 'error.category.global.not_found'));
        $this->response(success_format($category), 200);
    }

    // POST
    // ===========================================================================

    public function index_post($id = null, $endpoint = null) {

        switch ($endpoint) {

            case 'add_image':
                return $this->add_image($id);
        }

        if ($id)
            return $this->_update($id);
        $this->_store();
    }

    private function _store() {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);
        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;
        if(isset($data['category_name'])){
            $data['category_slug'] = $this->wooh_support->stringToSlug($data['category_name']);
        }

        $listCat = new StoreCategory($this->Categorys);
        $listCat->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data), 200);
    }

    private function _update($id) {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);
        $data = $this->input->post();

        $data['id_category'] = $id;
        $data['id_auth'] = $user->id_auth;
        if(isset($data['category_name'])){
            $data['category_slug'] = $this->wooh_support->stringToSlug($data['category_name']);
        }

        $updateCat = new UpdateCategory($this->Categorys);
        $updateCat->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.category.global.successfully_updated'));
    }

    public function add_image($id_category) {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        if (!$category = $this->Categorys->detailByFields(['id_category' => (int) $id_category]))
            return $this->response(failed_format(404, ['category' => 'error.category.global.not_found']));


        $image = upload_image('image');
        if ($image) {
            $data = [
                'id_category' => (int) $category->id_category,
                'image_path' => $image['cdn_url']
            ];
            if ($result = $this->Categorys->update($category, $data)) {
                $message = success_format(
                        [
                    'success' => true,
                    'id_category' => $category->id_category,
                    'image_path' => $image['cdn_url']
                        ], 'success.category.global.image_successfully_uploaded'
                );
                return $this->response($message);
            }
        }

        $error = failed_format(402, ['product' => 'error.category.global.failed_to_upload_image']
        );
        return $this->response($error);
    }

    // DELETE
    // ===========================================================================

    public function index_delete($id, $endpoint = null) {

        switch ($endpoint) {

            case 'remove_image':
                return $this->_remove_image_category($id);
        }

        $this->_delete_category($id);
    }

    public function _delete_category($id) {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $category = $this->Categorys->detailByFields([
            'id_auth' => $user->id_auth,
            'id_category' => (int) $id
        ]);

        if (!$category) {
            $errors = failed_format(404, ['category' => 'error.category.global.not_found']);
            return $this->response($errors, 404);
        }

        $result = $this->Categorys->update($category, ['deleted_at' => date('Y-m-d h:i:s')]);
        if ($result) {
            return $this->response(
                            success_format(
                                    ['success' => true], 'success.category.global.successfully_deleted'
                            )
            );
        }

        $errors = failed_format(401, ['category' => 'error.category.global.failed_to_delete']);
        return $this->response($errors, 401);
    }

    public function _remove_image_category($id) {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $condition = [
            'id_auth' => $user->id_auth,
            'id_category' => (int) $id
        ];

        if (!$category = $this->Categorys->detailByFields($condition)) {
            $errors = failed_format(404, ['category' => 'error.category.global.not_found']);
            return $this->response($errors, 404);
        }

        if (!$result = $this->Categorys->update($category, ['image_path' => null])) {
            $errors = failed_format(402, ['category' => 'error.category.global.failed_to_remove_image']);
            return $this->response($errors, 402);
        }

        return $this->response(success_format(['status' => 'success']), 404);
    }

    // PATCH
    // ===========================================================================

    public function index_patch($id) {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $patch = new PatchCategory($this->Categorys);

        parse_str($this->input->raw_input_stream, $data);

        $data['user'] = $user;
        $data['id_category'] = $id;

        try {
            $patch->execute($data, $this->presenter);
        } catch (CategoryNotFoundException $e) {
            $message = ['category' => 'error.category.global.not_found'];
            return $this->response(failed_format(404, $message), 404);
        }

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            return $this->response(failed_format(422, $errors), 422);
        }

        $result = $this->presenter->data;
        $this->response(success_format($result, 'success.user.global.successfully_updated'), 200);
    }

}
