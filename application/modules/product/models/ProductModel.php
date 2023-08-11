<?php

class ProductModel extends CI_Model {

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

    public function getCategory($id_auth = '') {
        $this->db->select('id_category, id_parent, category_name, image_path, created_at, updated_at');
        $query = $this->db->get_where('category', array('id_auth' => $id_auth))->result();

        if (!empty($query)) {
            $response['dataCategory'] = $query;
        } else {
            $response['message'] = 'formating data invalid';
        }
        return $response;
    }

    public function getProduct($id_auth = '', $dataView = '', $page = '') {
        $this->db->select('id_category, id_parent, category_name, image_path, created_at, updated_at');
        $query = $this->db->get_where('category', array('id_auth' => $id_auth))->result();

        if (!empty($query)) {
            $response['dataCategory'] = $query;
        } else {
            $response['message'] = 'formating data invalid';
        }
        return $response;
    }

    public function addUpdate($id_auth = '', $sku = '', $product_name = '', $desc = '', $sku_code = '', $variable = '', $price = '') {
        $query = $this->db->get_where('product', array('id_auth' => $id_auth, 'sku' => $sku))->result();
        if (!empty($query)) {
            $data = array(
                'product_name' => $product_name,
                'id_category' => 0,
                'sku' => $sku,
                'desc' => $desc,
                'updated_at' => date('Y-m-d H:i:s')
            );
            $this->db->set($data);
            $this->db->where('id_product', $query[0]->id_product);
            $this->db->update('product');

            $queryPdet = $this->db->get_where('product_details', array('sku_code' => $sku_code, 'id_product' => $query[0]->id_product))->result();
            if (!empty($queryPdet)) {
                $data1 = array(
                    'sku_code' => $sku_code,
                    'sku_barcode' => 'http://barcodes4.me/barcode/c128b/' . $sku_code . '.png',
                    'variable' => $variable,
                    'price' => $price,
                    'stock' => 0
                );
                $this->db->set($data1);
                $this->db->where('id_product_detail', $queryPdet[0]->id_product_detail);
                $this->db->update('product_details');
                $response['message'] = 'success update product & details';
            } elseif (empty($queryPdet)) {
                $data1 = array(
                    'id_product' => $query[0]->id_product,
                    'sku_code' => $sku_code,
                    'sku_barcode' => 'http://barcodes4.me/barcode/c128b/' . $sku_code . '.png',
                    'variable' => $variable,
                    'price' => $price,
                    'stock' => 0
                );
                $this->db->insert('product_details', $data1);
                $response['message'] = 'success update product & add new details';
            }

//            $response['message'] = 'success update product';
        } else {
            $data = array(
                'id_auth' => $id_auth,
                'product_name' => $product_name,
                'id_category' => 0,
                'sku' => $sku,
                'desc' => $desc,
                'created_at' => date('Y-m-d H:i:s')
            );
            $this->db->insert('product', $data);
            $insert_id = $this->db->insert_id();

            $queryPdet = $this->db->get_where('product_details', array('sku_code' => $sku_code, 'id_product' => $insert_id))->result();
            if (empty($queryPdet)) {
                $data1 = array(
                    'id_product' => $insert_id,
                    'sku_code' => $sku_code,
                    'sku_barcode' => 'http://barcodes4.me/barcode/c128b/' . $sku_code . '.png',
                    'variable' => $variable,
                    'price' => $price,
                    'stock' => 0
                );
                $this->db->insert('product_details', $data1);
            }

            $response['message'] = 'success add new product';
        }
        return $response;
    }

}
