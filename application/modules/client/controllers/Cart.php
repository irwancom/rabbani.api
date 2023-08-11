<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Client\Domain\Order\UseCases\AddToCart;

class Cart extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Carts');
        $this->load->model('Products');
        $this->load->model('Discounts');
        $this->load->model('Flashsales');
        
        $this->presenter = new Presenter;
    }


    public function index_get($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $result = $this->Carts->detail('id_cart', $id);
        if ($result) {
            $cart = (array)$result;
            $cart['items'] = (array)json_decode($cart['items']);
            $cart['items'] = array_values($cart['items']);
            
            if (count($cart['items']) > 0)
                return $this->response(success_format($cart), 200);
        }

        return $this->response(failed_format(404, ['cart' => 'error.cart.global.not_found']), 404);
    }


    public function index_post($id = null) {
        if ($id) return $this->_update($id);
        return $this->_store();
    }

    public function _store() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $data['id_auth_user'] = $user->id_auth_user;
        $this->__process($data);
    }

    private function __process($data) {
        $presenter = new Presenter;
        $cart = new AddToCart(
            $this->Carts,
            $this->Products,
            $this->Discounts,
            $this->Flashsales
        );

        $cart->execute($data, $presenter);
        if ($presenter->hasError())
            return $this->response(failed_format(402, $presenter->errors), 402);
        
        $data = $presenter->data;
        $this->response(success_format($data), 200);
    }


    public function index_delete($id) {
        parse_str($this->input->raw_input_stream, $data);

        $cart = $this->Carts->detail('id_cart', $id);
        if (!$cart) {
            return $this->response(failed_format(404, ['cart' => 'error.cart.global.not_found']), 404);
        }

        $items = (array)json_decode($cart->items);
        if (isset($items[$data['id_product_detail']]))  {
            unset($items[$data['id_product_detail']]);
            
            $this->Carts->update((object)$cart, ['items' => $items]);
        }
        
        $this->response(success_format(['status' => 'success'], 'success.cart.global.successfully_delete_item'));
    }

}
