<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Admin\Domain\Product\Contracts\ProductRepositoryContract;
use Picqer\Barcode\BarcodeGeneratorPNG;

use Andri\Engine\Shared\QueryFactory;
use Andri\Engine\Admin\Filter\ProductFilter;

class Products extends CI_Model implements ProductRepositoryContract {

    const TABLE = 'product';
    const TABLE_DETAIL = 'product_details';
    const TABLE_IMAGE = 'product_images';
    
    private $barcode_path;

    public function __construct() {
        parent::__construct();
        $this->load->database();

        $ci =& get_instance();
        $this->barcode_path = $ci->config->item('barcode_path');
    }

    public function list(array $options, $listByDetail = false) {
        
        if (!$listByDetail) {
            $this->db->select('product.*, sum(detail.stock) as stock, category.category_name');
            $this->db->join('product_details as detail', 'detail.id_product = product.id_product', 'left');
            $this->db->join('category as category', 'product.id_category = category.id_category', 'left');
            $this->db->group_by("product.id_product");
        } else {
            $this->db->select($this->buildSelect());
            $this->db->join('product_details', self::TABLE_DETAIL.'.id_product = '.self::TABLE.'.id_product', 'right');
        }

        $this->extractQuery($options);
        $this->db->where('product.deleted_at is null');
        
        $results = $this->db->get(self::TABLE)->result();

        if (!$listByDetail) {
            foreach ($results as $result) {
                $arrResult = (array)$result;
                $result->images = $this->images($arrResult);
            }
        }

        return $results;
    }


    public function totalItem($options, $listByDetail = false) {
        unset($options['perPage']);
        unset($options['page']);

        if (!$listByDetail) {
            $this->db->group_by("product.id_product");
            $this->db->from(self::TABLE);
        } else {
            $this->db->from(self::TABLE_DETAIL);
        }
        
        $this->extractQuery($options);
        
        $result = $this->db->count_all_results();
        return $result;
    }


    private function extractQuery($options) {
        $default = ['q', 'sorted', 'perPage', 'page'];
        
        foreach($options as $key => $value) {
            if (!in_array($key, $default)) {
                $this->db->where($key, $value);
            }
        }

        $offset = 0;
        $limit = 20;
        if (isset($options['perPage']))
            $limit = (int)$options['perPage'];
            $this->db->limit($limit);

        if (isset($options['page']))
            $offset = ((int)($options['page'])-1) * $limit;
            $this->db->offset($offset);
        
        if (isset($options['sorted'])) {
            $sorted = explode('.', $options['sorted']);
            $this->db->order_by(self::TABLE.'.'.$sorted[0], $sorted[1]);
        }

        if (isset($options['q'])) {
            $this->db->like(self::TABLE.'.product_name', $options['q']);
        }
    }


    private function buildSelect() {
        $productSelect = [
            'product_name as product_name',
            'id_category as product_id_category'
        ];

        $productSelect = array_map(function($key) {
            return self::TABLE .'.'. $key;
        }, $productSelect);

        $detailSelect = [
            'is_ready_stock as detail_is_ready_stock',
            'sku_code as detail_sku_code',
            'sku_barcode as detail_sku_barcode',
            'variable as detail_variable',
            'price',
            'stock'
        ];
        $detailSelect = array_map(function($key) {
            return self::TABLE_DETAIL .'.'. $key;
        }, $detailSelect);

        $detail = implode(', ', $detailSelect);
        $productSelect = implode(', ', $productSelect);

        return $productSelect .', '.$detail;
    }

    
    public function store(array $data) {
        $date = date('Y-m-d h:i:s');

        $data['created_at'] = $date;
        $data['updated_at'] = $date;

        $result = $this->db->insert(self::TABLE, $data);
        return $result ? $this->db->insert_id() : false;
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
        foreach ($results as $res) {
            if (isJson($res->variable)) {
                $res->variable = json_decode($res->variable);
            }
            if (empty($res->variable)) {
                $variables = [];
                $variables['COLOR'] = '';
                $variables['SIZE'] = '';
                $res->variable = $variables;
            }
        }

        return $results;
    }

   

