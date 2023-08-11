<?php
namespace Service\Inventory\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Handler\OrderHandler;

class InventoryPicklistHandler {

	private $auth;
	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function createPicklist () {
		$maxOrder = 5;
		$picklistOrders = [];
		$orderHandler = new OrderHandler($this->repository);
		$orderHandler->setAdmin($this->auth);
		$args = [
			'status' => OrderHandler::ORDER_STATUS_OPEN,
			'data' => $maxOrder
		];

		$handle = $orderHandler->getOrders($args);
		$orders = $handle->data['result'];
		if (empty($orders)) {
			$this->delivery->addError('No order found');
			return $this->delivery;
		}

		$picklistCode = sprintf('PIC-%s%s', uniqid(), time());
		$newPicklist = [
			'code' => $picklistCode,
			'picked_by_admin_id' => $this->auth['id'],
			'note' => '',
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$picklistId = $this->repository->insert('inventory_picklists', $newPicklist);

		$argsOrWhere = [];
		foreach ($orders as $order) {
			$argsOrWhere[] = sprintf('orders.order_code = "%s"', $order->order_code);
		}

		$actionOrder = $this->repository->update('orders', ['picklist_id' => $picklistId], null, $argsOrWhere);
		$picklist = $this->getPicklist(['id' => $picklistId]);
		$this->delivery->data = $picklist->data;
		return $this->delivery;
	}

	public function getPicklist ($filters = null) {
		$picklist = $this->repository->findOne('inventory_picklists', $filters);
		$this->delivery->data = $picklists;
		return $this->delivery;
	}

}