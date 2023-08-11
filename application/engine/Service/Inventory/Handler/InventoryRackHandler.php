<?php
namespace Service\Inventory\Handler;

use Service\Entity;
use Service\Delivery;

/**
 * 1 Rack memiliki banyak rooms
 **/
class InventoryRackHandler {

	private $auth;
	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function getRacks ($filters = null) {
		$args = [
			'inventory_racks.id_auth_api' => $this->auth['id_auth']
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
			'inventory_racks.id',
			'inventory_racks.code',
			'inventory_racks.maximum_rooms',
			'inventory_racks.total_levels',
			'inventory_racks.total_columns',
			'inventory_racks.created_at',
			'inventory_racks.updated_at',
		];
		$orderKey = 'inventory_racks.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('inventory_racks', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getRack ($filters = null) {
		$rack = $this->repository->findOne('inventory_racks', $filters);
		if (empty($rack)) {
			$this->delivery->addError(400, 'Inventory rack not found');
			return $this->delivery;
		}

		$this->delivery->data = $rack;
		return $this->delivery;
	}

	public function createRack ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];
		if (!isset($payload['code']) || empty($payload['code'])) {
			$payload['code'] = sprintf('A%s%s', $this->auth['id_auth'], time());
		}

		$existsCode = $this->repository->findOne('inventory_racks', ['code' => $payload['code'], 'id_auth_api' => $this->auth['id_auth']]);
		if (!empty($existsCode)) {
			$this->delivery->addError(400, 'Please use another code.');
			return $this->delivery;
		}

		if (!isset($payload['maximum_rooms']) || empty($payload['maximum_rooms'])) {
			$this->delivery->addError(400, 'Please fill maximum rooms');
			return $this->delivery;
		}

		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('inventory_racks', $payload);
		$result = $this->repository->findOne('inventory_racks', ['id' => $action]); 
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateRack ($payload, $filters = null) {
		$filters['id_auth_api'] = $this->auth['id_auth'];
		$existsRacks = $this->repository->find('inventory_racks', $filters);
		if (empty($existsRacks)) {
			$this->delivery->addError(409, 'No racks found.');
			return $this->delivery;
		}

		if (isset($payload['code']) && !empty($payload['code'])) {
			$existsCode = $this->repository->findOne('inventory_racks', ['code' => $payload['code'], 'id_auth_api' => $this->auth['id_auth']]);
			if (!empty($existsCode)) {
				$this->delivery->addError(400, 'Please use another code.');
				return $this->delivery;
			}

		}

		unset($payload['id_auth_api']);

		if (!isset($payload['maximum_rooms']) || empty($payload['maximum_rooms'])) {
			$this->delivery->addError(400, 'Please fill maximum rooms');
			return $this->delivery;
		}

		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('inventory_racks', $payload, $filters);
		$result = $this->repository->find('inventory_racks', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteRack ($filters = null) {
		$filters['id_auth_api'] = $this->auth['id_auth'];
		$existsRacks = $this->repository->find('inventory_racks', $filters);
		if (empty($existsRacks)) {
			$this->delivery->addError(409, 'No racks found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('inventory_racks', $payload, $filters);
		$result = $this->repository->find('inventory_racks', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function generateRack ($payload) {
		$result = null;
		$totalLevels = 1;
		$totalColumns = 1;
		if (!isset($payload['maximum_rooms']) || empty($payload['maximum_rooms'])) {
			$this->delivery->addError(400, 'Please fill maximum rooms');
			return $this->delivery;
		}

		if (!isset($payload['maximum_rooms']) || empty($payload['maximum_rooms'])) {
			$this->delivery->addError(400, 'Please fill maximum rooms');
			return $this->delivery;
		}

		if (!empty($payload['total_levels'])) {
			$totalLevels = $payload['total_levels'];
		}

		if (!empty($payload['total_columns'])) {
			$totalColumns = $payload['total_columns'];
		}

		if (empty($payload['prefix_rack'])) {
			$payload['prefix_rack'] = strtoupper(generateRandomString(2));
		}

		$currentTime = time();
		$rackCode = sprintf('%s%s', $payload['prefix_rack'], $this->auth['id_auth']);
		$existsCode = $this->repository->findOne('inventory_racks', ['code' => $rackCode, 'id_auth_api' => $this->auth['id_auth']]);
		if (!empty($existsCode)) {
			$this->delivery->addError(400, 'Please use another code.');
			return $this->delivery;
		}

		$roomCodes = [];
		$currentLevel = 1;
		$currentColumn = 1;
		if (empty($payload['prefix_room'])) {
			$payload['prefix_room'] = strtoupper(generateRandomString(2));
		}
		for ($i = 0; $i < $payload['maximum_rooms']; $i++) {
			// $roomCode = sprintf('%s%s%s%s', $payload['prefix_room'], $this->auth['id_auth'], $currentTime, str_pad($i+1, 4, '0', STR_PAD_LEFT));
			$roomCode = sprintf('%s-%s-%s-%s', $rackCode, $payload['prefix_room'], $currentLevel, $currentColumn);
			$roomCodes[] = [
				'code' => $roomCode,
				'level' => $currentLevel,
				'column' => $currentColumn
			];
			$currentColumn++;
			if ($currentColumn > $totalColumns) {
				$currentLevel++;
				$currentColumn = 1;
			}
		}

		try {
			$this->repository->beginTransaction();
			$rackPayload = [
				'code' => $rackCode,
				'maximum_rooms' => $payload['maximum_rooms'],
				'total_levels' => $totalLevels,
				'total_columns' => $totalColumns,
			];
			$createRackResult = $this->createRack($rackPayload);
			if ($createRackResult->hasErrors()) {
				throw new \Exception('Error create rack. Please try again');
			}

			$rack = $createRackResult->data;
			// create rooms
			$roomHandler = new InventoryRoomHandler($this->repository, $this->auth);
			foreach ($roomCodes as $roomCode) {
				$payloadRoom = [
					'id_inventory_rack' => $rack->id,
					'code' => $roomCode['code'],
					'level' => $roomCode['level'],
					'column' => $roomCode['column']
				];
				$roomHandlerResult = $roomHandler->createRoom($payloadRoom);
				if ($roomHandlerResult->hasErrors()) {
					throw new \Exception('Error create room. Please try again.');
				}
				$rack->inventory_rooms[] = $roomHandlerResult->data;
			}

			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
				$result = $rack;
			}
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}


}