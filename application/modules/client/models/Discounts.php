<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Client\Domain\Order\Contracts\DiscountRepositoryContract;

class Discounts extends CI_Model implements DiscountRepositoryContract {

    const TABLE = 'discounts';
    const TABLE_ITEM = 'discount_products';
  
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function detailItem(array $condition) {
        $this->db->select(self::TABLE . '.*, product.id_product_detail as id_product_detail');
        $this->db->join(self::TABLE_ITEM . ' as product', 'product.id_discount = '.self::TABLE.'.id_discount');
        $this->db->group_by(self::TABLE . '.id_discount');
        $this->db->where('product.id_product_detail', $condition['id_product_detail']);
        $result = $this->db->get(self::TABLE)->result();

        return $result ? $result[0] : null;
    }

    
}
