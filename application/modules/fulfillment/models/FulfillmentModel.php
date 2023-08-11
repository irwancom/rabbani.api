<?php

class FulfillmentModel extends CI_Model {

    public function __construct() {

        parent::__construct();

        $this->load->database();
    }

    private function updateQuotaBox($codeBox = '', $dataAuth = '') {
        $this->db->set('quota_box', 'quota_box+1', FALSE);
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('code_box', $codeBox);
        $this->db->where('id_auth', $dataAuth['id_auth']);
        $this->db->update('fulfillment_box');
    }

    private function updateStockProduct($skuBarcode) {
        $this->db->set('stock', 'stock+1', FALSE);
        $this->db->where('sku_code', $skuBarcode);
        $this->db->update('product_details');
    }

    private function dataProduct($skuBarcode = '') {
        $this->db->select('b.sku_code, a.product_name, b.variable');
        $this->db->join('product_details as b', 'b.id_product = a.id_product', 'left');
//        $this->db->join('product as c', 'c.id_product = b.id_product', 'left');
        $this->db->where('b.sku_code', $skuBarcode);
        $datax = $this->db->get('product as a')->result();
        return $datax;
    }

    public function inBOX($dataAuth = '', $codeBox = '', $sku = '') {
        $dataAuth = $dataAuth['data'];
//        echo $dataAuth['id_auth'] . $codeBox . $sku;
        $getBox = $this->db->get_where('fulfillment_box', array('id_auth' => $dataAuth['id_auth'], 'code_box' => $codeBox))->result();
//        print_r($getBox);
//        exit;
        if (empty($getBox)) {
            $data = array(
                'id_auth' => $dataAuth['id_auth'],
                'created_by' => $dataAuth['id'],
                'code_box' => $codeBox,
                'created_at' => date('Y-m-d H:i:s')
            );
            $this->db->insert('fulfillment_box', $data);
            $getBox = $this->db->insert_id();
        } else {
            $getBox = $getBox['0']->id_box;
        }

//        print_r($getBox[0]);
//        exit;
        $this->db->where('id_auth', $dataAuth['id_auth']);
        $this->db->where('iduser', $dataAuth['id']);
        $this->db->where('id_box', $getBox);
        $this->db->where('code_box', $codeBox);
        $this->db->where('skubarcode', $sku);
        $query = $this->db->get('fulfillment_in_box')->result();
        if (empty($query)) {
            $data = array(
                'id_auth' => $dataAuth['id_auth'],
                'iduser' => $dataAuth['id'],
                'id_box' => $getBox,
                'code_box' => $codeBox,
                'skubarcode' => $sku,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );
            $this->db->insert('fulfillment_in_box', $data);
            $this->updateQuotaBox($codeBox, $dataAuth);
            $msg = 'success add';
        } else {
            $data = $query[0];
            $this->db->set('qty', 'qty+1', FALSE);
            $this->db->where('idinbox', $data->idinbox);
            $this->db->where('id_auth', $dataAuth['id_auth']);
            $this->db->update('fulfillment_in_box');

            $this->updateQuotaBox($codeBox, $dataAuth);
            $msg = 'success update';
        }

        $this->updateStockProduct($sku);
        $datax = $this->dataProduct($sku);

        $response = array('message' => $msg, 'dataProduct' => $datax);

        return $response;
    }

    public function inRACK($dataAuth = '', $codeBox = '', $codeRack = '') {
        $dataAuth = $dataAuth['data'];
        $getRack = $this->db->get_where('fulfillment_rack', array('id_auth' => $dataAuth['id_auth'], 'code_rack' => $codeRack))->result();
        if (empty($getRack)) {
            $data = array(
                'id_auth' => $dataAuth['id_auth'],
                'iduser' => $dataAuth['id'],
                'code_rack' => $codeRack,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );
            $this->db->insert('fulfillment_rack', $data);
        } else {
            $this->db->set('updated_at', date('Y-m-d H:i:s'));
            $this->db->where('code_rack', $codeRack);
            $this->db->where('id_auth', $dataAuth['id_auth']);
            $this->db->update('fulfillment_rack');
        }

        $this->db->set('code_rack', $codeRack);
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->where('code_box', $codeBox);
        $this->db->where('id_auth', $dataAuth['id_auth']);
        $this->db->update('fulfillment_in_box');

        $response = array('success update');

        return $response;
    }

    public function stockOPNAME($dataAuth = '', $codeBox = '', $codeRack = '', $skuBarcode = '') {
        $dataAuth = $dataAuth['data'];
        $getRack = $this->db->get_where('fulfillment_stock_opname', array('id_auth' => $dataAuth['id_auth'], 'codeSO' => 'SO' . date('Ymd'), 'code_box' => $codeBox, 'code_rack' => $codeRack, 'skuBarcode' => $skuBarcode))->result();
        if (empty($getRack)) {
            $data = array(
                'id_auth' => $dataAuth['id_auth'],
                'id_user' => $dataAuth['id'],
                'codeSO' => 'SO' . date('Ymd'),
                'code_box' => $codeBox,
                'code_rack' => $codeRack,
                'skuBarcode' => $skuBarcode,
                'start_date' => date('Y-m-d H:i:s'),
                'finish_date' => date('Y-m-d H:i:s'),
            );
            $this->db->insert('fulfillment_stock_opname', $data);
            $msg = 'sucess add';
        } else {
            $data = array(
                'id_auth' => $dataAuth['id_auth'],
                'id_user' => $dataAuth['id'],
                'code_box' => $codeBox,
                'code_rack' => $codeRack,
                'skuBarcode' => $skuBarcode,
                'finish_date' => date('Y-m-d H:i:s'),
            );
            $this->db->set($data);
            $this->db->set('qty', 'qty+1', FALSE);
            $this->db->where('code_box', $codeBox);
            $this->db->where('code_rack', $codeRack);
            $this->db->where('id_auth', $dataAuth['id_auth']);
            $this->db->update('fulfillment_stock_opname');
            $msg = 'success update';
        }

        $datax = $this->dataProduct($skuBarcode);
        $response = array('message' => $msg, 'dataProduct' => $datax);

        return $response;
    }

}
