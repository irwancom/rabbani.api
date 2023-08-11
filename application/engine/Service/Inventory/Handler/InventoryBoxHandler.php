<?php
namespace Service\Inventory\Handler;

use Service\Entity;
use Service\Delivery;

/**
 * 1 Box bisa diluar room atau di dalam room 
 **/
class InventoryBoxHandler {

	private $auth;
	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function getBoxes ($filters = null) {
		$args = [
			'inventory_boxes.id_auth_api' => $this->auth['id_auth']
		];

		if (isset($filters['code']) && !empty($filters['code'])) {
			$args['code'] = $filters['code'];
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			$args['code'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
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
			'inventory_boxes.id',
			'inventory_boxes.id_inventory_room',
			'inventory_boxes.code',
			'inventory_boxes.box_check_in',
			'inventory_boxes.created_at',
			'inventory_boxes.updated_at',
		];
		$orderKey = 'inventory_boxes.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('inventory_boxes', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getBox ($filters = null) {
		$box = $this->repository->findOne('inventory_boxes', $filters);

		if (!empty($box->id_inventory_room)) {
			$roomHandler = new InventoryRoomHandler($this->repository, $this->auth);
			$roomResult = $roomHandler->getRoom(['id' => $box->id_inventory_room]);
			$box->inventory_room = $roomResult->data;
		}

		if (!empty($box)) {
			$productHandler = new InventoryProductHandler($this->repository, $this->auth);
			$productResult = $productHandler->getProducts(['id_inventory_box' => $box->id], false);
			$box->inventory_boxes = $productResult->data;
		}

		$this->delivery->data = $box;
		return $this->delivery;
	}

	public function createBox ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];

		if (!empty($payload['id_inventory_room'])) {
			$existsRoom = $this->repository->findOne('inventory_rooms', ['id' => $payload['id_inventory_room'], 'id_auth_api' => $this->auth['id_auth']]);
			if (empty($existsRoom)) {
				$this->delivery->addError(409, 'Room not found.');
				return $this->delivery;
			}

			$boxInThisRoom = $this->repository->find('inventory_boxes', ['id_inventory_room' => $payload['id_inventory_room'], 'id_auth_api' => $this->auth['id_auth']]);
			if (!empty($boxInThisRoom)) {
				$this->delivery->addError(409, sprintf('The room already has a box in it.'));
				return $this->delivery;
			}
			$payload['box_check_in'] = date('Y-m-d H:i:s');
		} else {
			$payload['id_inventory_room'] = null;
		}

		if (!isset($payload['code']) || empty($payload['code'])) {
			$payload['code'] = sprintf('AB%s%s', $this->auth['id_auth'], time());
		}

