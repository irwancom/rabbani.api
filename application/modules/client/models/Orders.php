<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends CI_Model  {

    const TABLE = 'orders';
    const TABLE_DETAIL = 'order_details';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    

    public function store(array $data) {

        $info = json_encode($data['order_info']);
        $noted = json_encode($data['discount_noted']);
        
        $cart = $data['cart'];

        $date = date('Y-m-d h:i:s');
        $code = strtoupper(uniqid());

        $order = [
            'id_auth'       => $data['id_auth'],
            'id_auth_user'  => $data['id_auth_user'],
            'order_code'    => $code,
            'order_info'    => $info,
            'total_price'   => $cart->total_price,
            'total_qty'     => $cart->total_qty,
            'id_cart'       => $cart->id_cart,
            'status'        => 0,
            'no_awb'        => isset($data['no_awb']) ? $data['no_awb'] : null,
            'shipping_courier' => strtolower($data['shipping_courier']),
            'discount_noted' => $noted,
            'order_source'  => isset($data['order_source']) ? $data['order_source'] : null,
            'created_at'    => $date,
            'updated_at'    => $date
        ];

        $items = (array)json_decode($cart->items);
        $details = [];
        $ids = [];
        foreach($items as $item) {
            $item = (array)$item;
            $details[$item['id_product_detail']] = [
                'order_code'        => $code, 
                'id_product_detail' => $item['id_product_detail'],
                'discount_type'     => $item['discount_type'],
                'discount_source'   => $item['discount_source'],
                'discount_value'    => $item['discount_value'],
                'price'             => $item['price'],
                'qty'               => $item['qty'],
                'total'             => $item['total']
            ];
            
            array_push($ids, $item['id_product_detail']);
        }

        $this->db->trans_start();
            $result = $this->db->insert(self::TABLE, $order);
            $this->db->insert_batch(self::TABLE_DETAIL, $details);
            $this->update_stock($details, $ids);
        $this->db->trans_complete();

        return $result ? ['order_code' => $code] : null ;
    }


    public function update_stock($products, $ids) { 

        $details = $this->db
                ->where_in('id_product_detail', $ids)
                ->get('product_details')
                ->result();
        
        $rest_stock = [];
        $query = [];

        foreach($details as $item) {
            $rest = ($item->stock - (int)$products[$item->id_product_detail]['qty']);
            array_push($query, "WHEN {$item->id_product_detail} THEN {$rest}");
        }

        $query = implode(' ', $query);
        $ids = implode(',', $ids);
        return $this->db->query("UPDATE product_details SET stock = (CASE id_product_detail {$query} END) WHERE id_product_detail IN ({$ids})");
    }

    
    
    
}
