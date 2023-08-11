<?php
namespace Service\Dictionary;

use Carbon\Carbon;
use Service\Delivery;

class DictionaryHandler {

	private $repository;
	private $delivery;
	private $auth;

	public function __construct ($repository, $auth = null) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->auth = $auth;
	}

	public function getDictionaries ($filters = []) {
		$args = [];
		if (isset($filters['word']) && !empty($filters['word'])) {
			$args['dictionaries.word'] = [
				'condition' => 'like',
				'value' => $filters['word']
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
			'dictionaries.id',
			'dictionaries.word',
			'dictionaries.rename_to',
			'dictionaries.created_at',
			'dictionaries.updated_at',
			'dictionaries.deleted_at'
		];
		$orderKey = 'dictionaries.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$dictionaries = $this->repository->findPaginated('dictionaries', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $dictionaries;
		return $this->delivery;
	}

	public function getDictionary ($filters = null) {
		$dictionary = $this->repository->findOne('dictionaries', $filters);
		$this->delivery->data = $dictionary;
		return $this->delivery;
	}

	public function getMemberDigitalAnswerByColumn ($column) {
		$args = [];

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'member_digitals.'.$column,
			'COUNT(member_digitals.id) as total'
		];
		$orderKey = 'total';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$groupBy = 'member_digitals.'.$column;

		$dictionaries = $this->repository->findPaginated('member_digitals', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		$this->delivery->data = $dictionaries;
		return $this->delivery;
	}

	public function createOrUpdateDictionary ($payload) {
		if (!isset($payload['word']) || empty($payload['word'])) {
			$this->delivery->addError(400, 'Word is required');
			return $this->delivery;
		}
		$existsDictionary = $this->repository->findOne('dictionaries', ['word' => $payload['word']]);
		if (!empty($existsDictionary)) {
			$updateData = [
				'rename_to' => $payload['rename_to'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('dictionaries', $updateData, ['word' => $payload['word']]);
		} else {
			$payload['created_at'] = date('Y-m-d H:i:s');		
			$action = $this->repository->insert('dictionaries', $payload);	
		}
		$result = $this->repository->findOne('dictionaries', ['word' => $payload['word']]);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function generateDictionaryToMemberDigital () {
		$result = [];
		$dictionaryCity = $this->getMemberDigitalDictionaryJoin('city');
		$dictionaryProvince = $this->getMemberDigitalDictionaryJoin('province');
		foreach ($dictionaryCity as $dictionary) {
			$data = [
				'city' => $dictionary->rename_to
			];
			$filters = [
				'city' => $dictionary->word
			];
			$action = $this->repository->update('member_digitals', $data, $filters);
			$result['city'][] = $dictionary;
		}
		foreach ($dictionaryProvince as $dictionary) {
			$data = [
				'province' => $dictionary->rename_to
			];
			$filters = [
				'province' => $dictionary->word
			];
			$action = $this->repository->update('member_digitals', $data, $filters);
		}
		$this->delivery->data = $result;
		return $this->delivery;
	}

	private function getMemberDigitalDictionaryJoin ($column) {
		$select = [
			'dictionaries.word',
			'dictionaries.rename_to',
		];
		$join = [
			'dictionaries' => 'dictionaries.word = member_digitals.'.$column
		];
		$groupBy = 'dictionaries.word';
		$result = $this->repository->find('member_digitals', null, null, $join, $select, $groupBy);
		return $result;
	}



}