		$existsCode = $this->repository->findOne('inventory_boxes', ['code' => $payload['code'], 'id_auth_api' => $this->auth['id_auth']]);
		if (!empty($existsCode)) {
			$this->delivery->addError(400, 'Please use another code.');
			return $this->delivery;
		}

		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('inventory_boxes', $payload);
		$result = $this->repository->findOne('inventory_boxes', ['id' => $action]); 
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateBox ($payload, $filters = null) {
		$filters['id_auth_api'] = $this->auth['id_auth'];
		$existsBoxes = $this->repository->find('inventory_boxes', $filters);
		if (empty($existsBoxes)) {
			$this->delivery->addError(409, 'No boxes found.');
			return $this->delivery;
		}

		unset($payload['id_auth_api']);

		if (isset($payload['code']) && !empty($payload['code'])) {
			$existsCode = $this->repository->findOne('inventory_boxes', ['code' => $payload['code'], 'id_auth_api' => $this->auth['id_auth']]);
			if (!empty($existsCode)) {
				$this->delivery->addError(400, 'Please use another code.');
				return $this->delivery;
			}
		}

		if (!empty($payload['id_inventory_room'])) {
			$existsRoom = $this->repository->findOne('inventory_rooms', ['id' => $payload['id_inventory_room'], 'id_auth_api' => $this->auth['id_auth']]);
			if (empty($existsRoom)) {
				$this->delivery->addError(409, 'Room not found.');
				return $this->delivery;
			}

			$boxInThisRoom = $this->repository->find('inventory_boxes', ['id_inventory_room' => $payload['id_inventory_room'], 'id_auth_api' => $this->auth['id_auth']]);
			if (!empty($boxInThisRoom)) {
				$this->delivery->addError(409, sprintf('The room already has a box in it.'));
				return $this->delivery;
			}
			$payload['box_check_in'] = date('Y-m-d H:i:s');
		} else {
			$payload['id_inventory_room'] = null;
		}

		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('inventory_boxes', $payload, $filters);
		$result = $this->repository->find('inventory_boxes', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteBox ($filters = null) {
		$filters['id_auth_api'] = $this->auth['id_auth'];
		$existsBoxes = $this->repository->find('inventory_boxes', $filters);
		if (empty($existsBoxes)) {
			$this->delivery->addError(409, 'No boxes found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('inventory_boxes', $payload, $filters);
		$result = $this->repository->find('inventory_boxes', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function generateBox ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];
		$payload['id_inventory_room'] = null;
		
		$result = null;
		$currentTime = time();
		if (!isset($payload['qty']) || empty($payload['qty'])) {
			$this->delivery->addError(409, 'Qty should not be empty');
			return $this->delivery;
		}

		if (empty($payload['prefix_box'])) {
			$payload['prefix_box'] = strtoupper(generateRandomString(3));
		}

		$boxCodes = [];
		for ($i = 0; $i < $payload['qty']; $i++) {
			$boxCodes[] = sprintf('%s%s%s', $payload['prefix_box'], $this->auth['id_auth'], str_pad($i+1, 4, '0', STR_PAD_LEFT));
		}

		$boxes = [];
		try {
			$this->repository->beginTransaction();
			// create boxes
			foreach ($boxCodes as $boxCode) {
				$payloadBox = [
					'id_inventory_room' => $payload['id_inventory_room'],
					'code' => $boxCode,
				];
				$boxResult = $this->createBox($payloadBox);
				if ($boxResult->hasErrors()) {
					throw new \Exception('Error create box. Please try again.');
				}
				$boxes[] = $boxResult->data;
			}

			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
				$result = $boxes;
			}
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function isCodeExists ($code) {
		$existsCode = $this->repository->findOne('inventory_boxes', ['code' => $code]);
		if (!empty($existsCode)) {
			return true;
		}
		return false;
	}

	public function scanAction ($code) {
		$result = null;
		try {
			$existsBox = $this->repository->findOne('inventory_boxes', ['code' => $code, 'id_auth_api' => $this->auth['id_auth']]);
			/* $alreadyScanned = $this->repository->findOne('inventory_scans', ['type' => 'inventory_boxes', 'code' => $code, 'id_auth_user' => $this->auth['id'], 'is_moved' => 0]);
			if (!empty($alreadyScanned)) {
				$this->delivery->addError(409, 'Box already scanned');
				return $this->delivery;
			} */
			$this->repository->beginTransaction();
			$scanPayload = [
				'id_auth_user' => $this->auth['id'],
				'type' => 'inventory_boxes',
				'code' => $code,
				'created_at' => date('Y-m-d H:i:s')
			];
			// $action = $this->repository->insert('inventory_scans', $scanPayload);

			$scannedProducts = $this->repository->find('inventory_scans', ['id_auth_user' => $this->auth['id'], 'type' => 'inventory_products', 'is_moved' => 0]);
			if (empty($scannedProducts)) {
				/* $this->delivery->addError(409, 'There is no product scanned');
				return $this->delivery; */
			}
			$productHandler = new InventoryProductHandler($this->repository, $this->auth);
			foreach ($scannedProducts as $scan) {
				// update scanned product
				$actionScan = $this->repository->update('inventory_scans', ['is_moved' => 1], ['id' => $scan->id]);

				// below is minus product from `null` box (-1)
				$payloadProduct = [
					'product_detail_sku_code' => $scan->code,
					'id_inventory_box' => null,
					'qty' => -1
				];
				$minusProductResult = $productHandler->createProduct($payloadProduct);
				if ($minusProductResult->hasErrors()) {
					throw new \Exception('Error scan box. ' . $minusProductResult->getFirstError()['detail']);
				}

				// below is add product to box (+1)
				$payloadProduct = [
					'product_detail_sku_code' => $scan->code,
					'id_inventory_box' => $existsBox->id,
					'qty' => 1
				];
				$productResult = $productHandler->createProduct($payloadProduct);
				if ($productResult->hasErrors()) {
					throw new \Exception('Error scan box. ' . $productResult->getFirstError()['detail']);
				}
				$result[] = $productResult->data;

				$activityHandler = new InventoryActivityHandler($this->repository, $this->auth);
				$activityResult = $activityHandler->createActivity(InventoryActivityHandler::ACTIVITY_TYPE_PRODUCT_TO_BOX, 'inventory_products', $scan->code, 'inventory_boxes', $existsBox->code);
				if ($activityResult->hasErrors()) {
					throw new \Exception('Error create activity.' . $activityResult->getFirstError()['detail']);
				}

			}

			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
			}
			$result = $this->getBox(['code' => $code, 'id_auth_api' => $this->auth['id_auth']]);
			$result = $result->data;
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		
		$this->delivery->data = $result;
		return $this->delivery;
	}


}