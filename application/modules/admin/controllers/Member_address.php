<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Client\Domain\MemberAddress\UseCases\ListMemberAddress;
use Andri\Engine\Client\Domain\MemberAddress\UseCases\StoreMemberAddress;
use Andri\Engine\Client\Domain\MemberAddress\UseCases\UpdateMemberAddress;

class Member_address extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('MemberAddresses');
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

        $list = new ListMemberAddress($this->MemberAddresses);
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

        $memberAddress = $this->MemberAddresses->detailByFields([
            'id_auth' => $user->id_auth,
            'id_member_address' => (int) $id
        ]);

        if (!$memberAddress)
            return $this->response(failed_response(404, 'error.member_address.global.not_found'));
        $this->response(success_format($memberAddress), 200);
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

        $listMemberAddress = new StoreMemberAddress($this->MemberAddresses);
        $listMemberAddress->execute($data, $this->presenter);

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

        $data['id_member_address'] = $id;
        $data['id_auth'] = $user->id_auth;

        $updateMemberAddress = new UpdateMemberAddress($this->MemberAddresses);
        $updateMemberAddress->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.member_address.global.successfully_updated'));
    }

    // DELETE
    // ===========================================================================

    public function index_delete($id, $endpoint = null) {
        $this->_delete_member_address($id);
    }

    public function _delete_member_address($id) {
        if (!$user = validate_token())
            return $this->response(failed_format(403), 403);

        $memberAddress = $this->MemberAddresses->detailByFields([
            'id_auth' => $user->id_auth,
            'id_member_address' => (int) $id
        ]);

        if (!$memberAddress) {
            $errors = failed_format(404, ['member_address' => 'error.member_address.global.not_found']);
            return $this->response($errors, 404);
        }

        $result = $this->MemberAddresses->update($memberAddress, ['deleted_at' => date('Y-m-d h:i:s')]);
        if ($result) {
            return $this->response(
                            success_format(
                                    ['success' => true], 'success.member_address.global.successfully_deleted'
                            )
            );
        }

        $errors = failed_format(401, ['member_address' => 'error.member_address.global.failed_to_delete']);
        return $this->response($errors, 401);
    }

}
