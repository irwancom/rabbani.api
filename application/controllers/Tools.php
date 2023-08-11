<?php
use Service\Command;
use Library\DomainService;
use Service\Delivery;
use Service\Validator;
use Service\MemberDigital\MemberDigitalHandler;

class Tools extends CI_Controller {

	private $command;

	public function __construct () {
		parent::__construct();
		$this->load->model('MainModel');
		$this->command = new Command($this->MainModel);
		$this->validator = new validator($this->MainModel);
		$this->delivery = new Delivery;
	}

	public function invoice()
	{
		// send invoice for renewal
		$this->command->sendInvoice();
	}

	public function suspend () {
		// suspend if collection expired
		$this->command->suspendCollection();
	}

	public function canceled () {
		// ignored transaction 2x24
		$this->command->cancelTransaction();
	}

	public function generate_user_service_invoice () {
		$this->command->generateUserServiceInvoice();
	}

	public function test () {
		// domain register
		$domainService = new DomainService;
		$domainAction = $domainService->register('betabanget', 'xyz', 1, 0, 0, 0, 1, 'NoInvoice', 1);
		print_r($domainAction);
		die();
	}

	public function recalculate () {
        set_time_limit(0);
        $secret = '2dc2968735c4fa0b047834a73ce5dff7a46a73871a37265a35e1e3eff8df72c3';
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $play = true;
        $page = 1;

        while ($play) {
        	echo 'Getting Page: '.$page.PHP_EOL;
            $filters = [
                'is_recalculated' => 0,
                'data' => 20,
                'page' => 1,
                'order_value' => 'ASC',
            ];
            $result = $handler->getMemberDigitals($filters);
            if ($result->hasErrors()) {
                $this->response($result->format(), $result->getStatusCode());
            }

            $members = $result->data['result'];
            if (empty($members)) {
                $play = false;
                break;
            }
            foreach ($members as $member) {
            	echo 'Processing ID: '.$member->id.PHP_EOL;
            	$memberPoint = $member->point;
                
            	// 2022 transactions
                $filterTransactions = [
                    'id_member_digital' => $member->id,
                    'from_created_at' => '2022-01-01 00:00:00',
                    'until_created_at' => '2022-12-31 23:59:59',
                    'data' => 200,
                ];
                $transactionsResult = $handler->getMemberDigitalTransactions($filterTransactions);
                $transactions2022 = $transactionsResult->data['result'];
                $point2022 = 0;
                foreach ($transactions2022 as $transaction) {
                	if (!empty($transaction->member_point)) {
                		$point2022 += $transaction->member_point;
                	}
                }

                // 2023 transactions
                $filterTransactions = [
                    'id_member_digital' => $member->id,
                    'from_created_at' => '2023-01-01 00:00:00',
                    'until_created_at' => '2023-12-31 23:59:59',
                    'data' => 200,
                ];
                $transactionsResult = $handler->getMemberDigitalTransactions($filterTransactions);
                $transactions2023 = $transactionsResult->data['result'];
                $point2023 = 0;
                $hasWithdraw2023 = false;
                $totalWithdraw2023 = 0;
                $activePoint2023 = 0;
                foreach ($transactions2023 as $transaction) {
                	if (!empty($transaction->member_point)) {
                		$point2023 += $transaction->member_point;
                		if ($transaction->member_point < 0) {
                			$hasWithdraw2023 = true;
                			$totalWithdraw2023 += $transaction->member_point;
                		} else {
                			$activePoint2023 += $transaction->member_point;
                		}
                	}
                }

                $finalPoint = 0;
                if (!empty($transactions2022) && empty($transactions2023)) {
                	// 2023 kosong
                	$finalPoint = $memberPoint - $point2022;
                	echo 'Case 1'.PHP_EOL;
	                echo 'Real Point: '.$memberPoint.PHP_EOL;
	                echo 'Total Point 2022: '.$point2022.PHP_EOL;
	                echo 'Final Point: '.$finalPoint.PHP_EOL;
	                if ($finalPoint == 0) {
	                	$payload = [
	                		'point' => $finalPoint,
	                		'point_2022' => $point2022,
	                		'is_recalculated' => 1,
	                		'updated_at' => date('Y-m-d H:i:s'),
	                	];

		                $filters = ['id' => $member->id];
		                $result = $handler->updateMemberDigitals($payload, $filters);
	                } else {
	                	echo 'Problem Case 1'.PHP_EOL;
	                	// jika ada problem catat point terakhir berdasarkan transaksi saja
	                	$payload = [
	                		'point' => 0,
	                		'point_2022' => $point2022,
	                		'is_recalculated' => 1,
	                		'updated_at' => date('Y-m-d H:i:s'),
	                	];

		                $filters = ['id' => $member->id];
		                $result = $handler->updateMemberDigitals($payload, $filters);
	                }
	                echo PHP_EOL;
                } else if (empty($transactions2022) && !empty($transactions2023)) {
                	// 2022 kosong
                	$finalPoint = $memberPoint - $point2022;
                	echo 'Case 2'.PHP_EOL;
	                echo 'Real Point: '.$memberPoint.PHP_EOL;
	                echo 'Total Point 2023: '.$point2023.PHP_EOL;
	                echo PHP_EOL;
	                if ($finalPoint == $point2023) {
	                	echo 'Final Point: '.$finalPoint.PHP_EOL;
	                	$payload = [
	                		'point' => $finalPoint,
	                		'point_2022' => $point2022,
	                		'is_recalculated' => 1,
	                		'updated_at' => date('Y-m-d H:i:s'),
	                	];

		                $filters = ['id' => $member->id];
		                $result = $handler->updateMemberDigitals($payload, $filters);
	                } else {
	                	echo 'Final Point: '.$point2023.PHP_EOL;
	                	$payload = [
	                		'point' => $point2023,
	                		'point_2022' => $point2022,
	                		'is_recalculated' => 1,
	                		'updated_at' => date('Y-m-d H:i:s'),
	                	];

		                $filters = ['id' => $member->id];
		                $result = $handler->updateMemberDigitals($payload, $filters);
	                	echo 'Problem in case 2. Member ID: '.$member->id.PHP_EOL;
	                }
                } else if (empty($transactions2022) && empty($transactions2023)) {
                	echo 'Case 3'.PHP_EOL;
	                echo 'Real Point: '.$memberPoint.PHP_EOL;
	                echo PHP_EOL;
	                if ($memberPoint == 0) {
	                	$payload = [
	                		'point' => $memberPoint,
	                		'point_2022' => $point2022,
	                		'is_recalculated' => 1,
	                		'updated_at' => date('Y-m-d H:i:s'),
	                	];

		                $filters = ['id' => $member->id];
		                $result = $handler->updateMemberDigitals($payload, $filters);
	                } else {
	                	echo 'Problem in case 3. Member ID: '.$member->id.PHP_EOL;
	                }
                } else {
                	echo 'Case 4'.PHP_EOL;
                	echo 'Current Point: '.$memberPoint.PHP_EOL;
                	echo 'Point 2022: '.$point2022.PHP_EOL;
                	echo 'Point 2023: '.$point2023.PHP_EOL;
                	if ($memberPoint == $point2022 + $point2023 && $point2023 >= 0) {
                		echo 'Final Point: '.$point2023.PHP_EOL;
                		$payload = [
	                		'point' => $point2023,
	                		'point_2022' => $point2022,
	                		'is_recalculated' => 1,
	                		'updated_at' => date('Y-m-d H:i:s'),
	                	];

		                $filters = ['id' => $member->id];
		                $result = $handler->updateMemberDigitals($payload, $filters);
                	} else if ($hasWithdraw2023) {
                		$totalWithdraw2023 *= -1;
                		if ($point2022 + $activePoint2023 > $totalWithdraw2023) {
                			// aktif point di 2022 masihh sisa setelah ditarik di 2023
                			// kalkulasi jumlah aktif point 2022 dengan aktif point 2023 dikurangkan dengan total withdraw 2023
                			$finalPoint = ($point2022+$activePoint2023 - $totalWithdraw2023);
                			if ($finalPoint > $activePoint2023) {
                				$finalPoint = $activePoint2023;
                			}
                			echo 'Sub 1'.PHP_EOL;
                			echo 'Active Point 2023: '.$activePoint2023.PHP_EOL;
                			echo 'Total Withdraw 2023: '.($totalWithdraw2023).PHP_EOL;
                			echo 'Final Point: '.$finalPoint.PHP_EOL;

	                		$payload = [
		                		'point' => $finalPoint,
		                		'point_2022' => $point2022,
		                		'is_recalculated' => 1,
		                		'updated_at' => date('Y-m-d H:i:s'),
		                	];

			                $filters = ['id' => $member->id];
			               	$result = $handler->updateMemberDigitals($payload, $filters);
                		} else if ($point2022 + $activePoint2023 < $totalWithdraw2023) {
                			// point 2022 dianggap hangus
                			// sisa point dianggap aktif
                			echo 'Sub 2'.PHP_EOL;
                			echo 'Active Point 2023: '.$activePoint2023.PHP_EOL;
                			echo 'Total Withdraw 2023: '.($totalWithdraw2023).PHP_EOL;
                			echo 'Final Point: '.($activePoint2023 + $point2022 - $totalWithdraw2023).PHP_EOL;

	                		$payload = [
		                		'point' => ($activePoint2023 + $point2022 - $totalWithdraw2023),
		                		'point_2022' => $point2022,
		                		'is_recalculated' => 1,
		                		'updated_at' => date('Y-m-d H:i:s'),
		                	];

			                $filters = ['id' => $member->id];
			               	$result = $handler->updateMemberDigitals($payload, $filters);
                		} else if ($point2022 + $point2023 == 0) {
                			// dapat point 2022
                			// semua dikeluarkan di 2023
                			echo 'Sub 3'.PHP_EOL;
                			echo 'Final Point: '.($point2022 + $point2023).PHP_EOL;
	                		$payload = [
		                		'point' => $point2022 + $point2023,
		                		'point_2022' => $point2022,
		                		'is_recalculated' => 1,
		                		'updated_at' => date('Y-m-d H:i:s'),
		                	];

			                $filters = ['id' => $member->id];
			               	$result = $handler->updateMemberDigitals($payload, $filters);
                		} else {
                			echo 'Problem in case 4 Sub 4. Member ID: '.$member->id.PHP_EOL;
                		}
                	} else if ($point2023 >= 0) {
                		// point 2022 tidak bermasalah
                		// point 2023 tidak bermasalah dan tidak pernah ditarik
                		echo 'Final Point: '.$point2023.PHP_EOL;
                		$payload = [
	                		'point' => $point2023,
	                		'point_2022' => $point2022,
	                		'is_recalculated' => 1,
	                		'updated_at' => date('Y-m-d H:i:s'),
	                	];

		                $filters = ['id' => $member->id];
		                $result = $handler->updateMemberDigitals($payload, $filters);
                	} else {
                		echo 'Problem in case 4. Member ID: '.$member->id.PHP_EOL;
                	}
                	echo PHP_EOL;
                }

                
            }
            $page++;
        }
    }

