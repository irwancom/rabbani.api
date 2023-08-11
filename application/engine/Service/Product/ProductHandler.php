<?php
namespace Service\Product;

use Service\Delivery;
use Service\Entity;

class ProductHandler {

	private $delivery;
	private $entity;
	private $repository;
	private $productValidator;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->entity = new Entity;
		$this->productValidator = new ProductValidator($repository);
	}

	public function createProduct ($payload) {
		$valid = $this->productValidator->validatePayload($payload);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$argsUnique = [
			'code' => $payload['code']
		];
		$existsCode = $this->repository->findOne('service_products', $argsUnique);
		if (!empty($existsCode)) {
			$this->delivery->addError(400, 'Service product code is not available.');
			return $this->delivery;
		}

		if ($payload['type'] == Entity::TYPE_HOSTING) {
			$this->delivery = $this->createProductHosting($payload);
		} else if ($payload['type'] == Entity::TYPE_DOMAIN) {
			$this->delivery = $this->createProductDomain($payload);
		} else if ($payload['type'] == Entity::TYPE_SERVICE) {
			$this->delivery = $this->createProductService($payload);
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function updateProduct ($payload, $filters) {
		$valid = $this->productValidator->validatePayload($payload);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$existsServiceProduct = $this->repository->findOne('service_products', $filters);
		if (empty($existsServiceProduct)) {
			$this->delivery->addError(404, 'Service product not found');
			return $this->delivery;
		}

		if ($payload['type'] == Entity::TYPE_HOSTING) {
			$this->delivery = $this->updateProductHosting($payload, $filters);
		} else if ($payload['type'] == Entity::TYPE_DOMAIN) {
			$this->delivery = $this->updateProductDomain($payload, $filters);
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function deleteProduct ($filters) {
		$existsServiceProduct = $this->repository->findOne('service_products', $filters);
		if (empty($existsServiceProduct)) {
			$this->delivery->addError(404, 'Service product not found');
			return $this->delivery;
		}

		$data = [
			'deleted_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->update('service_products', $data, $filters);
		$this->delivery->data = null;
		return $this->delivery;
	}

	public function createProductDomain ($payload) {
		$data = [
			'type' => $payload['type'],
			'code' => $payload['code'],
			'name' => $payload['name'],
			'description' => $payload['description'],
			'price' => $payload['price'],
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$action = $this->repository->insert('service_products', $data);
		$data['id'] = $action;
		$this->delivery->data = $data;
		return $this->delivery;
	}

	public function createProductHosting ($payload) {
		$valid = $this->productValidator->validatePayloadHosting($payload);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$paymentPeriods = $payload['payment_period'];
		foreach ($paymentPeriods as $period) {
			$period['discount'] = (int)$period['discount'];
		}

		$data = [
			'type' => $payload['type'],
			'code' => $payload['code'],
			'name' => $payload['name'],
			'description' => $payload['description'],
			'price' => $payload['price'],
			'payment_period' => json_encode($payload['payment_period']),
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$action = $this->repository->insert('service_products', $data);
		$data['id'] = $action;
		$this->delivery->data = $data;
		return $this->delivery;
	}

	public function createProductService ($payload) {
				$valid = $this->productValidator->validatePayloadHosting($payload);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$paymentPeriods = $payload['payment_period'];
		foreach ($paymentPeriods as $period) {
			$period['discount'] = (int)$period['discount'];
		}

		$data = [
			'type' => $payload['type'],
			'code' => $payload['code'],
			'name' => $payload['name'],
			'description' => $payload['description'],
			'price' => $payload['price'],
			'payment_period' => json_encode($payload['payment_period']),
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$action = $this->repository->insert('service_products', $data);
		$data['id'] = $action;
		$this->delivery->data = $data;
		return $this->delivery;	
	}

	public function updateProductDomain ($payload, $filters) {
		$data = [
			'type' => $payload['type'],
			'code' => $payload['code'],
			'name' => $payload['name'],
			'description' => $payload['description'],
			'price' => $payload['price'],
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$action = $this->repository->update('service_products', $data, $filters);
		$product = $this->repository->findOne('service_products', $filters);
		$this->delivery->data = $product;
		return $this->delivery;
	}

	public function updateProductHosting ($payload, $filters) {
		$valid = $this->productValidator->validatePayloadHosting($payload);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$paymentPeriods = $payload['payment_period'];
		foreach ($paymentPeriods as $period) {
			$period['discount'] = (int)$period['discount'];
		}

		$data = [
			'type' => $payload['type'],
			'code' => $payload['code'],
			'name' => $payload['name'],
			'description' => $payload['description'],
			'price' => $payload['price'],
			'payment_period' => json_encode($payload['payment_period']),
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$action = $this->repository->update('service_products', $data, $filters);
		$product = $this->repository->findOne('service_products', $filters);
		$this->delivery->data = $product;
		return $this->delivery;
	}

}