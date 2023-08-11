<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\Warehouse\UseCases\ListWarehouse;
use Andri\Engine\Admin\Domain\Warehouse\UseCases\StoreWarehouse;
use Andri\Engine\Admin\Domain\Warehouse\UseCases\UpdateWarehouse;

class User_warehouse extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Warehouses');
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

        $list = new ListWarehouse($this->Warehouses);
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

        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $warehouse = $this->Warehouses->detailByFields([
            'id_auth' => $user->id_auth,
            'id_warehouse' => (int) $id
        ]);

        if (!$warehouse)
            return $this->response(failed_response(404, 'error.warehouse.global.not_found'));
        $this->response(success_format($warehouse), 200);
    }

    // POST
    // ===========================================================================

    public function index_post($id = null, $endpoint = null) {
        if ($id)
            return $this->_update($id);
        $this->_store();
    }

    private function _store() {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);
        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;

        $listWarehouse = new StoreWarehouse($this->Warehouses);
        $listWarehouse->execute($data, $this->presenter);

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

        $data['id_warehouse'] = $id;
        $data['id_auth'] = $user->id_auth;

        $updateWarehouse = new UpdateWarehouse($this->Warehouses);
        $updateWarehouse->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.warehouse.global.successfully_updated'));
    }

    // DELETE
    // ===========================================================================

    public function index_delete($id, $endpoint = null) {
        $this->_delete_warehouse($id);
    }

    public function _delete_warehouse($id) {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $warehouse = $this->Warehouses->detailByFields([
            'id_auth' => $user->id_auth,
            'id_warehouse' => (int) $id
        ]);

        if (!$warehouse) {
            $errors = failed_format(404, ['warehouse' => 'error.warehouse.global.not_found']);
            return $this->response($errors, 404);
        }

        $result = $this->Warehouses->update($warehouse, ['deleted_at' => date('Y-m-d h:i:s')]);
        if ($result) {
            return $this->response(
                            success_format(
                                    ['success' => true], 'success.warehouse.global.successfully_deleted'
                            )
            );
        }

        $errors = failed_format(401, ['warehouse' => 'error.warehouse.global.failed_to_delete']);
        return $this->response($errors, 401);
    }

}
