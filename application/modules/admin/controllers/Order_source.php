<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\OrderSource\UseCases\ListOrderSource;
use Andri\Engine\Admin\Domain\OrderSource\UseCases\StoreOrderSource;
use Andri\Engine\Admin\Domain\OrderSource\UseCases\UpdateOrderSource;

class Order_source extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('OrderSources');
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

        $list = new ListOrderSource($this->OrderSources);
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

        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $orderSource = $this->OrderSources->detailByFields([
            'id_order_source' => (int) $id
        ]);

        if (!$orderSource)
            return $this->response(failed_response(404, 'error.order_source.global.not_found'));
        $this->response(success_format($orderSource), 200);
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

        $listCat = new StoreOrderSource($this->OrderSources);
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

        $data['id_order_source'] = $id;

        $updateCat = new UpdateOrderSource($this->OrderSources);
        $updateCat->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.order_source.global.successfully_updated'));
    }

    // DELETE
    // ===========================================================================

    public function index_delete($id, $endpoint = null) {

        $this->_delete_order_source($id);
    }

    public function _delete_order_source($id) {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $orderSource = $this->OrderSources->detailByFields([
            'id_order_source' => (int) $id
        ]);
        
        if (!$orderSource) {
            $errors = failed_format(404, ['order_source' => 'error.order_source.global.not_found']);
            return $this->response($errors, 404);
        }

        $result = $this->OrderSources->update($orderSource, ['deleted_at' => date('Y-m-d h:i:s')]);
        if ($result) {
            return $this->response(
                            success_format(
                                    ['success' => true], 'success.order_source.global.successfully_deleted'
                            )
            );
        }

        $errors = failed_format(401, ['order_source' => 'error.order_source.global.failed_to_delete']);
        return $this->response($errors, 401);
    }

}
