<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\Presenter;
use Andri\Engine\Admin\Domain\Order\UseCases\ListOrder;
use Andri\Engine\Admin\Domain\Order\UseCases\UpdateOrder;
use Andri\Engine\Admin\Domain\Order\UseCases\HandlerOrder;


class Order extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Orders');
        $this->load->model('Carts');
        $this->load->model('OrderSources');
        $this->load->model('Products');
        $this->load->model('Members');
        $this->load->model('MemberAddresses');
        $this->load->library('Shipper');
        $this->load->library('Xendit');
        $this->presenter = new Presenter;
    }



    // GET
    // ===========================================================================

    public function index_get($code = null) {
        if ($code) return $this->_detail($code);
        $this->_index();
    }


    private function _index() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $list = new ListOrder($this->Orders, $this->OrderSources);
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


    private function _detail($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $order = $this->Orders->detailByFields([
            'invoice_number' => $code
        ], true);

        if (!$order) {
            $errors = failed_format(404, ['order' => 'error.order.global.not_found']);
            return $this->response($errors, 404);
        }

        // $order['order_info'] = json_decode($order['order_info']);
        // $order['discount_noted'] = json_decode($order['discount_noted']);

        $this->response(success_format($order), 200);
    }
    


    // POST
    // ===========================================================================

    public function index_post($endpoints, $code = null) {
        switch($endpoints) {
        
            case 'shipping_rate':
                return $this->_get_shipping_rates();

            case 'create_waiting_payment';
                return $this->_create_waiting_payment();

            case 'payment_callback';
                return $this->_payment_callback();

            case 'accept_order';
                return $this->_accept_order($code);

            case 'find_pickup_agent';
                return $this->_find_pickup_agent($code);

            case 'request_pickup';
                return $this->_request_pickup($code);

            case 'awb_number';
                return $this->_awb_number($code);

            case 'done';
                return $this->_done($code);

            case 'cancel';
                return $this->_cancel($code);

            case 'track';
                return $this->_track($code);

        }

        return $this->_store();
    }

    public function _get_shipping_rates () {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;

        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->getShippingRates($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data), 200);
    }

    public function _create_waiting_payment () {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;
        $data['id_auth_user'] = $user->id_auth_user;
        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->createWaitingPayment($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 200));
    }

    public function _payment_callback () {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->receivePaymentCallback($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 200));
    }

    public function _accept_order ($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;
        $data['invoice_number'] = $code;
        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);

        $result = null;
        if (isset($data['use_shipper']) && $data['use_shipper'] == 'true') {
            $result = $handler->createDomesticOrderCreation($data, $this->presenter);
        } else {
            $data['use_shipper'] = 'false';
            $result = $handler->acceptOrder($data, $this->presenter);   
        }

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 200));
    }

    public function _find_pickup_agent ($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = [
            'id_auth' => $user->id_auth,
            'invoice_number' => $code
        ];
        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->findPickupAgent($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 200));
    }

    public function _request_pickup ($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;
        $data['invoice_number'] = $code;

        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->requestPickup($data, $this->presenter);


        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data));
    }

    public function _awb_number ($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;
        $data['invoice_number'] = $code;
        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->updateAwbNumber($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 200));
    }

    public function _done ($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = [
            'id_auth' => $user->id_auth,
            'invoice_number' => $code
        ];
        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->done($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 200));
    }

    public function _cancel ($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = [
            'id_auth' => $user->id_auth,
            'invoice_number' => $code
        ];
        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->done($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 200));
    }

    public function _track ($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = [
            'id_auth' => $user->id_auth,
            'invoice_number' => $code
        ];
        $handler = new HandlerOrder($this->Orders, $this->Products, $this->Members, $this->MemberAddresses, $this->shipper, $this->xendit);
        $result = $handler->track($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 200));
    }

    private function _store() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $data['id_auth'] = $user->id_auth;
        
        $this->isError($data);

        // $cart = $this->Carts->detail('id_cart', $data['id_cart']);
        // if (!$cart) 
        //    return $this->response(failed_format(402, ['order' => 'error.order.global.not_item_found_in_cart']));

        // $data['cart'] = $cart;
        $result = $this->Orders->storeFromAdmin($data);
        
        if ($result) 
            return $this->response(success_format(['status' => 'success', 'order_code' => $result['order_code']]));
        
        return $this->response(failed_format(402, ['order' => 'error.order.global.failed_to_store_order']));
    }

    private function isError($data) {

        if (!isset($data['id_order_source'])) {
            return $this->response(failed_format(402, ['id_order_source' => 'error.order.id_order_source.is_required']));
        }

        if (!isset($data['id_auth_user'])) {
            return $this->response(failed_format(402, ['id_auth_user' => 'error.order.id_auth_user.is_required']));
        }

        if (!isset($data['order_info']['name'])) {
            return $this->response(failed_format(402, ['name' => 'error.order.name.is_required']));
        }

        if (!isset($data['order_info']['address'])) {
            return $this->response(failed_format(402, ['address' => 'error.order.address.is_required']));
        }

        if (!isset($data['order_info']['phone'])) {
            return $this->response(failed_format(402, ['phone' => 'error.order.phone.is_required']));
        }

        if (!isset($data['shipping_courier'])) {
            return $this->response(failed_format(402, ['phone' => 'error.order.shipping_courier.is_required']));
        }

        return null;
    }

    private function _update($code) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        $data = $this->input->post();

        $data['order_code'] = $code;
        $data['id_auth']    = $user->id_auth;

        $update = new UpdateOrder($this->Orders);
        $update->execute($data, $this->presenter);

        if ($this->presenter->hasError()) {
            $errors = $this->presenter->errors;
            $this->response(failed_format(403, $errors));
        }

        $data = $this->presenter->data;
        $this->response(success_format($data, 'success.order.global.successfully_updated'));
    }


    // DELETE
    // ===========================================================================

    public function index_delete($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $letter = $this->Orders->detailByFields([
            'id_auth' => $user->id_auth,
            'id_letter' => $id
        ]);

        if (!$letter) {
            $errors = failed_format(404, ['newsletter' => 'error.newsletter.global.not_found']);
            return $this->response($errors, 404);
        }
        
        $result = $this->Orders->deleteDetail($id);
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


     // PATCH
    // ===========================================================================

    public function index_patch($code, $endpoint = null) {

        switch($endpoint) {

            case 'shipping':
                return $this->_patch_status($code, 'shipping');

            case 'payment':
                return $this->_patch_status($code, 'payment');

            case 'awb_code':
                return $this->_patch_status($code, 'awb_code');


        }
        
    }



    private function _patch_status($code, $endpoint) {
        if (!$user = validate_token()) return $this->response(failed_format(403), 403);

        if (!$order = $this->Orders->detailByFields(['order_code' => $code])) {
            $error = failed_format(404, ['order' => 'error.order.global.not_found']);
            return $this->response($error, 404);
        }

        parse_str($this->input->raw_input_stream, $data);

        if (!key_exists('value', $data)) {
            $error = failed_format(404, ['order' => 'error.order.value.is_required']);
            return $this->response($error, 404);
        }

        switch($endpoint) {
            case 'shipping':
                return $this->_shipping_status($order, $data);

            case 'payment':
                return $this->_payment_status($order, $data);

            case 'awb_code':
                return $this->_set_awb_code($order, $data);
        }
    }



    private function _shipping_status($order, $data) {
        $result = $this->Orders->update($order, ['shipping_status' => (int)$data['value']]);
        if ($result)
            return $this->response(success_format(['status' => 'success', 'order_code' => $order->order_code]));
        
            
        $error = failed_format(402, ['order' => 'error.order.global.failed_update_order']);
        return $this->response($error, 402);
    }



    private function _payment_status($order, $data) {
        $result = $this->Orders->updateStatus($order, ['status' => (int)$data['value']]);
        if ($result)
            return $this->response(success_format(['status' => 'success', 'order_code' => $order->order_code]));
        
            
        $error = failed_format(402, ['order' => 'error.order.global.failed_update_order']);
        return $this->response($error, 402);
    }



    private function _set_awb_code($order, $data) {
        $result = $this->Orders->update($order, ['no_awb' => $data['value']]);
        if ($result)
            return $this->response(success_format(['status' => 'success', 'order_code' => $order->order_code]));
        
            
        $error = failed_format(402, ['order' => 'error.order.global.failed_update_order']);
        return $this->response($error, 402);
    }

}

