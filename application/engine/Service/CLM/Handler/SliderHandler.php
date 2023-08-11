<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;

class SliderHandler {

	private $delivery;
	private $repository;

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

	public function getSliders ($filters = null) {
		$args = [];
		/* if (!empty($this->getAdmin())) {
			$args = [
				'sliders.id_auth' => $this->admin['id_auth']
			];
		} else if (!empty($this->getUser())) {
			$args = [
				'sliders.user_id' => $this->user['id']
			];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		} */

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			$args['start_time <='] = $filters['current_time'];
			$args['end_time >='] = $filters['current_time'];
		}
		if (isset($filters['type']) && !empty($filters['type'])) {
			$args['type'] = $filters['type'];
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
			'sliders.id_slider',
			'sliders.type',
			'sliders.image_path',
			'sliders.start_time',
			'sliders.end_time',
			'sliders.created_at',
		];
		$orderKey = 'sliders.id_slider';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$sliders = $this->repository->findPaginated('sliders', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $sliders;
		return $this->delivery;
	}

	public function createSlider ($payload) {
		if (!isset($payload['start_time']) || empty($payload['start_time'])) {
			$this->delivery->addError(400, 'Start time is required');
		}

		if (!isset($payload['end_time']) || empty($payload['end_time'])) {
			$this->delivery->addError(400, 'End time is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		try {
			$uploadService = new DigitalOceanService();
			$result = $uploadService->upload($payload, 'image');
			$payload['image_path'] = $result['cdn_url'];
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('sliders', $payload);
			$slider = $this->repository->findOne('sliders', ['id_slider' => $action]);
			$this->delivery->data = $slider;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateSlider ($sliderId, $payload) {
		$slider = $this->repository->findOne('sliders', ['id_slider' => $sliderId]);
		if(!$slider || is_null($slider)){
			$this->delivery->addError(400, 'Slider not found ot not available');
		}

		if (!isset($payload['start_time']) || empty($payload['start_time'])) {
			$this->delivery->addError(400, 'Start time is required');
		}

		if (!isset($payload['end_time']) || empty($payload['end_time'])) {
			$this->delivery->addError(400, 'End time is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		try {
			if(isset($_FILES['image']) && $_FILES['image'] && !empty($_FILES['image']) && !is_null($_FILES['image']) && is_array($_FILES['image'])){
				$fileImage = $_FILES['image'];
				if(isset($fileImage['path_name']) && $fileImage['path_name'] && !empty($fileImage['path_name']) && !is_null($fileImage['path_name'])){
					$uploadService = new DigitalOceanService();
					$result = $uploadService->upload($payload, 'image');
					$payload['image_path'] = $result['cdn_url'];
				}
			}

			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('sliders', $payload, ['id_slider' => $sliderId]);
			$slider = $this->repository->findOne('sliders', ['id_slider' => $sliderId]);
			$this->delivery->data = $slider;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteSlider ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('sliders', $payload, ['id_slider' => $id]);
			$result = $this->repository->findOne('sliders', ['id_slider' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

}