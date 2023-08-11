<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Admin\Domain\FlashSale\Contracts\FlashSaleRepositoryContract;

class Flashsales extends CI_Model implements FlashSaleRepositoryContract {

    const TABLE = 'flash_sales';
    const TABLE_PRODUCT_DETAIL = 'product_details';
    const TABLE_PRODUCT =  'product';
  
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }




    public function list(array $options) {
        $this->extractQuery($options, self::TABLE);

        $select = $this->buildSelect();

        $this->db->select($select);

        $this->db->join(self::TABLE_PRODUCT_DETAIL, self::TABLE .'.id_product_detail = ' . self::TABLE_PRODUCT_DETAIL .'.id_product_detail', 'right');
        $this->db->join(self::TABLE_PRODUCT, self::TABLE_PRODUCT_DETAIL.'.id_product = '.self::TABLE_PRODUCT .'.id_product', 'right');
        $result = $this->db->get(self::TABLE)->result();
        return $result;
    }

    private function buildSelect() {
        $productSelect = [
            'product_name as product_name',
            'id_category as product_id_category'
        ];

        $productSelect = array_map(function($key) {
            return self::TABLE_PRODUCT .'.'. $key;
        }, $productSelect);

        $detailSelect = [
            'sku_code as detail_sku_code',
            'sku_barcode as detail_sku_barcode',
            'variable as detail_variable',
            'price',
            'stock'
        ];
        $detailSelect = array_map(function($key) {
            return self::TABLE_PRODUCT_DETAIL .'.'. $key;
        }, $detailSelect);

        $flashsale = [
            'min_qty as sale_min_qty',
            'max_qty as sale_max_qty',
            'discount_type as sale_disc_type',
            'discount_value as sale_disc_value',
        ];
        $flashsale = array_map(function($key) {
            return self::TABLE .'.'. $key;
        }, $flashsale);

        $detail = implode(', ', $detailSelect);
        $sale = implode(', ', $flashsale);
        $productSelect = implode(', ', $productSelect);

        return $productSelect .', '.$detail .', '. $sale;
    }

    public function totalItem($options) {
        unset($options['perPage']);
        $this->extractQuery($options);
        
        $this->db->from(self::TABLE);
        $result = $this->db->count_all_results();
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
        
        $id      = $discount->id_flash_sale;
        $id_auth = $discount->id_auth;

        unset($data['id_flash_sale']);
        unset($data['id_auth']);
        unset($data['created_at']);
        
        foreach($data as $key => $value) {
            if (property_exists($discount, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        $this->db->where('id_flash_sale', $id);
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
        $result = $this->db->delete(self::TABLE, ['id_flash_sale' => $id]);
        return $result;
    }
    
}
