<?php
namespace Service\ResellerDigital;

use Library\WablasService;
use Library\XenditService;
use Service\Entity;
use Service\Delivery;
use Redis\Producer\MemberDigitalCardProducer;
use Redis\Producer\MemberDigitalBirthdayCardProducer;
use Milon\Barcode\DNS1D;
use mikehaertl\wkhtmlto\Image;

class ResellerDigitalHandler {

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->wablasDomain = 'https://selo.wablas.com';
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
		$data = [
			'fromcall' => 'WABLAS_RESELLER',
			'dataJson' => json_encode($payload),
			'dateTime' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->insert('logcallback', $data);

		if (empty(trim($payload['message']))) {
			$this->delivery->addError(409, 'Silahkan masukkan pesan');
			return $this->delivery;
		}


		$authApi = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $payload['receiver']]);
		if (empty($authApi)) {
			$this->delivery->addError(409, 'Config error!');
			return $this->delivery;
		}

		$existsMember = $this->repository->findOne('reseller_digitals', ['phone_number' => $payload['phone'], 'id_auth_api' => $authApi->id_auth_api]);
		if (empty($existsMember)) {
			if (strtolower($payload['message']) != 'daftar') {
				$message = 'Silahkan ketik "Daftar" untuk melakukan pendaftaran dan dapatkan potongan diskon hingga 37% sebagai reseller digital dan bisa di gunakan di seluruh toko offline dan online official rabbani.'.PHP_EOL.PHP_EOL.'Informasi diskon reseller.'.PHP_EOL.'Besaran diskon up to 37% di sesuaikan dengan jumlah pembelanjaan reseller di bulan sebelumnya, sebagai contoh :'.PHP_EOL.'total belanja bulan januari sebesar 300jt, maka di bulan februari akun anda akan otomatis mendapatkan diskon sebesar 37% selama 1 bulan penuh'.PHP_EOL.PHP_EOL.'Berikut slab besaran diskon yang di dapat jika melakukan pembelanjaan akumulatif dalam setiap bulan'.PHP_EOL.'Rp 1 - 99.999.999 Besaran diskon 30%'.PHP_EOL.'Rp 100.000.000 - 199.999.999 Besaran diskon 32%'.PHP_EOL.'Rp 200.000.000 - 299.999.999 Besaran diskon 35%'.PHP_EOL.'>/= Rp 300.000.000 Besaran diskon 37%'.PHP_EOL.PHP_EOL.'Untuk informasi lebih lanjut hubungi https://wa.me/6281217070153';
				$this->delivery->addError(409, $message);
				return $this->delivery;
			} else {
				$newMember = [
					'id_auth_api' => $authApi->id_auth_api,
					'phone_number' => $payload['phone'],
					'created_at' => date('Y-m-d H:i:s'),
					'wablas_phone_number_receiver' => $payload['receiver']
				];
				$action = $this->repository->insert('reseller_digitals', $newMember);
				$newMember['id'] = $action;
				$existsMember = $this->repository->findOne('reseller_digitals', ['id' => $newMember['id']]);
				$this->delivery->addError(409, 'Silahkan isi nama anda:');
				return $this->delivery;
			}
		}

		if (empty($existsMember->name)) {
			if (preg_match('/[\^£$:%&*()}{@#~?><>|=_+¬-]/', $payload['message']) > 0) {
				$this->delivery->addError(400, 'Silahkan isi nama anda: (contoh: Agus Sopian)');
				return $this->delivery;
			}
			$data = [
				'name' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi tanggal lahir anda: (cth: 01-05-1970)');
			return $this->delivery;
		}

		if (empty($existsMember->birthday)) {
			if (!preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/", $payload['message'])) {
			    $this->delivery->addError(409, 'Silahkan isi tanggal lahir anda: (cth: 01-05-1970)');
			    return $this->delivery;
			}
			$formattedDate = date('Y-m-d', strtotime($payload['message']));
			$data = [
				'birthday' => $formattedDate,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi jenis kelamin anda: (P/L)');
			return $this->delivery;
		}

		if (empty($existsMember->gender)) {
			$options = [
				'P' => 'female',
				'L' => 'male',
				'p' => 'male',
				'l' => 'female'
			];

			if (!isset($options[$payload['message']])) {
				$this->delivery->addError(409,' Silahkan isi jenis kelamin anda: (P/L)');
				return $this->delivery;
			}
			$data = [
				'gender' => $options[$payload['message']],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi alamat lengkap anda:');
			return $this->delivery;
		}

		if (empty($existsMember->address)) {
			$data = [
				'address' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi provinsi anda:');
			return $this->delivery;
		}

		if (empty($existsMember->province)) {
			$data = [
				'province' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi kota/kabupaten anda:');
			return $this->delivery;
		}

		if (empty($existsMember->city)) {
			$data = [
				'city' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
		}

		if ($existsMember->is_paid_membership == 0) {
			$invoiceUrl = $existsMember->invoice_membership_url;
			$now = date('Y-m-d H:i:s');
			if (empty($invoiceUrl) || !empty($invoiceUrl) && $existsMember->invoice_expired_at >= $now) {
				$memberCode = $this->createMemberCode($existsMember->id);
				$xenditService = new XenditService();
				$amount = 100000;
				$xenditInvoice = $xenditService->createInvoice($memberCode, null, null, $amount);
				$invoiceUrl = $xenditInvoice->invoice_url;
				$data = [
					'invoice_membership_url' => $invoiceUrl,
					'invoice_reference_no' => $memberCode,
					'invoice_expired_at' => date('Y-m-d H:i:s', strtotime($xenditInvoice->expiry_date)),
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
			}
			$paymentMenu = 'Satu langkah lagi menjadi reseller kami.'.PHP_EOL.PHP_EOL.'Harap melakukan pembayaran sebesar '.toRupiahFormat($amount).' melalui link berikut ini:'.PHP_EOL.PHP_EOL.$invoiceUrl.PHP_EOL.PHP_EOL.'Info : Pembayaran hanya menerima melalui pembayaran XENDIT dan atas nama rabbani, jika ada permintaan transfer ke rekening atas nama pribadi harap dikonfirmasi pada admin kami.'.PHP_EOL.PHP_EOL.'Kontak admin https://wa.me/6281217070153';
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $paymentMenu
			];
			header('Content-Type: application/json');
			$result = json_encode(['data' => $formattedPayload]);
			$this->delivery->data = $result;
			return $this->delivery;
		}

		$generalMenuText = 'Status kak '.$existsMember->name.' saat ini sudah menjadi reseller digital kami, silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.'.PHP_EOL.'1. PRODUK : Format untuk meminta list produk'.PHP_EOL.'2. ORDER : Format untuk melakukan pemesanan produk'.PHP_EOL.'3. POIN : format untuk mengetahui total poin'.PHP_EOL.'4. UPDATE : format untuk perubahan data'.PHP_EOL.'5. TRANSAKSI : format untuk mengetahui histori transaksi'.PHP_EOL.'6. CETAK : format untuk mendapatkan / cetak ulang kartu digital';
		if ($existsMember->wablas_menu_state == 'show_confirmation_update') {
			$data = [
				'wablas_menu_state' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
			if ($payload['message'] == 1) {
				$emptyData = [
					'name' => '',
					// 'member_code' => '',
					'birthday' => null,
					'gender' => '',
					'address' => '',
					'province' => '',
					'city' => '',
					// 'member_card_url' => '',
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('reseller_digitals', $emptyData, ['id' => $existsMember->id]);
				$this->delivery->addError(409, 'Silahkan isi nama anda:');
			} else {
				$this->delivery->addError(400, sprintf('Perubahan data dibatalkan %s%s%s', PHP_EOL, PHP_EOL, $generalMenuText));
			}
			return $this->delivery;
		}
		if (!empty($existsMember->member_code)) {
			$payload['message'] = strtolower($payload['message']);
			if (!in_array($payload['message'], ['1', '2', '3', '4' ,'poin', 'update', 'transaksi', 'cetak'])){
				$this->delivery->addError(409, $generalMenuText);
				return $this->delivery;
			} else {
				if ($payload['message'] == '1' || $payload['message'] == 'poin') {
					$this->delivery->addError(409, sprintf('Poin anda saat ini adalah %s %s%s%s', $existsMember->point, PHP_EOL, PHP_EOL, $generalMenuText));
				} else if ($payload['message'] == '2' || $payload['message'] == 'update') {
					$data = [
						'wablas_menu_state' => 'show_confirmation_update',
						'updated_at' => date('Y-m-d H:i:s')
					];
					$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
					$text = sprintf('Apakah anda ingin melanjutkan perubahan data?%s1. Lanjut%s0. Batal', PHP_EOL,PHP_EOL);
					$this->delivery->addError(400, $text);
				} else if ($payload['message'] == '3' || $payload['message'] == 'transaksi') {
					$this->auth['id_auth'] = $existsMember->id_auth_api;
					$filters = [
						'id_member_digital' => $existsMember->id,
						'data' => 10,
						'sort_key' => 'member_digital_transactions.created_at'
					];
					$transactionsResult = $this->getMemberDigitalTransactions($filters);
					$transactionsData = $transactionsResult->data['result'];
					if (!empty($transactionsData)) {
						$formattedMessage = sprintf('Berikut informasi 10 transaksi terakhir dari pembelanjaan kak %s', $existsMember->name);
						foreach ($transactionsData as $transaction) {
							if ($transaction->member_point > 0) {
								$formattedMessage .= sprintf('%s- %s : Belanja di %s dengan total *Rp %s* dan mendapatkan *%s* poin dari pembelanjaan', PHP_EOL, date('d/m/Y', strtotime($transaction->created_at)), $transaction->store_name, number_format($transaction->payment_amount, 0, ',', '.'), number_format($transaction->member_point, 0, ',', '.'));
							} else {
								$formattedMessage .= sprintf('%s- %s : Poin anda telah dikurangi sebesar %s', PHP_EOL, date('d/m/Y', strtotime($transaction->created_at)), abs($transaction->member_point));
							}
						}
					} else {
						$formattedMessage = 'Saat ini belum ada transaksi yang tercatat';
					}
					$this->delivery->addError(400, sprintf('%s%s%s%s', $formattedMessage, PHP_EOL, PHP_EOL, $generalMenuText));
				} else if ($payload['message'] == '4' || $payload['message'] == 'cetak') {
					$memberCard = $this->createMemberCard($existsMember);
					$data = [
						'member_card_url' => $memberCard['cdn_url']
					];
					$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
					$message = sprintf('Silahkan download kartu keanggotaan anda di link berikut: %s', $memberCard['cdn_url']);
					$formattedPayload[] = [
						'category' => 'text',
						'message' => 'Tunggu sebentar ya kak, kartunya lagi kita buat'
					];
					$formattedPayload[] = [
						'category' => 'image',
						'message' => 'Berikut kartu anggota anda',
						'mime_type' => 'image/png',
						'url_file' => $memberCard['cdn_url']
					];
					$formattedPayload[] = [
						'category' => 'text',
						'message' => $message
					];
					$formattedPayload[] = [
						'category' => 'text',
						'message' => sprintf('Untuk penggunaan kartu digital ini, kak %s tinggal melihatkan pada kasir', $existsMember->name)
					];
					$formattedPayload[] = [
						'category' => 'text',
						'message' => $generalMenuText
					];

					header('Content-Type: application/json');
					$this->delivery->addError(409, json_encode(['data' => $formattedPayload]));
				}


				return $this->delivery;
			}

		}


		/* try {
			$memberCode = $this->createMemberCode($existsMember->id);
			$existsMember->member_code = $memberCode;
			// $memberCard = $this->createMemberCard($existsMember);

			$data = [
				'member_code' => $memberCode,
				// 'member_card_url' => $memberCard['cdn_url'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			// $action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
			$waLink = 'https://wa.me/62895360976552';
			$telegramLink = 't.me/memberrabbani';
			$groupText = sprintf('Info lebih lanjut bergabung dengan Grup member digital %s%s%s%sGrup Telegram Member Digital %s%s%s', PHP_EOL, $waLink, PHP_EOL, PHP_EOL, PHP_EOL, $telegramLink, PHP_EOL);
			$message = sprintf('Pendaftaran kaka sudah berhasil dan aktif, berikut detail keanggotaan kaka. %s%sNama : %s%sNo Kartu Member : %s%s%sKak %s bisa gunakan di seluruh toko Rabbani se-Indonesia, cukup tunjukkan no member tersebut ke kasir. %s%s%s%s%s', PHP_EOL, PHP_EOL, $existsMember->name, PHP_EOL, $existsMember->member_code, PHP_EOL, PHP_EOL, $existsMember->name, PHP_EOL, PHP_EOL, $groupText, PHP_EOL, $generalMenuText);
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $message
			];
			header('Content-Type: application/json');
			$result = json_encode(['data' => $formattedPayload]);
		} catch (\Exception $e) {
			$this->delivery->addError(500, 'Internal Server Error');
			return $this->delivery;
		} */
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function callbackPaymentAction ($payload) {
		$referenceNo = $payload['external_id'];
		$existsMember = $this->repository->findOne('reseller_digitals', ['invoice_reference_no' => $referenceNo]);
		$data = [];
		if (!empty($existsMember)) {
			try {
				$generalMenuText = 'Status kak '.$existsMember->name.' saat ini sudah menjadi reseller digital kami, silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.'.PHP_EOL.'1. PRODUK : Format untuk meminta list produk'.PHP_EOL.'2. ORDER : Format untuk melakukan pemesanan produk'.PHP_EOL.'3. POIN : format untuk mengetahui total poin'.PHP_EOL.'4. UPDATE : format untuk perubahan data'.PHP_EOL.'5. TRANSAKSI : format untuk mengetahui histori transaksi'.PHP_EOL.'6. CETAK : format untuk mendapatkan / cetak ulang kartu digital';
				$memberCode = $this->createMemberCode($existsMember->id);
				$existsMember->member_code = $memberCode;

				$data = [
					'is_paid_membership' => 1,
					'invoice_paid_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s'),
					'member_code' => $memberCode,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
				$message = 'Pendaftaran kaka sudah berhasil dan aktif, berikut detail keanggotaan kaka.'.PHP_EOL.PHP_EOL.'Nama : '.$existsMember->name.PHP_EOL.'No Kartu Member : '. $memberCode.PHP_EOL.PHP_EOL.'Kak '.$existsMember->name.' bisa gunakan di seluruh toko Rabbani se-Indonesia, cukup tunjukkan no member tersebut ke kasir.'.PHP_EOL.PHP_EOL.'Info & bantuan reseller digital'.PHP_EOL.'https://wa.me/6281217070153'.PHP_EOL.PHP_EOL.$generalMenuText;
				$wablasAction = $this->sendWablasToMember($existsMember, $message);
				$data['wablas'] = $wablasAction;
			} catch (\Exception $e) {
				$this->delivery->addError(500, 'Internal Server Error');
				return $this->delivery;
			}
		} else {
			$this->delivery->addError(400, 'Member not found');
			return $this->delivery;
		}
		$this->delivery->data = $data;
		return $this->delivery;
	}

	public function getResellerDigitals ($filters = null) {
		$args = [
			'reseller_digitals.id_auth_api' => $this->auth['id_auth']
		];

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['member_code']) && !empty($filters['member_code'])) {
			if ($filters['member_code'] == '~~') {
				$args['member_code <>'] = null;
			} else if ($filters['member_code'] == '~') {
				$args['member_code'] = null;
			} else {
				$args['member_code'] = [
					'condition' => 'like',
					'value' => $filters['member_code']
				];	
			}
		}

		if (isset($filters['from_id']) && !empty($filters['from_id'])) {
			$args['id >='] = $filters['from_id'];
		}

		if (isset($filters['until_id']) && !empty($filters['until_id'])) {
			$args['id <='] = $filters['until_id'];
		}

		if (isset($filters['from_updated_at']) && !empty($filters['from_updated_at'])) {
			$args['updated_at >='] = $filters['from_updated_at'];
		}

		if (isset($filters['until_updated_at']) && !empty($filters['until_updated_at'])) {
			$args['updated_at <='] = $filters['until_updated_at'];
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
			'reseller_digitals.id',
			'reseller_digitals.name',
			'reseller_digitals.phone_number',
			'reseller_digitals.member_card_url',
			'reseller_digitals.member_code',
			'reseller_digitals.birthday',
			'reseller_digitals.gender',
			'reseller_digitals.address',
			'reseller_digitals.province',
			'reseller_digitals.city',
			'reseller_digitals.point',
			'reseller_digitals.invoice_membership_url',
			'reseller_digitals.invoice_expired_at',
			'reseller_digitals.is_paid_membership',
			'reseller_digitals.invoice_reference_no',
			'reseller_digitals.invoice_membership_url',
			'reseller_digitals.invoice_paid_at',
			'reseller_digitals.wablas_phone_number_receiver',
			'reseller_digitals.created_at',
			'reseller_digitals.updated_at',
		];
		$orderKey = 'reseller_digitals.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('reseller_digitals', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getResellerDigital ($filters = null) {
		$member = $this->repository->findOne('reseller_digitals', $filters);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createResellerDigital ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];
		$existsMember = $this->repository->findOne('reseller_digitals', ['id_auth_api' => $this->auth['id_auth'], 'phone_number' => $payload['phone_number']]);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Member already exists.');
			return $this->delivery;
		}
		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('reseller_digitals', $payload);
		$result = $this->repository->findOne('reseller_digitals', ['id' => $action]);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateResellerDigitals ($payload, $filters = null) {
		$existsMembers = $this->repository->find('reseller_digitals', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		unset($payload['phone_number']);
		unset($payload['id_auth_api']);
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('reseller_digitals', $payload, $filters);
		$result = $this->repository->find('reseller_digitals', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function generateResellerDigitalAttribute ($id) {
		$existsMember = $this->repository->findOne('reseller_digitals', ['id' => $id]);
		if (empty($existsMember)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$memberCard = $this->createMemberCard($existsMember);
		$data = [
			'member_card_url' => $memberCard['cdn_url'],
			'updated_at' => date('Y-m-d H:i:s')
		];
		if (empty($existsMember->member_code)) {
			$memberCode = $this->createMemberCode($existsMember->id);
			$data['member_code'] = $memberCode;
		}
		$action = $this->repository->update('reseller_digitals', $data, ['id' => $existsMember->id]);
		$result = $this->repository->findOne('reseller_digitals', ['id' => $existsMember->id]);
		/* $memberDigitalCardProducer = new MemberDigitalCardProducer;
		$result = $memberDigitalCardProducer->createCard($existsMember); */
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteResellerDigitals ($filters = null) {
		$existsMembers = $this->repository->find('reseller_digitals', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('reseller_digitals', $payload, $filters);
		$result = $this->repository->find('reseller_digitals', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberDigitalTransactions ($filters = null) {
		$join = [
			'reseller_digitals' => 'reseller_digitals.id = member_digital_transactions.id_member_digital'
		];

		$args = [
			'member_digital_transactions.id_auth_api' => $this->auth['id_auth']
		];

		if (isset($filters['id_member_digital']) && !empty($filters['id_member_digital'])) {
			$args['member_digital_transactions.id_member_digital'] = $filters['id_member_digital'];
		}

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['reseller_digitals.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['store_name']) && !empty($filters['store_name'])) {
			$args['member_digital_transactions.store_name'] = [
				'condition' => 'like',
				'value' => $filters['store_name']
			];
		}

		if (isset($filters['source_name']) && !empty($filters['source_name'])) {
			$args['member_digital_transactions.source_name'] = [
				'condition' => 'like',
				'value' => $filters['source_name']
			];
		}

		if (isset($filters['order_id']) && !empty($filters['order_id'])) {
			$args['member_digital_transactions.order_id'] = [
				'condition' => 'like',
				'value' => $filters['order_id']
			];
		}

		if (isset($filters['member_code']) && !empty($filters['member_code'])) {
			$args['reseller_digitals.member_code'] = [
				'condition' => 'like',
				'value' => $filters['member_code']
			];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['reseller_digitals.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
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
			'member_digital_transactions.id',
			'member_digital_transactions.id_member_digital',
			'reseller_digitals.phone_number',
			'reseller_digitals.wablas_phone_number_receiver',
			'member_digital_transactions.order_id',
			'member_digital_transactions.store_name',
			'member_digital_transactions.source_name',
			'member_digital_transactions.payment_amount',
			'member_digital_transactions.member_point',
			'member_digital_transactions.is_notified',
			'member_digital_transactions.created_at',
		];
		$orderKey = 'member_digital_transactions.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('member_digital_transactions', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
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
			'member_code' => $slug
		];
		$existsMember = $this->repository->findOne('reseller_digitals', ['id_auth_api' => $this->auth['id_auth']], $argsOrWhere);
		if (empty($existsMember)) {
			$this->delivery->addError(409, 'Member not found.');
			return $this->delivery;
		}

		$existsOrderId = $this->repository->findOne('member_digital_transactions' ,['order_id' => $payload['order_id'], 'id_auth_api' => $this->auth['id_auth']]);
		if (!empty($existsOrderId)) {
			$this->delivery->addError(409, 'Order ID already exists.');
			return $this->delivery;
		}

		$memberPoint = 0;
		if (isset($payload['member_point']) && !empty($payload['member_point'])) {
			$memberPoint = $payload['member_point'];
		} else if (isset($payload['payment_amount']) && !empty($payload['payment_amount'])) {
			$memberPoint = intval($payload['payment_amount']/100000);
		} else {
			$this->delivery->addError(409, 'Payment amount or member point should not be empty');
			return $this->delivery;
		}

		$payload['id_member_digital'] = $existsMember->id;
		$payload['member_point'] = $memberPoint;
		$payload['created_at'] = date('Y-m-d H:i:s');

		// update member_digital `point`
		$newPayload = [
			'point' => $existsMember->point + $payload['member_point'],
			'updated_at' => date('Y-m-d H:i:s')
		];

		$action = $this->repository->insert('member_digital_transactions', $payload);
		$newAction = $this->repository->update('reseller_digitals', $newPayload, ['id' => $existsMember->id]);

		$transactionResult = $this->repository->findOne('member_digital_transactions', ['id' => $action]);

		if (!empty($existsMember->wablas_phone_number_receiver)) {
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $existsMember->wablas_phone_number_receiver]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				$this->delivery->addError(400, 'Wablas Config is required');
				return $this->delivery;
			}
			$this->waService = new WablasService($this->wablasDomain, $wablasConfig->wablas_token);
			$message = sprintf('Halo kak %s terimakasih sudah melakukan pembelanjaan di %s - %s kami, dengan nominal pembelanjaan sebesar Rp %s dan kaka mendapatkan %s poin dari pembelanjaan ini.', $existsMember->name, $payload['store_name'], $payload['source_name'], number_format($transactionResult->payment_amount, 0, ',', '.'), $transactionResult->member_point);
			if ($memberPoint <= 0) {
				$message = sprintf('Halo kak %s, poin anda telah dikurangi sebesar %s', $existsMember->name, abs($memberPoint));
			}
			$sendWa = $this->waService->sendMessage($existsMember->phone_number, $message);
			if ($sendWa->status == true) {
				$action = $this->repository->update('member_digital_transactions', ['is_notified' => 1], ['id' => $transactionResult->id]);
				$transactionResult->is_notified = 1;
			}
			$transactionResult->wablas_alt = [
				'message' => $message,
				'result' => $sendWa
			];
		}

		$this->delivery->data = $transactionResult;
		return $this->delivery;
	}

	public function updateMemberDigitalTransactions ($payload, $filters = null) {
		$existsTransactions = $this->repository->find('member_digital_transactions', $filters);
		if (empty($existsTransactions)) {
			$this->delivery->addError(409, 'No transactions found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_digital_transactions', $payload, $filters);
		$result = $this->repository->find('member_digital_transactions', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberDigitalTransactions ($filters = null) {
		$existsTransactions = $this->repository->find('member_digital_transactions', $filters);
		if (empty($existsTransactions)) {
			$this->delivery->addError(409, 'No transactions found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_digital_transactions', $payload, $filters);
		$result = $this->repository->find('member_digital_transactions', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function notifyFinish () {
		$result = [];
		$memberDigitals = $this->repository->find('reseller_digitals', ['member_code' => null, 'notify_finish <=' => 3]);
		foreach ($memberDigitals as $memberDigital) {
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $memberDigital->wablas_phone_number_receiver]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				continue;
				// $this->delivery->addError(400, 'Wablas Config is required');
				// return $this->delivery;
			}

			$this->waService = new WablasService($this->wablasDomain, $wablasConfig->wablas_token);
			$message = sprintf('Halo kak %s pendaftarannya belum selesai jadi kami belum bisa memberikan kartunya, ayo diselesaikan agar bisa mendapatkan diskon 50%% di setiap belanja selama desember.', $memberDigital->name);
			$sendWa = $this->waService->sendMessage($memberDigital->phone_number, $message);
			$wablasAction = [
				'message' => $message,
				'result' => $sendWa
			];
			$result[] = $wablasAction;
			$data = [
				'notify_finish' => $memberDigital->notify_finish + 1
			];
			$action = $this->repository->update('reseller_digitals', $data, ['id' => $memberDigital->id]);
		}
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function notifyTransactions () {
		$filters = [
			'member_digital_transactions.id_auth_api' => $this->auth['id_auth'],
			'member_digital_transactions.is_notified' => 0
		];

		$join = [
			'reseller_digitals' => 'reseller_digitals.id = member_digital_transactions.id_member_digital'
		];

		$select = [
			'member_digital_transactions.id',
			'member_digital_transactions.id_member_digital',
			'reseller_digitals.name as member_name',
			'reseller_digitals.phone_number',
			'reseller_digitals.wablas_phone_number_receiver',
			'member_digital_transactions.order_id',
			'member_digital_transactions.store_name',
			'member_digital_transactions.source_name',
			'member_digital_transactions.payment_amount',
			'member_digital_transactions.member_point',
			'member_digital_transactions.is_notified',
			'member_digital_transactions.created_at',
		];

		$transactions = $this->repository->find('member_digital_transactions', $filters, null, $join, $select);
		$result = null;
		$messageSent = 0;
		foreach ($transactions as $transaction) {
			if (!empty($transaction->wablas_phone_number_receiver)) {
				$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $transaction->wablas_phone_number_receiver]);
				if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
					$this->delivery->addError(400, 'Wablas Config is required');
					return $this->delivery;
				}
				$this->waService = new WablasService($this->wablasDomain, $wablasConfig->wablas_token);
				$message = sprintf('Halo kak %s terimakasih sudah melakukan pembelanjaan di %s - %s kami, dengan nominal pembelanjaan sebesar Rp %s dan kaka mendapatkan %s poin dari pembelanjaan ini.', $transaction->member_name, $transaction->store_name, $transaction->source_name, number_format($transaction->payment_amount, 0, ',', '.'), $transaction->member_point);
				if ($transaction->member_point <= 0) {
					$message = sprintf('Halo kak %s, poin anda telah dikurangi sebesar %s', $transaction->member_name, abs($transaction->member_point));
				}
				$sendWa = $this->waService->sendMessage($transaction->phone_number, $message);
				if ($sendWa->status == true) {
					$action = $this->repository->update('member_digital_transactions', ['is_notified' => 1], ['id' => $transaction->id]);
					$result['details'][] = [
						'member_name' => $transaction->member_name,
						'phone_number' => $transaction->phone_number,
						'wablas_phone_number_receiver' => $transaction->wablas_phone_number_receiver,
						'message' => $message
					];
					$messageSent++;
				}
			}
		}

		$result['message'] = sprintf('Message sent: %s', $messageSent);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function sendBatchWablas ($payload) {
		set_time_limit(0);
		$availableMessageTypes = [
			'text',
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
			'province' => 'province'
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

		if (!isset($payload['send_at_date']) || empty($payload['send_at_date'])) {
			$this->delivery->addError(400, 'Send at is required');
		}

		if (!isset($payload['send_at_time']) || empty($payload['send_at_time'])) {
			$this->delivery->addError(400, 'Send at is required');
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
		$members = $this->repository->find('reseller_digitals', $filters);

		$result = [
			'total_member' => count($members),
			'total_recepient' => 0,
			'send_at_date' => $payload['send_at_date'],
			'send_at_time' => $payload['send_at_time'],
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
				}
				$detail['extras'] = $action;
			}
			$result['list'][] = $detail;
			$result['total_recepient']++;
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function createMemberCard ($member) {
		if (!empty($member->member_card_url)) {
			$oldFilename = str_replace('https://cdn.1itmedia.co.id/', '', $member->member_card_url);
			$action = delete_from_cloud($oldFilename);
		}

		$filename = sprintf('%s%s.png', $member->member_code, time());
		$formattedPath = sprintf('%s/%s', $this->uploadPath, $filename);
		$image = new Image([
			// 'binary' => 'C:\Program Files\wkhtmltopdf\bin\wkhtmltoimage.exe',
    		'commandOptions' => ['useExec' => true],
    		'ignoreWarnings' => true
		]);		
		$image->setPage($this->generateHTMLMemberCard($member));
		$image->saveAs($formattedPath);

		$im = imagecreatefrompng($formattedPath);
		  
		// find the size of image
		$size = min(imagesx($im), imagesy($im));
		  
		// Set the crop image size 
		$im2 = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => 750, 'height' => 990]);
		if ($im2 !== FALSE) {
			unlink($formattedPath);
	      	imagepng($im2, $formattedPath);
		    imagedestroy($im2);
		}
		imagedestroy($im);

		$uploadToDigitalOcean = upload_to_cloud($formattedPath, $filename);
		unlink($formattedPath);
		return $uploadToDigitalOcean;
	}

	private function createMemberCode ($id) {
		return sprintf('B%s%s', date('YmdH'), str_pad($id, 4, '0', STR_PAD_LEFT));
	}

	private function generateHTMLMemberCard ($member) {
		$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
		$bgPath = $this->uploadPath.'/bg_card.png';
		$html = '
		<html>
			<head>
				<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Poppins" />
				<style>
					body {
						font-family: "Poppins";
				    	margin:  0px;
		           	}

					.container {
				    	width: 750px;
		        		height: 990px;
						background-image: url("https://cdn.1itmedia.co.id/2d14bb5d3f4c877f4ff873a0b59358c4.png");
					 	background-repeat: no-repeat;
					}

					.blank {
						float: left;
						width: 100%;
					}

					.member-attribute {
						float: left;
						width: 100%;
						padding-left: 60px;
						font-size: 48px;
						color: white;
					}

					.barcode-attribute {
						padding-top: 20px;
						float: left;
						width: 100%;
						text-align: center;
					}

					.barcode-description-attribute {
						float: left;
						width: 100%;
						font-size: 20px;
						padding-top: 15px;
						color: black;
						text-align: center;
					}
				</style>
			</head>
			<body>
				<div class="container" id="print-area">
					<div class="blank" style="height: 320px;"></div>
					<div class="member-attribute" style="height: 100px;">'.$member->member_code.'</div>
					<div class="blank" style="height: 30px;"></div>
					<div class="member-attribute" style="height: 150px;">'.ucwords($member->name).'</div>
					<div class="blank" style="height: 210px; width: 120px;"></div>
					<div class="blank" style="height: 210px; width: 520px;">
						<div class="barcode-attribute">'.'<img src="data:image/png;base64,' . base64_encode($generator->getBarcode($member->member_code, $generator::TYPE_CODE_128, 4, 130)) . '">'.'</div>
						<div class="barcode-description-attribute">'.ucwords($member->name).'</div>
					</div>
					<div class="blank" style="height: 210px; width: 100px;"></div>
				</div>
			</body>
		</html>
		';

		return $html;
	}

	private function sendScheduledWablasToMember ($member, $message, $date, $time) {
		$result = null;
		if (!empty($member->wablas_phone_number_receiver)) {
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $member->wablas_phone_number_receiver]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				return $result;
			}
			$this->waService = new WablasService($this->wablasDomain, $wablasConfig->wablas_token);
			// $sendWa = $this->waService->createScheduledMessage($member->phone_number, $message, $date, $time);
			$sendWa = $this->waService->publishMessage('scheduled_message', $member->phone_number, $message, null, $date, $time);
			return $sendWa;
		}
		return $result;
	}

	private function sendWablasToMember ($member, $message) {
		$result = null;
		if (!empty($member->wablas_phone_number_receiver)) {
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $member->wablas_phone_number_receiver]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				return $result;
			}
			$this->waService = new WablasService($this->wablasDomain, $wablasConfig->wablas_token);
			// $sendWa = $this->waService->createScheduledMessage($member->phone_number, $message, $date, $time);
			$sendWa = $this->waService->publishMessage('send_message', $member->phone_number, $message);
			return $sendWa;
		}
		return $result;
	}

	private function sendBirthdayCardToMember ($member, $message = '') {
		$producer = new MemberDigitalBirthdayCardProducer;
		$result = $producer->sendBirthdayCard($member, $message);
		return $result;
	}

	public function generateAndPublishBirthdayCardToMember ($member, $message = '') {
		$filename = sprintf('%s%s.png', 'hbdcard', time());
		$formattedPath = sprintf('%s/%s', $this->uploadPath, $filename);
		$image = new Image([
			// 'binary' => 'C:\Program Files\wkhtmltopdf\bin\wkhtmltoimage.exe',
    		'commandOptions' => ['useExec' => true],
    		'ignoreWarnings' => true
		]);		
		$image->setPage($this->generateHTMLBirthdayCard($member));
		$image->saveAs($formattedPath);

		$im = imagecreatefrompng($formattedPath);
		  
		// find the size of image
		$size = min(imagesx($im), imagesy($im));
		  
		// Set the crop image size 
		$im2 = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => 1280, 'height' => 1024]);
		if ($im2 !== FALSE) {
			unlink($formattedPath);
	      	imagepng($im2, $formattedPath);
		    imagedestroy($im2);
		}
		imagedestroy($im);

		$uploadToDigitalOcean = upload_to_cloud($formattedPath, $filename);
		unlink($formattedPath);

		$result = null;
		if (!empty($member->wablas_phone_number_receiver)) {
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $member->wablas_phone_number_receiver]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				return $result;
			}
			$this->waService = new WablasService($this->wablasDomain, $wablasConfig->wablas_token);
			$sendWa = $this->waService->publishMessage('send_image', $member->phone_number, '', $uploadToDigitalOcean['cdn_url']);
			$result[] = $sendWa;	
			$sendWa2 = $this->waService->publishmessage('send_message', $member->phone_number, $message);
			$result[] = $sendWa2;
			return $result;
		}

		return $uploadToDigitalOcean;
	}

	private function generateHTMLBirthdayCard ($member) {
		$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
		$html = '
		<html>
			<head>
				<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Dancing+Script" />
				<style>
					body {
						font-family: "Dancing Script";
				    	margin:  0px;
		           	}

					.container {
				    	width: 1280px;
		        		height: 1024px;
						background-image: url("https://cdn.1itmedia.co.id/708a25ece33adc9859d5230bc0d31abb.jpeg");
					 	background-repeat: no-repeat;
					}

					.member-name-box {
						position: absolute;
						top: 740px;
						left: 40px;
						width: 520px;
						height: 105px;
						text-align: center;
						font-size: 72px;
						display: table;
					}

					.member-name-box > span {
						display: table-cell;
						vertical-align: middle;
					}
				</style>
			</head>
			<body>
				<div class="container" id="print-area">
					<div class="member-name-box">
						<span>'.$member->name.'</span>
					</div>
				</div>
			</body>
		</html>
		';

		return $html;
	}

}