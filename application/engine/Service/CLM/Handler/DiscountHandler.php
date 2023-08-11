<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Calculator;

class DiscountHandler {

	private $delivery;
	private $repository;
	private $productDetailHandler;
	private $productHandler;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->productDetailHandler = new ProductDetailHandler($this->repository);
		$this->productHandler = new ProductHandler($this->repository);
	}

	public function setUser ($user) {
		$this->user = $user;
	}

	public function getUser () {
		return $this->user;
	}

	public function setAdmin ($admin) {
		$this->admin = $admin;
	}

	public function getAdmin () {
		return $this->admin;
	}

	public function getDiscountProducts ($filters = null) {
		$args = [];
		if (!empty($this->getAdmin())) {
			$args = [
				'discount_products.id_auth_api' => $this->admin['id_auth']
			];
		} else if (!empty($this->getUser())) {
			$args = [
				'discount_products.id_auth_api' => $this->user['id_auth']
			];
		} else {
			$args = [
				'discount_products.id_auth_api' => 1
			];
		}

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			$args['start_time <='] = $filters['current_time'];
			$args['end_time >='] = $filters['current_time'];
		}

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'discount_products.id',
			'discount_products.start_time',
			'discount_products.end_time',
			'discount_products.id_product',
			'discount_products.discount_type',
			'discount_products.discount_value',
			'discount_products.created_at'
		];
		$orderKey = 'discount_products.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$discountProducts = $this->repository->findPaginated('discount_products', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($discountProducts['result'] as $discount) {
			if ($discount->discount_type == 1) {
				$discount->discount_type_note = 'amount';
			} else if ($discount->discount_type == 2) {
				$discount->discount_type_note = 'percentage';
			}
		}
		$this->delivery->data = $discountProducts;
		return $this->delivery;
	}

	public function getDiscountProduct ($filters = null) {
		$args = [];
		
		if (isset($filters['id_product']) && !empty($filters['id_product'])) {
			$args['id_product'] = $filters['id_product'];
		}

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			$args['start_time <='] = $filters['current_time'];
			$args['end_time >='] = $filters['current_time'];
		}
		$discount = $this->repository->findOne('discount_products', $args);
		$this->delivery->data = $discount;
		return $this->delivery;
	}

	public function getDiscountProductDetail ($filters = null) {
		$args = [];
		
		if (isset($filters['id_product']) && !empty($filters['id_product'])) {
			$args['discount_products.id_product'] = $filters['id_product'];
		}

		if (isset($filters['id_product_detail']) && !empty($filters['id_product_detail'])) {
			$args['discount_product_details.id_product_detail'] = $filters['id_product_detail'];
		}

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			$args['discount_products.start_time <='] = $filters['current_time'];
			$args['discount_products.end_time >='] = $filters['current_time'];
		}

		$join = [
			'discount_products' => 'discount_products.id = discount_product_details.id_discount_product'
		];

		$select = [
			'discount_product_details.id',
			'discount_product_details.id_discount_product',
			'discount_products.id_product',
			'discount_product_details.id_product_detail',
			'discount_products.start_time',
			'discount_products.end_time',
			'discount_product_details.discount_type',
			'discount_product_details.discount_value',
			'discount_product_details.created_at'
		];
		$discount = $this->repository->findOne('discount_product_details', $args, null, $join, $select);
		$this->delivery->data = $discount;
		return $this->delivery;
	}

	public function createDiscountProduct ($payload) {
		if (!empty($this->getAdmin())) {
			$payload['id_auth_api'] = $this->admin['id_auth'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['start_time']) || empty($payload['start_time'])) {
			$this->delivery->addError(400, 'Start time is required');
		}

		if (!isset($payload['end_time']) || empty($payload['end_time'])) {
			$this->delivery->addError(400, 'End time is required');
		}

		if (!isset($payload['id_product']) || empty($payload['id_product'])) {
			$this->delivery->addError(400, 'Product is required');
		}


		if (!isset($payload['discount_type']) || empty($payload['discount_type']) || !in_array($payload['discount_type'], [1,2])) {
			$this->delivery->addError(400, 'Discount type is required');
		}

		if (!isset($payload['discount_value'])) {
			$this->delivery->addError(400, 'Discount value is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$product = $this->productHandler->getProduct(['id_product' => $payload['id_product']]);
		if (empty($product->data)) {
			$this->delivery->addError(400, 'Product is required');
			return $this->delivery;
		}

		$findDiscountIntime = $this->getDiscountProduct(['id_product' => $payload['id_product'], 'current_time' => $payload['start_time']]);
		$discountIntime = $findDiscountIntime->data;
		if (!empty($discountIntime)) {
			$this->delivery->addError(400, 'Discount product in this timeline is already exists');
			return $this->delivery;
		}

		try {
			$payload['discount_value'] = (int)$payload['discount_value'];
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('discount_products', $payload);
			$discountProduct = $this->repository->findOne('discount_products', ['id' => $action]);
			$this->delivery->data = $discountProduct;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteDiscountProduct ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('discount_products', $payload, ['id' => $id]);
			$result = $this->repository->findOne('discount_products', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function createDiscountProductDetail ($payload) {
		if (!empty($this->getAdmin())) {
			// $payload['id_auth_api'] = $this->admin['id_auth'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['id_discount_product']) || empty($payload['id_discount_product'])) {
			$this->delivery->addError(400, 'Discount product is required');
		}

		if (!isset($payload['id_product_detail']) || empty($payload['id_product_detail'])) {
			$this->delivery->addError(400, 'Product detail is required');
		}


		if (!isset($payload['discount_type']) || empty($payload['discount_type']) || !in_array($payload['discount_type'], [1,2])) {
			$this->delivery->addError(400, 'Discount type is required');
		}

		if (!isset($payload['discount_value'])) {
			$this->delivery->addError(400, 'Discount value is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$findDiscount = $this->getDiscountProduct(['id' => $payload['id_discount_product']]);
		if (empty($findDiscount->data)) {
			$this->delivery->addError(400, 'Discount product is required');
			return $this->delivery;
		}

		$findProductDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $payload['id_product_detail']]);
		if (empty($findProductDetail->data)) {
			$this->delivery->addError(400, 'Product detail is required');
			return $this->delivery;
		}

		if ($findProductDetail->data->id_product != $findDiscount->data->id_product) {
			$this->delivery->addError(400, 'Product detail reference is wrong');
			return $this->delivery;
		}

		try {
			$payload['discount_value'] = (int)$payload['discount_value'];
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');

			$findDiscountProductDetail = $this->repository->findOne('discount_product_details', ['id_discount_product' => $payload['id_discount_product'], 'id_product_detail' => $payload['id_product_detail']]);
			if (empty($findDiscountProductDetail)) {
				$action = $this->repository->insert('discount_product_details', $payload);
				$discountProduct = $this->repository->findOne('discount_product_details', ['id' => $action]);
			} else {
				$action = $this->repository->update('discount_product_details', $payload, ['id' => $findDiscountProductDetail->id]);
				$discountProduct = $this->repository->findOne('discount_product_details', ['id' => $action]);
			}

			$this->delivery->data = $discountProduct;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}	

		$this->delivery->data = 'ok';
		return $this->delivery;
	}

}