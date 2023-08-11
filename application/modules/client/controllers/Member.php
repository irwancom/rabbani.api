<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';



class Member extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Members');
    }

    public function index_get($id = null) {
        $user = validate_token();

        $condition = ['id_auth' => $id];
        if ($user && $user->id_auth_user === $id) {
            $condition['owner'] = true;
        }

        $member = $this->Members->detailByFields($condition, true);
        if (!$member) {
            $error = failed_format(404, ['member' => 'error.member.global.not_found']);
            return $this->response($error, 404);
        }

        $this->response(success_format($member), 200);
    }

}
