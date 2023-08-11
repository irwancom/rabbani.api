<?php

class EceModel extends CI_Model {

    public function __construct() {

        parent::__construct();

        $this->load->database();
    }

    public function verfyApi($KeyCode = '', $token = '') {
        $query = $this->db->get_where('auth_api', array('keyCode' => $KeyCode, 'secret' => $token))->result();

        if (!empty($query)) {
            $response['status'] = 200;
            $response['error'] = FALSE;
            $response['dataSecret'] = $query;

            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function getOrdersDetails($noOrders = '') {
        $whereOr = [
            'salesorder_no' => $noOrders,
            'tracking_no' => $noOrders,
            'tracking_number' => $noOrders
        ];
        $this->db->or_like($whereOr);
        $query = $this->db->get('transaction')->result(); 

        if (!empty($query)) {
            $query2 = $this->db->get_where('transaction_item', array('salesorder_id' => $query[0]->salesorder_id))->result();

            $response['dataOrders'] = $query;
            $response['dataDetailsOrders'] = $query2;
        } else {
            $response['message'] = 'formating data invalid';
        }
        return $response;
    }

    //for QC v2
    public function getDataQC($noOrders = '') {
        $whereOr = [
            'salesorder_no' => $noOrders,
            'tracking_no' => $noOrders,
            'tracking_number' => $noOrders
        ];
        $this->db->select('id, salesorder_id, salesorder_no, customer_name, transaction_date, is_tax_included, sub_total, total_disc, total_tax, grand_total, ref_no, payment_method, channel_status, shipping_full_name, shipping_phone, shipping_address, shipping_area, shipping_city, shipping_province, shipping_post_code, tracking_number, courier, username, source_name, store_name, location_name, shipper, tracking_no, status');
        $this->db->or_like($whereOr);
        $query = $this->db->get('orders')->result();

        if (!empty($query)) {
            $this->db->select('salesorder_id, salesorder_detail_id, description, price, qty_in_base, disc, disc_amount, amount, qty, status, item_code, item_name, sell_price, original_price');
            $query2 = $this->db->get_where('order_details', array('salesorder_id' => $query[0]->salesorder_id))->result();

            $response['dataOrders'] = $query;
            $response['dataDetailsOrders'] = $query2;
        } else {
            $response['message'] = 'formating data invalid';
        }
        return $response;
    }

    //for Get Member Digital
    public function getDataMD($idPhone = '') {
        $whereOr = [
            // 'phone_number' => $idPhone,
            'member_code' => $idPhone
        ];
        // $this->db->select('id, name, member_code');
        $this->db->select('id, name, phone_number, member_code');
        $this->db->or_like($whereOr);
        $query = $this->db->get('member_digitals')->result();
        // print_r($query);
        // exit;
        if(!empty($query)){
            $query = array(
                'dataMembers' => $query,
                'dataVoucher' => '30B-VWK'.substr($query[0]->member_code,-10)
            );
        }else{
            $query = array(
                'status' => 'fail'
            );
        }       

        return $query;
    }

    //for create member digital RMB
    public function createMDRMB($phone = '', $name = '', $idMember = '') {
        $data = array(
            'name' => $name,
            'phone_number' => $phone,
            'created_at' => date('Y-m-d H:i:s'),
            'member_code' => $idMember
        );
        
        $this->db->insert('member_digitals', $data);

        if(!empty($query)){
            $query = array(
                'dataMembers' => $query,
                'dataVoucher' => '30B-VWK'.substr($query[0]->member_code,-10)
            );
        }else{
            $query = array(
                'status' => 'fail'
            );
        }       

        return $query;
    }

    public function dashboard($day='') {
        $this->db->select('count(*) as ttlInvoice, format(sum(sub_total),0) as ttlGmv, sum(total_disc) as ttlDisc, format(sum(grand_total),0) as ttlNmv, upper(channel_status) as status');
        $this->db->group_by("channel_status");
        $this->db->order_by('ttlNmv', 'DESC');
//        $query = $this->db->get_where('transaction', 'DATE(created_date) BETWEEN CURDATE() - 1 AND CURDATE()')->result();
        $query = $this->db->get_where('transaction', 'DATE(created_date) = CURDATE()')->result();

        if (!empty($query)) {
            $this->db->select('count(*) as ttl, upper(shipper) as expedition');
            $this->db->group_by("shipper");
            $this->db->order_by('ttl', 'DESC');
//            $query2 = $this->db->get_where('transaction', 'DATE(created_date) BETWEEN CURDATE() - 1 AND CURDATE()')->result();
//            $query2 = $this->db->get_where('transaction', 'DATE(created_date) = CURDATE()')->result();
            $query2 = $this->db->get_where('transaction', 'DATE(created_date) BETWEEN CURDATE() - '.$day.' AND CURDATE()')->result();
            
            $this->db->select('count(*) as ttl, upper(source_name) as marketplace, format(sum(sub_total),0) as ttlGmv, sum(total_disc) as ttlDisc, format(sum(grand_total),0) as ttlNmv');
            $this->db->group_by("source_name");
            $this->db->order_by('ttl', 'DESC');
//            $query3 = $this->db->get_where('transaction', 'DATE(created_date) BETWEEN CURDATE() - 1 AND CURDATE()')->result();
//            $query3 = $this->db->get_where('transaction', 'DATE(created_date) = CURDATE()')->result();
            $query3 = $this->db->get_where('transaction', 'DATE(created_date) BETWEEN CURDATE() - '.$day.' AND CURDATE()')->result();
            
            $this->db->select('count(*) as ttl, upper(source_name) as marketplace, format(sum(sub_total),0) as ttlGmv, sum(total_disc) as ttlDisc, format(sum(grand_total),0) as ttlNmv, upper(channel_status) as status');
            $this->db->group_by("source_name");
            $this->db->group_by("channel_status");
            $this->db->order_by('ttl', 'DESC');
//            $query3 = $this->db->get_where('transaction', 'DATE(created_date) BETWEEN CURDATE() - 1 AND CURDATE()')->result();
//            $query4 = $this->db->get_where('transaction', 'DATE(created_date) = CURDATE()')->result();
            $query4 = $this->db->get_where('transaction', 'DATE(created_date) BETWEEN CURDATE() - '.$day.' AND CURDATE()')->result();
            
            $response['dataOrders'] = $query;
            $response['dataExpedition'] = $query2;
            $response['dataMarketplace'] = $query3;
            $response['dataMarketplaceDetails'] = $query4;
        } else {
            $response['message'] = 'formating data invalid or data empty';
        }
        return $response;
    }
    
    public function report($day='') {
        $this->db->select('a.id, a.salesorder_id, a.salesorder_no, a. transaction_date, upper(a.customer_name) as customer_name, upper(a.channel_status) as channel_status, upper(a.shipping_full_name) as shipping_full_name, upper(a.shipping_area) as shipping_area, upper(a.shipping_city) as shipping_city, upper(a.shipping_province) as shipping_province, a.tracking_number, upper(a.courier) as courier, a.source_name, a.store_name, b.item_code, b.item_name, b.qty, b.original_price');
        $this->db->join('transaction_item as b', 'a.salesorder_id = b.salesorder_id', 'left');
        $this->db->order_by('a.id', 'DESC');
        $query = $this->db->get_where('transaction as a', 'DATE(created_date) BETWEEN CURDATE() - '.$day.' AND CURDATE()')->result();
//        $query = $this->db->get_where('transaction as a', 'DATE(a.created_date) = CURDATE()')->result();

        if (!empty($query)) {
            $response['totalData'] = count($query);
            $response['dataOrders'] = $query;
        } else {
            $response['message'] = 'formating data invalid or data empty';
        }
        return $response;
    }

    public function getDataCostumerJubelio($ids='',$idf='') {
        $this->db->select('id, channel_status, transaction_date, shipping_full_name, shipping_phone, shipping_province, shipping_city, shipping_area');
        $this->db->where('id BETWEEN "'. $ids . '" and "'. $idf .'"');
        $query = $this->db->get('transaction',10)->result();

        if (!empty($query)) {
            $response['totalData'] = count($query);
            $response['dataOrders'] = $query;
        } else {
            $response['message'] = 'formating data invalid or data empty';
        }
        return $response;
    }

    public function getMemberData($page='') {
        $this->db->select('id, name, phone_number, member_code, point');
        $query = $this->db->get_where('member_digitals', array('point !=' => '0'),400,$page)->result();

        // $response['dataMembers'] = $query;
        return $query;
    }

    public function getProvData() {
        $query = $this->db->get_where('provinces', array('deleted_at =' => null))->result();

        return $query;
    }

    public function getCityData($id='') {
        if(!empty($id)){
            $query = $this->db->get_where('districts', array('id_prov =' => $id, 'deleted_at =' => null))->result();
        }else{
            $query = $this->db->get_where('districts', array('deleted_at =' => null))->result();
        }

        return $query;
    }

}
