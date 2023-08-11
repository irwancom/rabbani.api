<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;

class SearchKeywordHandler {

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

	public function getSearchKeywords ($filters = null) {
		$args = [];
		$argsOrWhere = [];

		if (isset($filters['q']) && !empty($filters['q'])) {
			$args['text'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		if (isset($filters['flag'])) {
			$args['flag'] = $filters['flag'];
		}

		if (isset($filters['live']) && $filters['live']) {
			$argsOrWhere['publish_until >='] = date('Y-m-d H:i:s'); 
			$argsOrWhere['publish_until'] = null;
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
			'search_keywords.id',
			'search_keywords.text',
			'search_keywords.flag',
			'search_keywords.count',
			'search_keywords.publish_until',
			'search_keywords.created_at',
		];
		$orderKey = 'search_keywords.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$keywords = $this->repository->findPaginated('search_keywords', $args, $argsOrWhere, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $keywords;
		return $this->delivery;
	}

	public function getSearchKeyword ($filters = null) {
		$args = $filters;
		$keyword = $this->repository->findOne('search_keywords', $args);
		$this->delivery->data = $keyword;
		return $this->delivery;
	}

	public function createSearchKeyword ($payload) {
		if (!isset($payload['text']) || empty($payload['text'])) {
			$this->delivery->addError(400, 'Text is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$userId = (isset($payload['user_id'])) ? $payload['user_id'] : null;
		$payload['text'] = ucwords($payload['text']);

		$filters = [
			'text' => $payload['text']
		];
		$existsKeyword = $this->getSearchKeyword($filters);
		if (!empty($existsKeyword->data)) {
			$keyword = $existsKeyword->data;
			$updateData = [
				'count' => $keyword->count + 1
			];
			$action = $this->repository->update('search_keywords', $updateData, ['id' => $keyword->id]);
			$keywordResult = $this->getSearchKeyword(['id' => $keyword->id]);
			$historyAction = $this->createHistory($payload['text'], $userId);
			return $keywordResult;
		}

		$historyAction = $this->createHistory($payload['text'], $userId);

		try {
			$payload['flag'] = 0; // default 0 tidak tampil
			$payload['count'] = 1;
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('search_keywords', $payload);
			$keyword = $this->repository->findOne('search_keywords', ['id' => $action]);
			$this->delivery->data = $keyword;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateSearchKeyword ($payload, $filters = null) {
		$existsKeyword = $this->repository->findOne('search_keywords', $filters);
		if (empty($existsKeyword)) {
			$this->delivery->addError(409, 'No keywords found.');
			return $this->delivery;
		}

		unset($payload['text']);

		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('search_keywords', $payload, $filters);
		$result = $this->getSearchKeyword($filters);
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

	public function deleteSearchKeyword ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('search_keywords', $payload, ['id' => $id]);
			$result = $this->repository->findOne('search_keywords', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function getSearchKeywordHistory ($filters = null) {
		$args = $filters;
		$keyword = $this->repository->findOne('search_keyword_histories', $args);
		$this->delivery->data = $keyword;
		return $this->delivery;
	}

	public function createHistory ($text, $userId = null) {
		$existsHistory = $this->getSearchKeywordHistory(['text'=>$text, 'user_id'=>$userId]);
		$readyHistory = ($existsHistory->data && !empty($existsHistory->data)) ? $existsHistory->data->id : false;

		$historyData = array('updated_at'=>date('Y-m-d H:i:s'));
		if(!$readyHistory){
			$historyData['text'] = $text;
			$historyData['created_at'] = date('Y-m-d H:i:s');
			if($userId && !empty($userId) && !is_null($userId)){
				$historyData['user_id'] = $userId;
			}
		}
		//$historyData = [
			//'text' => $text,
			//'created_at' => date('Y-m-d H:i:s'),
			//'updated_at' => date('Y-m-d H:i:s')
		//];
		if(!$readyHistory){
			$action = $this->repository->insert('search_keyword_histories', $historyData);
		}else{
			$action = $this->repository->update('search_keyword_histories', $historyData, ['id'=>$readyHistory]);
		}
		return $action;
	}

	public function getSearchKeywordReports ($filters = null) {
		$args = [];
		if (isset($filters['from_created_at']) && !empty($filters['from_created_at'])) {
			$args['created_at >='] = $filters['from_created_at'];
		}

		if (isset($filters['until_created_at']) && !empty($filters['until_created_at'])) {
			$args['created_at <='] = $filters['until_created_at'];
		}

		$select = [
			'search_keyword_histories.text',
			'COUNT(search_keyword_histories.id) as total'
		];

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$orderKey = 'total';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$groupBy = 'search_keyword_histories.text';
		$keywords = $this->repository->findPaginated('search_keyword_histories', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		$this->delivery->data = $keywords;
		return $this->delivery;
	}

}