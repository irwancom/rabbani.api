<?php
namespace Service\Courier;

use Service\Delivery;
use Service\Entity;

class CourierValidator {

	private $delivery;
	private $repository;
	private $entity;

	public function __construct ($repository) {
		$this->delivery = new Delivery;
		$this->repository = $repository;
	}

	public function validatePayloadLionParcel ($payload) {
		if (!isset($payload['api_key']) || empty($payload['api_key'])) {
			$this->delivery->addError(400, 'API Key should not be empty');
		}
		return $this->delivery;
	}

	public function validatePayloadJNE ($payload) {
		if (!isset($payload['username']) || empty($payload['username'])) {
			$this->delivery->addError(400, 'JNE Username should not be empty');
		}

		if (!isset($payload['api_key']) || empty($payload['api_key'])) {
			$this->delivery->addError(400, 'API Key should not be empty');
		}
		return $this->delivery;	
	}
}