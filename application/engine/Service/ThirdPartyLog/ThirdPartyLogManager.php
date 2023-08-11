<?php
namespace Service\ThirdPartyLog;

use Service\Entity;
use Service\Delivery;

class ThirdPartyLogManager {

	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->uploadPath = getenv('UPLOAD_PATH') ? getenv('UPLOAD_PATH') . '/images' : "upload/images";
	}

	public function getLogs ($filters = null) {
		$args = [
			'third_party_logs.id_auth_api' => $this->auth['id_auth']
		];

		if (isset($filters['query'])) {
			foreach ($filters['query'] as $key => $value) {
				$jsonObj = $this->createJsonKey($key, $value);
				$args[] = [
					'condition' => 'custom',
					'value' => sprintf("JSON_EXTRACT(request_body, '$.%s') = '%s'", $jsonObj['key'], $jsonObj['value'])
				];
			}
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
			'third_party_logs.id',
			'third_party_logs.id_auth_api',
			'third_party_logs.request_body',
			'third_party_logs.created_at',
			'third_party_logs.updated_at'
		];
		$orderKey = 'third_party_logs.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$logs = $this->repository->findPaginated('third_party_logs', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($logs['result'] as $log) {
			$log->request_body = (isJson($log->request_body)) ? json_decode($log->request_body) : $log->request_body;
		}
		$this->delivery->data = $logs;
		return $this->delivery;
	}

	public function getLog ($filters = null) {
		$args = [];
		if (isset($filters['query'])) {
			foreach ($filters['query'] as $key => $value) {
				$jsonObj = $this->createJsonKey($key, $value);
				$args[] = [
					'condition' => 'custom',
					'value' => sprintf("JSON_EXTRACT(request_body, '$.%s') = '%s'", $jsonObj['key'], $jsonObj['value'])
				];
			}
		}
		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['third_party_logs.id'] = $filters['id'];
		}
		if (isset($filters['id_auth_api']) && !empty($filters['id_auth_api'])) {
			$args['third_party_logs.id_auth_api'] = $filters['id_auth_api'];
		}
		$log = $this->repository->findOne('third_party_logs', $args, null);
		if (!empty($log)) {
			$log->request_body = (isJson($log->request_body)) ? json_decode($log->request_body) : $log->request_body;
		}
		$this->delivery->data = $log;
		return $this->delivery;
	}

	public function createLog ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];
		$args = [];
		$args['id_auth_api'] = $this->auth['id_auth'];
		$decode = json_decode($payload['request_body'], true);
		$args[] = [
			'condition' => 'custom',
			'value' => sprintf("JSON_EXTRACT(request_body, '$.id') = '%s'", $decode['id'])
		];
		$existsLog = $this->repository->findOne('third_party_logs', $args);
		if (!empty($existsLog)) {
			$this->delivery->addError(409, 'Log already exists.');
			return $this->delivery;
		}
		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('third_party_logs', $payload);
		$result = $this->getLog(['id' => $action])->data;
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateLog ($payload, $filters = null) {
		$existsLog = $this->getLog($filters)->data;
		if (empty($existsLog)) {
			$this->delivery->addError(409, 'No log found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		unset($payload['external_id']);
		unset($payload['id_auth_api']);
		$action = $this->repository->update('third_party_logs', $payload, ['id' => $existsLog->id]);
		$result = $this->getLog($filters)->data;
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteLog ($filters = null) {
		$existsLog = $this->repository->find('third_party_logs', $filters);
		if (empty($existsLog)) {
			$this->delivery->addError(409, 'No log found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('third_party_logs', $payload, $filters);
		$result = $this->repository->find('third_party_logs', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	private function createJsonKey ($key, $value) {
		$result = [
			'key' => null,
			'value' => null
		];
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$result['key'] = $key.'.'.$this->createJsonKey($k, $v)['key'];
				$result['value'] = $this->createJsonKey($k, $v)['value'];
				return $result;
			}
		}
		$result['key'] = $key;
		$result['value'] = $value;
		return $result;
	}

}