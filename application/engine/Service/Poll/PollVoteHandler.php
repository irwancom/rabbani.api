<?php
namespace Service\Poll;

use Library\WablasService;
use Service\Delivery;
use \libphonenumber\PhoneNumberUtil;
use Library\DigitalOceanService;
use Library\TripayGateway;

class PollVoteHandler {

	const MAIN_WABLAS = '62895383334783';
	const VOTE_AMOUNT = 1250;

	const STATUS_WAITING_PAYMENT = 'waiting_payment';
	const STATUS_EXPIRED = 'expired';
	const STATUS_PAID = 'paid';

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;
	private $tripay;
	
	public function __construct ($repository, $auth = null) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		if (!empty($auth)) {
			$this->auth = $auth;
		}

		$this->tripay = new TripayGateway;
        // $this->tripay->setEnv('development');
        $this->tripay->setEnv('production');
        $this->tripay->setMerchantCode('T2286');
        $this->tripay->setApiKey('ZZUFAKR8Zp4UvC2Pcpaz9Bs0FdKu86Zq4SVrnKed');
        $this->tripay->setPrivateKey('Sfw9q-yuMNX-SK07D-qcggU-y1yCh');
	}

	public function getVoteTransactions ($filters = null, $paginated = true) {

		$argsOrWhere = [];
		$args = [];
		if (isset($filters['id'])) {
			$args['poll_vote_transactions.id'] = $filters['id'];
			unset($filters['id']);
		}
		if (isset($filters['poll_member_id']) && !empty($filters['poll_member_id'])) {
			$args['poll_vote_transactions.poll_member_id'] = $filters['poll_member_id'];
			unset($filters['poll_member_id']);
		}
		if (isset($filters['order_code']) && !empty($filters['order_code'])) {
			$args['poll_vote_transactions.order_code'] = $filters['order_code'];
			unset($filters['order_code']);
		}
		if (isset($filters['payment_reference_no']) && !empty($filters['payment_reference_no'])) {
			$args['poll_vote_transactions.payment_reference_no'] = $filters['payment_reference_no'];
			unset($filters['payment_reference_no']);
		}

		if (isset($filters['status']) && !empty($filters['status'])) {
			$args['poll_vote_transactions.status'] = $filters['status'];
			unset($filters['status']);
		}

		if (isset($filters['registration_number']) && !empty($filters['registration_number'])) {
			$args['poll_members.registration_number'] = $filters['registration_number'];
		}

		if (isset($filters['from_created_at']) && !empty($filters['from_created_at'])) {
			$args['poll_vote_transactions.created_at >='] = $filters['from_created_at'];
		}

		if (isset($filters['until_created_at']) && !empty($filters['until_created_at'])) {
			$args['poll_vote_transactions.created_at <='] = $filters['until_created_at'];
		}

		if (isset($filters['same_level']) && $filters['same_level'] === true) {
			$args[] = [
				'condition' => 'custom',
				'value' => 'poll_members.current_level = poll_vote_transactions.level'
			];
		}
		$join = [
			'poll_members' => 'poll_members.id = poll_vote_transactions.poll_member_id'
		];
		$select = [
			'poll_vote_transactions.id',
			'poll_vote_transactions.order_code',
			'poll_vote_transactions.invoice_number',
			'poll_vote_transactions.poll_member_id',
			'poll_members.registration_number as poll_member_registration_number',
			'poll_vote_transactions.level',
			'poll_vote_transactions.customer_phone_number',
			'poll_vote_transactions.status',
			'poll_vote_transactions.total_votes',
			'poll_vote_transactions.price',
			'poll_vote_transactions.total_price',
			'poll_vote_transactions.payment_fee_merchant',
			'poll_vote_transactions.payment_fee_customer',
			'poll_vote_transactions.payment_fee_total',
			'poll_vote_transactions.payment_amount',
			'poll_vote_transactions.payment_reference_no',
			'poll_vote_transactions.payment_method_code',
			'poll_vote_transactions.payment_method_name',
			'poll_vote_transactions.payment_method_instruction',
			'poll_vote_transactions.payment_method_instruction_qris_value',
			'poll_vote_transactions.checkout_url',
			'poll_vote_transactions.payment_expired_at',
			'poll_vote_transactions.is_paid',
			'poll_vote_transactions.paid_at',
			'poll_vote_transactions.created_at',
		];
		$offset = 0;
		$limit = 20;
		$orderKey = 'poll_members.id';
		$orderValue = 'ASC';
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		if ($paginated) {
			$transaction = $this->repository->findPaginated('poll_vote_transactions', $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue, null, $join);
		} else {
			$transaction = $this->repository->find('poll_vote_transactions', $args, $argsOrWhere, $join, $select);
			$transaction['result'] = $transaction;
		}
		$formattedTransaction = $transaction;
		if (isset($filters['public']) && $filters['public'] === true) {
			$groupTransaction = [];
			foreach ($transaction['result'] as $trans) {
				$phoneNumber = null;
				if ($trans->customer_phone_number != null) {
					$phoneNumber = substr($trans->customer_phone_number, 0, strlen($trans->customer_phone_number) -3).'XXX';
				}
				$groupTransaction[] = [
					'id' => $trans->id,
					'customer_phone_number' => $phoneNumber,
					'total_votes' => $trans->total_votes,
					'paid_at' => $trans->paid_at,
				];
			}
			$formattedTransaction['result'] = $groupTransaction;
		}
		$this->delivery->data = $formattedTransaction;
		return $this->delivery;
	}

	public function getVoteTransaction ($filters = null) {

		$argsOrWhere = [];
		if (isset($filters['id'])) {
			$filters['poll_vote_transactions.id'] = $filters['id'];
			unset($filters['id']);
		}
		if (isset($filters['order_code'])) {
			$filters['poll_vote_transactions.order_code'] = $filters['order_code'];
			unset($filters['id']);
		}
		if (isset($filters['payment_reference_no'])) {
			$filters['poll_vote_transactions.payment_reference_no'] = $filters['payment_reference_no'];
			unset($filters['id']);
		}
		$join = [
		];
		$select = [
			'poll_vote_transactions.id',
			'poll_vote_transactions.order_code',
			'poll_vote_transactions.invoice_number',
			'poll_vote_transactions.poll_member_id',
			'poll_vote_transactions.level',
			'poll_vote_transactions.customer_phone_number',
			'poll_vote_transactions.status',
			'poll_vote_transactions.total_votes',
			'poll_vote_transactions.price',
			'poll_vote_transactions.total_price',
			'poll_vote_transactions.payment_fee_merchant',
			'poll_vote_transactions.payment_fee_customer',
			'poll_vote_transactions.payment_fee_total',
			'poll_vote_transactions.payment_amount',
			'poll_vote_transactions.payment_reference_no',
			'poll_vote_transactions.payment_method_code',
			'poll_vote_transactions.payment_method_name',
			'poll_vote_transactions.payment_method_instruction',
			'poll_vote_transactions.payment_method_instruction_qris_value',
			'poll_vote_transactions.checkout_url',
			'poll_vote_transactions.payment_expired_at',
			'poll_vote_transactions.is_paid',
			'poll_vote_transactions.paid_at',
			'poll_vote_transactions.created_at',
		];
		$transaction = $this->repository->findOne('poll_vote_transactions', $filters, $argsOrWhere, $join, $select);
		if (!empty($transaction)) {
			if (!empty($transaction->payment_method_instruction) && isJson($transaction->payment_method_instruction)) {
				$transaction->payment_method_instruction = json_decode($transaction->payment_method_instruction);
			}
		}
		$this->delivery->data = $transaction;
		return $this->delivery;
	}

	public function approveVoteTransaction ($voteTransaction) {
		if ($voteTransaction->status == self::STATUS_PAID) {
			$this->delivery->addError(400, 'Vote transaction already paid');
			return $this->delivery;
		}
		$payloadUpdate = [
			'is_paid' => 1,
			'paid_at' => date('Y-m-d H:i:s'),
			'payment_method_name' => $voteTransaction->payment_method_code,
			'payment_method_code' => $voteTransaction->payment_method_name,
			'status' => self::STATUS_PAID
		];
		$action = $this->updatePollVoteTransactions($payloadUpdate, ['id' => $voteTransaction->id]);
		$resp = [];
		$memberHandler = new PollMemberHandler($this->repository);
		$member = $memberHandler->getPollMember(['id' => $voteTransaction->poll_member_id])->data;
		$addAction = $memberHandler->addTotalVoteToMember($member, $voteTransaction->total_votes);
		$member = $memberHandler->getPollMember(['id' => $voteTransaction->poll_member_id])->data;
		$level = $member->level;
		$resp['member'] = $member;

		if (!empty($voteTransaction->customer_phone_number)) {
			$order = $voteTransaction;
			$pollHandler = new PollHandler($this->repository);
			$message = 'Terimakasih!'.PHP_EOL.'Dukungan kamu sebanyak '.$order->total_votes.' VOTE untuk '.$member->name.' sudah kami terima.'.PHP_EOL.PHP_EOL.'Untuk memberi dukungan kembali, silahkan klik link '.$level['url'].PHP_EOL.'setiap VOTE kamu sangat berarti bagi *'.$member->name.'*'.PHP_EOL.PHP_EOL.'Selamat kaka mendapatkan voucher potongan harga khusus kaka sebesar 25% dengan kode voucher *VoteDPR*, cukup kaka tunjukan pesan ini pada kasir rabbani untuk mendapatkan potongan 25%.';
			$resp['notif_wa'] = $pollHandler->sendWablasByPhoneNumber($order->customer_phone_number, $message)->data;
		}
		$existsOrder = $this->getVoteTransaction(['id' => $voteTransaction->id]);
		$resp['order'] = $existsOrder->data;
		$this->delivery->data = $resp;
		return $this->delivery;
	}

	public function updatePollVoteTransactions ($payload, $filters) {
		$existsTransactions = $this->repository->find('poll_vote_transactions', $filters);
		if (empty($existsTransactions)) {
			$this->delivery->addError(400, 'No transactions found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		try {
			$action = $this->repository->update('poll_vote_transactions', $payload, $filters);
			$this->delivery->data = $action;
		} catch (\Exception $e) {
			$this->delivery->addError(400, 'Missing required fields');
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function checkVote ($payload) {
		$this->delivery->addError(400, 'Off competition');
    	return $this->delivery;
		
		if (empty($payload['registration_number'])) {
			$this->delivery->addError(400, 'Registration number is required');
			return $this->delivery;
		}
		$memberHandler = new PollMemberHandler($this->repository);
		$memberResult = $memberHandler->getPollMemberProfile(['registration_number' => $payload['registration_number']]);
		if ($memberResult->hasErrors()) {
			return $memberResult;
		}
		$member = $memberResult->data;
		if (!isset($member->level) || !isset($member->level['level']) || empty($member->level['level'])) {
			$this->delivery->addError(400, 'Invalid member');
			return $this->delivery;
		}
		
        $tripayResult = $this->tripay->channelPembayaran();
        $tripayResult = $tripayResult->data;
        $options = [];
        $paymentMethodCode = (isset($payload['payment_method_code']) && !empty($payload['payment_method_code']) ? $payload['payment_method_code'] : null);
        $paymentMethod = null;
        $totalVotes = (int)(isset($payload['total_votes']) && !empty($payload['total_votes']) ? $payload['total_votes'] : 0);
        if ($totalVotes <= 0) {
        	$this->delivery->addError(400, 'Vote is required');
        	return $this->delivery;
        }
        $totalPrice = $totalVotes * self::VOTE_AMOUNT;
        $paymentAmount = $totalPrice;
        foreach ($tripayResult as $tripay) {
            if ($tripay->group == 'E-Wallet') {
                $options[] = $tripay;
                if ($paymentMethodCode == $tripay->code) {
                	$paymentMethod = $tripay;
                }
            }
        }

        if (!empty($paymentMethodCode) && empty($paymentMethod)) {
        	$this->delivery->addError(400, 'Payment method is required');
        	return $this->delivery;
        }

        $feeMerchant = null;
        $feeCustomer = null;
        $totalFee = null;
        if (!empty($paymentMethod)) {

                $feeMerchant = ($totalPrice * $paymentMethod->fee_merchant->percent / 100) + $paymentMethod->fee_merchant->flat;
                $feeCustomer = ($totalPrice * $paymentMethod->fee_customer->percent / 100) + $paymentMethod->fee_customer->flat;
                $totalFee = ($totalPrice * $paymentMethod->total_fee->percent / 100) + $paymentMethod->total_fee->flat;

                $feeMerchant = ceil($feeMerchant);
                $feeCustomer = ceil($feeCustomer);
                $totalFee = ceil($totalFee);
                if ($paymentMethod->minimum_fee != null && $totalFee < $paymentMethod->minimum_fee) {
                	$totalFee = $paymentMethod->minimum_fee;
                	if ($feeMerchant > 0) {
                		$feeMerchant = $totalFee;
                	}
                	if ($feeCustomer > 9) {
                		$feeCustomer = $totalFee;
                	}
                }
                $paymentAmount += $totalFee;
        }

        $result = [
        	'member' => $member,
        	'level' => $member->level['level'],
        	'payment_method_code' => $paymentMethodCode,
        	'payment_method_options' => $options,
        	'payment_method' => $paymentMethod,
        	'fee_merchant' => $feeMerchant,
        	'fee_customer' => $feeCustomer,
        	'total_fee' => $totalFee,
        	'price' => self::VOTE_AMOUNT,
        	'total_votes' => $totalVotes,
        	'total_price' => $totalPrice,
        	'payment_amount' => $paymentAmount,
        ];

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function createVote ($payload) {
		$checkResult = $this->checkVote($payload);
		if ($checkResult->hasErrors()) {
			return $checkResult;
		}

		$customerPhoneNumber = (isset($payload['phone_number']) && !empty($payload['phone_number']) ? $payload['phone_number'] : null);

		$checkData = $checkResult->data;
		$paymentMethod = $checkData['payment_method'];
		$member = $checkData['member'];
		$level = $checkData['level'];
		$paymentMethod = $checkData['payment_method'];
		$feeMerchant = $checkData['fee_merchant'];
		$feeCustomer = $checkData['fee_customer'];
		$totalFee = $checkData['total_fee'];
		$price = $checkData['price'];
		$totalPrice = $checkData['total_price'];
		$totalVotes = $checkData['total_votes'];
		$paymentAmount = $checkData['payment_amount'];
		try {
			$this->repository->beginTransaction();

			$transactionData = [
				'order_code' => $this->generateOrderCode(),
				'invoice_number' => $this->generateInvoiceNumber(),
				'poll_member_id' => $member->id,
				'level' => $level,
				'customer_phone_number' => $customerPhoneNumber,
				'status' => self::STATUS_WAITING_PAYMENT,
				'total_votes' => $totalVotes,
				'price' => self::VOTE_AMOUNT,
				'total_price' => $checkData['total_price'],
				'payment_fee_merchant' => $feeMerchant,
				'payment_fee_customer' => $feeCustomer,
				'payment_fee_total' => $totalFee,
				'payment_amount' => $paymentAmount,
				'payment_reference_no' => null,
				'payment_method_code' => $paymentMethod->code,
				'payment_method_name' => $paymentMethod->name,
				'payment_method_instruction' => null,
				'payment_method_instruction_qris_value' => null,
				'checkout_url' => null,
				'payment_expired_at' => null,
				'paid_at' => null,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
			];

			$orderId = $this->repository->insert('poll_vote_transactions', $transactionData);

			// tripay
			$tripayCart = [
				[
					'sku' => 1,
					'name' => 'DPR VOTE '.$member->name,
					'price' => $transactionData['total_price'],
					'quantity' => 1
				]
			];
			$expiredAt = null;
			$callbackUrl = null;
			// gunakan amount sebelum dicharge fee (customer_fee)
			$tripayRequest = $this->tripay->requestTransaksi($transactionData['payment_method_code'], $transactionData['order_code'], $transactionData['total_price'], 'DPR VOTE', 'no-reply@rabbani.id', $transactionData['customer_phone_number'], $tripayCart, $expiredAt, $callbackUrl);
			if (isset($tripayRequest->isError)) {
				throw new \Exception('Payment problem.');
			}
			$tripayResult = $tripayRequest->data;
			$paymentExpiredAt = date('Y-m-d H:i:s', $tripayResult->expired_time);
			$updateData = [
				'payment_reference_no' => $tripayResult->reference,
				'payment_method_code' => $paymentMethod->code,
				'payment_method_name' => $paymentMethod->name,
				'checkout_url' => $tripayResult->checkout_url,
				'payment_method_instruction' => json_encode($tripayResult->instructions),
				'payment_method_instruction_qris_value' => (isset($tripayResult->qr_string) ? $tripayResult->qr_string : null),
				'payment_expired_at' => $paymentExpiredAt,
			];

			$action = $this->repository->update('poll_vote_transactions', $updateData, ['id' => $orderId]);
			$updateData['payment_method_instruction'] = json_decode($updateData['payment_method_instruction']);

			if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
			}

			$pollHandler = new PollHandler($this->repository);
			if (!empty($customerPhoneNumber)) {
				$message = '*VOTE '.$member->name.' SEKARANG JUGA!!*'.PHP_EOL.''.PHP_EOL.'silahkan lakukan pembayaran agar dukungan vote bertambah untuk _'.$member->name.'._'.PHP_EOL.''.PHP_EOL.'Ikuti step pembayaran di link berikut:'.PHP_EOL.$updateData['checkout_url'].''.PHP_EOL.''.PHP_EOL.'Dukung sebanyak-banyak, agar jagoan mu menjadi The Next DUTA PELAJAR RABBANI 2023';
				$action = $pollHandler->sendWablasByPhoneNumber($customerPhoneNumber, $message);
				$updateData['extras'] = $action->data;
			}
			$this->delivery->data = $updateData;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}

		return $this->delivery;
	}

	public function onTripayCallback ($payload) {

		$payload = (array)$payload;
		$reference = $payload['reference'];
		$merchantRef = $payload['merchant_ref'];
		$filterTrans = [
			'order_code' => $merchantRef,
			'payment_reference_no' => $reference
		];

		$existsOrder = $this->getVoteTransaction($filterTrans);
		$resp = [];
		if (!empty($existsOrder->data)) {
			$pollHandler = new PollHandler($this->repository);
			$order = $existsOrder->data;
			if ($payload['status'] == 'EXPIRED' && $order->status == self::STATUS_WAITING_PAYMENT) {
				
				$payloadUpdate = [
					'status' => self::STATUS_EXPIRED,
					'updated_at' => date("Y-m-d H:i:s")
				];
				$action = $this->updatePollVoteTransactions($payloadUpdate, $filterTrans);
				if (!empty($order->customer_phone_number)) {
					$message = 'Pesanan dengan no invoice '.$order->order_code.' telah kami batalkan karena waktu pembayaran telah habis.'.PHP_EOL.'Terima kasih';
					$resp['notif_wa'] = $pollHandler->sendWablasByPhoneNumber($order->customer_phone_number, $message)->data;
				}
			} else if ($payload['status'] == 'PAID' && $order->status != self::STATUS_PAID && !$order->is_paid) {
				$payloadUpdate = [
					'is_paid' => 1,
					'paid_at' => date('Y-m-d H:i:s'),
					'payment_method_name' => $payload['payment_method'],
					'payment_method_code' => $payload['payment_method_code'],
					'status' => self::STATUS_PAID
				];
				$action = $this->updatePollVoteTransactions($payloadUpdate, $filterTrans);

				$memberHandler = new PollMemberHandler($this->repository);
				$member = $memberHandler->getPollMember(['id' => $order->poll_member_id])->data;
				$addAction = $memberHandler->addTotalVoteToMember($member, $order->total_votes);
				$member = $memberHandler->getPollMember(['id' => $order->poll_member_id])->data;
				$level = $member->level;
				$resp['member'] = $member;

				if (!empty($order->customer_phone_number)) {
					$message = 'Terimakasih!'.PHP_EOL.'Dukungan kamu sebanyak '.$order->total_votes.' VOTE untuk '.$member->name.' sudah kami terima.'.PHP_EOL.PHP_EOL.'Untuk memberi dukungan kembali, silahkan klik link '.$level['url'].PHP_EOL.'setiap VOTE kamu sangat berarti bagi *'.$member->name.'*'.PHP_EOL.PHP_EOL.'Selamat kaka mendapatkan voucher potongan harga khusus kaka sebesar 25% dengan kode voucher *VoteDPR*, cukup kaka tunjukan pesan ini pada kasir rabbani untuk mendapatkan potongan 25%.';
					$resp['notif_wa'] = $pollHandler->sendWablasByPhoneNumber($order->customer_phone_number, $message)->data;
				}
			}
			$existsOrder = $this->getVoteTransaction($filterTrans);
			$resp['order'] = $existsOrder->data;
		}
		$this->delivery->data = $resp;
		return $this->delivery;
	}

	private function generateOrderCode () {
		return strtoupper('TPR-'.uniqid().time());
	}

	private function generateInvoiceNumber () {
		return strtoupper('INV-'.uniqid().time().'-DPR');
	}
}