<?php
namespace Service\Inventory\Handler;

use Service\Entity;
use Service\Delivery;

class InventoryActivityHandler {

	const ACTIVITY_TYPE_PRODUCT_TO_BOX = 'product_to_box';
	const ACTIVITY_TYPE_PRODUCT_TO_NULL = 'product_to_null';
	const ACTIVITY_TYPE_BOX_TO_ROOM = 'box_to_room';
	const ACTIVITY_TYPE_BOX_TO_NULL = 'box_to_null';
	const ACTIVITY_TYPE_STOCK_TRANSFER_BOX_TO_ROOM = 'stock_transfer_box_to_room';

	private $auth;
	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function createActivity ($activityType, $sourceType, $sourceCode, $destinationType, $destinationCode) {
		$availableTypes = [
			self::ACTIVITY_TYPE_BOX_TO_ROOM,
			self::ACTIVITY_TYPE_PRODUCT_TO_BOX,
			self::ACTIVITY_TYPE_STOCK_TRANSFER_BOX_TO_ROOM
		];
		if (!in_array($activityType, $availableTypes)) {
			$this->delivery->addError(400, 'Wrong activity type');
			return $this->delivery;
		}

		$argsSourceKey = 'code';
		if ($sourceType == 'inventory_products') {
			$argsSourceKey = 'product_detail_sku_code';
		}
		$source = $this->repository->findOne($sourceType, [$argsSourceKey => $sourceCode]);
		if (empty($source)) {
			$this->delivery->addError(400, 'Source not found');
			return $this->delivery;
		}

		$argsDestinationKey = 'code';
		if ($destinationType == 'inventory_products') {
			$argsDestinationKey = 'product_detail_sku_code';
		}
		$destination = $this->repository->findOne($destinationType, [$argsDestinationKey => $destinationCode]);
		if (empty($destination)) {
			$this->delivery->addError(400, 'Destination not found');
			return $this->delivery;
		}

		$payload = [
			'id_auth_user' => $this->auth['id'],
			'activity_type' => $activityType,
			'source_type' => $sourceType,
			'source_code' => $sourceCode,
			'destination_type' => $destinationType,
			'destination_code' => $destinationCode,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->insert('inventory_activities', $payload);
		$result = $this->repository->findOne('inventory_activities', ['id' => $action]);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getActivities ($filters = null) {
		$argsOrWhere = null;
		$args = null;
		if (isset($filters['code']) && !empty($filters['code'])) {
			$argsOrWhere['source_code'] = $filters['code'];
			$argsOrWhere['destination_code'] = $filters['code'];
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
			'inventory_activities.id',
			'inventory_activities.activity_type',
			'inventory_activities.source_type',
			'inventory_activities.source_code',
			'inventory_activities.destination_type',
			'inventory_activities.destination_code',
			'inventory_activities.created_at',
			'inventory_activities.updated_at',
		];
		$orderKey = 'inventory_activities.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$activities = $this->repository->findPaginated('inventory_activities', $args, $argsOrWhere, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $activities;
		return $this->delivery;
	}


}