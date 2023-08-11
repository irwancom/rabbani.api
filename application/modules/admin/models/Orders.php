<?php defined('BASEPATH') OR exit('No direct script access allowed');


use Andri\Engine\Admin\Domain\Order\Contracts\OrderRepositoryContract;

class Orders extends CI_Model implements OrderRepositoryContract {

    const TABLE = 'orders';
    const TABLE_DETAIL = 'order_details';
    const TABLE_ORIGIN = 'order_origins';
    const TABLE_DESTINATION = 'order_destinations';
    const TABLE_PAYMENT = 'order_payments';
    const TABLE_SHIPPING = 'order_shippings';
    const ORDER_STATUS = [
        'waiting_payment',
        'open',
        'in_process',
        'in_shipment',
        'done',
        'canceled'
    ];

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function getQuery ($options) {
        $this->extractQuery($options);
        $this->db->select('
            orders.id_order as id_order,
            orders.id_auth as id_auth,
            orders.id_auth_user as id_auth_user,
            orders.id_cart as id_cart,
            orders.invoice_number as invoice_number,
            orders.notes as notes,
            orders.no_awb as no_awb,
            orders.total_price as total_price,
            orders.total_qty as total_qty,
            orders.total_weight as total_weight,
            orders.total_length as total_length,
            orders.total_width as total_width,
            orders.total_height as total_height,
            orders.order_source as order_source,
            orders.voucher_code as voucher_code,
            orders.status as status,
            orders.final_amount as final_amount,
            origins.province_name as origin_province_name,
            origins.city_name as origin_city_name,
            origins.suburb_name as origin_suburb_name,
            origins.area_name as origin_area_name,
            origins.name as origin_name,
            origins.address as origin_address,
            destinations.province_name as destination_province_name,
            destinations.city_name as destiantion_city_name,
            destinations.suburb_name as destiantion_suburb_name,
            destinations.area_name as destination_area_name,
            destinations.name as destination_name,
            destinations.address as destination_address,
            destinations.receiver_name as destination_receiver_name,
            destinations.receiver_phone as destination_receiver_phone,
            members.name as member_name,
            shippings.shipper_name as shipping_shipper_name,
            shippings.shipper_rate_name as shipping_shipper_rate_name,
            payments.xendit_status as payment_xendit_status,
            orders.created_at as created_at,
            orders.updated_at as updated_at,
            orders.deleted_at as deleted_at
        ');
        $this->db->join('order_origins as origins', 'orders.invoice_number = origins.invoice_number', 'left');
        $this->db->join('order_destinations as destinations', 'orders.invoice_number = destinations.invoice_number', 'left');
        $this->db->join('order_payments as payments', 'orders.invoice_number = payments.invoice_number', 'left');
        $this->db->join('order_shippings as shippings', 'orders.invoice_number = shippings.invoice_number', 'left');
        $this->db->join('members','members.id_member = destinations.id_member');
    }

    
    public function list(array $options) {
        $this->getQuery($options);
        $result = $this->db->get(self::TABLE)->result();
        return $result;
    }


    public function totalItem($options) {
        // unset($options['perPage']);
        $this->getQuery($options);
        $this->db->from(self::TABLE);
        $result = $this->db->count_all_results();
        return $result;
    }

    private function extractQuery($options) {
        $default = ['q', 'sorted', 'perPage', 'page'];
        foreach($options as $key => $value) {
            if (!in_array($key, $default) && !empty($value)) {
                $key = str_replace('-','.', $key);
                $this->db->where($key, $value);
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

    public function detailByFields(array $condition, bool $with_detail = false) {
        if (!key_exists('deleted_at', $condition)) {
            $options['deleted_at'] = null;
        }

        $order = $this->db->get_where(self::TABLE, $condition)->result();
        $order = $order ? $order[0] : null;
        
        if ($order && $with_detail) {
            $order = (array)$order;
            $order['details'] = $this->details($order);
            $order['origin'] = $this->originByInvoiceNumber($order['invoice_number']);
            $order['destination'] = $this->destinationByInvoiceNumber($order['invoice_number']);
            $order['payment'] = $this->paymentByInvoiceNumber($order['invoice_number']);
            $order['shipping'] = $this->shippingByInvoiceNumber($order['invoice_number']);
        }

        return $order;
    }


    public function details(array $order) {
        $invoiceNumber = $order['invoice_number'];
        $result = $this->db
                        ->get_where(self::TABLE_DETAIL, ['invoice_number' => $invoiceNumber])
                        ->result();
        return $result;
    }


    public function detailItemByFields(array $condition) {
        $detail = $this->db->get_where(self::TABLE_DETAIL, $condition)->result();
        return $detail ? (array)$detail[0] : null;
    }


    public function update(array $args, array $data) {
        $exceptionKey = [
            'id',
            'id_auth',
            'created_at',
            'invoice_number'
        ];

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        foreach ($data as $key => $value) {
            if ($key == 'status' && !in_array($value, self::ORDER_STATUS))
                return false;
            if (!in_array($key, $exceptionKey))
                $this->db->set($key, $value);
        }

        foreach ($args as $key => $value) {
            $this->db->where($key, $value);
        }

        $result = $this->db->update(self::TABLE);
        
        return $result;
    }

    public function store(array $data, $carts, $origin = null, $destination = null, $shipping = null, $payment = null) {
        unset($data['discount_noted']);
        unset($data['member']);
        unset($data['cart']);

        $date = date('Y-m-d h:i:s');

        $data['created_at'] = $date;
        $data['updated_at'] = $date;

        if (!isset($data['status']) || in_array($data['status'], self::ORDER_STATUS)) {
            $data['status'] = 'waiting_payment';
        }

        $details = [];
        foreach ($carts as $cart) {
            $cart = [
                'invoice_number'    => $data['invoice_number'], 
                'id_product_detail' => $cart['id_product_detail'],
                'discount_type'     => $cart['discount_type'],
                'discount_source'   => $cart['discount_source'],
                'discount_value'    => $cart['discount_value'],
                'price'             => $cart['price'],
                'qty'               => $cart['qty'],
                'total'             => $cart['total'],
                'final_amount'      => $cart['final_amount'],
                'description'       => $cart['description'],
                'product_name'      => $cart['product_name'],
                'product_weight'    => $cart['product_weight'],
                'product_length'    => $cart['product_length'],
                'product_width'     => $cart['product_width'],
                'product_height'    => $cart['product_height']
            ];
            $details[] = $cart;
        }

        $this->db->trans_start();
            $result = $this->db->insert(self::TABLE, $data);
            $this->db->insert_batch(self::TABLE_DETAIL, $details);
            
            if (!empty($origin))
                $this->originStore($data['invoice_number'], $origin);


            if (!empty($destination))
                $this->destinationStore($data['invoice_number'], $destination);


            if (!empty($shipping))
                $this->shippingStore($data['invoice_number'], $shipping);

            if (!empty($payment))
                $this->paymentStore($data['invoice_number'], $payment);

        $this->db->trans_complete();
        return $result;
    }


    // ORIGIN
    public function originStore($invoiceNumber, array $data) {
        $data['invoice_number'] = $invoiceNumber;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = $this->db->insert(self::TABLE_ORIGIN, $data);
    }

    public function originUpdate($invoiceNumber, array $data) {
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('invoice_number', $invoiceNumber);
        foreach ($data as $key => $value) {
            $this->db->set($key, $value);
        }

        $result = $this->db->update(self::TABLE_ORIGIN);
        return $result;
    }

    public function originByInvoiceNumber($invoiceNumber) {
        $condition = ['invoice_number' => $invoiceNumber];
        $origin = $this->db->get_where(self::TABLE_ORIGIN, $condition)->result();
        return $origin ? (array)$origin[0] : null;
    }

    // DESTINATION
    public function destinationStore($invoiceNumber, array $data) {
        $data['invoice_number'] = $invoiceNumber;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = $this->db->insert(self::TABLE_DESTINATION, $data);
    }

    public function destinationUpdate($invoiceNumber, array $data) {
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('invoice_number', $invoiceNumber);
        foreach ($data as $key => $value) {
            $this->db->set($key, $value);
        }

        $result = $this->db->update(self::TABLE_DESTINATION);
        return $result;
    }

    public function destinationByInvoiceNumber($invoiceNumber) {
        $condition = ['invoice_number' => $invoiceNumber];
        $destination = $this->db->get_where(self::TABLE_DESTINATION, $condition)->result();
        return $destination ? (array)$destination[0] : null;
    }

    // SHIPPING
    public function shippingStore($invoiceNumber, array $data) {
        $data['invoice_number'] = $invoiceNumber;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = $this->db->insert(self::TABLE_SHIPPING, $data);
    }

    public function shippingUpdate($invoiceNumber, array $data) {
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('invoice_number', $invoiceNumber);
        foreach ($data as $key => $value) {
            $this->db->set($key, $value);
        }

        $result = $this->db->update(self::TABLE_SHIPPING);
        return $result;
    }

    public function shippingByInvoiceNumber($invoiceNumber) {
        $condition = ['invoice_number' => $invoiceNumber];
        $shipping = $this->db->get_where(self::TABLE_SHIPPING, $condition)->result();
        return $shipping ? (array)$shipping[0] : null;
    }

    // PAYMENT
    public function paymentStore($invoiceNumber, array $data) {
        $data['invoice_number'] = $invoiceNumber;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $result = $this->db->insert(self::TABLE_PAYMENT, $data);
    }

    public function paymentUpdate($invoiceNumber, array $data) {
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('invoice_number', $invoiceNumber);
        foreach ($data as $key => $value) {
            $this->db->set($key, $value);
        }

        $result = $this->db->update(self::TABLE_PAYMENT);
        return $result;
    }

    public function paymentByInvoiceNumber($invoiceNumber) {
        $condition = ['invoice_number' => $invoiceNumber];
        $payment = $this->db->get_where(self::TABLE_PAYMENT, $condition)->result();
        return $payment ? (array)$payment[0] : null;
    }
}