    public function update($product, array $data) {
        
        $id      = $product->id_product;
        $id_auth = $product->id_auth;

        unset($data['id_product']);
        unset($data['id_auth']);
        unset($data['created_at']);

        foreach($data as $key => $value) {
            if (property_exists($product, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        $this->db->where('id_product', $id);
        $this->db->where('id_auth', $id_auth);

        $result = $this->db->update(self::TABLE);
        return $result;
    }
 

    public function detailItemByFields(array $condition, bool $withDetail = false) {
        if (!key_exists('deleted_at', $condition)) {
            $condition['deleted_at'] = null;
        }
        $result = $this->db
                        ->get_where(self::TABLE_DETAIL, $condition)
                        ->result();

        $detail = $result ? $result[0] : null;
        if ($detail && $withDetail) {
            // $result['images'] = $this->images($detail);
        }

        return $result ? $detail : null;
    }


    public function images(array $product) {
        $id_product = $product['id_product'];
        $result = $this->db
                        ->get_where('product_images', ['id_product' => $id_product, 'deleted_at' => null])
                        ->result();
        return $result;
    }


    public function storeDetail(array $data) {
        $sku_detail = $data['sku_product'] . $data['sku_code'];
        unset($data['sku_product']);
        $data['sku_barcode'] = $this->barcode_path . '/'.strtolower($sku_detail).'.png';

        $result = $this->db->insert(self::TABLE_DETAIL, $data);
        $id = $this->db->insert_id();

        $this->generateBarcode($sku_detail, $data['sku_barcode']);
        return $result ? $id : false;
    }

    /**
     * Generate Barcode
     */
    private function generateBarcode($sku, $file) {
        if (!is_dir($this->barcode_path)) {
            mkdir($this->barcode_path, 0777, true);
        }
        $barcode = new BarcodeGeneratorPNG();
        file_put_contents($file, $barcode->getBarcode(strtoupper($sku), $barcode::TYPE_CODE_128));
    }

    /**
     * Update Detail
     */
    public function updateDetail($detail, array $data) {
        $id = $detail->id_product_detail;
        unset($data['id_product_detail']);

        if (!is_dir($this->barcode_path)) {
            mkdir($this->barcode_path, 0777, true);
        }

        if (isset($data['sku_code'])) {
            $sku_detail = $data['sku_product'] . $data['sku_code'];
            $this->generateBarcode($sku_detail, $this->barcode_path. '/' .strtolower($sku_detail). '.png');
        }

        unset($data['sku_product']);

        foreach($data as $key => $value) {
            if (property_exists($detail, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->where('id_product_detail', $id);

        $result = $this->db->update(self::TABLE_DETAIL);
        return $result;
    }

    /**
     * Delete Item
     */
    public function deleteItem(array $condition) {
        $this->db->set('deleted_at', date('Y-m-d h:i:s'));
        $this->db->where('id_product', $condition['id_product']);
        $this->db->where('id_product_detail', $condition['id_product_detail']);
        return $this->db->update(self::TABLE_DETAIL);
    }

    /**
     * Store Image
     */
    public function storeImage(array $data) {
        $date = date('Y-m-d h:i:s');
        $data['created_at'] = $date;
        $data['updated_at'] = $date;

        $result = $this->db->insert(self::TABLE_IMAGE, $data);
        return $result ? $this->db->insert_id() : false;
    }

    /**
     * Store Image
     */
    public function deleteImageImage(array $data) {
        $this->db->set('deleted_at', date('Y-m-d H:i:s'));
        $this->db->where('id_product', $data['id_product']);
        $this->db->where('id_product_image', $data['id_product_image']);
        return $this->db->update(SELF::TABLE_IMAGE);

        // return $this->db->delete(self::TABLE_IMAGE, $data);
    }
}
