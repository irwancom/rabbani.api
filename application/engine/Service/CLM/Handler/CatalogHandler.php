<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;

class CatalogHandler {

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

	public function getCatalogs ($filters = null) {
		$args = [];
		if (!empty($this->getAdmin())) {
			$args = [
				'catalogs.id_auth' => $this->admin['id_auth']
			];
		} else if (!empty($this->getUser())) {
			$args = [
				'catalogs.id_auth' => $this->user['id_auth']
			];
		} else {
			$args = [
				'catalogs.id_auth' => 1
			];
		}

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			$args['catalogs.publish_from <='] = $filters['current_time'];
			$args['catalogs.publish_until >='] = $filters['current_time'];
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
			'catalogs.id',
			'catalogs.thumbnail_url',
			'catalogs.catalog_pdf_url',
			'catalogs.publish_from',
			'catalogs.publish_until',
			'catalogs.created_at',
			'catalogs.updated_at',
			'catalogs.deleted_at',
		];
		$orderKey = 'catalogs.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$covers = $this->repository->findPaginated('catalogs', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $covers;
		return $this->delivery;
	}

	public function getCatalog ($filters = null) {
		$cover = $this->repository->findOne('catalogs', $filters);
		if (!empty($cover)) {
			$cover->images = $this->repository->find('catalog_images', ['id_catalog' => $cover->id]);
		}
		$this->delivery->data = $cover;
		return $this->delivery;
	}

	public function createCatalog ($payload) {
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
			$uploadResult = $digitalOceanService->upload($payload, 'thumbnail');
			$thumbnailUrl = $uploadResult['cdn_url'];
			$payload['thumbnail_url'] = $thumbnailUrl;


			$uploadResult = $digitalOceanService->upload($payload, 'catalog_pdf');
			$thumbnailUrl = $uploadResult['cdn_url'];
			$payload['catalog_pdf_url'] = $thumbnailUrl;

			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('catalogs', $payload);
			$covers = $this->repository->findOne('catalogs', ['id' => $action]);
			$this->delivery->data = $covers;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function createCatalogImage ($payload) {
		if (!empty($this->getAdmin())) {
			$payload['id_auth'] = $this->admin['id_auth'];
		}else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['id_catalog']) || empty($payload['id_catalog'])) {
			$this->delivery->addError(400, 'Catalog is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$findCatalog = $this->getCatalog(['id' => $payload['id_catalog']]);
		if (empty($findCatalog->data)) {
			$this->delivery->addError(400, 'Catalog is required');
			return $this->delivery;
		}

		try {
			$digitalOceanService = new DigitalOceanService();
			$uploadResult = $digitalOceanService->upload($payload, 'image');
			$thumbnailUrl = $uploadResult['cdn_url'];
			$payload['image_url'] = $thumbnailUrl;

			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('catalog_images', $payload);
			$image = $this->repository->findOne('catalog_images', ['id' => $action]);
			$this->delivery->data = $image;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateCatalog ($payload, $filters = null) {
		try {
			if (isset($_FILES['thumbnail']) && !empty($_FILES['thumbnail']['tmp_name'])) {
				$digitalOceanService = new DigitalOceanService();
				$uploadResult = $digitalOceanService->upload($payload, 'thumbnail');
				$payload['thumbnail_url'] = $uploadResult['cdn_url'];
			}

			if (isset($_FILES['catalog_pdf']) && !empty($_FILES['catalog_pdf']['tmp_name'])) {
				$digitalOceanService = new DigitalOceanService();
				$uploadResult = $digitalOceanService->upload($payload, 'catalog_pdf');
				$payload['catalog_pdf_url'] = $uploadResult['cdn_url'];
			}
			$action = $this->repository->update('catalogs', $payload, $filters);
			$result = $this->getCatalog($filters);
			return $result;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteCatalog ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('catalogs', $payload, ['id' => $id]);
			$result = $this->repository->findOne('catalogs', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteCatalogImage ($idCatalogImage) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('catalog_images', $payload, ['id' => $idCatalogImage]);
			$result = $this->repository->findOne('catalog_images', ['id' => $idCatalogImage]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

}