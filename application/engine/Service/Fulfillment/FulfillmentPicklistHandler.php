<?php
namespace Service\Fulfillment;

use Service\Entity;
use Service\Delivery;


class FulfillmentPicklistHandler {

	private $auth;
	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

    public function create($ids, $note = null) {
		$lastRowId = $this->repository->getLastRowId('fulfillment_picklists');
		$currentId = $lastRowId + 1;
		$pickListCode = sprintf('PICK-%09d', $currentId);

		$this->repository->startTransaction();
		$picklistData['created_by'] = $this->auth['id_auth'];
		$picklistData['created_at'] = date('Y-m-d H:i:s');
		$picklistData['note'] = $note;
		$picklistData['code'] = $pickListCode;
		$picklistLastInsertedId = $this->repository->insert('fulfillment_picklists', $picklistData);

		$this->repository->update('fulfillment_orders', [
			'updated_at' => date('Y-m-d H:i:s'),
			'fulfillment_picklist_id' => $picklistLastInsertedId,
		], [
			'id' => [
				'condition' => 'where_in',
				'value' => $ids,
			]
		]);
		$this->repository->completeTransaction();
		
		if ($this->repository->statusTransaction() === FALSE){
			$this->delivery->addError(500, 'Batch insert failed.');
			$this->delivery->data = [
				'status' => 'failed',
				'message' => 'Batch insert failed',
			];
			return $this->delivery;
		}

		$this->delivery->data =  [
			'id' => $picklistLastInsertedId,
			'picklist_code' => $pickListCode,
			'note' => $note,
			'items' => $ids,
		];
		return $this->delivery;
	}

	public function get($filters = null, $findPaginated = true) {
		$args = [];
		$argsOrWhere = [];

		if (isset($filters['q'])) {
			$argsOrWhere['fulfillment_picklists.code'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		if (isset($filters['start_date'])) {
			$startDate = date('Y-m-d', strtotime($filters['start_date']));
			$args['fulfillment_picklists.created_at'] = [
				'condition' => 'custom',
				'value' => "
					DATE(fulfillment_picklists.created_at) >= '$startDate'
				"
			];
		}

		if (isset($filters['end_date'])) {
			$endDate = date('Y-m-d', strtotime($filters['end_date']));
			$args['fulfillment_picklists.created_at'] = [
				'condition' => 'custom',
				'value' => "
					DATE(fulfillment_picklists.created_at) <= '$endDate'
				"
			];
		}

		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}

		$offset = 0;
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}

		$orderKey = 'fulfillment_orders.id';
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		
        $result = $this->repository->find('fulfillment_picklists', $args, $argsOrWhere);

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function delete($id) {
		$this->repository->startTransaction();
		$this->repository->deleteSoft('fulfillment_picklists', 'id', $id);
		$this->repository->update('fulfillment_orders', [
			'fulfillment_picklist_id' => NULL,
		], ['fulfillment_picklist_id' => $id]);
		$this->repository->completeTransaction();
		
		if ($this->repository->statusTransaction() === FALSE){
			$this->delivery->addError(500, 'Failed to delete picklist');
			$this->delivery->data = [
				'status' => 'failed',
				'message' => 'Failed to delete picklist',
			];
		} else {
			$this->delivery->data = [
				'status' => 'success',
				'message' => 'Picklist has been deleted',
			];
		}

		return $this->delivery;
	}

	public function deleteBatch($ids) {
		$this->repository->startTransaction();
		$this->repository->deleteBatchSoft('fulfillment_picklists', 'id', $ids);
		$this->repository->update('fulfillment_orders', [
			'fulfillment_picklist_id' => NULL,
		], ['fulfillment_picklist_id' => [
			'condition' => 'where_in',
			'value' => $ids,
		]]);
		$this->repository->completeTransaction();
		
		if ($this->repository->statusTransaction() === FALSE){
			$this->delivery->addError(500, 'Failed to batch delete picklist');
			$this->delivery->data = [
				'status' => 'failed',
				'message' => 'Failed to batch delete picklist',
			];
		} else {
			$this->delivery->data = [
				'status' => 'success',
				'message' => 'Batch Picklists has been deleted',
			];
		}

		return $this->delivery;
	}

	public function removeItem($id) {
		$result = $this->repository->update('fulfillment_orders', [
			'fulfillment_picklist_id' => NULL
		], [
			'id' => $id
		]);
		if ($result) {
			$this->delivery->data = [
				'status' => 'success',
				'message' => 'Picklist item has been removed',
			];
		} else {
			$this->delivery->addError(500, 'Failed to remove item from picklist');
			$this->delivery->data = [
				'status' => 'failed',
				'message' => 'Failed to remove item from picklist',
			];
		}
		return $this->delivery;
	}

}