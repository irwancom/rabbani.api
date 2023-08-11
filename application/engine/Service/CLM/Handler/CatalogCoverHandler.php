<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;

class CatalogCoverHandler {

	private $delivery;
	private $repository;
	private $productDetailHandler;

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

	public function getCatalogCovers ($filters = null) {
		$args = [];
		if (!empty($this->getAdmin())) {
			$args = [
				'catalog_covers.id_auth' => $this->admin['id_auth']
			];
		} else if (!empty($this->getUser())) {
			$args = [
				'catalog_covers.id_auth' => $this->user['id_auth']
			];
		} else {
			$args = [
				'catalog_covers.id_auth' => 1
			];
		}

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			$args['catalog_covers.publish_from <='] = $filters['current_time'];
			$args['catalog_covers.publish_until >='] = $filters['current_time'];
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
			'catalog_covers.id',
			'catalog_covers.image_url',
			'catalog_covers.publish_from',
			'catalog_covers.publish_until',
			'catalog_covers.created_at',
			'catalog_covers.updated_at',
			'catalog_covers.deleted_at',
		];
		$orderKey = 'catalog_covers.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$covers = $this->repository->findPaginated('catalog_covers', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $covers;
		return $this->delivery;
	}

	public function createCatalogCover ($payload) {
		if (!empty($this->getAdmin())) {
			$payload['id_auth'] = $this->admin['id_auth'];
		}else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['publish_from']) || empty($payload['publish_from'])) {
			$this->delivery->addError(400, 'Publish time is required');
		}

		if (!isset($payload['publish_until']) || empty($payload['publish_until'])) {
			$this->delivery->addError(400, 'Publish time is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		try {
			$digitalOceanService = new DigitalOceanService();
			$uploadResult = $digitalOceanService->upload($payload, 'image');
			$thumbnailUrl = $uploadResult['cdn_url'];
			$payload['image_url'] = $thumbnailUrl;

			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('catalog_covers', $payload);
			$covers = $this->repository->findOne('catalog_covers', ['id' => $action]);
			$this->delivery->data = $covers;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateCatalogCover ($payload, $filters = null) {
		try {
			if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
				$digitalOceanService = new DigitalOceanService();
				$uploadResult = $digitalOceanService->upload($payload, 'image');
				$payload['image_url'] = $uploadResult['cdn_url'];
			}
			$action = $this->repository->update('catalog_covers', $payload, $filters);
			$result = $this->getCatalogCovers($filters);
			return $result;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteCatalogCover ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('catalog_covers', $payload, ['id' => $id]);
			$result = $this->repository->findOne('catalog_covers', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

}