<?php

use Service\Poll\PollMemberHandler;
use Service\Poll\PollVoteHandler;
use Library\TripayGateway;

class Command extends CI_Controller {

	private $command;

	public function __construct () {
		parent::__construct();
		$this->load->model('MainModel');
	}

	public function notify_poll_member () {
		$memberHandler = new PollMemberHandler($this->MainModel);
		$memberResult = $memberHandler->sendNotifyVote(1);
	}

	public  function recheck_transaction () {
		$voteHandler = new PollVoteHandler($this->MainModel);
		$args = [
			'from_created_at' => '2023-06-22 00:00:00',
			'until_created_at' => '2023-06-22 23:59:59',
		];
		$voteResult = $voteHandler->getVoteTransactions($args, false);
		$total = count($voteResult->data);

		$votes = $voteResult->data;
		$tripay = new TripayGateway;

        $tripay->setEnv('production');
        $tripay->setMerchantCode('T2286');
        $tripay->setApiKey('ZZUFAKR8Zp4UvC2Pcpaz9Bs0FdKu86Zq4SVrnKed');
        $tripay->setPrivateKey('Sfw9q-yuMNX-SK07D-qcggU-y1yCh');
		foreach ($votes['result'] as $key => $vote) {
			$tripayHandler = $tripay->detailTransaksiClosed($vote->payment_reference_no);
			$tripayData = $tripayHandler->data;
			if ($tripayData->status == 'PAID' && $vote->status != 'paid') {
				echo 'ID: '.$vote->id.' Tripay Reference: '.$vote->payment_reference_no.PHP_EOL;
				$approveAction = $voteHandler->approveVoteTransaction($vote);
				if ($approveAction->hasErrors()) {
					echo 'Error'.PHP_EOL;
				} else {
					echo 'Success'.PHP_EOL;
				}
			}
			echo ($key+1). '/'.$total.PHP_EOL;
		}
	}
}
