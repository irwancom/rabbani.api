<?php
namespace Service;

use Library\MailgunService;
use Library\TripayGateway;
use Library\DomainService;
use Library\CPanelService;

class Handler {

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

	public function getTransactionItems ($filters = null) {
		$join = [
			'service_transactions' => 'service_transactions.id = service_transaction_items.service_transaction_id'
		];
		$args = [
			'service_transactions.id_auth_user' => $this->auth['id']
		];

		if (isset($filters['type']) && !empty($filters['type'])) {
			$args['service_transaction_items.service_product_type'] = $filters['type'];
		}

		if (isset($filters['payment_status']) && !empty($filters['payment_status'])) {
			$args['service_transactions.payment_status'] = $filters['payment_status'];
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
			'service_transaction_items.id',
			'service_transaction_items.service_user_collection_id',
			'service_transaction_items.service_transaction_id',
			'service_transactions.payment_status',
			'service_transactions.payment_method_code',
			'service_transactions.expired_at as payment_expired_at',
			'service_transaction_items.service_product_type',
			'service_transaction_items.service_product_code',
			'service_transaction_items.service_product_name',
			'service_transaction_items.price',
			'service_transaction_items.discount',
			'service_transaction_items.total',
			'service_transaction_items.payment_period',
			'service_transaction_items.detail',
			'service_transaction_items.created_at',
			'service_transaction_items.updated_at',
			'service_transaction_items.sld',
			'service_transaction_items.tld',
			'service_transaction_items.expired_at',
			'service_transaction_items.next_invoice_at',
			'service_transaction_items.created_at',
			'service_transaction_items.title',
		];
		$orderKey = 'service_transaction_items.id';
		$orderValue = 'DESC';
		$products = $this->repository->findPaginated('service_transaction_items', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getTransactionItem ($filters) {
		$join = [
			'service_transactions' => 'service_transactions.id = service_transaction_items.service_transaction_id'
		];
		$args = [
			'service_transactions.id_auth_user' => $this->auth['id']
		];

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['service_transaction_items.id'] = $filters['id'];
		}

		if (isset($filters['type']) && !empty($filters['type'])) {
			$args['service_transaction_items.service_product_type'] = $filters['type'];
		}

		if (isset($filters['payment_status']) && !empty($filters['payment_status'])) {
			$args['service_transactions.payment_status'] = $filters['payment_status'];
		}
		$select = [
			'service_transaction_items.id',
			'service_transaction_items.service_user_collection_id',
			'service_transaction_items.service_transaction_id',
			'service_transactions.payment_status',
			'service_transactions.payment_method_code',
			'service_transactions.payment_instructions',
			'service_transactions.expired_at as payment_expired_at',
			'service_transaction_items.service_product_type',
			'service_transaction_items.service_product_code',
			'service_transaction_items.service_product_name',
			'service_transaction_items.price',
			'service_transaction_items.discount',
			'service_transaction_items.total',
			'service_transaction_items.payment_period',
			'service_transaction_items.detail',
			'service_transaction_items.created_at',
			'service_transaction_items.updated_at',
			'service_transaction_items.sld',
			'service_transaction_items.tld',
			'service_transaction_items.expired_at',
			'service_transaction_items.next_invoice_at',
			'service_transaction_items.created_at',
			'service_transaction_items.title',
		];
		$products = $this->repository->findOne('service_transaction_items', $args, null, $join, $select);
		if (isJson($products->payment_instructions)) {
			$products->payment_instructions = json_decode($products->payment_instructions, true);
		}

		if (isJson($products->detail)) {
			$products->detail = json_decode($products->detail, true);
		}

		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getMeProduct ($serviceProductType) {
		$mandatoryServices = [
			Entity::SERVICE_HOSTING,
			Entity::SERVICE_DOMAIN
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$args = [
			'id_auth_user' => $this->auth['id'],
			'service_product_type' => $serviceProductType
		];
		$products = $this->repository->find('service_user_collections', $args);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getCloudProduct () {
		$mandatoryServices = [
			Entity::SERVICE_HOSTING,
			Entity::SERVICE_DOMAIN
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$args = [
			'type' => Entity::TYPE_HOSTING
		];
		$cloudProduct = $this->repository->find('service_products', $args);
		$products = [];
		foreach ($cloudProduct as $product) {
			$formattedProduct = $this->entity->formatProduct($product);
			$products[] = $formattedProduct;
		}

		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getServiceProduct () {
		$args = [
			'type' => Entity::TYPE_SERVICE
		];
		$serviceProduct = $this->repository->find('service_products', $args);
		$products = [];
		foreach ($serviceProduct as $product) {
			$formattedProduct = $this->entity->formatProduct($product);
			$products[] = $formattedProduct;
		}

		$this->delivery->data = $products;
		return $this->delivery;	
	}
	
	public function checkDomainCart ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_DOMAIN
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$tldProduct = $this->repository->findOne('service_products', ['code' => $payload['tld']]);
		if (empty($tldProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}

		$domainService = new DomainService;
		$result = $domainService->checkAvailability($payload['sld'], $payload['tld']);
		if (empty($result)) {
			$this->delivery->addError(500, 'Internal Server Error');
			return $this->delivery;
		}
		foreach ($result as $r) {
			if ($r->status != 'available') {
				$this->delivery->addError(500, 'Domain not available');
				return $this->delivery;
			}
		}
		// create dummy domain
		$domain = new \stdClass;
		$domain->type = Entity::TYPE_DOMAIN;
		$domain->code = 'domain';
		$domain->name = 'Domain';
		$domain->extras = [
			'sld' => $payload['sld'],
			'tld' => $payload['tld']
		];
		$domain->description = '';
		$domain->price = $tldProduct->price;
		$domain->discount = 0;
		$domainTerms = [
			[
				'period' => '1Y',
				'discount' => 0
			]
		];
		$domainTerms = json_encode($domainTerms);
		$domain->payment_period = $domainTerms;
		$domain = $this->entity->formatProduct($domain);

		$calculator = new Calculator;
		$calculator->addDomain($domain, $domain['payment_period'][0]);
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		$result = $calculator->checkCart();
		$this->delivery->data = $result;
		return $this->delivery;
	}	

	public function checkHostingCart ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_HOSTING
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$args = [
			'type' => Entity::TYPE_HOSTING,
			'code' => $payload['code']
		];
		$cloudProduct = $this->repository->findOne('service_products', $args);
		if (empty($cloudProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}
		$cloudProduct->extras = [
			'sld' => $payload['sld'],
			'tld' => $payload['tld']
		];
		$product = $this->entity->formatProduct($cloudProduct);
		$term = null;

		foreach ($product['payment_period'] as $period) {
			if ($period['code'] == $payload['period_code']) {
				$term = $period;
			}
		}

		if (empty($term)) {
			$this->delivery->addError(404, 'Payment period not found');
			return $this->delivery;
		}

		$calculator = new Calculator;
		$calculator->addCloud($product, $term);
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		$result = $calculator->checkCart();
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function checkCloudCart ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_HOSTING,
			Entity::SERVICE_DOMAIN
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$args = [
			'type' => Entity::TYPE_HOSTING,
			'code' => $payload['code']
		];
		$cloudProduct = $this->repository->findOne('service_products', $args);
		if (empty($cloudProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}
		$cloudProduct->extras = [
			'sld' => $payload['sld'],
			'tld' => $payload['tld']
		];
		$product = $this->entity->formatProduct($cloudProduct);
		$term = null;

		foreach ($product['payment_period'] as $period) {
			if ($period['code'] == $payload['period_code']) {
				$term = $period;
			}
		}

		if (empty($term)) {
			$this->delivery->addError(404, 'Payment period not found');
			return $this->delivery;
		}

		$tldProduct = $this->repository->findOne('service_products', ['code' => $payload['tld']]);
		if (empty($tldProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}

		$domainService = new DomainService;
		$result = $domainService->checkAvailability($payload['sld'], $payload['tld']);
		if (empty($result)) {
			$this->delivery->addError(500, 'Internal Server Error');
			return $this->delivery;
		}
		foreach ($result as $r) {
			if ($r->status != 'available') {
				$this->delivery->addError(500, 'Domain not available');
				return $this->delivery;
			}
		}

		// create dummy domain
		$domain = new \stdClass;
		$domain->type = Entity::TYPE_DOMAIN;
		$domain->code = 'domain';
		$domain->name = 'Domain';
		$domain->extras = [
			'sld' => $payload['sld'],
			'tld' => $payload['tld']
		];
		$domain->description = '';
		$domain->price = $tldProduct->price;
		$domain->discount = 0;
		$domainTerms = [
			[
				'period' => '1Y',
				'discount' => 0
			]
		];
		$domainTerms = json_encode($domainTerms);
		$domain->payment_period = $domainTerms;
		$domain = $this->entity->formatProduct($domain);

		$calculator = new Calculator;
		$calculator->addCloud($product, $term);
		$calculator->addDomain($domain, $domain['payment_period'][0]);
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		$result = $calculator->checkCart();
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function checkServiceCart ($payload) {
		$args = [
			'type' => Entity::TYPE_SERVICE,
			'code' => $payload['code']
		];
		$serviceProduct = $this->repository->findOne('service_products', $args);
		if (empty($serviceProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}
		$product = $this->entity->formatProduct($serviceProduct);
		$term = null;

		foreach ($product['payment_period'] as $period) {
			if ($period['code'] == $payload['period_code']) {
				$term = $period;
			}
		}

		if (empty($term)) {
			$this->delivery->addError(404, 'Payment period not found');
			return $this->delivery;
		}

		$calculator = new Calculator;
		$calculator->addService($product, $term);
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		$result = $calculator->checkCart();
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function createHostingTransaction ($payload, $isExtension = false, $expiredAt = null, $collectionId = null) {
		$mandatoryServices = [
			Entity::SERVICE_HOSTING
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$args = [
			'type' => Entity::TYPE_HOSTING,
			'code' => $payload['code']
		];
		$cloudProduct = $this->repository->findOne('service_products', $args);
		if (empty($cloudProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}
		$cloudProduct->extras = [
			'sld' => $payload['sld'],
			'tld' => $payload['tld']
		];
		$product = $this->entity->formatProduct($cloudProduct);
		$term = null;

		foreach ($product['payment_period'] as $period) {
			if ($period['code'] == $payload['period_code']) {
				$term = $period;
			}
		}

		if (empty($term)) {
			$this->delivery->addError(404, 'Payment period not found');
			return $this->delivery;
		}

		$calculator = new Calculator;
		$calculator->addCloud($product, $term);
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		$handlePaymentMethod = null;
		if (isset($payload['payment_method']) && !empty($payload['payment_method'])) {
			$paymentMethods = $calculator->getPaymentMethods();
			foreach ($paymentMethods as $paymentMethod) {
				if ($paymentMethod['code'] == $payload['payment_method']) {
					$handlePaymentMethod = $paymentMethod;
					break;
				}
			}

		} else {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		if (empty($handlePaymentMethod)) {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		$this->repository->startTransaction();
		if (!$isExtension) {
			$expiredAt = date('Y-m-d H:i:s', strtotime('+48 hours'));
		}

		$dataTransaction = [
			'id_auth_user' => $this->auth['id'],
			'price' => $calculator->getPrice(),
			'discount' => $calculator->getDiscount(),
			'promocode' => $calculator->getPromocode(),
			'total' => $calculator->getTotal(),
			'payment_status' => $calculator->getPaymentStatus(),
			'unique_code' => rand(100, 9999),
			'paid_at' => null,
			'expired_at' => $expiredAt,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$actionHeader = $this->repository->insert('service_transactions', $dataTransaction);
		$dataTransaction['id'] = $actionHeader;
		$dataItems = [];
		foreach ($calculator->getItems() as $item) {
			$dataItem = [
				'service_transaction_id' => $dataTransaction['id'],
				'service_product_type' => $item['type'],
				'service_product_code' => $item['code'],
				'service_product_name' => $item['name'],
				'price' => $item['price'],
				'discount' => $item['discount'],
				'total' => $item['total'],
				'payment_period' => $item['payment_period'],
				'detail' => json_encode($item['detail']),
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
				'sld' => isset($item['sld']) ? $item['sld'] : null,
				'tld' => isset($item['tld']) ? $item['tld'] : null,
				'cpanel_username' => isset($item['cpanel_username']) ? $item['cpanel_username'] : null,
				'cpanel_password' => isset($item['cpanel_password']) ? $item['cpanel_password'] : null,
				'is_extension' => ($isExtension) ? 1 : 0,
			];
			if ($isExtension == 1) {
				$dataItem['service_user_collection_id'] = $collectionId;
			}
			$dataItems[] = $dataItem;
			$this->repository->insert('service_transaction_items', $dataItem);
		}
		$this->repository->completeTransaction();

		$response = [];
		if ($this->repository->statusTransaction() === FALSE) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, 'Server Error');
			return $this->delivery;
		} else {
			$this->repository->commitTransaction();
			$response = [
				'service_transaction_id' => $dataTransaction['id'],
				'payment_status' => $dataTransaction['payment_status'],
				'expired_at' => $dataTransaction['expired_at']
			];
			$response['payment_method'] = $this->handlePaymentMethod($dataTransaction, $dataItems, $handlePaymentMethod, $this->auth);
			$mailgun = new MailgunService;
			$subject = 'Subject Do Payment';
			$text = 'Message Do Payment';
			$mailgun = $mailgun->send('', '', 'no-reply@1itmedia.co.id', $this->auth['email'], $subject, $text);
			$response['extras']['mailgun'] = $mailgun;
		}

		$this->delivery->data = $response;
		return $this->delivery; 
	}

	public function createDomainTransaction ($payload, $isExtension = false, $expiredAt = null, $collectionId = null) {
		$mandatoryServices = [
			Entity::SERVICE_DOMAIN
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$tldProduct = $this->repository->findOne('service_products', ['code' => $payload['tld']]);
		if (empty($tldProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}

		$domainService = new DomainService;
		if (empty($result)) {
			$this->delivery->addError(500, 'Internal Server Error');
			return $this->delivery;
		}
		$result = $domainService->checkAvailability($payload['sld'], $payload['tld']);
		foreach ($result as $r) {
			if ($r->status != 'available') {
				$this->delivery->addError(500, 'Domain not available');
				return $this->delivery;
			}
		}

		// create dummy domain
		$domain = new \stdClass;
		$domain->type = Entity::TYPE_DOMAIN;
		$domain->code = 'domain';
		$domain->name = 'Domain';
		$domain->extras = [
			'sld' => $payload['sld'],
			'tld' => $payload['tld']
		];
		$domain->sld = $payload['sld'];
		$domain->tld = $payload['tld'];
		$domain->description = '';
		$domain->price = $tldProduct->price;
		$domain->discount = 0;
		$domainTerms = [
			[
				'period' => '1Y',
				'discount' => 0
			]
		];
		$domainTerms = json_encode($domainTerms);
		$domain->payment_period = $domainTerms;
		$domain = $this->entity->formatProduct($domain);
		$calculator = new Calculator;
		$calculator->addDomain($domain, $domain['payment_period'][0]);
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();

		$handlePaymentMethod = null;
		if (isset($payload['payment_method']) && !empty($payload['payment_method'])) {
			$paymentMethods = $calculator->getPaymentMethods();
			foreach ($paymentMethods as $paymentMethod) {
				if ($paymentMethod['code'] == $payload['payment_method']) {
					$handlePaymentMethod = $paymentMethod;
					break;
				}
			}

		} else {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		if (empty($handlePaymentMethod)) {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		$this->repository->startTransaction();
		if (!$isExtension) {
			$expiredAt = date('Y-m-d H:i:s', strtotime('+48 hours'));
		}

		$dataTransaction = [
			'id_auth_user' => $this->auth['id'],
			'price' => $calculator->getPrice(),
			'discount' => $calculator->getDiscount(),
			'promocode' => $calculator->getPromocode(),
			'total' => $calculator->getTotal(),
			'payment_status' => $calculator->getPaymentStatus(),
			'unique_code' => rand(100, 9999),
			'paid_at' => null,
			'expired_at' => $expiredAt,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];
		$actionHeader = $this->repository->insert('service_transactions', $dataTransaction);
		$dataTransaction['id'] = $actionHeader;
		$dataItems = [];
		foreach ($calculator->getItems() as $item) {
			$dataItem = [
				'service_transaction_id' => $dataTransaction['id'],
				'service_product_type' => $item['type'],
				'service_product_code' => $item['code'],
				'service_product_name' => $item['name'],
				'price' => $item['price'],
				'discount' => $item['discount'],
				'total' => $item['total'],
				'payment_period' => $item['payment_period'],
				'detail' => json_encode($item['detail']),
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
				'sld' => isset($item['sld']) ? $item['sld'] : null,
				'tld' => isset($item['tld']) ? $item['tld'] : null,
				'cpanel_username' => isset($item['cpanel_username']) ? $item['cpanel_username'] : null,
				'cpanel_password' => isset($item['cpanel_password']) ? $item['cpanel_password'] : null,
				'is_extension' => ($isExtension) ? 1 : 0,
			];
			if ($isExtension == 1) {
				$dataItem['service_user_collection_id'] = $collectionId;
			}
			$dataItems[] = $dataItem;
			$this->repository->insert('service_transaction_items', $dataItem);
		}
		$this->repository->completeTransaction();

		$response = [];
		if ($this->repository->statusTransaction() === FALSE) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, 'Server Error');
			return $this->delivery;
		} else {
			$this->repository->commitTransaction();
			$response = [
				'service_transaction_id' => $dataTransaction['id'],
				'payment_status' => $dataTransaction['payment_status'],
				'expired_at' => $dataTransaction['expired_at']
			];
			$response['payment_method'] = $this->handlePaymentMethod($dataTransaction, $dataItems, $handlePaymentMethod, $this->auth);
			$mailgun = new MailgunService;
			$subject = 'Subject Do Payment';
			$text = 'Message Do Payment';
			$mailgun = $mailgun->send('', '', 'no-reply@1itmedia.co.id', $this->auth['email'], $subject, $text);
			$response['extras']['mailgun'] = $mailgun;
		}

		$this->delivery->data = $response;
		return $this->delivery;
	}

	public function createServiceTransaction ($payload, $isExtension = false, $expiredAt = null, $collectionId = null) {
		$args = [
			'type' => Entity::TYPE_SERVICE,
			'code' => $payload['code']
		];
		$serviceProduct = $this->repository->findOne('service_products', $args);
		if (empty($serviceProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}
		$product = $this->entity->formatProduct($serviceProduct);
		$term = null;

		foreach ($product['payment_period'] as $period) {
			if ($period['code'] == $payload['period_code']) {
				$term = $period;
			}
		}

		if (empty($term)) {
			$this->delivery->addError(404, 'Payment period not found');
			return $this->delivery;
		}

		$calculator = new Calculator;
		$calculator->addService($product, $term);
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		$handlePaymentMethod = null;
		if (isset($payload['payment_method']) && !empty($payload['payment_method'])) {
			$paymentMethods = $calculator->getPaymentMethods();
			foreach ($paymentMethods as $paymentMethod) {
				if ($paymentMethod['code'] == $payload['payment_method']) {
					$handlePaymentMethod = $paymentMethod;
					break;
				}
			}

		} else {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		if (empty($handlePaymentMethod)) {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		$this->repository->startTransaction();
		if (!$isExtension) {
			$expiredAt = date('Y-m-d H:i:s', strtotime('+48 hours'));
		}

		$dataTransaction = [
			'id_auth_user' => $this->auth['id'],
			'price' => $calculator->getPrice(),
			'discount' => $calculator->getDiscount(),
			'promocode' => $calculator->getPromocode(),
			'total' => $calculator->getTotal(),
			'payment_status' => $calculator->getPaymentStatus(),
			'unique_code' => rand(100, 9999),
			'paid_at' => null,
			'expired_at' => $expiredAt,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$actionHeader = $this->repository->insert('service_transactions', $dataTransaction);
		$dataTransaction['id'] = $actionHeader;
		$dataItems = [];
		foreach ($calculator->getItems() as $item) {
			$dataItem = [
				'service_transaction_id' => $dataTransaction['id'],
				'service_product_type' => $item['type'],
				'service_product_code' => $item['code'],
				'service_product_name' => $item['name'],
				'price' => $item['price'],
				'discount' => $item['discount'],
				'total' => $item['total'],
				'payment_period' => $item['payment_period'],
				'detail' => json_encode($item['detail']),
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
				'sld' => isset($item['sld']) ? $item['sld'] : null,
				'tld' => isset($item['tld']) ? $item['tld'] : null,
				'cpanel_username' => isset($item['cpanel_username']) ? $item['cpanel_username'] : null,
				'cpanel_password' => isset($item['cpanel_password']) ? $item['cpanel_password'] : null,
				'is_extension' => ($isExtension) ? 1 : 0,
			];
			if ($isExtension == 1) {
				$dataItem['service_user_collection_id'] = $collectionId;
			}
			$dataItems[] = $dataItem;
			$this->repository->insert('service_transaction_items', $dataItem);
		}
		$this->repository->completeTransaction();

		$response = [];
		if ($this->repository->statusTransaction() === FALSE) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, 'Server Error');
			return $this->delivery;
		} else {
			$this->repository->commitTransaction();
			$response = [
				'service_transaction_id' => $dataTransaction['id'],
				'payment_status' => $dataTransaction['payment_status'],
				'expired_at' => $dataTransaction['expired_at']
			];
			$response['payment_method'] = $this->handlePaymentMethod($dataTransaction, $dataItems, $handlePaymentMethod, $this->auth);
			$mailgun = new MailgunService;
			$subject = 'Subject Do Payment';
			$text = 'Message Do Payment';
			$mailgun = $mailgun->send('', '', 'no-reply@1itmedia.co.id', $this->auth['email'], $subject, $text);
			$response['extras']['mailgun'] = $mailgun;
		}

		$this->delivery->data = $response;
		return $this->delivery; 
	}

	public function createCloudTransaction ($payload) {
		$mandatoryServices = [
			Entity::SERVICE_HOSTING,
			Entity::SERVICE_DOMAIN
		];

		$valid = $this->validator->validateAuthHandler($this->auth, $mandatoryServices);
		if ($valid->hasErrors()) {
			return $valid;
		}

		$args = [
			'type' => Entity::TYPE_HOSTING,
			'code' => $payload['code']
		];
		$cloudProduct = $this->repository->findOne('service_products', $args);
		if (empty($cloudProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}
		$cloudProduct->extras = [
			'sld' => $payload['sld'],
			'tld' => $payload['tld']
		];
		$product = $this->entity->formatProduct($cloudProduct);
		$term = null;

		foreach ($product['payment_period'] as $period) {
			if ($period['code'] == $payload['period_code']) {
				$term = $period;
			}
		}

		if (empty($term)) {
			$this->delivery->addError(404, 'Payment period not found');
			return $this->delivery;
		}

		$tldProduct = $this->repository->findOne('service_products', ['code' => $payload['tld']]);
		if (empty($tldProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}

		$domainService = new DomainService;
		$result = $domainService->checkAvailability($payload['sld'], $payload['tld']);
		if (empty($result)) {
			$this->delivery->addError(500, 'Internal Server Error');
			return $this->delivery;
		}
		foreach ($result as $r) {
			if ($r->status != 'available') {
				$this->delivery->addError(500, 'Domain not available');
				return $this->delivery;
			}
		}

		$tldProduct = $this->repository->findOne('service_products', ['code' => $payload['tld']]);
		if (empty($tldProduct)) {
			$this->delivery->addError(404, 'Product not found');
			return $this->delivery;
		}

		$domainService = new DomainService;
		$result = $domainService->checkAvailability($payload['sld'], $payload['tld']);
		foreach ($result as $r) {
			if ($r->status != 'available') {
				$this->delivery->addError(500, 'Domain not available');
				return $this->delivery;
			}
		}

		// create dummy domain
		$domain = new \stdClass;
		$domain->type = Entity::TYPE_DOMAIN;
		$domain->code = 'domain';
		$domain->name = 'Domain';
		$domain->extras = [
			'sld' => $payload['sld'],
			'tld' => $payload['tld']
		];
		$domain->sld = $payload['sld'];
		$domain->tld = $payload['tld'];
		$domain->description = '';
		$domain->price = $tldProduct->price;
		$domain->discount = 0;
		$domainTerms = [
			[
				'period' => '1Y',
				'discount' => 0
			]
		];
		$domainTerms = json_encode($domainTerms);
		$domain->payment_period = $domainTerms;
		$domain = $this->entity->formatProduct($domain);
		$calculator = new Calculator;
		$calculator->addCloud($product, $term, sprintf('Hosting %s%s', $payload['sld'], $payload['tld']));
		$calculator->addDomain($domain, $domain['payment_period'][0]);
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		
		$handlePaymentMethod = null;
		if (isset($payload['payment_method']) && !empty($payload['payment_method'])) {
			$paymentMethods = $calculator->getPaymentMethods();
			foreach ($paymentMethods as $paymentMethod) {
				if ($paymentMethod['code'] == $payload['payment_method']) {
					$handlePaymentMethod = $paymentMethod;
					break;
				}
			}

		} else {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		if (empty($handlePaymentMethod)) {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		$this->repository->startTransaction();

		$expiredAt = date('Y-m-d H:i:s', strtotime('+48 hours'));

		$dataTransaction = [
			'id_auth_user' => $this->auth['id'],
			'price' => $calculator->getPrice(),
			'discount' => $calculator->getDiscount(),
			'promocode' => $calculator->getPromocode(),
			'total' => $calculator->getTotal(),
			'payment_status' => $calculator->getPaymentStatus(),
			'unique_code' => rand(100, 9999),
			'paid_at' => null,
			'expired_at' => $expiredAt,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$actionHeader = $this->repository->insert('service_transactions', $dataTransaction);
		$dataTransaction['id'] = $actionHeader;
		$dataItems = [];
		foreach ($calculator->getItems() as $item) {
			$dataItem = [
				'service_transaction_id' => $dataTransaction['id'],
				'service_product_type' => $item['type'],
				'service_product_code' => $item['code'],
				'service_product_name' => $item['name'],
				'price' => $item['price'],
				'discount' => $item['discount'],
				'total' => $item['total'],
				'payment_period' => $item['payment_period'],
				'detail' => json_encode($item['detail']),
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
				'sld' => isset($item['sld']) ? $item['sld'] : null,
				'tld' => isset($item['tld']) ? $item['tld'] : null,
				'cpanel_username' => isset($item['cpanel_username']) ? $item['cpanel_username'] : null,
				'cpanel_password' => isset($item['cpanel_password']) ? $item['cpanel_password'] : null,
				'title' => $item['title']
			];
			$dataItems[] = $dataItem;
			$this->repository->insert('service_transaction_items', $dataItem);
		}
		$this->repository->completeTransaction();

		$response = [];
		if ($this->repository->statusTransaction() === FALSE) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, 'Server Error');
			return $this->delivery;
		} else {
			$this->repository->commitTransaction();
			$response = [
				'service_transaction_id' => $dataTransaction['id'],
				'payment_status' => $dataTransaction['payment_status'],
				'expired_at' => $dataTransaction['expired_at']
			];
			$response['payment_method'] = $this->handlePaymentMethod($dataTransaction, $dataItems, $handlePaymentMethod, $this->auth);
			$mailgun = new MailgunService;
			$subject = 'Subject Do Payment';
			$text = 'Message Do Payment';
			$mailgun = $mailgun->send('', '', 'no-reply@1itmedia.co.id', $this->auth['email'], $subject, $text);
			$response['extras']['mailgun'] = $mailgun;
		}

		$this->delivery->data = $response;
		return $this->delivery;
	}

	public function changeTransactionPaymentMethod ($filters, $payload) {
		$transaction = $this->repository->findOne('service_transactions', $filters);
		if (empty($transaction)) {
			$this->delivery->addError(404, 'Transaction not found');
			return $this->delivery;
		}

		$args = [
			'service_transaction_id' => $transaction->id
		];
		$items = $this->repository->find('service_transaction_items', $args);
		$handlePaymentMethod = null;
		$calculator = new Calculator;
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		if (isset($payload['payment_method']) && !empty($payload['payment_method'])) {
			$paymentMethods = $calculator->getPaymentMethods();
			foreach ($paymentMethods as $paymentMethod) {
				if ($paymentMethod['code'] == $payload['payment_method']) {
					$handlePaymentMethod = $paymentMethod;
					break;
				}
			}

		} else {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		if (empty($handlePaymentMethod)) {
			$this->delivery->addError(404, 'Payment method unavailable');
			return $this->delivery;
		}

		$action = $this->handlePaymentMethod((array)$transaction, (array)$items, $handlePaymentMethod, $this->auth);
		$this->delivery->data = $action;
		$this->delivery->success = true;
		return $this->delivery;
	}

	public function onTripayCallback ($payload) {
		$result = null;
		$merchantRef = (int)$payload['merchant_ref'];
		$args = [
			'id' => $merchantRef
		];
		$transaction = $this->repository->findOne('service_transactions', $args);
		if (!empty($transaction)) {
			$result = $this->handleCallback($transaction, $payload['payment_method_code']);
		}
		return $result;
	}

	public function onMootaCallback ($payload) {
		$results = [];
		foreach ($payload as $load) {
			$amount = $load['amount'];
			$args = [
				'(total+unique_code)' => $amount,
				'expired_at >=' => date('Y-m-d H:i:s'),
				'created_at <= ' => date('Y-m-d H:i:s')
			];
			$transaction = $this->repository->findOne('service_transactions', $args);
			if (!empty($transaction)) {
				$result = $this->handleCallback($transaction, Calculator::PAYMENT_METHOD_TYPE_MANUAL);
				$results[] = $result;
			}
		}
		return $results;
	}

	private function handlePaymentMethod ($transaction, $items, $paymentMethod, $auth) {
		if ($paymentMethod['code'] === Calculator::PAYMENT_METHOD_TYPE_MANUAL) {
			// manual moota
			$data = [];
			$data['instructions'] = [
				'title' => 'Direct Debit',
				'steps' => [
					sprintf('Transfer ke rekening XXX dengan nominal sebesar Rp %s', $transaction['total'] + $transaction['unique_code'])
				]
			];
			$action = $this->repository->update('service_transactions', ['payment_instructions' => json_encode($data)], ['id' => $transaction['id']]);
			return $data;
		} else {
			// tripay
			$orderItems = [];
			foreach ($items as $item) {
				$item = (array)$item;
				$orderItems[] = [
					'sku' => $item['service_product_code'],
					'name' => $item['service_product_name'],
					'price' => $item['total'],
					'quantity' => 1
				];
			}
			$tripay = new TripayGateway;
			$result = $tripay->requestTransaksi($paymentMethod['code'], $transaction['id'], $transaction['total'], $auth['name'], $auth['email'], $auth['phone'], $orderItems, strtotime($transaction['expired_at']));
			if (isset($result->data)) {
				$action = $this->repository->update('service_transactions', ['payment_instructions' => json_encode($result->data)], ['id' => $transaction['id']]);
				return $result->data;
			} else {
				return $result;
			}
		}
	}

	public function getPaymentMethods () {
		$calculator = new Calculator;
		$calculator->addManualPaymentMethod();
		$calculator->addDirectPaymentMethod();
		$this->delivery->data = $calculator->getPaymentMethods();
		return $this->delivery;
	}

	private function handleCallback ($transaction, $paymentMethod) {
		$currentTime = date('Y-m-d H:i:s');
		$update = [
			'payment_method_code' => $paymentMethod,
			'paid_at' => $currentTime,
			'payment_status' => Calculator::TYPE_PAYMENT_STATUS_PAID
		];
		$this->repository->update('service_transactions', $update, ['id' => $transaction->id]);
		$result = $update;
		$result['service_transaction_id'] = $transaction->id;
		$argsAuth = [
			'id_auth_user' => $transaction->id_auth_user
		];
		$auth = $this->repository->findOne('auth_user', $argsAuth);
		if (!empty($auth)) {
			$mailgun = new MailgunService;
			$subject = 'Payment Successful';
			$text = 'Payment Successful';
			$mailgun = $mailgun->send('', '', 'no-reply@1itmedia.co.id', $auth->email, $subject, $text);
			$result['extras']['mailgun_payment'] = $mailgun;
		}

		$argsItems = [
			'service_transaction_id' => $transaction->id
		];
		$items = $this->repository->find('service_transaction_items', $argsItems);
		foreach ($items as $item) {
			// handle each product
			$cpanelUsername = null;
			$cpanelPassword = null;
			if ($item->service_product_type == Entity::TYPE_HOSTING && $item->is_extension == 0) {
				// hosting register
				$sld = $item->sld;
				$domain = sprintf('%s.%s', $item->sld, $item->tld);
				$cpanelUsername = $sld;
				$cpanelPassword = generateRandomString(12);
				$cPanelService = new CPanelService;
				$cPanelAction = $cPanelService->createCPanelAccount($cpanelUsername, $domain, $cpanelPassword);
				$result['extras']['cpanel_action'] = $cPanelAction;

				$updateData = [
					'cpanel_username' => $cpanelUsername,
					'cpanel_password' => $cpanelPassword
				];
				$action = $this->repository->update('service_transaction_items', $updateData, ['id' => $item->id]);
				$mailgun = new MailgunService;
				$subject = 'Your account for cPanel';
				$text = sprintf('
						Your account for cPanel <br/>
						Username: %s <br/>
						Password: %s
				', $cpanelUsername, $cpanelPassword);

				$mailgun = $mailgun->send('', '', 'no-reply@1itmedia.co.id', $auth->email, $subject, $text);
				$result['extras']['mailgun_action'] = $mailgun;
			} else if ($item->service_product_type == Entity::TYPE_DOMAIN && $item->is_extension == 0) {
				// domain register
				$domainService = new DomainService;
				$domainAction = $domainService->register($item->sld, $item->tld, $auth->id_auth_user, 0, 0, 0, 1, $invoiceOption = 'NoInvoice', 1);
				$result['extras']['domain_action'] = $domainAction;
			}

			$expiredAt = Entity::modifyDate($item->payment_period, $currentTime, 'add');
			if ($item->is_extension == 1) {
				$expiredAt = Entity::modifyDate($item->payment_period, $transaction->expired_at, 'add');
			}
			$nextInvoiceAt = Entity::modifyDate('7D', $expiredAt, 'sub');
			$updateData = [
				'expired_at' => $expiredAt,
				'next_invoice_at' => $nextInvoiceAt
			];

			if ($item->is_extension == 0) {
				$detail = json_decode($item->detail);
				$dataCollection = [
					'id_auth_user' => $auth->id_auth_user,
					'service_product_type' => $item->service_product_type,
					'service_product_code' => $item->service_product_code,
					'service_product_name' => $item->service_product_name,
					'title' => null,
					'price' => $item->price,
					'payment_period' => $item->payment_period,
					'expired_at' => $expiredAt,
					'next_invoice_at' => $nextInvoiceAt,
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s'),
					'status' => Entity::STATUS_ACTIVE,
					'payment_method_code' => $paymentMethod,
					'title' => $item->title,
					'sld' => isset($detail->sld) ? $detail->sld : null,
					'tld' => isset($detail->tld) ? $detail->tld : null,
					'first_payment_amount' => $item->price,
					'recurring_amount' => $item->price,
					'cpanel_username' => $cpanelUsername,
					'cpanel_password' => $cpanelPassword,
					'registration_date' => date('Y-m-d H:i:s'),
					'detail' => $item->detail
				];
				$action = $this->repository->insert('service_user_collections', $dataCollection);
				$updateData['service_user_collection_id'] = $action;
			} else {
				$action = $this->repository->update('service_user_collections', $updateData, ['id' => $item->service_user_collection_id]);
			}
			$action = $this->repository->update('service_transaction_items', $updateData, ['id' => $item->id]);
		}

		return $result;
	}

}