<?php
namespace Service\Inventory\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Handler\ProductDetailHandler;

/**
 * 1 Product bisa di dalam box atau diluar
 **/
class InventoryProductHandler {

	private $auth;
	private $delivery;
	private $repository;

	private $productDetailHandler;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->productDetailHandler = new ProductDetailHandler($this->repository);
	}

	public function getProducts ($filters = null, $findPaginated = true) {
		$args = [];
		$argsOrWhere = [];

		if (isset($filters['code']) && !empty($filters['code'])) {
			$args['product_detail_sku_code'] = $filters['code'];
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			$argsOrWhere['product_detail_sku_code'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['inventory_rooms.code'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['inventory_boxes.code'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['product.product_name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		if (isset($filters['id_inventory_box']) && !empty($filters['id_inventory_box'])) {
			$args['id_inventory_box'] = $filters['id_inventory_box'];
		}

		if (isset($filters['in_box']) && !empty($filters['in_box'])) {
			if ($filters['in_box'] == 1) {
				$args['id_inventory_box <>'] = null;
			} else {
				$args['id_inventory_box'] = null;	
			}
		}

		if (isset($filters['has_qty']) && $filters['has_qty'] != "") {
			$args['inventory_products.qty >='] = (int)$filters['has_qty'];
		}

		$args['inventory_boxes.deleted_at'] = null;
		$args['inventory_rooms.deleted_at'] = null;
		$args['inventory_racks.deleted_at'] = null;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'inventory_products.id',
			'inventory_products.product_detail_sku_code',
			'inventory_products.id_inventory_box',
			'inventory_boxes.code as code_inventory_box',
			'inventory_rooms.id as id_inventory_room',
			'inventory_rooms.code as code_inventory_room',
			'inventory_rooms.level as level_inventory_room',
			'inventory_rooms.column as column_inventory_room',
			'inventory_racks.id as id_inventory_rack',
			'inventory_racks.code as code_inventory_rack',
			'inventory_products.product_check_in',
			'inventory_products.qty',
			'product_details.id_product',
			'product.product_name',
			'product.desc',
			'product.weight',
			'product.sku',
			'product_details.sku_barcode',
			'product_details.host_path',
			'product_details.image_path',
			'product_details.is_ready_stock',
			'product_details.variable',
			'product_details.price',
			'product_details.stock',
			'inventory_products.created_at',
			'inventory_products.updated_at',
		];
		$orderKey = 'inventory_products.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'product_details' => [
				'value' =>'product_details.sku_code = inventory_products.product_detail_sku_code' ,
				'type' => 'left'
			],
			'product' => [
				'value' => 'product.id_product = product_details.id_product',
				'type' => 'left'
			],
			'inventory_boxes' => [
				'value' => 'inventory_boxes.id = inventory_products.id_inventory_box',
				'type' => 'left'
			],
			'inventory_rooms' => [
				'value' => 'inventory_rooms.id = inventory_boxes.id_inventory_room',
				'type' => 'left'
			],
			'inventory_racks' => [
				'value' => 'inventory_racks.id = inventory_rooms.id_inventory_rack',
				'type' => 'left'
			]
		];

		$products = null;
		if ($findPaginated) {
			$products = $this->repository->findPaginated('inventory_products', $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue);
			foreach ($products['result'] as $product) {
				$product->qty_details = $this->productDetailHandler->getQtyInProcess($product->product_detail_sku_code);
			}
		} else {
			$products = $this->repository->find('inventory_products', $args, $argsOrWhere, $join);
		}
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getProduct ($filters = null) {
		$product = $this->repository->findOne('inventory_products', $filters);

		if (!empty($product->id_inventory_box)) {
			$boxHandler = new InventoryBoxHandler($this->repository, $this->auth);
			$boxResult = $boxHandler->getBox(['id' => $product->id_inventory_box]);
			$product->inventory_box = $boxResult->data;
		}

		$this->delivery->data = $product;
		return $this->delivery;
	}

	public function createProduct ($payload) {
		if (!isset($payload['qty']) || empty($payload['qty'])) {
			$this->delivery->addError(409, 'Qty should not be empty');
			return $this->delivery;
		}

		if (!isset($payload['id_inventory_box']) || empty($payload['id_inventory_box'])) {
			$payload['id_inventory_box'] = null;
		} else {
			$existsBox = $this->repository->findOne('inventory_boxes', ['id' => $payload['id_inventory_box']]);
			if (empty($existsBox)) {
				$this->delivery->addError(409, 'Inventory box not found.');
				return $this->delivery;
			}
		}

		if (isset($payload['inventory_box_code']) && !empty($payload['inventory_box_code'])) {
			$existsBox = $this->repository->findOne('inventory_boxes', ['code' => $payload['inventory_box_code']]);
			if (empty($existsBox)) {
				$this->delivery->addError(409, 'Inventory box not found.');
				return $this->delivery;
			}
			$payload['id_inventory_box'] = $existsBox->id;
			unset($payload['inventory_box_code']);
		}

		$existsInventoryProduct = $this->repository->findOne('inventory_products', ['product_detail_sku_code' => $payload['product_detail_sku_code'], 'id_inventory_box' => $payload['id_inventory_box']]);
		if (!empty($existsInventoryProduct)) {
			// update qty saja
			$payload['qty'] = $existsInventoryProduct->qty + $payload['qty'];
			$action = $this->updateProduct($payload, ['id' => $existsInventoryProduct->id]);
			return $action;
		}

		$payload['product_check_in'] = date('Y-m-d H:i:s');
		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('inventory_products', $payload);
		$result = $this->repository->findOne('inventory_products', ['id' => $action]); 
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateProduct ($payload, $filters = null) {
		$existsProduct = $this->repository->findOne('inventory_products', $filters);
		if (empty($existsProduct)) {
			$this->delivery->addError(409, 'No product found in the inventory.');
			return $this->delivery;
		}

		if (!isset($payload['id_inventory_box']) || empty($payload['id_inventory_box'])) {
			$payload['id_inventory_box'] = null;
		} else {
			$existsBox = $this->repository->findOne('inventory_boxes', ['id' => $payload['id_inventory_box']]);
			if (empty($existsBox)) {
				$this->delivery->addError(409, 'Inventory box not found.');
				return $this->delivery;
			}
		}

		if (isset($payload['inventory_box_code']) && !empty($payload['inventory_box_code'])) {
			$existsBox = $this->repository->findOne('inventory_boxes', ['code' => $payload['inventory_box_code']]);
			if (empty($existsBox)) {
				$this->delivery->addError(409, 'Inventory box not found.');
				return $this->delivery;
			}
			$payload['id_inventory_box'] = $existsBox->id;
			unset($payload['inventory_box_code']);
		}

		if (isset($payload['product_detail_sku_code']) && !empty($payload['product_detail_sku_code'])) {
			/* $existsProductDetail = $this->repository->findOne('product_details', ['sku_code' => $payload['product_detail_sku_code']]);
			if (empty($existsProductDetail)) {
				$this->delivery->addError(400, 'Product detail not found.');
				return $this->delivery;
			} */

			if ($payload['product_detail_sku_code'] != $existsProduct->product_detail_sku_code) {
				$this->delivery->addError(400, 'Product detail already check in.');
				return $this->delivery;
			}
		}


		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('inventory_products', $payload, $filters);
		$result = $this->getProduct($filters);
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

	public function deleteProduct ($filters = null) {
		$existsProduct = $this->repository->find('inventory_products', $filters);
		if (empty($existsProduct)) {
			$this->delivery->addError(409, 'No inventory product found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('inventory_products', $payload, $filters);
		$result = $this->repository->find('inventory_products', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function scanAction ($code) {
		$result = null;
		try {
			$this->repository->beginTransaction();
			$scanPayload = [
				'id_auth_user' => $this->auth['id'],
				'type' => 'inventory_products',
				'code' => $code,
				'created_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('inventory_scans', $scanPayload);

			$payload['product_detail_sku_code'] = $code;
			$payload['qty'] = 1;

			$existsProduct = $this->getProduct(['product_detail_sku_code' => $code, 'product_check_in' => null]);
			if (empty($existsProduct->data)) {
				$result = $this->createProduct($payload);
				if ($result->hasErrors()) {
					throw new \Exception('Error scan product. ' . $result->getFirstError()['detail']);
				}
			} else {
				$result = $existsProduct;
			}


			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
			}
			$result = $result->data;
			$scans = $this->repository->find('inventory_scans', ['type' => 'inventory_products' ,'id_auth_user' => $this->auth['id'], 'is_moved' => 0]);
			$amountToAdd = 0;
			foreach ($scans as $scan) {
				if ($scan->code == $code) {
					$amountToAdd++;
				}
			}
			$result->extras = [
				'amount_to_add' => $amountToAdd,
				'history_scans' => $scans
			];
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

}