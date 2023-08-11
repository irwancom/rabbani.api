<?php
namespace Service\MemberDigital;

use Carbon\Carbon;
use Service\Delivery;

class MemberDigitalReportHandler {

	private $repository;
	private $delivery;
	private $auth;

	public function __construct ($repository, $auth = null) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->auth = $auth;
	}

	public function getMemberDigitalStatusReport () {
		$now = Carbon::now();
		$select = [
			'COUNT(*) as total_member_digital'
		];
		$argsMemberDigitalPending = [
			'member_digitals.member_code' => null
		];
		$argsMemberDigitalActive = [
			'member_digitals.member_code <>' => null
		];
		$groupBy = 'member_digitals.id';
		$reportMemberDigitalPending = $this->repository->findOne('member_digitals', $argsMemberDigitalPending, null, null, $select, null, null, null, null, $groupBy);
		$reportMemberDigitalActive = $this->repository->findOne('member_digitals', $argsMemberDigitalActive, null, null, $select, null, null, null, null, $groupBy);
		$reportMemberDigitalAll = $this->repository->findOne('member_digitals', null, null, null, $select, null, null, null, null, $groupBy);
		$selectPeriod = [
			'YEAR(member_digitals.created_at) as year_created_at',
			'MONTH(member_digitals.created_at) as month_created_at',
			'COUNT(*) as total_member_digital'
		];
		$argsPeriod = [
			'member_digitals.created_at <=' => $now->format('Y-m-d 23:59:59'),
			'member_digitals.created_at >=' => $now->subMonth(12)->format('Y-m-d 00:00:00')
		];
		$groupByPeriod = 'YEAR(member_digitals.created_at), MONTH(member_digitals.created_at)';
		$reportPeriod = $this->repository->find('member_digitals', $argsPeriod, null, null, $selectPeriod, $groupByPeriod);

		$nowAffiliate = Carbon::now();
		$selectPeriodAffiliate = [
			'YEAR(member_digitals.affiliate_active_at) as year_created_at',
			'MONTH(member_digitals.affiliate_active_at) as month_created_at',
			'COUNT(*) as total_member_digital_affiliate'
		];
		$argsPeriodAffiliate = [
			'member_digitals.affiliate_active_at <=' => $nowAffiliate->format('Y-m-d 23:59:59'),
			'member_digitals.affiliate_active_at >=' => $nowAffiliate->subMonth(12)->format('Y-m-d 00:00:00')
		];
		$groupByPeriodAffiliate = 'YEAR(member_digitals.affiliate_active_at), MONTH(member_digitals.affiliate_active_at)';
		$reportPeriodAffiliate = $this->repository->find('member_digitals', $argsPeriodAffiliate, null, null, $selectPeriodAffiliate, $groupByPeriodAffiliate);

		$nowAffiliator = Carbon::now();
		$selectPeriodAffiliator = [
			'YEAR(member_digitals.affiliator_active_at) as year_created_at',
			'MONTH(member_digitals.affiliator_active_at) as month_created_at',
			'COUNT(*) as total_member_digital_affiliator'
		];
		$argsPeriodAffiliator = [
			'member_digitals.affiliator_active_at <=' => $nowAffiliator->format('Y-m-d 23:59:59'),
			'member_digitals.affiliator_active_at >=' => $nowAffiliator->subMonth(12)->format('Y-m-d 00:00:00')
		];
		$groupByPeriodAffiliator = 'YEAR(member_digitals.affiliator_active_at), MONTH(member_digitals.affiliator_active_at)';
		$reportPeriodAffiliator = $this->repository->find('member_digitals', $argsPeriodAffiliator, null, null, $selectPeriodAffiliator, $groupByPeriodAffiliator);

		$nowTransaction = Carbon::now();
		$selectPeriodTransaction = [
			'YEAR(member_digital_transactions.created_at) as year_created_at',
			'MONTH(member_digital_transactions.created_at) as month_created_at',
			'SUM(member_digital_transactions.payment_amount) as total_payment_amount',
			'SUM(member_digital_transactions.amount) as total_amount', // total reward fee
			'SUM(member_digital_transactions.member_point) as total_member_point'
		];
		$argsPeriodTransaction = [
			'member_digital_transactions.created_at <=' => $nowTransaction->format('Y-m-d 23:59:59'),
			'member_digital_transactions.created_at >=' => $nowTransaction->subMonth(12)->format('Y-m-d 00:00:00'),
		];
		$groupByPeriodTransaction = 'YEAR(member_digital_transactions.created_at), MONTH(member_digital_transactions.created_at)';
		$reportPeriodTransaction = $this->repository->find('member_digital_transactions', $argsPeriodTransaction, null, null, $selectPeriodTransaction, $groupByPeriodTransaction);

		foreach ($reportPeriod as $report) {
			$report->period = sprintf('%s-%s', $report->year_created_at, $report->month_created_at);
			$resultAffiliate = collect($reportPeriodAffiliate)->where('year_created_at', $report->year_created_at)->where('month_created_at', $report->month_created_at)->first();
			$report->total_member_affiliate = ($resultAffiliate) ? $resultAffiliate->total_member_digital_affiliate : 0;
			$resultAffiliator = collect($reportPeriodAffiliator)->where('year_created_at', $report->year_created_at)->where('month_created_at', $report->month_created_at)->first();
			$report->total_member_affiliator = ($resultAffiliator) ? $resultAffiliator->total_member_digital_affiliator : 0;
			$resultTransaction = collect($reportPeriodTransaction)->where('year_created_at', $report->year_created_at)->where('month_created_at', $report->month_created_at)->first();
			$report->total_payment_amount = ($resultTransaction) ? $resultTransaction->total_payment_amount : 0;
			$report->total_amount = ($resultTransaction) ? $resultTransaction->total_amount : 0;
			$report->total_point = ($resultTransaction) ? $resultTransaction->total_member_point : 0;
		}

		$now = Carbon::now();
		$selectAverage = [
			'DATE(member_digitals.created_at) as date_created_at',
			'COUNT(*) total_member_digital'
		];
		$argsAverage = [
			'member_digitals.created_at >=' => $now->startOfMonth()->format('Y-m-d 00:00:00'),
			'member_digitals.created_at <=' => $now->endOfMonth()->format('Y-m-d 23:59:59'),
		];
		$groupByAverage = 'DATE(member_digitals.created_at)';
		$reportAverage = $this->repository->find('member_digitals', $argsAverage, null, null, $selectAverage, $groupByAverage);
		$memberDigitalAveragePerDay = null;
		foreach ($reportAverage as $report) {
			$memberDigitalAveragePerDay += $report->total_member_digital;
		}
		$memberDigitalAveragePerDay = round($memberDigitalAveragePerDay/count($reportAverage));
		$report = [
			'member_digital_pending_total' => $reportMemberDigitalPending->total_member_digital,
			'member_digital_active_total' => $reportMemberDigitalActive->total_member_digital,
			'member_digital_total' => $reportMemberDigitalAll->total_member_digital,
			'member_digital_average_per_day' => $memberDigitalAveragePerDay,
			'member_digital_period' => $reportPeriod
		];

		$this->delivery->data = $report;
		return $this->delivery;
	}

	public function getMemberDigitalTransactionsReport () {
		$today = Carbon::now(); 
		$select = [
			'COUNT(member_digital_transactions.id) as total_transaction',
			'CAST(SUM(member_digital_transactions.payment_amount) AS UNSIGNED) as total_amount'
		];
		$argsTransactionDaily = [
			'member_digital_transactions.created_at >=' => $today->format('Y-m-d 00:00:00'),
			'member_digital_transactions.created_at <=' => $today->format('Y-m-d 23:59:59'),
			'transaction_type' => 'shop_purchase'
		];
		$argsTransactionMonthly = [
			'member_digital_transactions.created_at >=' => $today->startOfMonth()->format('Y-m-d 00:00:00'),
			'member_digital_transactions.created_at <=' => $today->endOfMonth()->format('Y-m-d 23:59:59'),
			'transaction_type' => 'shop_purchase'
		];
		$reportTransactionDaily = $this->repository->findOne('member_digital_transactions', $argsTransactionDaily, null, null, $select, null, null, null, null);
		$reportTransactionMonthly = $this->repository->findOne('member_digital_transactions', $argsTransactionMonthly, null, null, $select, null, null, null, null);
		
		$selectPeriod = [
			'YEAR(member_digital_transactions.created_at) as year_created_at',
			'MONTH(member_digital_transactions.created_at) as month_created_at',
			'SUM(member_digital_transactions.payment_amount) as total_payment_amount'
		];
		$argsPeriod = [
			'member_digital_transactions.created_at <=' => $today->format('Y-m-d 23:59:59'),
			'member_digital_transactions.created_at >=' => $today->subMonth(12)->format('Y-m-d 00:00:00')
		];
		$groupByPeriod = 'YEAR(member_digital_transactions.created_at), MONTH(member_digital_transactions.created_at)';
		$reportPeriod = $this->repository->find('member_digital_transactions', $argsPeriod, null, null, $selectPeriod, $groupByPeriod);
		foreach ($reportPeriod as $report) {
			$report->period = sprintf('%s-%s', $report->year_created_at, $report->month_created_at);
		}

		$now = Carbon::now();
		$selectAverage = [
			'DATE(member_digital_transactions.created_at) as date_created_at',
			'SUM(member_digital_transactions.payment_amount) total_payment_amount'
		];
		$argsAverage = [
			'member_digital_transactions.created_at >=' => $now->startOfMonth()->format('Y-m-d 00:00:00'),
			'member_digital_transactions.created_at <=' => $now->endOfMonth()->format('Y-m-d 23:59:59'),
			'transaction_type' => 'shop_purchase',
		];
		$groupByAverage = 'DATE(member_digital_transactions.created_at)';
		$reportAverage = $this->repository->find('member_digital_transactions', $argsAverage, null, null, $selectAverage, $groupByAverage);
		$transactionAmountAveragePerDay = null;
		foreach ($reportAverage as $report) {
			$transactionAmountAveragePerDay += $report->total_payment_amount;
		}
		$transactionAmountAveragePerDay = round($transactionAmountAveragePerDay/count($reportAverage));

		$report = [
			'daily_transactions' => $reportTransactionDaily,
			'monthly_transactions' => $reportTransactionMonthly,
			'average_amount_transaction_per_day' => $transactionAmountAveragePerDay,
			'period_transactions' => $reportPeriod,
		];
		$this->delivery->data = $report;
		return $this->delivery;
	}

	public function getMemberDigitalAffiliateStatusReport () {
		$selectAffiliator = [
			'COUNT(DISTINCT(member_digitals.referred_by_member_digital_id)) as total_member_digital'
		];
		$selectAffiliate = [
			'COUNT(member_digitals.id) as total_member_digital'
		];
		$argsAffiliate = [
			'member_digitals.referred_by_member_digital_id <>' => null
		];
		$reportMemberDigitalAffiliator = $this->repository->findOne('member_digitals', null, null, null, $selectAffiliator);
		$reportMemberDigitalAffiliate = $this->repository->findOne('member_digitals', $argsAffiliate, null, null, $selectAffiliate);

		$memberDigitalHandler = new MemberDigitalHandler($this->repository, $this->auth);
		$filterTopAffiliator = [
			'order_key' => 'total_affiliate',
			'order_value' => 'DESC',
			'data' => 10,
			'page' => 1
		];
		$topAffiliator = $memberDigitalHandler->getMemberDigitals($filterTopAffiliator);

		$now = Carbon::now();
		$selectAverage = [
			'DATE(member_digitals.created_at) as date_created_at',
			'COUNT(*) total_member_digital'
		];
		$argsAverage = [
			'member_digitals.created_at >=' => $now->startOfMonth()->format('Y-m-d 00:00:00'),
			'member_digitals.created_at <=' => $now->endOfMonth()->format('Y-m-d 23:59:59'),
			'referred_by_member_digital_id <>' => null
		];
		$groupByAverage = 'DATE(member_digitals.created_at)';
		$reportAverage = $this->repository->find('member_digitals', $argsAverage, null, null, $selectAverage, $groupByAverage);
		$memberDigitalAffiliateAveragePerDay = null;
		foreach ($reportAverage as $report) {
			$memberDigitalAffiliateAveragePerDay += $report->total_member_digital;
		}
		$memberDigitalAffiliateAveragePerDay = round($memberDigitalAffiliateAveragePerDay/count($reportAverage));

		$report = [
			'total_affiliator' => $reportMemberDigitalAffiliator->total_member_digital,
			'total_affiliate' => $reportMemberDigitalAffiliate->total_member_digital,
			'average_affiliate_per_day' => $memberDigitalAffiliateAveragePerDay,
			'top_affiliators' => $topAffiliator->data['result']
		];
		$this->delivery->data = $report;
		return $this->delivery;
	}

	public function getMemberDigitalAffiliateSalesReport () {
		$today = Carbon::now(); 
		$select = [
			'COUNT(member_digital_transactions.id) as total_transaction',
			'CAST(SUM(member_digital_transactions.payment_amount) AS UNSIGNED) as total_amount'
		];
		$argsTransactionDaily = [
			'member_digital_transactions.created_at >=' => $today->format('Y-m-d 00:00:00'),
			'member_digital_transactions.created_at <=' => $today->format('Y-m-d 23:59:59'),
		];
		$argsTransactionMonthly = [
			'member_digital_transactions.created_at >=' => $today->StartOfMonth()->format('Y-m-d 00:00:00'),
			'member_digital_transactions.created_at <=' => $today->endOfMonth()->format('Y-m-d 23:59:59'),
		];
		$args = [
			'member_digitals.referred_by_member_digital_id <>' => null,
			'transaction_type' => 'shop_purchase'
		];
		$join = [
			'member_digitals' => 'member_digitals.id = member_digital_transactions.id_member_digital'
		];
		$reportTransactionDaily = $this->repository->findOne('member_digital_transactions', array_merge($args, $argsTransactionDaily), null, $join, $select, null, null, null, null);
		$reportTransactionMonthly = $this->repository->findOne('member_digital_transactions', array_merge($args, $argsTransactionMonthly), null, $join, $select, null, null, null, null);
		$reportTransactionAll = $this->repository->findOne('member_digital_transactions', $args, null, $join, $select);
		
		$now = Carbon::now();
		$selectAverage = [
			'DATE(member_digital_transactions.created_at) as date_created_at',
			'SUM(member_digital_transactions.payment_amount) total_payment_amount'
		];
		$argsAverage = [
			'member_digital_transactions.created_at >=' => $now->startOfMonth()->format('Y-m-d 00:00:00'),
			'member_digital_transactions.created_at <=' => $now->endOfMonth()->format('Y-m-d 23:59:59'),
			'transaction_type' => 'shop_purchase',
			'member_digitals.referred_by_member_digital_id <>' => null
		];
		$groupByAverage = 'DATE(member_digital_transactions.created_at)';
		$reportAverage = $this->repository->find('member_digital_transactions', $argsAverage, null, $join, $selectAverage, $groupByAverage);
		$transactionAmountAveragePerDay = null;
		foreach ($reportAverage as $report) {
			$transactionAmountAveragePerDay += $report->total_payment_amount;
		}
		$transactionAmountAveragePerDay = round($transactionAmountAveragePerDay/count($reportAverage));
		$reportTransactionMonthly->average_amount_transaction_per_day = $transactionAmountAveragePerDay;
		$report = [
			'all_transaction' => $reportTransactionAll,
			'daily_transactions' => $reportTransactionDaily,
			'monthly_transactions' => $reportTransactionMonthly
		];
		$this->delivery->data = $report;
		return $this->delivery;
	}

	public function getMemberDigitalMarketingRewardReport () {
		$today = Carbon::now(); 
		$select = [
			'COUNT(member_digital_transactions.id) as total_transaction',
			'CAST(SUM(member_digital_transactions.amount) AS UNSIGNED) as total_amount',
		];
		$args = [
			'member_digital_transactions.transaction_type' => 'marketing_reward'
		];
		$reportMarketingReward = $this->repository->findOne('member_digital_transactions', $args, null, null, $select);

		$memberDigitalHandler = new MemberDigitalHandler($this->repository, $this->auth);
		$filterTopAffiliator = [
			'order_key' => 'member_digitals.balance_reward',
			'order_value' => 'DESC',
			'data' => 10,
			'page' => 1
		];
		$topAffiliator = $memberDigitalHandler->getMemberDigitals($filterTopAffiliator);

		$now = Carbon::now();
		$selectAverage = [
			'DATE(member_digital_transactions.created_at) as date_created_at',
			'SUM(member_digital_transactions.amount) total_amount'
		];
		$argsAverage = [
			'member_digital_transactions.created_at >=' => $now->startOfMonth()->format('Y-m-d 00:00:00'),
			'member_digital_transactions.created_at <=' => $now->endOfMonth()->format('Y-m-d 23:59:59'),
			'transaction_type' => 'marketing_reward',
		];
		$groupByAverage = 'DATE(member_digital_transactions.created_at)';
		$reportAverage = $this->repository->find('member_digital_transactions', $argsAverage, null, null, $selectAverage, $groupByAverage);
		$transactionAmountAveragePerDay = null;
		foreach ($reportAverage as $report) {
			$transactionAmountAveragePerDay += $report->total_amount;
		}
		$transactionAmountAveragePerDay = round($transactionAmountAveragePerDay/count($reportAverage));

		$report = [
			'total_transaction' => $reportMarketingReward->total_transaction,
			'total_amount' => $reportMarketingReward->total_amount,
			'average_amount_transaction_per_day' => $transactionAmountAveragePerDay,
			'top_affiliators' => $topAffiliator->data['result']
		];
		$this->delivery->data = $report;
		return $this->delivery;
	}

}