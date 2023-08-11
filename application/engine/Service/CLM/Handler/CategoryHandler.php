<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;

class CategoryHandler {

	private $delivery;
	private $repository;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function getCategories ($filters = null) {
		$args = [];

		if (isset($filters['status'])) {
			$args['category.status'] = (int)$filters['status'];
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
			'category.id_category',
			'category.id_parent',
			'category.category_name',
			'category.category_slug',
			'category.image_path',
			'category.status'
		];
		$orderKey = 'category.id_category';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$sliders = $this->repository->findPaginated('category', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $sliders;
		return $this->delivery;
	}

}