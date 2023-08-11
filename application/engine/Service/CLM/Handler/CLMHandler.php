<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Handler\OrderHandler;

class CLMHandler {

	const BASE_REWARD_FEE = 1000;

	private $delivery;
	private $repository;

	private $orderHandler;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->orderHandler = new OrderHandler($this->repository);
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

	public function getCLMStandings () {
		$standings = $this->repository->find('clm_standings');
		$this->delivery->data = $standings;
		return $this->delivery;
	}

	public function getCLMStanding ($filters = null) {
		$clm = $this->repository->findOne('clm_standings', $filters);

		$this->delivery->data = $clm;
		return $this->delivery;
	}

	public function createCLMStanding ($payload) {
		if (!isset($payload['level']) || empty($payload['level'])) {
			$this->delivery->addError(400, 'Level is required');
		}

		if (!isset($payload['reward_fee']) || empty($payload['reward_fee'])) {
			$this->delivery->addError(400, 'Reward fee is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}


		$existsStanding = $this->repository->findOne('clm_standings', ['level' => $payload['level']]);
		if (!empty($existsStanding)) {
			// update harga saja
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('clm_standings', $payload, ['level' => $payload['level']]);
		} else {
			$payload['created_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('clm_standings', $payload);
		}

		$result = $this->repository->findOne('clm_standings', ['level' => $payload['level']]); 
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function updateCLMTransactions ($payload, $filters = null) {
		if (!empty($this->getAdmin())) {

		} else {
			$this->delivery->addError(400, 'Not Allowed');
			return $this->delivery;
		}

		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('clm_transactions', $payload, $filters);
		$result = $this->getCLMTransactions($filters);
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

	public function getCLMTransactions ($filters = null) {
		$args = [];
		if (!empty($this->getUser())) {
			$args = [
				'clm_transactions.user_id' => $this->user['id']
			];
		} else if (!empty($this->getAdmin())) {

		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (isset($filters['user_id']) && !empty($filters['user_id'])) {
			$args['clm_transactions.user_id'] = $filters['user_id'];
		}

		if (isset($filters['is_settled']) && is_int($filters['is_settled'])) {
			$args['clm_transactions.is_settled'] = $filters['is_settled'];
		}

		if (isset($filters['from_created_at']) && !empty($filters['from_created_at'])) {
			$args['clm_transactions.created_at >='] = $filters['from_created_at'];
		}

		if (isset($filters['until_created_at']) && !empty($filters['until_created_at'])) {
			$args['clm_transactions.created_at <='] = $filters['until_created_at'];
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
			'clm_transactions.id',
			'clm_transactions.user_id',
			'users.first_name',
			'users.last_name',
			'users.username',
			'users.email',
			'users.phone',
			'clm_transactions.order_id',
			'orders.invoice_number',
			'clm_transactions.reward_fee',
			'clm_transactions.is_settled',
			'clm_transactions.settled_at',
			'clm_transactions.created_at',
			'clm_transactions.updated_at',
		];
		$orderKey = 'clm_transactions.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'orders' => 'orders.id_order = clm_transactions.order_id',
			'users' => 'users.id = clm_transactions.user_id'
		];
		$transactions = $this->repository->findPaginated('clm_transactions', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $transactions;
		return $this->delivery;
	}

	public function getCLMTransactionGroupByUsers ($filters = null) {
		$args = [];

		if (isset($filters['is_settled']) && is_int($filters['is_settled'])) {
			$args['is_settled'] = $filters['is_settled'];
		}

		if (isset($filters['user_id']) && !empty($filters['user_id'])) {
			$args['clm_transactions.user_id'] = $filters['user_id'];
		}

		if (isset($filters['from_created_at']) && !empty($filters['from_created_at'])) {
			$args['clm_transactions.created_at >='] = $filters['from_created_at'];
		}

		if (isset($filters['until_created_at']) && !empty($filters['until_created_at'])) {
			$args['clm_transactions.created_at <='] = $filters['until_created_at'];
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
			'users.id',
			'users.first_name',
			'users.last_name',
			'users.picImage as profile_picture',
			'users.bank_name as bank_name',
			'users.bank_account_name',
			'users.bank_account_number',
			'SUM(clm_transactions.reward_fee) as total_unsettled_reward_fee'
		];
		$orderKey = 'users.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'users' => 'users.id = clm_transactions.user_id'
		];
		$groupBy = 'users.id';
		$transactions = $this->repository->findPaginated('clm_transactions', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		$this->delivery->data = $transactions;
		return $this->delivery;
	}

	public function handle ($orderCode) {
		$orderResult = $this->orderHandler->getOrder(['order_code' => $orderCode]);
		if (empty($orderResult->data)) {
			$this->delivery->addError(400, 'Order not found in CLM');
			return $this->delivery;
		}

		$order = $orderResult->data;
		$user = $this->repository->findOne('users', ['id' => $order->user_id]);
		$userReferrer = null;
		$userReferralFirstPurchase = false;
		if (empty($order->referral_user_id) && empty($user->clm_referrer_by_user_id)) {
			$actionUserReferrer = $this->repository->update('users', ['clm_level' => 1], ['id' => $user->id]);
		}

		if (!empty($user->clm_referrer_by_user_id) && !empty($user->clm_level)) {
			// sudah pernah pake referral code
			$userReferrer = $this->repository->findOne('users', ['id' => $user->clm_referrer_by_user_id]);
		} else if (!empty($order->referral_user_id)) {
			// pertama kali pake referral code
			$userReferrer = $this->repository->findOne('users', ['id' => $order->referral_user_id]);
			if (empty($user->clm_referrer_by_user_id)) {
				$userReferrerCLMLevel = 1;
				if (empty($userReferrer->clm_referrer_by_user_id) || empty($userReferrer->clm_level)) {
					$actionUserReferrer = $this->repository->update('users', ['clm_level' => $userReferrerCLMLevel, 'clm_referrer_by_user_id' => null], ['id' => $userReferrer->id]);
				} else {
					$userReferrerCLMLevel = $userReferrer->clm_level;
				}
				$actionUser = $this->repository->update('users', ['clm_level' => $userReferrerCLMLevel + 1, 'clm_referrer_by_user_id' => $userReferrer->id], ['id' => $user->id]);
				$userReferralFirstPurchase = true;
			}
		}

		$rewardResult = $this->generateReward($userReferrer, $order, 1, $userReferralFirstPurchase);

		$this->delivery->data = $rewardResult;
		return $this->delivery;
	}

	public function generateReward ($userReferrer, $order, $currentGivenReward = 1, $firstPurchase = false) {
		if (empty($userReferrer)) {
			return null;
		}

		if ($currentGivenReward > 10) {
			return null;
		}

		$filters = [
			'order_id' => $order->id_order,
			'user_id' => $userReferrer->id
		];
		$existsTransaction = $this->repository->findOne('clm_transactions', ['order_id' => $order->id_order, 'user_id' => $userReferrer->id]);
		$clmStanding = $this->getCLMStanding(['level' => $userReferrer->clm_level]);
		$clmRewardFee = 1000;
		if (!empty($clmStanding->data)) {
			$clm = $clmStanding->data;
			$clmRewardFee = $clm->reward_fee;
		}

		// kalau referrer baru dipake pertama kali, rewardnya 10%, total_discount gunakan minus
		if ($firstPurchase) {
			$clmRewardFee = (int)($order->shopping_price + $order->total_discount) * 10 / 100;
		}

		if (empty($existsTransaction)) {
			$payload = [
				'order_id' => $order->id_order,
				'user_id' => $userReferrer->id,
				'reward_fee' => $clmRewardFee,
				'created_at' => date('Y-m-d H:i:s')
			];
			$actionReferrer = $this->repository->insert('clm_transactions', $payload);
		} else {
			$payload = [
				'reward_fee' => $clmRewardFee,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$actionReferrer = $this->repository->update('clm_transactions', $payload, $filters);
		}

		$nextReferrer = null;
		if (!empty($userReferrer->clm_referrer_by_user_id)) {
			$nextReferrer = $this->repository->findOne('users', ['id' => $userReferrer->clm_referrer_by_user_id]);
		}
		$result = $this->repository->findOne('clm_transactions', ['order_id' => $order->id_order, 'user_id' => $userReferrer->id]);
		$referrerResult = $this->generateReward($nextReferrer, $order, $currentGivenReward+1);
		$result->referrer = $referrerResult;
		return $result;
	}

}