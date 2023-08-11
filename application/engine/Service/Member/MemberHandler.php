<?php
namespace Service\Member;

use Library\WablasService;
use Service\Entity;
use Service\Delivery;
use Redis\Producer\MemberDigitalCardProducer;
use Redis\Producer\MemberDigitalBirthdayCardProducer;
use Milon\Barcode\DNS1D;
use mikehaertl\wkhtmlto\Image;
use Library\DigitalOceanService;
use \libphonenumber\PhoneNumberUtil;
use Service\Student\StudentHandler;
use Library\TripayGateway;

class MemberHandler {

	const MAIN_WABLAS = '6289658642914';
	const MAIN_TABLE = 'members';
	const TRANSACTION_TABLE = 'member_transactions';

	const TRANSACTION_STATUS_WAITING_PAYMENT = 'waiting_payment';
	const TRANSACTION_STATUS_PAYMENT_EXPIRED = 'payment_expired';
	const TRANSACTION_STATUS_PENDING = 'pending'; // sudah dibayar namun voucher belum ada
	const TRANSACTION_STATUS_SUCCESS = 'success';

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;
	private $askSourceText;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->wablasDomain = 'https://selo.wablas.com';
		$this->askSourceText = 'Silahkan masukkan kode poin:';
		// $this->wablasSecret = 'RrlxUeh9Y53DBGK7J2qcQr7N9yLiQLkUqfBm7F1qpLYVf3loICKTz8GmLHTc6iHn';
		// $this->waService = new WablasService($this->wablasDomain, $this->wablasSecret);
		$this->uploadPath = getenv('UPLOAD_PATH') ? getenv('UPLOAD_PATH') . '/images' : "upload/images";
	}

	/**
	 * Minta nama, tanggal lahir, jenis kelamin, alamat, provinsi, kabupaten, kota. Setelah itu generate image dan member id
	 * 
	 * 
	 **/
	public function callbackAction ($payload) {
		$result = '';
		/* $data = [
			'fromcall' => 'WABLAS',
			'dataJson' => json_encode($payload),
			'dateTime' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->insert('logcallback', $data); */

		if (empty(trim($payload['message']))) {
			$this->delivery->addError(409, 'Silahkan masukkan pesan');
			return $this->delivery;
		}

		$authApi = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $payload['receiver']]);
		if (empty($authApi)) {
			$this->delivery->addError(409, 'Config error!');
			return $this->delivery;
		}
		$dummyAuth = [
			'id_auth' => $authApi->id
		];
		$this->auth = $dummyAuth;

		$existsMember = $this->repository->findOne(self::MAIN_TABLE, ['phone_number' => $payload['phone']]);
		if (empty($existsMember)) {
			$newMember = [
				'id_auth_api' => $authApi->id_auth_api,
				'phone_number' => $payload['phone'],
				'created_at' => date('Y-m-d H:i:s'),
				'wablas_phone_number_receiver' => $payload['receiver'],
			];
			$action = $this->repository->insert(self::MAIN_TABLE, $newMember);
			$newMember['id'] = $action;
			$existsMember = $this->repository->findOne(self::MAIN_TABLE, ['id' => $newMember['id']]);

			$formattedPayload = [];
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $this->askSourceText
			];
			header('Content-Type: application/json');
			$result = json_encode(['data' => $formattedPayload]);

			$data = [
				'wablas_menu_state' => 'ask_source',
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('members', $data, ['id' => $existsMember->id]);
			$this->delivery->data = $result;
			return $this->delivery;
		}


		// $generalMenuText = sprintf('Status kak %s saat ini sudah menjadi member digital kami, silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.%s1. POIN : format untuk mengetahui total poin%s2. UPDATE : format untuk perubahan data%s3. TRANSAKSI : format untuk mengetahui histori transaksi%s4. CETAK : format untuk mendapatkan / cetak ulang kartu digital', $existsMember->name, PHP_EOL, PHP_EOL, PHP_EOL, PHP_EOL);
		// $generalMenuText = sprintf('Status kak %s saat ini sudah menjadi member digital kami, silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.%s1. POIN : format untuk mengetahui total poin%s2. UPDATE : format untuk perubahan data%s3. TRANSAKSI : format untuk mengetahui histori transaksi%s4. CETAK : format untuk mendapatkan / cetak ulang kartu digital', $existsMember->name, PHP_EOL, PHP_EOL, PHP_EOL, PHP_EOL).PHP_EOL;
		
		if ($existsMember->wablas_menu_state == 'ask_source') {
			$existsSource = $this->getSource(['id_auth_api' => $authApi->id_auth_api, 'code' => $payload['message']])->data;
			if (!empty($existsSource)) {

				$formattedPayload = [];
				$formattedPayload[] = $this->generateGeneralWablasMenuText($existsSource->id);
				header('Content-Type: application/json');
				$result = json_encode(['data' => $formattedPayload]);

				$data = [
					'wablas_menu_state' => 'show_voucher_lists',
					'temporary_source_id' => $existsSource->id,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('members', $data, ['id' => $existsMember->id]);
				$this->delivery->data = $result;
				return $this->delivery;
			}
			$this->delivery->addError(400, 'Kode point tidak ditemukan. Silahkan masukkan kode poin:');
			return $this->delivery;
		} else if ($existsMember->wablas_menu_state == 'show_voucher_lists') {
			$voucherAction = $this->getMemberDigitalVouchers(['data' => 10]);
			$voucherData = $voucherAction->data['result'];
			foreach ($voucherData as $voucher) {
				if (strpos($payload['message'], $voucher->name) !== false) {
					$data = [
						'wablas_menu_state' => 'show_voucher_variant_lists',
						'temporary_member_voucher_id' => $voucher->id,
						'updated_at' => date('Y-m-d H:i:s')
					];
					$action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);

					$formattedPayload = [];
					$formattedPayload[] = $this->generateVoucherVariantListText($voucher->id);
					header('Content-Type: application/json');
					$result = json_encode(['data' => $formattedPayload]);
					$this->delivery->data = $result;
					return $this->delivery;
				}
			}
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $this->askSourceText
			];
			$data = [
				'temporary_source_id' => null,
				'wablas_menu_state' => 'ask_source',
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);

			header('Content-Type: application/json');
			$result = json_encode(['data' => $formattedPayload]);
			$this->delivery->data = $result;
			return $this->delivery;
		} else if ($existsMember->wablas_menu_state == 'show_voucher_variant_lists') {
			$voucherAction = $this->getMemberDigitalVoucherVariants(['data' => 10, 'member_voucher_id' => $existsMember->temporary_member_voucher_id]);
			$voucherData = $voucherAction->data['result'];
			foreach ($voucherData as $voucher) {
				if (strpos($payload['message'], $voucher->name) !== false) {
					$data = [
						'wablas_menu_state' => 'show_payment_methods',
						'choose_member_voucher_variant_id' => $voucher->id,
						'updated_at' => date('Y-m-d H:i:s')
					];
					$action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);
					$tripayResult = $this->generateTripayPaymentMethod();

					$formattedPayload = [];
					$formattedPayload[] = $tripayResult['message'];
					header('Content-Type: application/json');
					$result = json_encode(['data' => $formattedPayload]);
					$this->delivery->data = $result;
					return $this->delivery;
				}
			}
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $this->askSourceText
			];
			$data = [
				'temporary_source_id' => null,
				'temporary_member_voucher_id' => null,
				'wablas_menu_state' => 'ask_source',
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);

			header('Content-Type: application/json');
			$result = json_encode(['data' => $formattedPayload]);
			$this->delivery->data = $result;
			return $this->delivery;
		} else if ($existsMember->wablas_menu_state == 'show_payment_methods') {
			$tripayResult = $this->generateTripayPaymentMethod();
			$tripayKey = $tripayResult['data'];
			foreach ($tripayKey as $key => $tpay) {
				if (strpos($payload['message'], $tpay) !== false) {					$voucher = $this->getMemberDigitalVoucherVariant(['id' => $existsMember->choose_member_voucher_variant_id])->data;
					$source = $this->getSource(['id' => $existsMember->temporary_source_id])->data;
					$tripayAction = $this->generateTripayInvoice($existsMember, $source, $voucher, $tripayResult['tripay'][$key]);
					if (!$tripayAction) {
						$message = 'Mohon maaf saat ini kami tidak bisa menggunakan metode pembayaran yang anda pilih. Silahkan pilih metode pembayaran yang lain atau hubungi kami untuk info lebih lanjut.';
						$this->delivery->addError(400, $message);
						return $this->delivery;
					}

					$data = [
						'choose_member_voucher_variant_id' => null,
						'temporary_member_voucher_id' => null,
						'temporary_source_id' => null,
						'wablas_menu_state' => null,
						'updated_at' => date('Y-m-d H:i:s')
					];
					$action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);
					$message = 'Terima kasih telah order voucher kami. Langkah selanjutnya silahkan ikuti cara pembayaran melalui link berikut: '.PHP_EOL.$tripayAction['tripay']->data->checkout_url;
					$this->delivery->addError(400, $message);
					return $this->delivery;
				}
			}
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $this->askSourceText
			];
			$data = [
				'temporary_source_id' => null,
				'temporary_member_voucher_id' => null,
				'choose_member_voucher_variant_id' => null,
				'wablas_menu_state' => 'ask_source',
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);

			header('Content-Type: application/json');
			$result = json_encode(['data' => $formattedPayload]);
			$this->delivery->data = $result;
			return $this->delivery;
		}


		try {
			$formattedPayload = [];
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $this->askSourceText,
			];
			$data = [
				'wablas_menu_state' => 'ask_source',
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);
			header('Content-Type: application/json');
			$result = json_encode(['data' => $formattedPayload]);
		} catch (\Exception $e) {
			$this->delivery->addError(500, 'Internal Server Error');
			return $this->delivery;
		}
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberDigitals ($filters = null) {
		$args = [];
		$args['members.id_auth_api'] = $this->auth['id_auth'];
		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['members.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['members.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['member_code']) && !empty($filters['member_code'])) {
			if ($filters['member_code'] == '~~') {
				$args['members.member_code <>'] = null;
			} else if ($filters['member_code'] == '~') {
				$args['members.member_code'] = null;
			} else {
				$args['members.member_code'] = [
					'condition' => 'like',
					'value' => $filters['member_code']
				];	
			}
		}

		if (isset($filters['from_id']) && !empty($filters['from_id'])) {
			$args['members.id >='] = $filters['from_id'];
		}

		if (isset($filters['until_id']) && !empty($filters['until_id'])) {
			$args['members.id <='] = $filters['until_id'];
		}

		if (isset($filters['from_updated_at']) && !empty($filters['from_updated_at'])) {
			$args['members.updated_at >='] = $filters['from_updated_at'];
		}

		if (isset($filters['until_updated_at']) && !empty($filters['until_updated_at'])) {
			$args['members.updated_at <='] = $filters['until_updated_at'];
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
			'members.id',
			'members.phone_number',
			'members.wablas_phone_number_receiver',
			'members.created_at',
			'members.updated_at',
		];
		$orderKey = 'members.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [];
		$groupBy = 'members.id';
		$members = $this->repository->findPaginated(self::MAIN_TABLE, $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		/* foreach ($members['result'] as $member) {
			if (!empty($member->referred_by_member_digital_id)) {
				$member->referred_by_member_digital = $this->getMemberDigital(['id' => $member->referred_by_member_digital_id])->data;
			}
		} */
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberDigital ($filters = null) {
		$argsOrWhere = null;
		if (isset($filters['iden'])) {
			$argsOrWhere = [
				'phone_number' => $filters['iden'],
				'id' => $filters['iden'],
			];
			unset($filters['iden']);
		}
		$member = $this->repository->findOne(self::MAIN_TABLE, $filters, $argsOrWhere);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberDigital ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];
		$existsMember = $this->repository->findOne(self::MAIN_TABLE, ['phone_number' => $payload['phone_number']]);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Member already exists.');
			return $this->delivery;
		}
		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert(self::MAIN_TABLE, $payload);
		$result = $this->repository->findOne(self::MAIN_TABLE, ['id' => $action]);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberDigitals ($payload, $filters = null) {
		$existsMembers = $this->repository->find(self::MAIN_TABLE, $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		// unset($payload['phone_number']);
		unset($payload['id_auth_api']);
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update(self::MAIN_TABLE, $payload, $filters);
		$result = $this->repository->find(self::MAIN_TABLE, $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberDigitals ($filters = null) {
		$existsMembers = $this->repository->find(self::MAIN_TABLE, $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update(self::MAIN_TABLE, $payload, $filters);
		$result = $this->repository->find(self::MAIN_TABLE, $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberDigitalTransactions ($filters = null) {
		$join = [
			self::MAIN_TABLE => 'members.id = member_transactions.member_id'
		];

		$args = [];

		$argsOrWhere = [];

		if (isset($filters['member_id']) && !empty($filters['member_id'])) {
			$args['member_transactions.member_id'] = $filters['member_id'];
		}

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['members.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['order_id']) && !empty($filters['order_id'])) {
			$args['member_transactions.order_id'] = [
				'condition' => 'like',
				'value' => $filters['order_id']
			];
		}

		if (isset($filters['transaction_type']) && !empty($filters['transaction_type'])) {
			$args['member_transactions.transaction_type'] = $filters['transaction_type'];
		}

		if (isset($filters['amount']) && !empty($filters['amount'])) {
			$args['member_transactions.amount'] = $filters['amount'];
		}

		if (isset($filters['status']) && !empty($filters['status'])) {
			$args['member_transactions.status'] = $filters['status'];
		}

		if (isset($filters['from_amount']) && !empty($filters['from_amount'])) {
			$args['member_transactions.amount >='] = $filters['from_amount'];
		}

		if (isset($filters['until_amount']) && !empty($filters['until_amount'])) {
			$args['member_transactions.amount <='] = $filters['until_amount'];
		}

		if (isset($filters['transaction_types']) && !empty($filters['transaction_types'])) {
			if (is_array($filters['transaction_types'])) {
				foreach ($filters['transaction_types'] as $type) {
					$argsOrWhere[] = sprintf('member_transactions.transaction_type = "%s"', $type);
				}
			} else {
				$argsOrWhere[] = sprintf('member_transactions.transaction_type = "%s"', $filters['transaction_types']);	
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
			'member_transactions.id',
			'member_transactions.member_id',
			'member_transactions.transaction_type',
			'member_transactions.status',
			'members.phone_number',
			'members.wablas_phone_number_receiver',
			'member_transactions.order_id',
			'member_transactions.payment_amount',
			'member_transactions.payment_method_name',
			'member_transactions.payment_reference_no',
			'member_transactions.payment_method_code',
			'member_transactions.shopping_amount',
			'member_transactions.shopping_amount',
			'member_transactions.created_at',
		];
		$orderKey = 'member_transactions.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated(self::TRANSACTION_TABLE, $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getMemberDigitalTransactionsAll ($filters = null) {
		$join = [
			self::MAIN_TABLE => 'members.id = member_transactions.member_id',
			'member_voucher_variants' => [
				'type' => 'left',
				'value' => 'member_voucher_variants.id = member_transactions.member_voucher_variant_id',
			],
			'member_vouchers' => [
				'type' => 'left',
				'value' => 'member_vouchers.id = member_voucher_variants.member_voucher_id'
			],
			'member_sources' => [
				'type' => 'left',
				'value' => 'member_sources.id = member_vouchers.member_source_id'
			]
		];

		$args = [];

		$argsOrWhere = [];

		if (isset($filters['member_id']) && !empty($filters['member_id'])) {
			$args['member_transactions.member_id'] = $filters['member_id'];
		}

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['members.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['order_id']) && !empty($filters['order_id'])) {
			$args['member_transactions.order_id'] = [
				'condition' => 'like',
				'value' => $filters['order_id']
			];
		}

		if (isset($filters['transaction_type']) && !empty($filters['transaction_type'])) {
			$args['member_transactions.transaction_type'] = $filters['transaction_type'];
		}

		if (isset($filters['amount']) && !empty($filters['amount'])) {
			$args['member_transactions.amount'] = $filters['amount'];
		}

		if (isset($filters['status']) && !empty($filters['status'])) {
			$args['member_transactions.status'] = $filters['status'];
		}

		if (isset($filters['from_amount']) && !empty($filters['from_amount'])) {
			$args['member_transactions.amount >='] = $filters['from_amount'];
		}

		if (isset($filters['until_amount']) && !empty($filters['until_amount'])) {
			$args['member_transactions.amount <='] = $filters['until_amount'];
		}

		if (isset($filters['from_created_at']) && !empty($filters['from_created_at'])) {
			$args['member_transactions.created_at >='] = $filters['from_created_at'];
		}

		if (isset($filters['until_created_at']) && !empty($filters['until_created_at'])) {
			$args['member_transactions.created_at <='] = $filters['until_created_at'];
		}

		if (isset($filters['transaction_types']) && !empty($filters['transaction_types'])) {
			if (is_array($filters['transaction_types'])) {
				foreach ($filters['transaction_types'] as $type) {
					$argsOrWhere[] = sprintf('member_transactions.transaction_type = "%s"', $type);
				}
			} else {
				$argsOrWhere[] = sprintf('member_transactions.transaction_type = "%s"', $filters['transaction_types']);	
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
			'member_transactions.id',
			'member_transactions.member_id',
			'member_transactions.transaction_type',
			'member_transactions.status',
			'members.phone_number',
			'members.wablas_phone_number_receiver',
			'member_transactions.order_id',
			'member_transactions.shopping_amount',
			'member_transactions.payment_amount',
			'member_transactions.payment_fee',
			'member_transactions.admin_fee',
			'member_transactions.additional_fee',
			'member_transactions.payment_method_name',
			'member_transactions.payment_reference_no',
			'member_transactions.payment_method_code',
			'member_transactions.shopping_amount',
			'member_transactions.shopping_amount',
			'member_transactions.created_at',
			'member_sources.id as member_source_id',
			'member_sources.name as member_source_name',
			'member_sources.code as member_source_code',
			'member_vouchers.name as member_voucher_name',
			'member_voucher_variants.name as member_voucher_variant_name',
		];
		$orderKey = 'member_transactions.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->find(self::TRANSACTION_TABLE, $args, $argsOrWhere, $join, $select, null, null, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getMemberDigitalTransaction ($filters = null) {
		$transaction = $this->repository->findOne('member_transactions', $filters);
		/* if (!empty($voucher)) {
			$codes = $this->repository->find('member_voucher_codes', ['member_digital_parent_voucher_id' => $voucher->id]);
			$voucher->codes = $codes;
		} */
		$this->delivery->data = $transaction;
		return $this->delivery;
	}

	/**
	 * Setiap 10.000 Payment Amount = 1 Poin
	 * Jika payload memiliki data "poin" maka perhitungan diatas tidak dihitung
 	 **/
	public function createMemberDigitalTransaction ($slug, $payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];

		$argsOrWhere = [
			'phone_number' => $slug,
			'id' => $slug
		];
		$existsMember = $this->repository->findOne(self::MAIN_TABLE, [], $argsOrWhere);
		if (empty($existsMember)) {
			$this->delivery->addError(409, 'Member not found.');
			return $this->delivery;
		}

		$existsOrderId = $this->repository->findOne(self::TRANSACTION_TABLE ,['order_id' => $payload['order_id']]);
		if (!empty($existsOrderId)) {
			$this->delivery->addError(409, 'Order ID already exists.');
			return $this->delivery;
		}

		$payload['member_id'] = $existsMember->id;
		$payload['created_at'] = date('Y-m-d H:i:s');

		$action = $this->repository->insert(self::TRANSACTION_TABLE, $payload);
		$transactionResult = $this->repository->findOne(self::TRANSACTION_TABLE, ['id' => $action]);
		$this->delivery->data = $transactionResult;
		return $this->delivery;
	}

	public function updateMemberDigitalTransactions ($payload, $filters = null) {
		$existsTransactions = $this->repository->find(self::TRANSACTION_TABLE, $filters);
		if (empty($existsTransactions)) {
			$this->delivery->addError(409, 'No transactions found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update(self::TRANSACTION_TABLE, $payload, $filters);
		$result = $this->repository->find(self::TRANSACTION_TABLE, $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberDigitalTransactions ($filters = null) {
		$existsTransactions = $this->repository->find(self::TRANSACTION_TABLE, $filters);
		if (empty($existsTransactions)) {
			$this->delivery->addError(409, 'No transactions found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update(self::TRANSACTION_TABLE, $payload, $filters);
		$result = $this->repository->find(self::TRANSACTION_TABLE, $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberDigitalVouchers ($filters = null) {
		$args = [];
		$args['member_vouchers.id_auth_api'] = $this->auth['id_auth'];
		$join = [
			'member_sources' => 'member_sources.id = member_vouchers.member_source_id AND member_sources.deleted_at is null'
		];
		$altJoin = [
			'member_sources' => 'member_sources.id = member_vouchers.member_source_id AND member_sources.deleted_at is null'
		];
		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_vouchers.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['member_vouchers.id'] = $filters['id'];
		}

		if (isset($filters['member_source_id']) && !empty($filters['member_source_id'])) {
			$args['member_vouchers.member_source_id'] = $filters['member_source_id'];
		}

		if (isset($filters['member_source_code']) && !empty($filters['member_source_code'])) {
			$args['member_sources.code'] = $filters['member_source_code'];
		}
		/* if (isset($filters['has_left'])) {
			if ($filters['has_left'] == "~") {
				$altJoin['member_voucher_codes'] = 'member_voucher_codes.member_voucher_id = member_vouchers.id AND member_voucher_codes.is_purchased = 0';
				$join['member_voucher_codes'] = 'member_voucher_codes.member_voucher_id = member_vouchers.id AND member_voucher_codes.is_purchased = 0';
				// $args['member_voucher_codes.is_purchased'] = 0;
			} else if ($filters['has_left'] == "~~") {
				$altJoin['member_voucher_codes'] = 'member_voucher_codes.member_voucher_id = member_vouchers.id AND member_voucher_codes.is_purchased = 1';
				$join['member_voucher_codes'] = 'member_voucher_codes.member_voucher_id = member_vouchers.id AND member_voucher_codes.is_purchased = 1';
			}
		} */

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'member_vouchers.id',
			'member_vouchers.member_source_id',
			'member_sources.code',
			'member_vouchers.name',
			'member_vouchers.price',
			'member_vouchers.created_at',
			'member_vouchers.updated_at'
		];
		$orderKey = 'member_vouchers.id';
		$groupBy = 'member_vouchers.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('member_vouchers', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy, $altJoin, $groupBy, 'COUNT(distinct member_vouchers.id) as total');
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getMemberDigitalVoucher ($filters = null) {
		$args = [];
		$join = [
		];

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['member_vouchers.id'] = (int)$filters['id'];
		}
		$select = [
			'member_vouchers.id',
			'member_vouchers.name',
			'member_vouchers.price',
			'member_vouchers.created_at',
			'member_vouchers.updated_at'
		];
		$voucher = $this->repository->findOne('member_vouchers', $args, null, $join, $select);
		if (empty($voucher->id)) {
			$voucher = null;
		}
		/* if (!empty($voucher)) {
			$codes = $this->repository->find('member_voucher_codes', ['member_digital_parent_voucher_id' => $voucher->id]);
			$voucher->codes = $codes;
		} */
		$this->delivery->data = $voucher;
		return $this->delivery;
	}

	public function createMemberDigitalVouchers ($payload) {
		if (!isset($payload['name']) || empty($payload['name'])) {
			$this->delivery->addError(400, 'Name is required');
		}
		if (!isset($payload['price']) || empty($payload['price'])) {
			$this->delivery->addError(400, 'Price is required');
		}

		if (!isset($payload['member_source_id']) || empty($payload['member_source_id'])) {
			$this->delivery->addError(400, 'Source is required');
		}

		if (!isset($payload['admin_fee']) || empty($payload['admin_fee'])) {
			$payload['admin_fee'] = 0;
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$source = $this->getSource(['id' => $payload['member_source_id']])->data;
		if (empty($source)) {
			$this->delivery->addError(400, 'Source is required');
			return $this->delivery;
		}

		try {
			$builder = [
				'id_auth_api' => $this->auth['id_auth'],
				'member_source_id' => $source->id,
				'name' => $payload['name'],
				'price' => (int)$payload['price'],
				'admin_fee' => (int)$payload['admin_fee'],
				'created_at' => date("Y-m-d H:i:s"),
			];
			$action = $this->repository->insert('member_vouchers', $builder);
			$result = $this->repository->findOne('member_vouchers', ['id' => $action]);

		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function updateMemberDigitalVouchers ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_vouchers', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No voucher found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_vouchers', $payload, $filters);
		$result = $this->repository->find('member_vouchers', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberDigitalVouchers ($filters = null) {
		$existsMembers = $this->repository->find('member_vouchers', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No voucher found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_vouchers', $payload, $filters);
		$result = $this->repository->find('member_vouchers', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberDigitalVoucherVariants ($filters = null) {
		$args = [];
		$join = [
			'member_vouchers' => 'member_voucher_variants.member_voucher_id = member_vouchers.id and member_vouchers.deleted_at is null',
			'member_sources' => 'member_sources.id = member_vouchers.member_source_id and member_sources.deleted_at is null',
		];
		$altJoin = $join;
		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_vouchers.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['member_voucher_id']) && !empty($filters['member_voucher_id'])) {
			$args['member_voucher_variants.member_voucher_id'] = $filters['member_voucher_id'];
		}

		if (isset($filters['member_source_id']) && !empty($filters['member_source_id'])) {
			$args['member_sources.id'] = $filters['member_source_id'];
		}

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['member_voucher_variants.id'] = $filters['id'];
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
			'member_voucher_variants.id',
			'member_voucher_variants.name',
			'member_voucher_variants.member_voucher_id',
			'member_vouchers.name as member_voucher_name',
			'member_sources.id as member_source_id',
			'member_sources.code',
			'member_sources.name as member_source_name',
			'member_voucher_variants.price',
			'member_voucher_variants.admin_fee',
			'member_voucher_variants.additional_fee',
			'member_voucher_variants.created_at',
			'member_voucher_variants.updated_at',
			'member_voucher_variants.deleted_at',
		];
		$orderKey = 'member_voucher_variants.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('member_voucher_variants', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, null, $altJoin, null, 'COUNT(distinct member_voucher_variants.id) as total');
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getMemberDigitalVoucherVariant ($filters = null) {
		$args = [];
		$join = [
			'member_vouchers' => 'member_voucher_variants.member_voucher_id = member_vouchers.id and member_vouchers.deleted_at is null',
			'member_sources' => 'member_sources.id = member_vouchers.member_source_id and member_sources.deleted_at is null',
		];

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['member_voucher_variants.id'] = (int)$filters['id'];
		}
		$select = [
			'member_voucher_variants.id',
			'member_voucher_variants.name',
			'member_voucher_variants.price',
			'member_voucher_variants.admin_fee',
			'member_voucher_variants.additional_fee',
			'member_voucher_variants.created_at',
			'member_voucher_variants.updated_at'
		];
		$voucher = $this->repository->findOne('member_voucher_variants', $args, null, $join, $select);
		if (empty($voucher->id)) {
			$voucher = null;
		}
		$this->delivery->data = $voucher;
		return $this->delivery;
	}

	public function createMemberDigitalVoucherVariant ($payload) {

		if (!isset($payload['member_voucher_id']) || empty($payload['member_voucher_id'])) {
			$this->delivery->addError(400, 'Voucher is required');
		}

		if (!isset($payload['admin_fee']) || empty($payload['admin_fee'])) {
			$payload['admin_fee'] = 0;
		}

		if (!isset($payload['additional_fee']) || empty($payload['additional_fee'])) {
			$payload['additional_fee'] = 0;
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$voucher = $this->getMemberDigitalVoucher(['id' => $payload['member_voucher_id']])->data;
		if (empty($voucher)) {
			$this->delivery->addError(400, 'Voucher is required');
			return $this->delivery;
		}

		try {
			$builder = [
				'name' => $payload['name'],
				'member_voucher_id' => $voucher->id,
				'price' => (int)$payload['price'],
				'admin_fee' => (int)$payload['admin_fee'],
				'additional_fee' => (int)$payload['additional_fee'],
				'created_at' => date("Y-m-d H:i:s"),
			];
			$action = $this->repository->insert('member_voucher_variants', $builder);
			$result = $this->repository->findOne('member_voucher_variants', ['id' => $action]);

			if (isset($payload['quota']) && !empty((int)$payload['quota'])) {
				$suffix = generateRandomString(2);
				if (isset($payload['prefix']) && !empty($payload['prefix'])) {
					$suffix = $payload['prefix'];
				}
				$quota = (int)$payload['quota'];
				for ($i = 0; $i < $quota; $i++) {
					$randomCode = $suffix.generateRandomString(5, 'alphanumeric_uppercase');
					$codeBuilder = [
						'member_voucher_variant_id' => $result->id,
						'code' => $randomCode,
					];
					$codeAction = $this->createMemberDigitalVoucherCodes($codeBuilder);
				}
			}

		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function updateMemberDigitalVoucherVariants ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_voucher_variants', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No voucher variant found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_voucher_variants', $payload, $filters);
		$result = $this->repository->find('member_voucher_variants', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberDigitalVoucherVariants ($filters = null) {
		$existsMembers = $this->repository->find('member_voucher_variants', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No voucher variant found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_voucher_variants', $payload, $filters);
		$result = $this->repository->find('member_voucher_variants', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberDigitalVoucherCodes ($filters = null) {
		$args = [];
		$join = [
			'member_voucher_variants' => 'member_voucher_variants.id = member_voucher_codes.member_voucher_variant_id',
			'member_vouchers' => 'member_vouchers.id = member_voucher_variants.member_voucher_id AND member_vouchers.deleted_at is null'
		];
		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_vouchers.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['member_voucher_codes.id'] = $filters['id'];
		}

		if (isset($filters['code']) && !empty($filters['code'])) {
			$args['member_voucher_codes.code'] = $filters['code'];
		}

		if (isset($filters['is_purchased'])) {
			$args['member_voucher_codes.is_purchased'] = $filters['is_purchased'];
		}

		if (isset($filters['is_used'])) {
			$args['member_voucher_codes.is_used'] = $filters['is_used'];
		}

		if (isset($filters['member_voucher_variant_id']) && !empty($filters['member_voucher_variant_id'])) {
			$args['member_voucher_codes.member_voucher_variant_id'] = $filters['member_voucher_variant_id'];
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
			'member_voucher_codes.id',
			'member_voucher_codes.member_voucher_variant_id',
			'member_vouchers.name as member_voucher_name',
			'member_voucher_variants.name as member_variant_name',
			'member_voucher_codes.code',
			'member_voucher_codes.password',
			'member_voucher_codes.purchased_by_member_id',
			'member_voucher_codes.is_purchased',
			'member_voucher_codes.is_used',
			'member_voucher_codes.payment_url',
			'member_voucher_codes.payment_reference_no',
			'member_voucher_codes.purchased_at',
			'member_voucher_codes.created_at',
			'member_voucher_codes.updated_at'
		];
		$orderKey = 'member_voucher_codes.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('member_voucher_codes', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getMemberDigitalVoucherCode ($filters = null) {
		$args = [];
		$join = [
			'member_voucher_variants' => 'member_voucher_variants.id = member_voucher_codes.member_voucher_variant_id',
			'member_vouchers' => 'member_vouchers.id = member_voucher_variants.member_voucher_id AND member_vouchers.deleted_at is null'
		];
		/* if (isset($filters['member_voucher_id']) && !empty($filters['member_voucher_id'])) {
			$args['member_voucher_codes.member_voucher_id'] = $filters['member_voucher_id'];
		} */

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['member_voucher_codes.id'] = $filters['id'];
		}

		if (isset($filters['member_voucher_variant_id']) && !empty($filters['member_voucher_variant_id'])) {
			$args['member_voucher_codes.member_voucher_variant_id'] = $filters['member_voucher_variant_id'];
		}

		if (isset($filters['code']) && !empty($filters['code'])) {
			$args['member_voucher_codes.code'] = $filters['code'];
		}

		if (isset($filters['is_purchased'])) {
			$args['member_voucher_codes.is_purchased'] = $filters['is_purchased'];
		}

		if (isset($filters['is_used'])) {
			$args['member_voucher_codes.is_used'] = $filters['is_used'];
		}
		$select = [
			'member_voucher_codes.id',
			'member_voucher_codes.member_voucher_variant_id',
			'member_vouchers.name as member_voucher_name',
			'member_voucher_variants.name as member_voucher_variant_name',
			'member_voucher_codes.code',
			'member_voucher_codes.password',
			'member_voucher_codes.purchased_by_member_id',
			'member_voucher_codes.is_purchased',
			'member_voucher_codes.is_used',
			'member_voucher_codes.payment_url',
			'member_voucher_codes.payment_reference_no',
			'member_voucher_codes.purchased_at',
			'member_voucher_codes.created_at',
			'member_voucher_codes.updated_at'
		];
		$transaction = $this->repository->findOne('member_voucher_codes', $args, null, $join, $select);
		/* if (!empty($voucher)) {
			$codes = $this->repository->find('member_voucher_codes', ['member_digital_parent_voucher_id' => $voucher->id]);
			$voucher->codes = $codes;
		} */
		$this->delivery->data = $transaction;
		return $this->delivery;
	}

	public function createMemberDigitalVoucherCodes ($payload) {
		if (!isset($payload['code']) || empty($payload['code'])) {
			$this->delivery->addError(400, 'Name is required');
		}
		if (!isset($payload['member_voucher_variant_id']) || empty($payload['member_voucher_variant_id'])) {
			$this->delivery->addError(400, 'Voucher Variant is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$existsVoucher = $this->repository->findOne('member_voucher_variants', ['id' => $payload['member_voucher_variant_id']]);
		if (empty($existsVoucher)) {
			$this->delivery->addError(400, 'Voucher variant not exists');
			return $this->delivery;
		}

		$existsCode = $this->repository->findOne('member_voucher_codes', ['code' => $payload['code']]);
		if (!empty($existsCode)) {
			$this->delivery->addError(400, 'Code already exists');
			return $this->delivery;
		}

		try {
			if (!isset($payload['password']) || empty($payload['password'])) {
				$payload['password'] = null;
			}
			$builder = [
				'member_voucher_variant_id' => $payload['member_voucher_variant_id'],
				'code' => $payload['code'],
				'password' => $payload['password'],
				'created_at' => date("Y-m-d H:i:s"),
			];
			$action = $this->repository->insert('member_voucher_codes', $builder);
			$result = $this->getMemberDigitalVoucherCode(['id' => $action])->data;

			// check if has pending transactions
			$pendingTransaction = $this->getMemberDigitalTransaction(['status' => self::TRANSACTION_STATUS_PENDING, 'member_voucher_variant_id' => $payload['member_voucher_variant_id']])->data;
			if (!empty($pendingTransaction)) {
				$resp = [];
				$member = $this->getMemberDigital(['id' => $pendingTransaction->member_id])->data;
				$payloadUpdate = [
					'status' => self::TRANSACTION_STATUS_SUCCESS
				];
				$transAction = $this->updateMemberDigitalTransactions($payloadUpdate, ['id' => $pendingTransaction->id]);

				$updateVoucherData = [
					'is_purchased' => 1,
					'member_transaction_id' => $pendingTransaction->id,
					'purchased_by_member_id' => $member->id
				];
				$updateAction = $this->updateMemberDigitalVoucherCodes($updateVoucherData, ['id' => $result->id]);
				$resp['voucher_code_action'] = $updateAction->data;
				$message = 'Pembayaran anda telah kami terima. Terima kasih sudah berbelanja voucher melalui layanan kami. Berikut detail voucher anda:'.PHP_EOL.PHP_EOL.'Nama Voucher: '.$result->member_voucher_name.PHP_EOL.'Jenis Voucher: '.$result->member_voucher_variant_name.PHP_EOL.'Kode Voucher: '.$result->code;
				if (!empty($voucherCode->password)) {
					$message = 'Pembayaran anda telah kami terima. Terima kasih sudah berbelanja voucher melalui layanan kami. Berikut detail voucher anda:'.PHP_EOL.PHP_EOL.'Nama Voucher: '.$result->member_voucher_name.PHP_EOL.'Jenis Voucher: '.$result->member_voucher_variant_name.PHP_EOL.'Username: '.$result->code.PHP_EOL.'Password: '.$result->password;
				}
				$sendWa = $this->sendWablasToMember($member, $message);
				$resp['notif'] = $sendWa->data;	
				$result->extras = $resp;
			}

		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function updateMemberDigitalVoucherCodes ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_voucher_codes', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No voucher code found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_voucher_codes', $payload, $filters);
		$result = $this->repository->find('member_voucher_codes', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberDigitalVoucherCodes ($filters = null) {
		$existsMembers = $this->repository->find('member_voucher_codes', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No voucher code found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_voucher_codes', $payload, $filters);
		$result = $this->repository->find('member_voucher_codes', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getSources ($filters = null) {
		$args = [];
		$args['member_sources.id_auth_api'] = $this->auth['id_auth'];
		$join = [];
		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_sources.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['code']) && !empty($filters['code'])) {
			$args['member_sources.code'] = $filters['code'];
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
			'member_sources.id',
			'member_sources.name',
			'member_sources.code',
			'member_sources.created_at',
			'member_sources.updated_at'
		];
		$orderKey = 'member_sources.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('member_sources', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getSource ($filters = null) {
		$voucher = $this->repository->findOne('member_sources', $filters);
		$this->delivery->data = $voucher;
		return $this->delivery;
	}

	public function createSource ($payload) {
		if (!isset($payload['code']) || empty($payload['code'])) {
			$this->delivery->addError(400, 'Name is required');
		}
		if (!isset($payload['name']) || empty($payload['name'])) {
			$this->delivery->addError(400, 'Name is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$existsCode = $this->repository->findOne('member_sources', ['code' => $payload['code']]);
		if (!empty($existsCode)) {
			$this->delivery->addError(400, 'Code already exists');
			return $this->delivery;
		}

		try {
			$builder = [
				'id_auth_api' => $this->auth['id_auth'],
				'code' => $payload['code'],
				'name' => $payload['name'],
				'created_at' => date("Y-m-d H:i:s"),
			];
			$action = $this->repository->insert('member_sources', $builder);
			$result = $this->repository->findOne('member_sources', ['id' => $action]);
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function updateSource ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_sources', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No source found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_sources', $payload, $filters);
		$result = $this->repository->find('member_sources', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteSource ($filters = null) {
		$existsMembers = $this->repository->find('member_sources', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No source found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_sources', $payload, $filters);
		$result = $this->repository->find('member_sources', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}


	public function sendBatchWablas ($payload) {
		set_time_limit(0);
		$availableMessageTypes = [
			'text',
			'text_direct',
			'birthday_card'
		];

		$availableTypes = [
			'all' => [],
			'developer' => [
				'phone_number' => '6287824622895',
			],
			'test' => [
				'phone_number' => '628986002287',
			],
			'inactive_member' => [
				'member_code' => null,
			],
			'active_member' => [
				'member_code <>' => null,
			],
			'active_member_with_point' => [
				'member_code <>' => null,
				'point >' => 0
			],
			'active_member_no_point' => [
				'member_code <>' => null,
				'point' => 0
			]
		];

		$availableCustomFilters = [
			'birthday_day' => 'DAY(birthday)',
			'birthday_month' => 'MONTH(birthday)',
			'gender' => 'gender',
			'city' => 'city',
			'province' => 'province',
			'minimal_balance_reward' => 'balance_reward >='
		];

		if (!array_key_exists($payload['member_type'], $availableTypes)) {
			$this->delivery->addError(400, 'Member type is required');
		}

		if (!isset($payload['message_type']) || !in_array($payload['message_type'], $availableMessageTypes)) {
			$this->delivery->addError(400, 'Message type is required');
		}

		if (!isset($payload['message']) || empty($payload['message'])) {
			$this->delivery->addError(400, 'Message is required');
		}

		if ($payload['message_type'] == 'text') {
			if (!isset($payload['send_at_date']) || empty($payload['send_at_date'])) {
				$this->delivery->addError(400, 'Send at is required');
			}

			if (!isset($payload['send_at_time']) || empty($payload['send_at_time'])) {
				$this->delivery->addError(400, 'Send at is required');
			}
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$filters = $availableTypes[$payload['member_type']];
		if (isset($payload['custom_filter'])) {
			foreach ($payload['custom_filter'] as $key => $value) {
				if (!array_key_exists($key, $availableCustomFilters)) {
					$this->delivery->addError(400, sprintf('Custom filter %s is not supported', $key));
					return $this->delivery;
				} else if (!empty($value)) {
					$filters[$availableCustomFilters[$key]] = $value;
				}
			}
		}
		$members = $this->repository->find(self::MAIN_TABLE, $filters);

		$result = [
			'total_member' => count($members),
			'total_recepient' => 0,
			'conditions' => $payload,
			'list' => [],
		];
		foreach ($members as $member) {
			$customFormat = [
				'{member_name}' => $member->name
			];

			$formattedMessage = strtr($payload['message'], $customFormat);
			$detail = [
				'id' => $member->id,
				'name' => $member->name,
				'phone_number' => $member->phone_number,
				'wablas_phone_number_receiver' => $member->wablas_phone_number_receiver,
				'message_type' => $payload['message_type'],
				'message' => $formattedMessage
			];

			if (isset($payload['send_immediately']) && $payload['send_immediately'] == 'true') {
				$action = null;
				if ($payload['message_type'] == 'text') {
					$action = $this->sendScheduledWablasToMember($member, $formattedMessage, $payload['send_at_date'], $payload['send_at_time']);
				} else if ($payload['message_type'] == 'birthday_card') {
					try {
						$action = $this->sendBirthdayCardToMember($member, $formattedMessage);
					} catch (\Exception $e) {
						$action = $e->getMessage();
					}
				} else if ($payload['message_type'] == 'text_direct') {
					// by pass langsung kirim array of member
					$action = $this->sendBulkWablasToMember($members, $payload['message'], $customFormat);
					break;
				}
				$detail['extras'] = $action;
			}
			$result['list'][] = $detail;
			$result['total_recepient']++;
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function sendWablasToMember ($member, $message, $additionalMessage = null) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}
		$result = null;
		if (!empty($member->wablas_phone_number_receiver)) {
			// $wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $member->wablas_phone_number_receiver]);
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => ($member->wablas_phone_number_receiver == '6289658642914' ? self::MAIN_WABLAS : $member->wablas_phone_number_receiver)]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				$this->delivery->addError(400, 'Config error');
				return $this->delivery;
			}
			$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
			$sendWa = $this->waService->publishMessage('send_message', $member->phone_number, $message);
			$result[] = $sendWa;
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function generateTripayPaymentMethod () {
		$tripay = new TripayGateway;
        // $tripay->setEnv('development');
        $tripay->setEnv('production');
        $tripay->setMerchantCode('T2286');
        $tripay->setApiKey('ZZUFAKR8Zp4UvC2Pcpaz9Bs0FdKu86Zq4SVrnKed');
        $tripay->setPrivateKey('Sfw9q-yuMNX-SK07D-qcggU-y1yCh');
        // gunakan amount sebelum dicharge fee (customer_fee)
		$tripayResult = $tripay->channelPembayaran();
		$paymentChannels = $tripayResult->data;
		$result = [];
		$tripay = [];
		$index = 1;


		$paymentMethods = [];
		foreach ($paymentChannels as $channel) {
			$result[$index] = $index.'. '.$channel->name;
			$tripay[$index] = $channel;
			$paymentMethods[] = [
				'title' => $index.'. '.$channel->name,
			];
			$index++;
		}
		$paymentMethods[] = [
			'title' => 'Batal'
		];
		$payload = [
			'title' => 'Metode Pembayaran',
			'description' => 'Silahkan pilih salah satu metode pembayaran yang tersedia',
			'buttonText' => 'Metode Pembayaran',
			'lists' => $paymentMethods,
			'footer' => 'Pilih Batal untuk kembali ke menu awal'
		];
		$res = [
			'tripay' => $tripay,
			'data' => $result,
			'message' => [
				'category' => 'list',
				'message' => json_encode($payload)
			]
		];
		return $res;
	}

	public function generateTripayInvoice($member, $source, $voucherVariant, $tripayPaymentMethod) {
		$tripay = new TripayGateway;
        // $tripay->setEnv('development');
        $tripay->setEnv('production');
        $tripay->setMerchantCode('T2286');
        $tripay->setApiKey('ZZUFAKR8Zp4UvC2Pcpaz9Bs0FdKu86Zq4SVrnKed');
        $tripay->setPrivateKey('Sfw9q-yuMNX-SK07D-qcggU-y1yCh');
	
		$merchantRef = 'V-'.generate_invoice_number(7);
		$price = $voucherVariant->price + $voucherVariant->admin_fee + $voucherVariant->additional_fee;
		$tripayCart = [
			[
				'sku' => $merchantRef,
				'name' => $voucherVariant->name,
				'price' => $price,
				'quantity' => 1
			]
		];
		$callbackUrl = 'https://api.1itmedia.co.id/callbacks/tripay/voucher';
		$tripayAction = $tripay->requestTransaksi($tripayPaymentMethod->code, $merchantRef, $price, $member->phone_number, 'no-reply@1itmedia.co.id', $member->phone_number, $tripayCart, null);
		if ($tripayAction->success != 1 && $tripayAction->success != true) {
			return false;
		}
        
        $feeMerchant = ($price * $tripayPaymentMethod->fee_merchant->percent / 100) + $tripayPaymentMethod->fee_merchant->flat;
        $feeCustomer = ($price * $tripayPaymentMethod->fee_customer->percent / 100) + $tripayPaymentMethod->fee_customer->flat;
        $totalFee = ($price * $tripayPaymentMethod->total_fee->percent / 100) + $tripayPaymentMethod->total_fee->flat;
        $paymentAmount = $totalFee + $price;
		$transactionData = [
			'member_id' => $member->id,
			'source_id' => $source->id,
			'status' => self::TRANSACTION_STATUS_WAITING_PAYMENT,
			'transaction_type' => 'voucher_purchase',
			'member_voucher_variant_id' => $voucherVariant->id,
			'order_id' => $merchantRef,
			'payment_reference_no' => $tripayAction->data->reference,
			'payment_method_code' => $tripayPaymentMethod->code,
			'payment_method_name' => $tripayPaymentMethod->name,
			'shopping_amount' => $voucherVariant->price,
			'admin_fee' => $voucherVariant->admin_fee,
			'additional_fee' => $voucherVariant->additional_fee,
			'payment_fee' => $totalFee,
			'payment_amount' => $paymentAmount,
		];
		$action = $this->createMemberDigitalTransaction($member->phone_number, $transactionData);
		$transactionResult = $action->data;
		$result = [
			'transaction' => $transactionResult,
			'tripay' => $tripayAction
		];
		return $result;
	}

	private function generateGeneralWablasMenuText ($memberSourceId) {
		$voucherAction = $this->getMemberDigitalVouchers(['data' => 10, 'member_source_id' => $memberSourceId]);
		$voucherData = $voucherAction->data['result'];
		$payload = [
			'type' => 'text',
			'message' => 'Mohon maaf saat ini belum tersedia voucher'
		];
		if (!empty($voucherData)) {
			$voucherLists = [];
			foreach ($voucherData as $voucher) {
				$voucherLists[] = [
					'title' => $voucher->name,
					// 'description' => 'Harga: '.toRupiahFormat($voucher->price),
					'description' => '',
					'buttonText' => 'Beli',
				];
			}
			$voucherLists[] = [
				'title' => 'Batal'
			];
			$list = [
				'title' => 'Voucher Tersedia',
				'description' => 'Anda akan diarahkan ke cara produk yang tersedia setelah memilih voucher',
				'buttonText' => 'Daftar Voucher',
				'lists' => $voucherLists,
				'footer' => 'Pilih Batal untuk kembali ke menu awal',
			];
			$payload = [
				'category' => 'list',
				'message' => json_encode($list)
			];
			$data = [
				'wablas_menu_state' => 'show_voucher_lists',
				'updated_at' => date('Y-m-d H:i:s')
			];
			// $action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);
			header('Content-Type: application/json');
		}

		return $payload;
	}

	private function generateVoucherVariantListText ($memberVoucherId) {
		$voucherAction = $this->getMemberDigitalVoucherVariants(['data' => 10, 'member_voucher_id' => $memberVoucherId]);
		$voucherData = $voucherAction->data['result'];
		$payload = [
			'type' => 'text',
			'message' => 'Mohon maaf saat ini belum tersedia produk'
		];
		if (!empty($voucherData)) {
			$voucherLists = [];
			foreach ($voucherData as $voucher) {
				$voucherLists[] = [
					'title' => $voucher->name,
					'description' => 'Harga: '.toRupiahFormat($voucher->price + $voucher->admin_fee + (int)$voucher->additional_fee),
					'buttonText' => 'Beli',
				];
			}
			$voucherLists[] = [
				'title' => 'Batal'
			];
			$list = [
				'title' => 'Produk Tersedia',
				'description' => 'Anda akan diarahkan ke cara pembayaran setelah memilih salah satu produk yang tersedia',
				'buttonText' => 'Daftar Produk',
				'lists' => $voucherLists,
				'footer' => 'Pilih Batal untuk kembali ke menu awal',
			];
			$payload = [
				'category' => 'list',
				'message' => json_encode($list)
			];
			$data = [
				'wablas_menu_state' => 'show_payment_method',
				'updated_at' => date('Y-m-d H:i:s')
			];
			// $action = $this->repository->update(self::MAIN_TABLE, $data, ['id' => $existsMember->id]);
			header('Content-Type: application/json');
		}

		return $payload;
	}

	public function onTripayCallback ($payload, $token) {

		$authApi = $this->repository->findOne('auth_api', ['secret' => $token]);
		if (empty($authApi)) {
			$this->delivery->addError(400, 'Invalid config');
			return $this->delivery;
		}

		$payload = (array)$payload;
		$reference = $payload['reference'];
		$merchantRef = $payload['merchant_ref'];
		$filterTrans = [
			'order_id' => $merchantRef,
			'payment_reference_no' => $reference,
			'id_auth_api' => $authApi->id_auth,
		];

		$existsOrder = $this->getMemberDigitalTransaction($filterTrans);
		if (!empty($existsOrder->data)) {
			$resp = [];
			$order = $existsOrder->data;

			if ($payload['status'] == 'EXPIRED' && $order->status == self::ORDER_STATUS_WAITING_PAYMENT) {
				
				$payloadUpdate = [
					'status' => self::TRANSACTION_STATUS_PAYMENT_EXPIRED,
					'updated_at' => date("Y-m-d H:i:s")
				];
				$action = $this->updateMemberDigitalTransactions($payloadUpdate, $filterTrans);
				$resp['action'] = $action->data;

				$this->delivery->data = $resp;
				return $this->delivery;

			} else if ($payload['status'] == 'PAID') {
				$status = self::TRANSACTION_STATUS_SUCCESS;

				$voucherCode = $this->getMemberDigitalVoucherCode(['member_voucher_variant_id' => $order->member_voucher_variant_id, 'is_purchased' => 0])->data; // dibawah kasih if kalau voucher abis
				if (empty($voucherCode)) {
					$status = self::TRANSACTION_STATUS_PENDING;
				}

				$payloadUpdate = [
					'paid_at' => date('Y-m-d H:i:s'),
					'payment_method_name' => $payload['payment_method'],
					'payment_method_code' => $payload['payment_method_code'],
					'status' => $status
				];
				$action = $this->updateMemberDigitalTransactions($payloadUpdate, $filterTrans);
				$resp['order_action'] = $action->data;
				$member = $this->getMemberDigital(['iden' => $order->member_id])->data;

				if (empty($voucherCode)) {
					$message = 'Pembayaran anda telah kami terima. Terima kasih sudah berbelanja voucher melalui layanan kami. Voucher anda saat ini sedang kami proses, mohon menunggu ketersediannya dan akan kami kirim detail voucher melalui WA ini. Terima kasih.';
					$sendWa = $this->sendWablasToMember($member, $message);
					$resp['notif'] = $sendWa->data;	
				} else {
					$updateVoucherData = [
						'is_purchased' => 1,
						'member_transaction_id' => $order->id,
						'purchased_by_member_id' => $member->id
					];
					$updateAction = $this->updateMemberDigitalVoucherCodes($updateVoucherData, ['id' => $voucherCode->id]);
					$resp['voucher_code_action'] = $updateAction->data;
					$message = 'Pembayaran anda telah kami terima. Terima kasih sudah berbelanja voucher melalui layanan kami. Berikut detail voucher anda:'.PHP_EOL.PHP_EOL.'Nama Voucher: '.$voucherCode->member_voucher_name.PHP_EOL.'Jenis Voucher:'.$voucherCode->member_voucher_variant_name.PHP_EOL.'Kode Voucher: '.$voucherCode->code;
					if (!empty($voucherCode->password)) {
						$message = 'Pembayaran anda telah kami terima. Terima kasih sudah berbelanja voucher melalui layanan kami. Berikut detail voucher anda:'.PHP_EOL.PHP_EOL.'Nama Voucher: '.$voucherCode->member_voucher_name.PHP_EOL.'Jenis Voucher:'.$voucherCode->member_voucher_variant_name.PHP_EOL.'Username: '.$voucherCode->code.PHP_EOL.'Password: '.$voucherCode->password;
					}
					$sendWa = $this->sendWablasToMember($member, $message);
					$resp['notif'] = $sendWa->data;	
				}

				$this->delivery->data = $resp;
				return $this->delivery;
			}
		}
		// $this->delivery->data = null;
		return $this->delivery;
	}

}