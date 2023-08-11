<?php
namespace Service\Student;

use Service\Entity;
use Service\Delivery;

class StudentHandler {

	private $delivery;
	private $repository;

	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function setAdmin ($admin) {
		$this->admin = $admin;
	}

	public function getAdmin () {
		return $this->admin;
	}

	public function getStudents ($filters = null) {
		$args = [];
		$argsOrWhere = [];
		if (isset($filters['q']) && !empty($filters['q'])) {
			$argsOrWhere['name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['nik'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			unset($args['data']);
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			unset($args['page']);
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$select = [
			'students.id',
			'students.nik',
			'students.name',
			'students.created_at',
			'students.updated_at',
		];
		$orderKey = 'students.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$students = $this->repository->findPaginated('students', $args, $argsOrWhere, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $students;
		return $this->delivery;
	}

	public function getStudent ($filters = null) {
		$student = $this->repository->findOne('students', $filters);
		$this->delivery->data = $student;
		return $this->delivery;
	}

	public function createStudent ($payload) {

		if (!isset($payload['nik']) || empty($payload['nik'])) {
			$this->delivery->addError(400, 'NIK is required');
		}

		if (!isset($payload['name']) || empty($payload['name'])) {
			$this->delivery->addError(400, 'Name is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$existsByNik = $this->repository->findOne('students', ['nik' => $payload['nik']]);
		if (!empty($existsByNik)) {
			$this->delivery->addError(400, 'NIK already registered');
			return $this->delivery;
		}

		try {
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('students', $payload);
			$student = $this->repository->findOne('students', ['id' => $action]);
			$this->delivery->data = $student;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateStudent ($payload, $filters = null) {
		try {
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('students', $payload, $filters);
			$result = $this->repository->findOne('students', $filters);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteStudent ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('students', $payload, ['id' => $id]);
			$result = $this->repository->findOne('students', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

}