<?php
namespace Service\Inventory\Handler;

use Service\Entity;
use Service\Delivery;

/**
 * 1 Room dimiliki oleh sebuah Rack
 * 1 Room hanya boleh memiliki 1 box atau kosong
 **/
class InventoryRoomHandler {

	private $auth;
	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function getRooms ($filters = null) {
		$args = [
			'inventory_rooms.id_auth_api' => $this->auth['id_auth']
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
			'inventory_rooms.id',
			'inventory_rooms.id_inventory_rack',
			'inventory_rooms.level',
			'inventory_rooms.column',
			'inventory_rooms.code',
			'inventory_rooms.created_at',
			'inventory_rooms.updated_at',
		];
		$orderKey = 'inventory_rooms.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('inventory_rooms', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getRoom ($filters = null) {
		$room = $this->repository->findOne('inventory_rooms', $filters);

		if (!empty($room->id_inventory_rack)) {
			$rackHandler = new InventoryRackHandler($this->repository, $this->auth);
			$rackResult = $rackHandler->getRack(['id' => $room->id_inventory_rack]);
			$room->inventory_rack = $rackResult->data;
		}

		$this->delivery->data = $room;
		return $this->delivery;
	}

	public function createRoom ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];

		$existsRack = $this->repository->findOne('inventory_racks', ['id' => $payload['id_inventory_rack'], 'id_auth_api' => $this->auth['id_auth']]);
		if (empty($existsRack)) {
			$this->delivery->addError(409, 'Rack not found.');
			return $this->delivery;
		}

		/* $roomInThisRack = $this->repository->find('inventory_rooms', ['id_inventory_rack' => $payload['id_inventory_rack'], 'id_auth_api' => $this->auth['id_auth']]);
		if (count($roomInThisRack) >= $existsRack->maximum_rooms) {
			$this->delivery->addError(409, sprintf('Maximum room exceeded on this rack (%s)', $existsRack->maximum_rooms));
			return $this->delivery;
		} */

		if (!isset($payload['code']) || empty($payload['code'])) {
			$payload['code'] = sprintf('AR%s%s', $this->auth['id_auth'], time());
		}

		$existsCode = $this->repository->findOne('inventory_rooms', ['code' => $payload['code'], 'id_auth_api' => $this->auth['id_auth']]);
		if (!empty($existsCode)) {
			$this->delivery->addError(400, 'Please use another code.');
			return $this->delivery;
		}

		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('inventory_rooms', $payload);
		$result = $this->repository->findOne('inventory_rooms', ['id' => $action]); 
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateRoom ($payload, $filters = null) {
		$filters['id_auth_api'] = $this->auth['id_auth'];
		$existsRooms = $this->repository->find('inventory_rooms', $filters);
		if (empty($existsRooms)) {
			$this->delivery->addError(409, 'No rooms found.');
			return $this->delivery;
		}
		unset($payload['code']);
		unset($payload['id_auth_api']);

		if (isset($payload['id_inventory_rack'])) {
			$existsRack = $this->repository->findOne('inventory_racks', ['id' => $payload['id_inventory_rack'], 'id_auth_api' => $this->auth['id_auth']]);
			if (empty($existsRack)) {
				$this->delivery->addError(409, 'Rack not found.');
				return $this->delivery;
			}

			/* $roomInThisRack = $this->repository->find('inventory_rooms', ['id_inventory_rack' => $payload['id_inventory_rack'], 'id_auth_api' => $this->auth['id_auth']]);
			if (count($roomInThisRack) >= $existsRack->maximum_rooms) {
				$this->delivery->addError(409, sprintf('Maximum room exceeded on this rack (%s)', $existsRack->maximum_rooms));
				return $this->delivery;
			} */
		}

		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('inventory_rooms', $payload, $filters);
		$result = $this->repository->find('inventory_rooms', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteRoom ($filters = null) {
		$filters['id_auth_api'] = $this->auth['id_auth'];
		$existsRooms = $this->repository->find('inventory_rooms', $filters);
		if (empty($existsRooms)) {
			$this->delivery->addError(409, 'No rooms found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('inventory_rooms', $payload, $filters);
		$result = $this->repository->find('inventory_rooms', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function isCodeExists ($code) {
		$existsCode = $this->repository->findOne('inventory_rooms', ['code' => $code]);
		if (!empty($existsCode)) {
			return true;
		}
		return false;
	}

	public function scanAction ($code) {
		$result = null;
		try {
			$existsRoom = $this->repository->findOne('inventory_rooms', ['code' => $code, 'id_auth_api' => $this->auth['id_auth']]);
			$alreadyScanned = $this->repository->findOne('inventory_scans', ['type' => 'inventory_rooms', 'code' => $code, 'id_auth_user' => $this->auth['id'], 'is_moved' => 0]);
			if (!empty($alreadyScanned)) {
				$this->delivery->addError(409, 'Room already scanned');
				return $this->delivery;
			}
			$this->repository->beginTransaction();
			$scanPayload = [
				'id_auth_user' => $this->auth['id'],
				'type' => 'inventory_rooms',
				'is_moved' => 1, // by default its already moved to its place
				'code' => $code,
				'created_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('inventory_scans', $scanPayload);

			// cari scan yg terakhir
			$scannedBox = $this->repository->findOne('inventory_scans', ['id_auth_user' => $this->auth['id'], 'type' => 'inventory_boxes', 'is_moved' => 0], null, null, null, 'created_at', 'DESC');
			if (empty($scannedBox)) {
				$this->delivery->addError(409, 'There is no box scanned');
				return $this->delivery;
			}
			$actionScan = $this->repository->update('inventory_scans', ['is_moved' => 1], ['id' => $scannedBox->id]);
			
			$boxHandler = new InventoryBoxHandler($this->repository, $this->auth);
			$payloadBox = [
				'id_inventory_room' => $existsRoom->id
			];
			$filterBox = [
				'code' => $scannedBox->code
			];
			$boxResult = $boxHandler->updateBox($payloadBox, $filterBox);
			if ($boxResult->hasErrors()) {
				throw new \Exception('Error scan room. ' . $boxResult->getFirstError()['detail']);
			}
			$result = $boxResult->data;

			$activityHandler = new InventoryActivityHandler($this->repository, $this->auth);
			$activityResult = $activityHandler->createActivity(InventoryActivityHandler::ACTIVITY_TYPE_BOX_TO_ROOM, 'inventory_boxes', $scannedBox->code, 'inventory_rooms', $existsRoom->code);
			if ($activityResult->hasErrors()) {
				throw new \Exception('Error create activity.' . $activityResult->getFirstError()['detail']);
			}

			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
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