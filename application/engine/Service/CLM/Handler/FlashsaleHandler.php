<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Calculator;

class FlashsaleHandler {

	private $delivery;
	private $repository;
	private $productDetailHandler;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->productDetailHandler = new ProductDetailHandler($this->repository);
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

	public function getFlashsales ($filters = null) {
		$args = [];
		if (!empty($this->getAdmin())) {
			$args = [
				'flash_sales.id_auth' => $this->admin['id_auth']
			];
		} else if (!empty($this->getUser())) {
			$args = [
				'flash_sales.id_auth' => $this->user['id_auth']
			];
		} else {
			$args = [
				'flash_sales.id_auth' => 1
			];
		}

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			// current_time >= start_time and current_time <= end_time
			$args[] = [
				'condition' => 'custom',
				'value' => sprintf("'%s' >= %s", $filters['current_time'], 'start_time')
			];
			$args[] = [
				'condition' => 'custom',
				'value' => sprintf("'%s' <= %s", $filters['current_time'], 'end_time')
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
			'flash_sales.id_flash_sale',
			'flash_sales.id_product_detail',
			'flash_sales.start_time',
			'flash_sales.end_time',
			'flash_sales.min_qty',
			'flash_sales.max_qty',
			'flash_sales.discount_type',
			'flash_sales.discount_value',
			'flash_sales.created_at',
			'flash_sales.flashsale_category',
		];
		$orderKey = 'flash_sales.id_flash_sale';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}


		$flashsales = $this->repository->findPaginated('flash_sales', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($flashsales['result'] as $flashsale) {
			$productDetailResult = $this->productDetailHandler->getProductDetail(['id_product_detail' => $flashsale->id_product_detail]);
			$productDetail = $productDetailResult->data;

			$calculator = new Calculator($this->repository, false);
			$calculatorResult = $calculator->getDetails($productDetail, 1);
			$productDetail->details = $calculatorResult;

			$flashsale->product_detail = $productDetail;
			$flashsale->details = $calculatorResult;
			$flashsale->current_time = date('Y-m-d H:i:s');


			// perhitungan progress bar
			$current = dateDiff(date('Y-m-d H:i:s'), $flashsale->end_time, 'second');
			$finish = dateDiff($flashsale->start_time, $flashsale->end_time, 'second');
			$progress = intval($current*100/$finish);
			if ($progress > 100) {
				$progress = 100;
			}
			$flashsale->progress = $progress;

			// cek category
			$flashsale->category = null;
			$cekCategory = $this->repository->findOne(
				'flashsale_categories', ['fscat_id' => $flashsale->flashsale_category], null, null, ['fscat_id','fscat_title','fscat_slug','fscat_pic']
			);
			if($cekCategory && !empty($cekCategory) && !is_null($cekCategory)){
				$flashsale->category = array();
				$flashsale->category['id'] = $cekCategory->fscat_id;
				$flashsale->category['title'] = $cekCategory->fscat_title;
				$flashsale->category['slug'] = $cekCategory->fscat_slug;
				$imgCatPic =  null;
				if($cekCategory->fscat_pic && !empty($cekCategory->fscat_pic) && !is_null($cekCategory->fscat_pic)){
					$imgCatPic = json_decode($cekCategory->fscat_pic)->cdn_url;
				}
				$flashsale->category['host_path'] = $imgCatPic;
				$flashsale->category['image_path'] = $imgCatPic;
			}

		}
		$this->delivery->data = $flashsales;
		return $this->delivery;
	}

	public function createFlashsale ($payload) {
		if (!empty($this->getAdmin())) {
			$payload['id_auth'] = $this->admin['id_auth'];
		}else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['start_time']) || empty($payload['start_time'])) {
			$this->delivery->addError(400, 'Start time is required');
		}

		if (!isset($payload['end_time']) || empty($payload['end_time'])) {
			$this->delivery->addError(400, 'End time is required');
		}

		if (!isset($payload['id_product_detail']) || empty($payload['id_product_detail'])) {
			$this->delivery->addError(400, 'Product Detail is required');
		}

		if (!isset($payload['min_qty']) || empty($payload['min_qty'])) {
			$this->delivery->addError(400, 'Minimum qty is required');
		}

		if (!isset($payload['max_qty']) || empty($payload['max_qty'])) {
			$this->delivery->addError(400, 'Maximum qty is required');
		}

		if (!isset($payload['discount_type']) || empty($payload['discount_type']) || !in_array($payload['discount_type'], [1,2])) {
			$this->delivery->addError(400, 'Discount type is required');
		}

		if (!isset($payload['discount_value']) || empty($payload['discount_value'])) {
			$this->delivery->addError(400, 'Discount value is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$productDetail = $this->productDetailHandler->getProductDetail(['id_product_detail' => $payload['id_product_detail']]);
		if (empty($productDetail->data)) {
			$this->delivery->addError(400, 'Product detaial is required');
			return $this->delivery;
		}

		try {
			$payload['status'] = 1;
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('flash_sales', $payload);
			$flashsale = $this->repository->findOne('flash_sales', ['id_flash_sale' => $action]);
			$this->delivery->data = $flashsale;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteFlashsale ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('flash_sales', $payload, ['id_flash_sale' => $id]);
			$result = $this->repository->findOne('flash_sales', ['id_flash_sale' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

}