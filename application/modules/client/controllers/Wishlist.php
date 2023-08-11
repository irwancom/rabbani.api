<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Client\Domain\Wishlist\UseCases\ListWishlist;


class Wishlist extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Wishlists');
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

        $list = new ListWishlist($this->Wishlists);
        $list->execute(['id_auth_user' => $user->id_auth_user ], $this->presenter);

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

        $wishlist = $this->Wishlists->detailByFields([
            'id_auth_user' => $user->id_auth_user,
            'id_wishlist' => (int)$id
        ]);

        if (!$wishlist) return $this->response(failed_response(404, 'error.wishlist.global.not_found'));
        $this->response(success_format($wishlist), 200);
    }

}
