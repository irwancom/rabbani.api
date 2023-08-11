<?php
namespace Service\MemberDigital;

use Service\Delivery;

class MemberDigitalMarketingHandler {

	private $repository;
	private $delivery;
	private $wablasDomain;
	private $auth;

	public function __construct ($repository, $auth = null) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->wablasDomain = 'https://selo.wablas.com';
		$this->auth = $auth;
	}

	public function handleTransaction ($transaction) {
		$memberDigitalHandler = new MemberDigitalHandler($this->repository);
		$memberResult = $memberDigitalHandler->getMemberDigital(['id' => $transaction->id_member_digital]);
		$member = $memberResult->data;
		if (!empty($member) && !empty($member->referred_by_member_digital_id)) {
			$memberReferrer = $memberDigitalHandler->getMemberDigital(['id' => $member->referred_by_member_digital_id]);
			$result = $this->generateReward($memberReferrer->data, $transaction, 1, 1);
			$this->delivery->data = $result;
		} else {
			$this->delivery->data = null;
		}
		return $this->delivery;
	}

	public function getMemberDigitalMarketingStandings ($filters = null) {
		$args = $filters;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'member_digital_marketing_standings.id',
			'member_digital_marketing_standings.level',
			'member_digital_marketing_standings.reward_type',
			'member_digital_marketing_standings.reward_amount',
		];
		$orderKey = 'member_digital_marketing_standings.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$standings = $this->repository->findPaginated('member_digital_marketing_standings', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $standings;
		return $this->delivery;
	}

	public function createMemberDigitalMarketingStandings ($payload) {
		if (!is_int($payload['level'])) {
			$this->delivery->addError(400, 'Level is required');
		}

		if (!isset($payload['reward_type']) || empty($payload['reward_type']) || !in_array($payload['reward_type'], ['percentage', 'amount'])) {
			$this->delivery->addError(400, 'Reward type is required');
		}

		if (!isset($payload['reward_amount'])) {
			$this->delivery->addErorr(400, 'Reward amount is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$existsLevel = $this->repository->findOne('member_digital_marketing_standings', ['level' => $payload['level']]);
		if (!empty($existsLevel)) {
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('member_digital_marketing_standings', $payload, ['level' => $existsLevel->level]);
		} else {
			$payload = [
				'level' => $payload['level'],
				'reward_type' => $payload['reward_type'],
				'reward_amount' => $payload['reward_amount'],
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('member_digital_marketing_standings', $payload);
		}
		$existsLevel = $this->repository->findOne('member_digital_marketing_standings', ['level' => $payload['level']]);
		$this->delivery->data = $existsLevel;
		return $this->delivery;
	}

	private function generateReward ($memberReferrer, $transaction, $currentGivenReward = 1, $maxRewardGiven) {
		if (empty($memberReferrer)) {
			return null;
		}

		if ($currentGivenReward > $maxRewardGiven) {
			return null;
		}

		$memberDigitalHandler = new MemberDigitalHandler($this->repository);
		$memberInTransaction = $memberDigitalHandler->getMemberDigital(['id' => $transaction->id_member_digital])->data;

		$rewardType = 'percentage';
		$rewardAmount = 5;
		$existsStanding = $this->repository->findOne('member_digital_marketing_standings', ['level' => $memberReferrer->member_digital_level]);
		if (!empty($existsStanding)) {
			$rewardType = $existsStanding->reward_type;
			$rewardAmount = $existsStanding->reward_amount;
		}

		if (empty($rewardAmount)) {
			return null;
		}

		$rewardFee = $rewardAmount;
		if ($rewardType == 'percentage') {
			$rewardFee = round($transaction->payment_amount * $rewardAmount / 100);
		}

		$transactionData = [
			'id_auth_api' => $memberReferrer->id_auth_api,
			'id_member_digital' => $memberReferrer->id,
			'transaction_type' => 'marketing_reward',
			'order_id' => '',
			'store_name' => '',
			'source_name' => '',
			'payment_amount' => null,
			'member_point' => null,
			'is_notified' => 0,
			'amount' => $rewardFee,
			'referred_by_member_digital_transaction_id' => $transaction->id,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];
		$actionReferrer = $this->repository->insert('member_digital_transactions', $transactionData);

		// update balance
		$memberReferrerData = [
			'balance_reward' => $memberReferrer->balance_reward + $rewardFee,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$actionMemberReferrer = $this->repository->update('member_digitals', $memberReferrerData, ['id' => $memberReferrer->id]);

		$nextReferrer = null;
		if (!empty($memberReferrer->referred_by_member_digital_id)) {
			$nextReferrerResult = $memberDigitalHandler->getMemberDigital(['id' => $memberReferrer->referred_by_member_digital_id]);
			$nextReferrer = $nextReferrerResult->data;
		}
		$result = $this->repository->findOne('member_digital_transactions', ['id' => $actionReferrer]);
		$waText = 'Anda mendapatkan komisi sebesar '.toRupiahFormat($rewardFee).' dari pembelajaan '.$memberInTransaction->name.'.';
		$sendWa = $memberDigitalHandler->sendWablasToMember($memberReferrer, $waText);
		$result->send_wa = $sendWa->data;

		$referrerResult = $this->generateReward($nextReferrer, $transaction, $currentGivenReward+1, $maxRewardGiven);
		$result->referrer = $referrerResult;
		return $result;
	}

	public function debitMemberDigitalBalance ($member, $payload) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
		}

		if (!isset($payload['amount']) || empty($payload['amount'])) {
			$this->delivery->addError(400, 'Amount is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$amount = $payload['amount'];
		if ($member->balance_reward < $amount) {
			$this->delivery->addError(400, 'Insufficient balance');
			return $this->delivery;
		}

		$transactionData = [
			'id_auth_api' => $member->id_auth_api,
			'id_member_digital' => $member->id,
			'transaction_type' => 'marketing_reward_withdraw',
			'order_id' => '',
			'store_name' => '',
			'source_name' => '',
			'payment_amount' => null,
			'member_point' => null,
			'is_notified' => 0,
			'amount' => $amount,
			'referred_by_member_digital_transaction_id' => null,
			'transfer_bank_name' => $member->bank_name,
			'transfer_bank_account_number' => $member->bank_account_number,
			'transfer_bank_account_name' => $member->bank_account_name,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];
		$action = $this->repository->insert('member_digital_transactions', $transactionData);

		$memberData = [
			'balance_reward' => $member->balance_reward - $amount,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$memberAction = $this->repository->update('member_digitals', $memberData, ['id' => $member->id]);

		//send wa
		$memberDigitalHandler = new MemberDigitalHandler($this->repository);
		$waText = sprintf('Pencairan komisi sebesar %s berhasil dilakukan, rekening penerima %s - %s a.n %s', toRupiahFormat($amount), $member->bank_name, $member->bank_account_number, $member->bank_account_name);
		$sendWa = $memberDigitalHandler->sendWablasToMember($member, $waText);

		$transaction = $this->repository->findOne('member_digital_transactions', ['id' => $action]);
		$member = $this->repository->findOne('member_digitals', ['id' => $member->id]);
		$this->delivery->data = [
			'transaction' => $transaction,
			'member_digital' => $member
		];
		return $this->delivery;
	}

	public function refundMemberDigitalBalance ($member, $payload) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
		}

		if (!isset($payload['amount']) || empty($payload['amount'])) {
			$this->delivery->addError(400, 'Amount is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$amount = $payload['amount'];

		$transactionData = [
			'id_auth_api' => $member->id_auth_api,
			'id_member_digital' => $member->id,
			'transaction_type' => 'marketing_reward_refund',
			'order_id' => '',
			'store_name' => '',
			'source_name' => '',
			'payment_amount' => null,
			'member_point' => null,
			'is_notified' => 0,
			'amount' => $amount,
			'referred_by_member_digital_transaction_id' => null,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];
		$action = $this->repository->insert('member_digital_transactions', $transactionData);

		$memberData = [
			'balance_reward' => $member->balance_reward + $amount,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$memberAction = $this->repository->update('member_digitals', $memberData, ['id' => $member->id]);

		//send wa
		$memberDigitalHandler = new MemberDigitalHandler($this->repository);
		$waText = sprintf('Saldo komisi anda dikembalikan sebesar %s', toRupiahFormat($amount));
		$sendWa = $memberDigitalHandler->sendWablasToMember($member, $waText);

		$transaction = $this->repository->findOne('member_digital_transactions', ['id' => $action]);
		$member = $this->repository->findOne('member_digitals', ['id' => $member->id]);
		$this->delivery->data = [
			'transaction' => $transaction,
			'member_digital' => $member
		];
		return $this->delivery;
	}

	public function getMemberDigitalAffiliator ($filters = null) {
		$args = [
			'member_digitals.id_auth_api' => $this->auth['id_auth']
		];

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'ref.id',
			'ref.name',
			'ref.phone_number',
			'ref.member_code',
			'ref.member_digital_level',
			'ref.balance_reward',
			'COUNT(member_digitals.id) as total_affiliate_member_digital',
		];
		$orderKey = 'ref.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'member_digitals as ref' => 'ref.id = member_digitals.referred_by_member_digital_id'
		];
		$groupBy = 'member_digitals.referred_by_member_digital_id';
		$members = $this->repository->findPaginated('member_digitals', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function removeAffiliate ($member) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}

		$data = [
			'member_digital_level' => null,
			'referred_by_member_digital_id' => null,
			'bank_name' => null,
			'bank_account_number' => null,
			'bank_account_name' => null,
			'referral_code' => null,
			'affiliate_active_at' => null
		];
		$action = $this->repository->update('member_digitals', $data, ['id' => $member->id]);
		$memberDigitalHandler = new MemberDigitalHandler($this->repository);
		$memberResult = $memberDigitalHandler->getMemberDigital(['id' => $member->id]);
		$this->delivery->data = $memberResult->data;
		return $this->delivery;
	}

	public function joinAffiliate ($member, $affiliator) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}

		if (empty($affiliator)) {
			$this->delivery->addError(400, 'Affiliator is required');
			return $this->delivery;
		}

		$data = [
			'member_digital_level' => $affiliator->member_digital_level + 1,
			'referred_by_member_digital_id' => $affiliator->id,
			'affiliate_active_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->update('member_digitals', $data, ['id' => $member->id]);
		$memberDigitalHandler = new MemberDigitalHandler($this->repository);
		$memberResult = $memberDigitalHandler->getMemberDigital(['id' => $member->id]);
		$this->delivery->data = $memberResult->data;
		return $this->delivery;
	}

}