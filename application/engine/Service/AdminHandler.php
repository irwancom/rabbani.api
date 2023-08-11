<?php
namespace Service;

use Service\Product\ProductHandler as ProductHandler;

class AdminHandler {

	private $auth;
	private $validator;
	private $delivery;
	private $entity;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->validator = new Validator($repository);
		$this->delivery = new Delivery;
		$this->entity = new Entity;
	}

	public function updateUserServices ($payload) {
		$idAuthUser = $payload['id_auth_user'];
		if (!isset($idAuthUser) || empty($idAuthUser)) {
			$this->delivery->addError(400, "Auth user not found");
			return $this->delivery;
		}
		$authUser = $this->repository->findOne('auth_user', ['id_auth_user' => $idAuthUser]);
		if (empty($authUser)) {
			$this->delivery->addError(400, "Auth user not found");
			return $this->delivery;
		}
		$authUserServices = $this->entity->formatAuth($authUser->services);
		$this->delivery->data = $authUserServices;

		$updatedServices = $payload['services'];
		foreach ($updatedServices as $key => $updatedService) {
			if (in_array($key, $this->entity->getTypes()) && isset($updatedService['fee']) && isset($updatedService['type']) && isset($updatedService['is_active'])) {
				$authUserServices[$key]['fee'] = $updatedService['fee'];
				$authUserServices[$key]['type'] = $key;
				$authUserServices[$key]['is_active'] = ($updatedService['is_active'] == "true") ? true : false;
			} else {
				$this->delivery->addError(400, 'Format services incorrect');
				return $this->delivery;
			}
		}
		$modifiedServices = json_encode($authUserServices);
		$action = $this->repository->update('auth_user', ['services' => $modifiedServices], ['id_auth_user' => $idAuthUser]);
		$this->delivery->data = $this->validator->validateAuth($authUser->secret)->data;
		return $this->delivery;
	}

	public function getServiceProducts ($filters = null) {
		$args = [];

		if (isset($filters['type']) && !empty($filters['type'])) {
			$args['type'] = $filters['type'];
		}

		if (isset($filters['code']) && !empty($filters['code'])) {
			$args['code'] = $filters['code'];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['name'] = [
				'condition' => 'like',
				'value' => $filters['name']
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
			'service_products.id',
			'service_products.type',
			'service_products.code',
			'service_products.name',
			'service_products.description',
			'service_products.price',
			'service_products.created_at',
			'service_products.updated_at',
			'service_products.deleted_at'
		];
		$orderKey = 'service_products.id';
		$orderValue = 'DESC';
		$products = $this->repository->findPaginated('service_products', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getServiceProduct ($filters) {
		$args = [];

		if (isset($filters['type']) && !empty($filters['type'])) {
			$args['type'] = $filters['type'];
		}

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['id'] = $filters['id'];
		}

		if (isset($filters['code']) && !empty($filters['code'])) {
			$args['code'] = $filters['code'];
		}

		$select = [
			'service_products.id',
			'service_products.type',
			'service_products.code',
			'service_products.name',
			'service_products.description',
			'service_products.price',
			'service_products.payment_period',
			'service_products.created_at',
			'service_products.updated_at',
			'service_products.deleted_at'
		];
		$product = $this->repository->findOne('service_products', $args, null, null, $select);
		if (empty($product)) {
			$this->delivery->addError(404, 'Service product not found.');
			return $this->delivery;
		}
		if (isJson($product->payment_period)) {
			$product->payment_period = json_decode($product->payment_period, true);
		}
		$this->delivery->data = $product;
		return $this->delivery;
	}

	public function createServiceProduct ($payload) {
		$productHandler = new ProductHandler($this->repository);
		$result = $productHandler->createProduct($payload);
		if ($result->hasErrors()) {
			return $result;
		}
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

	public function updateServiceProduct ($payload, $filters) {
		$productHandler = new ProductHandler($this->repository);
		$result = $productHandler->updateProduct($payload, $filters);
		if ($result->hasErrors()) {
			return $result;
		}
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

	public function deleteServiceProduct ($filters) {
		$productHandler = new ProductHandler($this->repository);
		$result = $productHandler->deleteProduct($filters);
		if ($result->hasErrors()) {
			return $result;
		}
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

}