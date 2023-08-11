<?php

use GuzzleHttp\Client;

class Mpshopee extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('shopee');

    }

    public function getShopInfo () {
    	$resp = $this->shopee->getShopInfo();

        $data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getShopCategories () {
    	$resp = $this->shopee->getShopCategories();

    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function addShopCategory () {
    	$name = $this->input->post('name');

    	$resp = $this->shopee->addShopCategory( $name);

    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function uploadImg () {
    	$images = $this->input->post('images');

    	$resp = $this->shopee->uploadImg( $images);

    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getCategoriesByCountry () {
    	$language = $this->input->post('language');

    	$resp = $this->shopee->getCategoriesByCountry( $language);

    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getAttributes () {
    	$categoryId = $this->input->post('category_id');
    	$language = $this->input->post('language');

    	$resp = $this->shopee->getAttributes( $categoryId, $language);

    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getLogistics () {
    	$resp = $this->shopee->getLogistics();

        $data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function addItem () {
    	$categoryId = $this->input->post('category_id');
    	$name = $this->input->post('name');
    	$description = $this->input->post('description');
    	$price = $this->input->post('price');
    	$stock = $this->input->post('stock');
    	$itemSku = $this->input->post('item_sku');
    	$images = $this->input->post('images');
    	$attributes = $this->input->post('attributes');
    	$logistics = $this->input->post('logistics');
    	$weight = $this->input->post('weight');
    	$packageLength = $this->input->post('package_length');
    	$packageWidth = $this->input->post('package_width');
    	$packageHeight = $this->input->post('package_weight');
    	$daysToShip = $this->input->post('days_to_ship');
    	$wholesales = $this->input->post('wholesales');
    	$sizeChart = $this->input->post('size_chart');
    	$condition = $this->input->post('condition');
    	$status = $this->input->post('status');
    	$isPreOrder = $this->input->post('is_pre_order');
    	
    	$resp = $this->shopee->addItem( $categoryId, $name, $description, $price, $stock, $itemSku, $images, $attributes, $logistics, $weight, $packageLength, $packageWidth, $packageHeight, $daysToShip, $wholesales, $sizeChart, $condition, $status, $isPreOrder);
    	
    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function updateStock () {
    	$itemId = $this->input->post('item_id');
    	$stock = $this->input->post('stock');

    	$resp = $this->shopee->updateStock( $itemId, $stock);
    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getOrdersList () {
    	$createTimeFrom = $this->input->post('create_time_from');
    	$createTimeTo = $this->input->post('create_time_to');
    	$updateTimeFrom = $this->input->post('update_time_from');
    	$updateTimeTo = $this->input->post('update_time_to');
    	$paginationEntriesPerPage = $this->input->post('pagination_entries_per_page');
    	$paginationOffset = $this->input->post('pagination_offset');

    	$resp = $this->shopee->getOrdersList( $createTimeFrom, $createTimeTo, $updateTimeFrom, $updateTimeTo, $paginationEntriesPerPage, $paginationOffset);
    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getOrdersByStatus () {
    	$orderStatus = $this->input->post('order_status');
    	$createTimeFrom = $this->input->post('create_time_from');
    	$createTimeTo = $this->input->post('create_time_to');
    	$updateTimeFrom = $this->input->post('update_time_from');
    	$updateTimeTo = $this->input->post('update_time_to');
    	$paginationEntriesPerPage = $this->input->post('pagination_entries_per_page');
    	$paginationOffset = $this->input->post('pagination_offset');

    	$resp = $this->shopee->getOrdersByStatus( $orderStatus, $createTimeFrom, $createTimeTo, $updateTimeFrom, $updateTimeTo, $paginationEntriesPerPage, $paginationOffset);
    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getOrderDetails () {
    	$ordersnList = $this->input->post('ordersn_list');

    	$resp = $this->shopee->getOrderDetails( $ordersnList);
    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }

    public function getOrderLogistics () {
    	$ordersn = $this->input->post('ordersn');

    	$resp = $this->shopee->getOrderLogistics( $ordersn);
    	$data = [
            'success' => true,
            'shopeeResponse ' => $resp
        ];
        return $this->returnJSON($data);
    }


    /*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

}