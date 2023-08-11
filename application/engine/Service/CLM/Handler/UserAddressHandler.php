<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;

class UserAddressHandler {

	private $delivery;
	private $repository;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
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

	public function getUserAddresses ($filters = null) {
		$args = [];

		if (isset($filters['user_id'])) {
			$args['user_address.user_id'] = $filters['user_id'];
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
			'user_address.id',
			'user_address.address_name',
			'user_address.received_name',
			'user_address.phone_number',
			'user_address.post_code',
			'user_address.user_id',
			'user_address.address',
			'user_address.city_name',
			'user_address.city_code',
			'user_address.created_at',
			'user_address.deleted_at',
		];
		$orderKey = 'user_address.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$keywords = $this->repository->findPaginated('user_address', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $keywords;
		return $this->delivery;
	}

	public function getUserAddress ($filters = null) {
		$args = $filters;
		$keyword = $this->repository->findOne('user_address', $args);
		$this->delivery->data = $keyword;
		return $this->delivery;
	}

	public function createUserAddress ($payload) {
		if (!isset($payload['user_id']) || empty($payload['user_id'])) {
			$this->delivery->addError(400, 'User is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		try {
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('user_address', $payload);
			$keyword = $this->repository->findOne('user_address', ['id' => $action]);
			$this->delivery->data = $keyword;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateUserAddress ($payload, $filters = null) {
		$existsKeyword = $this->repository->findOne('user_address', $filters);
		if (empty($existsKeyword)) {
			$this->delivery->addError(409, 'No user address found.');
			return $this->delivery;
		}

		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('user_address', $payload, $filters);
		$result = $this->getUserAddress($filters);
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

	public function deleteUserAddress ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('user_address', $payload, ['id' => $id]);
			$result = $this->repository->findOne('user_address', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

}