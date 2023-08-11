<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Client\Domain\Order\UseCases\AddToCart;
use Andri\Engine\Client\Domain\Order\Contracts\CartRepositoryContract;

class Carts extends CI_Model implements CartRepositoryContract {

    const TABLE = 'carts';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    public function list(array $options) {
        $query = $this->db->get_where(self::TABLE, $options)->result();
        if (!empty($query))
            return $query;
        
        return [];
    }

    public function store(array $data) {
        $id     = $data['id_cart'];
        $user   = $data['id_auth_user'];
        
        unset($data['id_cart']);
        unset($data['id_user_auth']);

        $item = [ $data['id_product_detail'] => $data ];
        $item = json_encode($item);
        $payload = [
            'id_cart'       => $id,
            'id_auth_user'  => $user,
            'items'         => $item,
            'total_price'   => (float)$data['total'],
            'total_qty'     => (int)$data['qty']
        ];
        return $this->db->insert(self::TABLE, $payload);
    }

    
    public function detail($field, $value) {
        $result = $this->db
                        ->get_where(self::TABLE, [$field => $value])
                        ->result();

        return $result ? $result[0] : null;
    }


    public function pushItem($cart, $data) {
        $item   = (array)json_decode($cart->items);
        $id     = $data['id_product_detail'];

        unset($data['id_auth_user']);

        if (!isset($item[$id])) {
            $item[$id] = (object)$data;
        } else {
            foreach($data as $key => $value) {
                $item[$id]->{$key} = "{$value}";
            }
        }

        list($total, $qty) = $this->calculateTotal($item);
        
        $this->db->set('items', json_encode($item));
        $this->db->set('total_price', $total);
        $this->db->set('total_qty', $qty);
        $this->db->where('id_cart', $cart->id_cart);
        return $this->db->update(self::TABLE);
    }


    public function update($cart, $data) {
        list($total, $qty) = $this->calculateTotal($data['items']);

        $data['total_price'] = $total;
        $data['total_qty'] = $qty;
        $data['items'] = json_encode($data['items']);

        foreach($data as $key => $value) {
            if (property_exists($cart, $key)) {
                $this->db->set($key, $value);
            }
        }
        $this->db->where('id_cart', $cart->id_cart);
        return $this->db->update(self::TABLE);
    }


    public function calculateTotal($data) {
        $price = 0;
        $qty = 0;

        foreach($data as $value) {
            $price += (float)$value->total;
            $qty += (int)$value->qty;
        }
        return [$price, $qty];
    }
    
}
