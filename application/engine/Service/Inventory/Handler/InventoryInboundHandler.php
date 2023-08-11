<?php
namespace Service\Inventory\Handler;

use Service\Entity;
use Service\Delivery;

class InventoryInboundHandler {

	private $auth;
	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function stockTransfer ($inventoryBoxCodeSource, $inventoryRoomCodeDestination) {
		$box = $this->repository->findOne('inventory_boxes', ['code' => $inventoryBoxCodeSource, 'id_auth_api' => $this->auth['id_auth']]);
		if (empty($box)) {
			$this->delivery->addError(400, 'Inventory box not found');
			return $this->delivery;
		}

		$room = $this->repository->findOne('inventory_rooms', ['code' => $inventoryRoomCodeDestination, 'id_auth_api' => $this->auth['id_auth']]);
		if (empty($room)) {
			$this->delivery->addError(400, 'Inventory room not found');
			return $this->delivery;	
		}

		$boxInThisRoom = $this->repository->findOne('inventory_boxes', ['id_inventory_room' => $room->id]);
		$boxHandler = new InventoryBoxHandler($this->repository, $this->auth);
		if (!empty($boxInThisRoom)) {
			$payloadBox = [
				'id_inventory_room' => null
			];
			$filterBox = [
				'id' => $boxInThisRoom->id
			];
			$resultBoxHandler = $boxHandler->updateBox($payloadBox, $filterBox);
			if ($resultBoxHandler->hasErrors()) {
				return $this->delivery;
			}
		}

		$payloadBox = [
			'id_inventory_room' => $room->id
		];
		$filterBox = [
			'id' => $box->id
		];
		$resultBoxHandler = $boxHandler->updateBox($payloadBox, $filterBox);
		if ($resultBoxHandler->hasErrors()) {
			return $this->delivery;
		}

		$activityHandler = new InventoryActivityHandler($this->repository, $this->auth);
		$activityResult = $activityHandler->createActivity(InventoryActivityHandler::ACTIVITY_TYPE_STOCK_TRANSFER_BOX_TO_ROOM, 'inventory_boxes', $box->code, 'inventory_rooms', $room->code);
		if ($activityResult->hasErrors()) {
			$this->delivery->addError(400, 'Error create activity '.$activityResult->getFirstError()['detail']);
			return $this->delivery;
		}

		$resultBoxHandler = $boxHandler->getBox($filterBox);
		$this->delivery->data = $resultBoxHandler->data;
		return $this->delivery;
	}


}