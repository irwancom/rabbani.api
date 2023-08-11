<?php
namespace Service\Product;

use Service\Delivery;
use Service\Entity;

class ProductValidator {

	private $delivery;
	private $repository;
	private $entity;

	public function __construct ($repository) {
		$this->delivery = new Delivery;
		$this->repository = $repository;
	}

	public function validatePayload ($payload) {
		if (!isset($payload['type']) || empty($payload['type'])) {
			$this->delivery->addError(400, 'Type should not be empty');
		}

		if (!isset($payload['code']) || empty($payload['code'])) {
			$this->delivery->addError(400, 'Code should not be empty');
		}

		if (!isset($payload['name']) || empty($payload['name'])) {
			$this->delivery->addError(400, 'Name should not be empty');
		}

		if (!isset($payload['price']) || empty($payload['price'])) {
			$this->delivery->addError(400, 'Price should not be empty');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$availableTypes = [
			Entity::TYPE_HOSTING,
			Entity::TYPE_DOMAIN,
			ENTITY::TYPE_SERVICE
		];

		if (!in_array($payload['type'], $availableTypes)) {
			$this->delivery->addError(400, 'Service product type unknown.');
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function validatePayloadHosting ($payload) {
		if (!isset($payload['payment_period']) || empty($payload['payment_period'])) {
			$this->delivery->addError(400, 'Payment period not found');
		}

		$paymentPeriods = $payload['payment_period'];
		foreach ($paymentPeriods as $period) {
			if (!isset($period['period']) || empty($period['period'])) {
				$this->delivery->addError(400, 'Format payment period incorrect');
			}

			if (!isset($period['discount'])) {
				$this->delivery->addError(400, 'Format payment period incorrect');
			}
		}

		return $this->delivery;
	}
}