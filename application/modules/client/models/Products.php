<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Client\Domain\Order\Contracts\ProductRepositoryContract;

class Products extends CI_Model implements ProductRepositoryContract {

    const TABLE         = 'product';
    const TABLE_DETAIL  = 'product_details';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function detailItem(array $options) {
        $product = $this->db->get_where(self::TABLE_DETAIL, $options)->result();
        return  $product ? $product[0] : null;
    }

    public function detailByFields(array $options, bool $with_details = false) {
        if (!key_exists('deleted_at', $options)) 
            $options['deleted_at'] = NULL;

        $product = $this->db->get_where(self::TABLE, $options)->result();
        $product = $product ? $product[0] : null;

        if ($product && $with_details) {
            $product = (array)$product;
            $product['details'] = $this->details($product, true);
            $product['images'] = $this->images($product, true);
            
        }
        
        return $product;
    }

    public function details(array $product) {
        $id_product = $product['id_product'];
        $results = $this->db
                        ->get_where('product_details', ['id_product' => $id_product, 'deleted_at' => null])
                        ->result();

        return $results;
    }

    public function images(array $product) {
        $id_product = $product['id_product'];
        $result = $this->db
                        ->get_where('product_images', ['id_product' => $id_product, 'deleted_at' => null])
                        ->result();
        return $result;
    }

}
