<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Client\Domain\Category\UseCases\ListCategory;


class Category extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Categorys');
        $this->presenter = new Presenter;
    }

    public function index_get($id = null) {
        if ($id) return $this->_detail($id);
        $this->_index();
    }



    // GET
    // ===========================================================================

    private function _index() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $listCat = new ListCategory($this->Categorys);
        $listCat->execute(['id_auth' => $user->id_auth ], $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $totalItem = 0;
        $totalPage = 1;
        
        $this->response(success_format($data, '', $totalItem, $totalPage), 200);
    }

}