    public function recalculate_transactions () {
    	set_time_limit(0);
        $secret = '2dc2968735c4fa0b047834a73ce5dff7a46a73871a37265a35e1e3eff8df72c3';
        $auth = $this->validator->validateAuthApi($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new MemberDigitalHandler($this->MainModel, $auth->data);
        $play = true;
        $page = 1;

        while ($play) {
        	$filterTransactions = [
                'from_created_at' => '2023-01-01 00:00:00',
                'transaction_type' => 'shop_purchase',
                'wrong_member_point' => true,
                'data' => 200,
                'from_payment_amount' => 1
            ];
            $transactionsResult = $handler->getMemberDigitalTransactions($filterTransactions);
            $transactionsData = $transactionsResult->data;
            if (empty($transactionsData['result'])) {
            	die('ASD');
            	$play = false;
            	break;
            }
            $transactions = $transactionsData['result'];
            foreach ($transactions as $transaction) {
            	$args = [
            		'id' => $transaction->id_member_digital,
            	];
            	$memberDigitalHandle = $handler->getMemberDigital($args);
            	$memberDigital = $memberDigitalHandle->data;
            	
            	$expectedPoint = intval($transaction->payment_amount/100000);
            	$givenPoint = $transaction->member_point;
            	$adjustment = $expectedPoint - $givenPoint;
            	
            	$finalPoint = $memberDigital->point + $adjustment;
            	$payload = [
            		'point' => $finalPoint,
            		'updated_at' => date('Y-m-d H:i:s'),
            	];
            	$payloadTransaction = [
            		'member_point' => $expectedPoint,
            		'updated_at' => date('Y-m-d H:i:s'),
            	];
            	$action = $handler->updateMemberDigitals($payload, ['id' => $memberDigital->id]);
            	$actionTransaction = $handler->updateMemberDigitalTransactions($payloadTransaction, ['id' => $transaction->id]);
            	echo sprintf('Updating Member Digital Transaction ID: %s, final point transaction: %s, final point member: %s, adjustment: %s, given point: %s, amount: %s', $transaction->id, $expectedPoint, $finalPoint, $adjustment, $givenPoint, $transaction->payment_amount).PHP_EOL;
            	// die();
            }
            // die('ASD');
        }
    }
}
