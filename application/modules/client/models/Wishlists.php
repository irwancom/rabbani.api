<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Client\Domain\Wishlist\Contracts\WishlistRepositoryContract;

class Wishlists extends CI_Model implements WishlistRepositoryContract {

    const TABLE = 'wishlists';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    
    public function list(array $options) {
        $user   = $this->user((int)$options['id_auth_user']);
        $result = $this->db->get_where(self::TABLE, $options)->result();

        if (!empty($result)) return $this->with_product_and_user($result, $user);
        
        return [];
    }



    public function detailByFields(array $condition) {
        $wishlist = $this->db->get_where(self::TABLE, $condition)->result();
        if(count($wishlist) > 0) {
            $wishlist   = (array)$wishlist[0];

            $user       = $this->user((int)$wishlist['id_auth_user']);
            $product    = $this->product((int)$wishlist['product_id']);
            
            if (!$product) return null;
            
            $wishlist['product'] = $product;
            $wishlist['user']    = $user;

            return (object)$wishlist;
        }

        return null;
    }



    public function with_product_and_user($wishlists, $user) {
        $ids = array_map(function($item) {
            return $item->product_id;
        }, $wishlists);

        $this->db->where_in('id_product', $ids);

        $results = $this->db->get('product')->result();
        $results = $this->_populate($results, 'id_product');

        return array_map(function($item) use ($results, $user) {
            $item = (array)$item;

            $item['product'] = $results[$item['product_id']];
            $item['user']    = $user;

            return (object)$item;
        }, $wishlists);
    }


    private function _populate($items, $key) {
        $results = [];
        foreach($items as $item) {
            $results[$item->{$key}] = $item;
        }
        return $results;
    }


    public function product(int $id) {
        $condition = [
            'id_product' => $id,
            'deleted_at' => NULL
        ];
        $result = $this->db->get_where('product', $condition)->result();
        
        if (count($result) > 0) return $result[0];
        return null;
    }


    public function user(int $id) {
        $condition = [
            'id_auth_user' => $id,
            'deleted_at' => NULL
        ];

        $result = $this->db->get_where('auth_user', $condition)->result();
        if (count($result) > 0) {
            $user = (array)$result[0];

            unset($user['secret']);
            unset($user['paswd']);
            unset($user['last_login']);
            unset($user['id_auth']);
            
            return (object)$user;
        }
        return null;
    }


}
