<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Admin\Domain\Discount\Contracts\DiscountRepositoryContract;

class Discounts extends CI_Model implements DiscountRepositoryContract {

    const TABLE = 'discounts';
    const TABLE_DETAIL = 'discount_products';
    const TABLE_PRODUCT = 'product';
    const TABLE_PRODUCT_DETAIL = 'product_details';
    
    const TYPE_PERCENT = 1;
    const TYPE_NOMINAL = 2;

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function list(array $options) {
        $options['deleted_at'] = null;
        $this->extractQuery($options);
        $result = $this->db->get(self::TABLE)->result();
        return $result;
    }

    

    private function extractQuery($options) {
        $default = ['q', 'sorted', 'perPage', 'page'];
        foreach($options as $key => $value) {
            if (!in_array($key, $default)) {
                $this->db->where(self::TABLE .'.'. $key, $value);
            }
        }

        if (isset($options['perPage']))
            $this->db->limit($options['perPage']);

        if (isset($options['page']))
            $this->db->offset($options['page']);
        
        if (isset($options['sorted'])) {
            $sorted = explode('.', $options['sorted']);
            $this->db->order_by(self::TABLE.'.'.$sorted[0], $sorted[1]);
        }

    }








    public function detailBy($field, $value) {
        $condition = [
            'deleted_at' => NULL,
            $field => $value
        ];

        $discount = $this->db->get_where(self::TABLE, $condition)->result();
        return count($discount) > 0 ? $discount[0] : null;
    }

    public function detailByFields(array $condition) {

        $condition['deleted_at'] = NULL;

        $discount = $this->db->get_where(self::TABLE, $condition)->result();
        return count($discount) > 0 ? $discount[0] : null;
    }

    public function update($discount, array $data) {
        
        $id      = $discount->id_discount;
        $id_auth = $discount->id_auth;

        unset($data['id_discount']);
        unset($data['id_auth']);
        unset($data['created_at']);
        
        foreach($data as $key => $value) {
            if (property_exists($discount, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        $this->db->where('id_discount', $id);
        $this->db->where('id_auth', $id_auth);

        $result = $this->db->update(self::TABLE, $data);
        return $result;
    }


    public function store(array $data) {
        $date = date('Y-m-d h:i:s');

        $data['created_at'] = $date;
        $data['updated_at'] = $date;

        $result = $this->db->insert(self::TABLE, $data);
        return $result ? $this->db->insert_id() : false;
    }


    public function delete(int $id) {
        $result = $this->db->delete(self::TABLE, ['id_discount' => $id]);
        return $result;
    }




    public function listProduct(array $options) {
        $options['deleted_at'] = null;
        
        $this->extractQuery($options);
        $select = $this->buildSelect();
        
        $this->db->select($select);
        $this->db->join(self::TABLE_DETAIL, self::TABLE_DETAIL .'.id_discount = '.self::TABLE . '.id_discount', 'left');
        $this->db->join(self::TABLE_PRODUCT_DETAIL, self::TABLE_DETAIL .'.id_product_detail = '.self::TABLE_PRODUCT_DETAIL.'.id_product_detail', 'right');
        $this->db->join(self::TABLE_PRODUCT, self::TABLE_PRODUCT_DETAIL . '.id_product = '.self::TABLE_PRODUCT .'.id_product', 'right');
        $result = $this->db->get(self::TABLE)->result();
        
        return $result;
    }

    private function buildSelect() {
        $productSelect = [
            'id_product as product_id_product',
            'product_name as product_name',
            'id_category as product_id_category'
        ];

        $productSelect = array_map(function($key) {
            return self::TABLE_PRODUCT .'.'. $key;
        }, $productSelect);

        $detailSelect = [
            'id_product_detail as detail_id_product_detail',
            'sku_code as detail_sku_code',
            'sku_barcode as detail_sku_barcode',
            'variable as detail_variable',
            'price',
            'stock'
        ];
        $detailSelect = array_map(function($key) {
            return self::TABLE_PRODUCT_DETAIL .'.'. $key;
        }, $detailSelect);

        $discount = [
            'id_discount as discount_id_discount',
            'title as discount_title',
            'desc as discount_desc',
            'min_qty as discount_min_qty',
            'max_qty as discount_max_qty',
            'start_time as discount_start_time',
            'end_time as discount_end_time',
            'discount_type as discount_disc_type',
            'discount_value as discount_disc_value',
        ];
        $discount = array_map(function($key) {
            return self::TABLE .'.'. $key;
        }, $discount);

        $detail = implode(', ', $detailSelect);
        $discount = implode(', ', $discount);
        $productSelect = implode(', ', $productSelect);

        return $productSelect .', '.$detail .', '. $discount;
    }

    public function storeProduct(array $data) {
        return $this->db->insert(self::TABLE_DETAIL, $data);
    }


    public function deleteProduct($id) {
        return $this->db->delete(self::TABLE_DETAIL, ['id_product_detail' => $id]);
    }
    
}
