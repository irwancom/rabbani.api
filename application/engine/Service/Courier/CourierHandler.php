<?php
namespace Service\Courier;

use Service\Entity;
use Service\Delivery;
use Service\Validator;
use Service\Courier\CourierValidator;
use Library\LionParcelService;
use Library\JNEService;

class CourierHandler {

	private $auth;
	private $validator;
	private $courierValidator;
	private $delivery;
	private $entity;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->validator = new Validator($repository);
		$this->courierValidator = new CourierValidator($repository);
		$this->delivery = new Delivery;
		$this->entity = new Entity;
	}

	public function getLionParcelTarif ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_LIONPARCEL
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadLionParcel($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new LionParcelService($payload['api_key']);
        $result = $service->getTarif($payload['origin'], $payload['destination'], $payload['weight'], $payload['commodity'], $payload['goods_value'], $payload['is_insurance'], $payload['is_wood_packing']);

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getLionParcelTrack ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_LIONPARCEL
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadLionParcel($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new LionParcelService($payload['api_key']);
        $result = $service->getTracking($payload['q']);

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getLionParcelDetails ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_LIONPARCEL
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadLionParcel($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new LionParcelService($payload['api_key']);
        $result = $service->getDetails($payload['q']);
        
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function createLionParcelBooking ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_LIONPARCEL
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadLionParcel($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new LionParcelService($payload['api_key']);
        $result = $service->createBooking ($payload['order_no'], $payload['client_code'], $payload['user_type'], $payload['external_number'], $payload['tracking_no'], $payload['package_id'], $payload['order_no_tag'], $payload['package_date'], $payload['product_type'], $payload['service_type'],
        $payload['commodity_type'], $payload['no_of_pieces'], $payload['gross_weight'], $payload['volume_weight'], $payload['cod_amount'], $payload['shipper_name'], $payload['pickup_address'], $payload['pickup_location'], $payload['pickup_phone'], $payload['pickup_email'], $payload['receiver_name'],
        $payload['receiver_address'], $payload['receiver_location'], $payload['receiver_phone'], $payload['receiver_email']);
        
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getJNEOrigin ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_JNE
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadJNE($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new JNEService($payload['username'], $payload['api_key']);
        $result = $service->getOrigin();
        
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getJNEDestination ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_JNE
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadJNE($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new JNEService($payload['username'], $payload['api_key']);
        $result = $service->getDestination();
        
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getJNETariff ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_JNE
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadJNE($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new JNEService($payload['username'], $payload['api_key']);
        $result = $service->getTariff($payload['from'], $payload['thru'], $payload['weight']);
        
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getJNETraceTracking ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_JNE
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadJNE($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new JNEService($payload['username'], $payload['api_key']);
        $result = $service->getTraceTracking($payload['awb']);
        
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function generateJNEAirwayBill ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_JNE
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$validPayload = $this->courierValidator->validatePayloadJNE($payload);
		if ($validPayload->hasErrors()) {
			return $validPayload;
		}

        $service = new JNEService($payload['username'], $payload['api_key']);
        $result = $service->generateAirwayBill ($payload['branch'], $payload['cust'], $payload['order_id'], $payload['shipper_name'], $payload['shipper_addr1'], $payload['shipper_addr2'], $payload['shipper_addr3'], $payload['shipper_city'], $payload['shipper_region'], $payload['shipper_zip'], $payload['shipper_phone'],
				$payload['receiver_name'], $payload['receiver_addr1'], $payload['receiver_addr2'], $payload['receiver_addr3'], $payload['receiver_city'], $payload['receiver_region'], $payload['receiver_zip'], $payload['receiver_phone'],
				$payload['qty'], $payload['weight'], $payload['goods_desc'], $payload['goods_value'], $payload['goods_type'], $payload['inst'], $payload['ins_flag'], $payload['origin'], $payload['destination'], $payload['service'], $payload['cod_flag'], $payload['cod_amount']
				);
        
		$this->delivery->data = $result;
		return $this->delivery;
	}

}