<?php
namespace Service\MemberDigital;

use Library\WablasService;
use Service\Entity;
use Service\Delivery;
use Redis\Producer\MemberDigitalCardProducer;
use Redis\Producer\MemberDigitalBirthdayCardProducer;
use Milon\Barcode\DNS1D;
use mikehaertl\wkhtmlto\Image;
use Library\DigitalOceanService;
use Library\RabbaniService;
use \libphonenumber\PhoneNumberUtil;

class MemberDigitalHandler {

	const MAIN_WABLAS = '62895383334783';

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;

	public $existsValidation = true;
	
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
		/* $data = [
			'fromcall' => 'WABLAS',
			'dataJson' => json_encode($payload),
			'dateTime' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->insert('logcallback', $data); */

		if (empty(trim($payload['message']))) {
			$this->delivery->addError(409, 'Silahkan masukkan pesan!');
			return $this->delivery;
		}

		$authApi = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $payload['receiver']]);
		if (empty($authApi)) {
			$this->delivery->addError(409, 'Config error!');
			return $this->delivery;
		}

		$existsMember = $this->repository->findOne('member_digitals', ['phone_number' => $payload['phone'], 'id_auth_api' => $authApi->id_auth_api]);
		if (empty($existsMember)) {
			if (strtolower($payload['message']) == 'daftar') {
				$newMember = [
					'id_auth_api' => $authApi->id_auth_api,
					'phone_number' => $payload['phone'],
					'created_at' => date('Y-m-d H:i:s'),
					'wablas_phone_number_receiver' => $payload['receiver']
				];
				$action = $this->repository->insert('member_digitals', $newMember);
				$newMember['id'] = $action;
				$existsMember = $this->repository->findOne('member_digitals', ['id' => $newMember['id']]);
				$this->delivery->addError(409, 'Silahkan isi nama anda:');
				return $this->delivery;
			} else {
				$formattedString = preg_split('/[\s]+/', $payload['message']);
				$firstWord = $payload['message'];
				if (count($formattedString) > 0) {
					$firstWord = $formattedString[0];
				}
				$memberReferral = $this->repository->findOne('member_digitals', ['referral_code' => strtolower($firstWord)]);
				if (!empty($memberReferral)) {
					// set member referral level = 1 jika tidak memiliki level
					if (empty($memberReferral->member_digital_level)) {
						$referralData = [
							'member_digital_level' => 1,
							'updated_at' => date('Y-m-d H:i:s')
						];
						$action = $this->repository->update('member_digitals', $referralData, ['id' => $memberReferral->id]);
						$memberReferral->member_digital_level = $referralData['member_digital_level'];
					}

					// bikin member baru dengan level + 1 member referral
					$newMember = [
						'id_auth_api' => $authApi->id_auth_api,
						'phone_number' => $payload['phone'],
						'created_at' => date('Y-m-d H:i:s'),
						'wablas_phone_number_receiver' => $payload['receiver'],
						'member_digital_level' => $memberReferral->member_digital_level + 1,
						'referred_by_member_digital_id' => $memberReferral->id,
						'affiliate_active_at' => date('Y-m-d H:i:s'),
					];
					$action = $this->repository->insert('member_digitals', $newMember);
					$text = 'Selamat bergabung di digital member rabbani, saat ini kaka di referensikan oleh '.$memberReferral->name.', untuk selanjutnya silahkan isi nama lengkap kaka';
					$this->delivery->addError(400, $text);
					return $this->delivery;
				} else {
					$this->delivery->addError(409, 'Silahkan ketik "Daftar" untuk melakukan pendaftaran. Pendaftaran gratis dan bisa digunakan seluruh toko se-Indonesia.');
					return $this->delivery;	
				}
			}
		}

		if (empty($existsMember->name)) {
			$nameIsReferral = $this->repository->findOne('member_digitals', ['referral_code' => strtolower($payload['message'])]);
			if ($nameIsReferral) {
				$this->delivery->addError(400, 'Silahkan isi nama anda: (contoh: Agus Sopian)');
				return $this->delivery;
			}
			if (preg_match('/[\^£$:%&*()}{@#~?><>|=_+¬-]/', $payload['message']) > 0) {
				$this->delivery->addError(400, 'Silahkan isi nama anda: (contoh: Agus Sopian)');
				return $this->delivery;
			}
			if (strtolower($payload['message']) == 'daftar') {
				$this->delivery->addError(400, 'Silahkan isi nama anda: (contoh: Agus Sopian)');
				return $this->delivery;
			}
			$data = [
				'name' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
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
			$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
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
			$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi alamat lengkap anda:');
			return $this->delivery;
		}

		if (empty($existsMember->address)) {
			$data = [
				'address' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi provinsi anda:');
			return $this->delivery;
		}

		if (empty($existsMember->province)) {
			// untuk isi `province` cari di table `provinces`
			$argsProvince['name'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findProvince = $this->repository->find('provinces', $argsProvince);
			if (count($findProvince) > 1) {
				$isExact = false;
				$message = 'Kami menemukan '.count($findProvince).' provinsi dengan nama tersebut. Silahkan ketik salah satu provinsi dengan lengkap.';
				foreach ($findProvince as $province) {
					$message .= PHP_EOL.'- '.$province->name;
					if (strtolower($payload['message']) == strtolower($province->name)) {
						$findProvince[0] = $province;
						$isExact = true;
						break;
					}
				}
				if (!$isExact) {
					$this->delivery->addError(400, $message);
					return $this->delivery;
				}
			} else if (empty($findProvince)) {
				$allProvince = $this->repository->find('provinces');
				$provinceMessage = 'Silahkan pilih dan ketik dengan lengkap salah satu provinsi berikut:';
				foreach ($allProvince as $province) {
					$provinceMessage .= PHP_EOL.'- '.$province->name;
				}
				$this->delivery->addError(400, $provinceMessage);
				return $this->delivery;
			}
			$message = $findProvince[0]->name;

			$data = [
				'province' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi kota/kabupaten anda:');
			return $this->delivery;
		}

		if (empty($existsMember->city)) {
			$argsKabupaten['nama'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findProvince = $this->repository->findOne('provinces', ['name' => $existsMember->province]);
			$argsKabupaten['id_prov'] = $findProvince->id;
			$findKabupaten = $this->repository->find('districts', $argsKabupaten);
			if (count($findKabupaten) > 1) {
				$isExact = false;
				$message = 'Kami menemukan '.count($findKabupaten).' kota/kabupaten dengan nama tersebut. Silahkan ketik salah satu kota/kabupaten dengan lengkap.';
				foreach ($findKabupaten as $kabupaten) {
					$message .= PHP_EOL.'- '.$kabupaten->nama;
					if (strtolower($payload['message']) == strtolower($kabupaten->nama)) {
						$findKabupaten[0] = $kabupaten;
						$isExact = true;
					}
				}
				if (!$isExact) {
					$this->delivery->addError(400, $message);
					return $this->delivery;
				}
			} else if (empty($findKabupaten)) {
				$allKabupaten = $this->repository->find('districts', ['id_prov' => $findProvince->id]);
				$kabupatenMessage = 'Silahkan pilih dan ketik dengan lengkap salah satu kota/kabupaten berikut:';
				foreach ($allKabupaten as $kabupaten) {
					$kabupatenMessage .= PHP_EOL.'- '.$kabupaten->nama;
				}
				$this->delivery->addError(400, $kabupatenMessage);
				return $this->delivery;
			}
			$message = $findKabupaten[0]->nama;

			$data = [
				'city' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
		}

		$menuAffiliateDescription = '5. AFFILIATE: format untuk mendaftar program affiliate rabbani, dapatkan penghasilan cukup referensikan agar berbelanja dan daftar member digital';
		if (!empty($existsMember->referral_code)) {
			$menuAffiliateDescription = '5. AFFILIATE : format untuk melihat mutasi komisi dan pembaharuan data bank';
		}
		$generalMenuText = sprintf('Status kak %s saat ini sudah menjadi member digital kami, silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.%s1. POIN : format untuk mengetahui total poin%s2. UPDATE : format untuk perubahan data%s3. TRANSAKSI : format untuk mengetahui histori transaksi%s4. CETAK : format untuk mendapatkan / cetak ulang kartu digital', $existsMember->name, PHP_EOL, PHP_EOL, PHP_EOL, PHP_EOL).PHP_EOL.$menuAffiliateDescription;
		// $generalMenuText = sprintf('Status kak %s saat ini sudah menjadi member digital kami, silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.%s1. POIN : format untuk mengetahui total poin%s2. UPDATE : format untuk perubahan data%s3. TRANSAKSI : format untuk mengetahui histori transaksi%s4. CETAK : format untuk mendapatkan / cetak ulang kartu digital', $existsMember->name, PHP_EOL, PHP_EOL, PHP_EOL, PHP_EOL).PHP_EOL;
		
		if ($existsMember->wablas_menu_state == 'show_confirmation_update') {
			$data = [
				'wablas_menu_state' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
			if ($payload['message'] == 1) {
				$emptyData = [
					'name' => '',
					// 'member_code' => '',
					'birthday' => null,
					'gender' => '',
					'address' => '',
					'province' => '',
					'city' => '',
					'member_card_url' => '',
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_digitals', $emptyData, ['id' => $existsMember->id]);
				$this->delivery->addError(409, 'Silahkan isi nama anda:');
			} else {
				$this->delivery->addError(400, sprintf('Perubahan data dibatalkan %s%s%s', PHP_EOL, PHP_EOL, $generalMenuText));
			}
			return $this->delivery;
		} else if ($existsMember->wablas_menu_state == 'show_affiliator_menu') {
			if (empty($existsMember->bank_name)) {
				$data = [
					'bank_name' => $payload['message'],
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
				$this->delivery->addError(400, 'Silahkan isi nomor rekening anda:');
				return $this->delivery;
			}
			if (empty($existsMember->bank_account_number)) {
				$data = [
					'bank_account_number' => $payload['message'],
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
				$this->delivery->addError(400, 'Silahkan isi nama pemilik rekening:');
				return $this->delivery;
			}
			if (empty($existsMember->bank_account_name)) {
				$existsMember->bank_account_name = $payload['message'];
				$data = [
					'bank_account_name' => $payload['message'],
					'updated_at' => date('Y-m-d H:i:s'),
					'wablas_menu_state' => null
				];
				if (empty($existsMember->referral_code)) {
					$referralCode = MemberDigitalHelper::generateReferralCode($existsMember);
					$data['referral_code'] = $referralCode;
					$data['affiliator_active_at'] = date('Y-m-d H:i:s');
					$existsMember->referral_code = $referralCode;

					// jika member tidak punya level, maka set level = 1
					if (empty($existsMember->member_digital_level)) {
						$data['member_digital_level'] = 1;
					}
				}
				$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
				$text = 'Nama Bank: '.$existsMember->bank_name.PHP_EOL.'Nomor Rekening: '.$existsMember->bank_account_number.PHP_EOL.'Nama Pemilik Rekening: '.$existsMember->bank_account_name.PHP_EOL.PHP_EOL.'Bagikan link ini untuk affiliate anda: '.PHP_EOL.'https://wa.me/62818755552?text='.$existsMember->referral_code.PHP_EOL.PHP_EOL.$generalMenuText;
				$this->delivery->addError(400, $text);
				return $this->delivery;
			}
		} else if ($existsMember->wablas_menu_state == 'show_affiliator_refresh') {
			if ($payload['message'] == 1) {
				$data = [
					'wablas_menu_state' => 'show_affiliator_menu',
					'bank_account_name' => null,
					'bank_name' => null,
					'bank_account_number' => null,
					'updated_at'=> date('Y-m-d H:i:s'),
				];
				$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
				$text = 'Silahkan masukkan nama bank anda: (BCA/Mandiri/Jago/dll)';
				$this->delivery->addError(400, $text);
			} else {
				$data = [
					'wablas_menu_state' => null,
				];
				$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
				$this->delivery->addError(400, $generalMenuText);
			}
			return $this->delivery;
		}
		if (!empty($existsMember->member_code)) {
			$payload['message'] = strtolower($payload['message']);
			if (!in_array($payload['message'], ['1', '2', '3', '4' ,'5','poin', 'update', 'transaksi', 'cetak', 'affiliate', 'diskon kas', 'diskon'])){
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
					$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
					$text = sprintf('Apakah anda ingin melanjutkan perubahan data?%s1. Lanjut%s0. Batal', PHP_EOL,PHP_EOL);
					$this->delivery->addError(400, $text);
				} else if ($payload['message'] == '3' || $payload['message'] == 'transaksi') {
					$this->auth['id_auth'] = $existsMember->id_auth_api;
					$filters = [
						'id_member_digital' => $existsMember->id,
						'transaction_type' => 'shop_purchase',
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
					$memberCard['cdn_url'] = $existsMember->member_card_url;
					if (empty($existsMember->member_card_url)) {
						$memberCard = $this->createMemberCard($existsMember);
					}
					$data = [
						'member_card_url' => $memberCard['cdn_url']
					];
					$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
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

					$extras = null;
					if (!empty($existsMember->wablas_phone_number_receiver)) {
						// $wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $existsMember->wablas_phone_number_receiver]);
						$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => ($existsMember->wablas_phone_number_receiver == '62895383334783' ? self::MAIN_WABLAS : $existsMember->wablas_phone_number_receiver)]);
						if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
							$this->delivery->addError(400, 'Wablas Config is required');
							return $this->delivery;
						}
						$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
						$sendWa = $this->waService->publishMessage ('send_image', $existsMember->phone_number, 'Berikut kartu anggota anda', $memberCard['cdn_url']);
						$extras['send_image'] = $sendWa;
					}

					header('Content-Type: application/json');
					$this->delivery->addError(409, json_encode(['data' => $formattedPayload, 'extras' => $extras]));
				} else if ($payload['message'] == '5' || $payload['message'] == 'affiliate') {
					$wablasMenuState = 'show_affiliator_menu';
					$text = 'Silahkan masukkan nama bank anda: (BCA/Mandiri/Jago/dll)';
					if (!empty($existsMember->bank_account_name)) {
						$wablasMenuState = 'show_affiliator_refresh';

						$this->auth['id_auth'] = $existsMember->id_auth_api;
						$filters = [
							'id_member_digital' => $existsMember->id,
							'transaction_types' => [
								'marketing_reward',
								'marketing_reward_withdraw',
								'marketing_reward_refund'
							],
							'data' => 10,
							'sort_key' => 'member_digital_transactions.created_at',
							'sort_value' => 'DESC',
						];
						$transactionsResult = $this->getMemberDigitalTransactions($filters);
						$transactionsData = $transactionsResult->data['result'];
						if (!empty($transactionsData)) {
							// $formattedMessage = sprintf('Berikut informasi 10 transaksi terakhir dari pembelanjaan kak %s', $existsMember->name);
							$text = 'Halo kak '.$existsMember->name.', saat ini total komisi kaka sebesar '.toRupiahFormat($existsMember->balance_reward).PHP_EOL.PHP_EOL;
							foreach ($transactionsData as $transaction) {
								if ($transaction->transaction_type == 'marketing_reward') {
									$text .= sprintf('- %s: Komisi dari pembelanjaan %s sebesar %s', date('d/m/Y', strtotime($transaction->created_at)), $transaction->referred_member_digital_name ,toRupiahFormat($transaction->amount)).PHP_EOL;
								} else if ($transaction->transaction_type == 'marketing_reward_withdraw') {
									$text .= sprintf('- %s: Pencairan komisi sebesar %s berhasil dilakukan, rekening penerima %s-%s a.n %s', date('d/m/Y', strtotime($transaction->created_at)), toRupiahFormat($transaction->amount), $transaction->transfer_bank_name, $transaction->transfer_bank_account_number, $transaction->transfer_bank_account_name).PHP_EOL;
								} else if ($transaction->transaction_type == 'marketing_reward_refund') {
									$text .= sprintf('- %s: Saldo komisi anda dikembalikan sebesar %s', date('d/m/Y', strtotime($transaction->created_at)), toRupiahFormat($transaction->amount)).PHP_EOL;
								}
							}
						} else {
							$formattedMessage = 'Saat ini belum ada transaksi komisi yang tercatat';
						}

						$text .= PHP_EOL.'Nama Bank: '.$existsMember->bank_name.PHP_EOL.'Nomor Rekening: '.$existsMember->bank_account_number.PHP_EOL.'Nama Pemilik Rekening: '.$existsMember->bank_account_name.PHP_EOL.PHP_EOL.'Bagikan link ini untuk affiliate anda: '.PHP_EOL.'https://wa.me/62818755552?text='.$existsMember->referral_code.PHP_EOL.PHP_EOL.'Apakah anda ingin melakukan perubahan data rekening?'.PHP_EOL.'1. Lanjut'.PHP_EOL.'2. Batal';
					}

					$data = [
						'wablas_menu_state' => $wablasMenuState,
						'updated_at' => date('Y-m-d H:i:s')
					];
					$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
					$this->delivery->addError(400, $text);
					return $this->delivery;
				} else if ($payload['message'] == 'diskon kas' || $payload['message'] == 'diskon') {
					header('Content-Type: application/json');
					$formattedPayload = [];

					$voucherCode = '20B-WB'.time();
					// hit ke rabbani
					$rabbaniService = new RabbaniService();
					$action = $rabbaniService->generateVoucherWeekend($voucherCode);
					if (!isset($action->status) || $action->status != 202) {
						$messageError = 'Mohon maaf terjadi kesalahan. Silahkan coba beberapa saat lagi.'.PHP_EOL.PHP_EOL.$generalMenuText;
						$this->delivery->addError(400, $messageError);
						return $this->delivery;
					}

					// generate gambar
					$imageAction = null;
					try {
						$imageAction = $this->createDiscountCard($existsMember, $voucherCode);
					} catch (\Exception $e) {
						$messageError = 'Mohon maaf terjadi kesalahan. Silahkan coba beberapa saat lagi.'.PHP_EOL.PHP_EOL.$generalMenuText;
						$this->delivery->addError(400, $messageError);
						return $this->delivery;
					}
					
					/* $formattedPayload[] = [
						'category' => 'image',
						'caption' => $this->generateDiscountCardCaption($existsMember, $voucherCode),
						'urlFile' => $imageAction['ObjectURL'],
					]; */
					$imageUrl = $imageAction['cdn_url'];
					$formattedPayload[] = [
					    'category' => 'image',
					    'caption' => 'caption image',
					    'urlFile' => $imageUrl,
					];

					// generate image
					$formattedPayload[] = [
						'category' => 'text',
						'message' => $this->generateDiscountCardCaption($existsMember, $voucherCode),
					];

					$imageText = 'DOWNLOAD VOUCHER'.PHP_EOL.$imageUrl;
					$formattedPayload[] = [
						'category' => 'text',
						'message' => $imageText,
					];	

					// generate image
					$formattedPayload[] = [
						'category' => 'text',
						'message' => $generalMenuText,
					];	
					$this->delivery->addError(409, json_encode(['data' => $formattedPayload]));
				}

				return $this->delivery;
			}

		}


		try {
			$memberCode = $this->createMemberCode($existsMember->id);
			$existsMember->member_code = $memberCode;
			// $memberCard = $this->createMemberCard($existsMember);

			/* $memberDigitalCardProducer = new MemberDigitalCardProducer;
			$memberDigitalCardProducer->createCard($existsMember); */

			$data = [
				'member_code' => $memberCode,
				// 'member_card_url' => $memberCard['cdn_url'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
			$waLink = 'https://wa.me/62811248838';
			$telegramLink = 't.me/memberrabbani';
			$groupText = sprintf('Info lebih lanjut bergabung dengan Grup member digital %s%s%s%sGrup Telegram Member Digital %s%s%s', PHP_EOL, $waLink, PHP_EOL, PHP_EOL, PHP_EOL, $telegramLink, PHP_EOL);
			$message = sprintf('Pendaftaran kaka sudah berhasil dan aktif, berikut detail keanggotaan kaka. %s%sNama : %s%sNo Kartu Member : %s%s%sKak %s bisa gunakan di seluruh toko Rabbani se-Indonesia, cukup tunjukkan no member tersebut ke kasir. %s%s%s%s%s', PHP_EOL, PHP_EOL, $existsMember->name, PHP_EOL, $existsMember->member_code, PHP_EOL, PHP_EOL, $existsMember->name, PHP_EOL, PHP_EOL, $groupText, PHP_EOL, $generalMenuText);	
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $message
			];
			
			//send voucher after regis success -wekend only redem voucher-
			// file_get_contents('https://member.rabbani.id/notewa.php?idMember='.$existsMember->member_code); 

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
		$args = [
			'member_digitals.id_auth_api' => $this->auth['id_auth']
		];

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['member_digitals.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_digitals.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['member_code']) && !empty($filters['member_code'])) {
			if ($filters['member_code'] == '~~') {
				$args['member_digitals.member_code <>'] = null;
			} else if ($filters['member_code'] == '~') {
				$args['member_digitals.member_code'] = null;
			} else {
				$args['member_digitals.member_code'] = [
					'condition' => 'like',
					'value' => $filters['member_code']
				];	
			}
		}

		if (isset($filters['from_id']) && !empty($filters['from_id'])) {
			$args['member_digitals.id >='] = $filters['from_id'];
		}

		if (isset($filters['until_id']) && !empty($filters['until_id'])) {
			$args['member_digitals.id <='] = $filters['until_id'];
		}

		if (isset($filters['from_updated_at']) && !empty($filters['from_updated_at'])) {
			$args['member_digitals.updated_at >='] = $filters['from_updated_at'];
		}

		if (isset($filters['until_updated_at']) && !empty($filters['until_updated_at'])) {
			$args['member_digitals.updated_at <='] = $filters['until_updated_at'];
		}

		if (isset($filters['until_created_at']) && !empty($filters['until_created_at'])) {
			$args['member_digitals.created_at <='] = $filters['until_created_at'];
		}

		if (isset($filters['from_point']) && !empty($filters['from_point'])) {
			$args['member_digitals.point >='] = $filters['from_point'];
		}

		if (isset($filters['referred_by_member_digital_id']) && !empty($filters['referred_by_member_digital_id'])) {
			$args['member_digitals.referred_by_member_digital_id'] = $filters['referred_by_member_digital_id'];
		}

		if (isset($filters['member_digital_level']) && !empty($filters['member_digital_level'])) {
			$args['member_digitals.member_digital_level'] = $filters['member_digital_level'];
		}

		if (isset($filters['show_deleted']) && !empty($filters['show_deleted']) && $filters['show_deleted'] == "true") {
			$args['show_deleted'] = true;
		}

		if (isset($filters['is_recalculated'])) {
			$args['member_digitals.is_recalculated'] = $filters['is_recalculated'];
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
			'member_digitals.id',
			'member_digitals.name',
			'member_digitals.phone_number',
			'member_digitals.member_card_url',
			'member_digitals.member_code',
			'member_digitals.birthday',
			'member_digitals.gender',
			'member_digitals.address',
			'member_digitals.province',
			'member_digitals.city',
			'member_digitals.point',
			'member_digitals.balance_reward',
			'member_digitals.bank_name',
			'member_digitals.bank_account_number',
			'member_digitals.bank_account_name',
			'member_digitals.referral_code',
			'member_digitals.member_digital_level',
			'member_digitals.referred_by_member_digital_id',
			'COUNT(referral_member_digitals.id) as total_affiliate',
			'member_digitals.affiliator_active_at',
			'member_digitals.affiliate_active_at',
			'member_digitals.wablas_phone_number_receiver',
			'member_digitals.created_at',
			'member_digitals.updated_at',
		];
		$orderKey = 'member_digitals.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'member_digitals as referral_member_digitals' => [
				'type' => 'left',
				'value' => 'referral_member_digitals.referred_by_member_digital_id = member_digitals.id'
			]
		];
		$groupBy = 'member_digitals.id';
		$members = $this->repository->findPaginated('member_digitals', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		foreach ($members['result'] as $member) {
			if (!empty($member->referred_by_member_digital_id)) {
				$member->referred_by_member_digital = $this->getMemberDigital(['id' => $member->referred_by_member_digital_id])->data;
			}
		}
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberDigital ($filters = null) {
		$argsOrWhere = null;
		if (isset($filters['iden'])) {
			$argsOrWhere = [
				'phone_number' => $filters['iden'],
				'id' => $filters['iden'],
				'member_code' => $filters['iden'],
				'referral_code' => $filters['iden']
			];
			unset($filters['iden']);
		}
		$member = $this->repository->findOne('member_digitals', $filters, $argsOrWhere);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberDigital ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];
		$existsMember = $this->repository->findOne('member_digitals', ['id_auth_api' => $this->auth['id_auth'], 'phone_number' => $payload['phone_number']]);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Member already exists.');
			return $this->delivery;
		}
		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('member_digitals', $payload);
		$result = $this->repository->findOne('member_digitals', ['id' => $action]);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberDigitals ($payload, $filters = null) {
		if ($this->existsValidation) {
			$existsMembers = $this->repository->find('member_digitals', $filters);
			if (empty($existsMembers)) {
				$this->delivery->addError(409, 'No member found.');
				return $this->delivery;
			}
		}
		// unset($payload['phone_number']);
		unset($payload['id_auth_api']);
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_digitals', $payload, $filters);
		$result = $this->repository->find('member_digitals', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function generateMemberDigitalAttribute ($id) {
		$existsMember = $this->repository->findOne('member_digitals', ['id' => $id]);
		if (empty($existsMember)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$data = array();
		if (empty($existsMember->member_code)) {
			$memberCode = $this->createMemberCode($existsMember->id);
			$data['member_code'] = $memberCode;
			$existsMember->member_code = $memberCode;
		}
		$memberCard = $this->createMemberCard($existsMember);
		$data['member_card_url'] = $memberCard['cdn_url'];
		$data['updated_at'] = date('Y-m-d H:i:s');
		//$data = [
			//'member_card_url' => $memberCard['cdn_url'],
			//'updated_at' => date('Y-m-d H:i:s')
		//];
		$action = $this->repository->update('member_digitals', $data, ['id' => $existsMember->id]);
		$result = $this->repository->findOne('member_digitals', ['id' => $existsMember->id]);
		/* $memberDigitalCardProducer = new MemberDigitalCardProducer;
		$result = $memberDigitalCardProducer->createCard($existsMember); */
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberDigitals ($filters = null) {
		$existsMembers = $this->repository->find('member_digitals', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_digitals', $payload, $filters);
		$result = $this->repository->find('member_digitals', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberDigitalTransactions ($filters = null) {
		$join = [
			'member_digitals' => 'member_digitals.id = member_digital_transactions.id_member_digital',
			'member_digital_transactions as referred_member_digital_transactions' => [
				'type' => 'left',
				'value' => 'referred_member_digital_transactions.id = member_digital_transactions.referred_by_member_digital_transaction_id'
			],
			'member_digitals as referred_member_digitals' => [
				'type' => 'left',
				'value' => 'referred_member_digitals.id = referred_member_digital_transactions.id_member_digital'
			]
		];

		$args = [
			'member_digital_transactions.id_auth_api' => $this->auth['id_auth']
		];

		$argsOrWhere = [];

		if (isset($filters['id_member_digital']) && !empty($filters['id_member_digital'])) {
			$args['member_digital_transactions.id_member_digital'] = $filters['id_member_digital'];
		}

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['member_digitals.phone_number'] = [
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
			$args['member_digitals.member_code'] = [
				'condition' => 'like',
				'value' => $filters['member_code']
			];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_digitals.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['wrong_member_point']) && $filters['wrong_member_point'] == true) {
			$args[] = [
				'condition' => 'custom',
				'value' => 'member_digital_transactions.member_point <> FLOOR(member_digital_transactions.payment_amount/100000)'
			];
		}

		if (isset($filters['transaction_type']) && !empty($filters['transaction_type'])) {
			$args['member_digital_transactions.transaction_type'] = $filters['transaction_type'];
		}

		if (isset($filters['amount']) && !empty($filters['amount'])) {
			$args['member_digital_transactions.amount'] = $filters['amount'];
		}

		if (isset($filters['from_amount']) && !empty($filters['from_amount'])) {
			$args['member_digital_transactions.amount >='] = $filters['from_amount'];
		}

		if (isset($filters['from_payment_amount']) && !empty($filters['from_payment_amount'])) {
			$args['member_digital_transactions.payment_amount >='] = $filters['from_payment_amount'];
		}

		if (isset($filters['until_amount']) && !empty($filters['until_amount'])) {
			$args['member_digital_transactions.amount <='] = $filters['until_amount'];
		}

		if (isset($filters['latest']) && !empty($filters['latest'])) {
			if($filters['latest']=='month' || $filters['latest']=='year'){
				$args['YEAR(member_digital_transactions.created_at)'] = date('Y');
			}
			if($filters['latest']=='month'){
				$args['MONTH(member_digital_transactions.created_at)'] = date('m');
			}
		}

		if (isset($filters['transaction_types']) && !empty($filters['transaction_types'])) {
			if (is_array($filters['transaction_types'])) {
				foreach ($filters['transaction_types'] as $type) {
					$argsOrWhere[] = sprintf("member_digital_transactions.transaction_type = '%s'", $type);
				}
			} else {
				$argsOrWhere[] = sprintf("member_digital_transactions.transaction_type = '%s'", $filters['transaction_types']);	
			}
		}

		if (isset($filters['from_created_at']) && !empty($filters['from_created_at'])) {
			$args['member_digital_transactions.created_at >='] = $filters['from_created_at'];
		}

		if (isset($filters['until_created_at']) && !empty($filters['until_created_at'])) {
			$args['member_digital_transactions.created_at <='] = $filters['until_created_at'];
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
			'member_digital_transactions.transaction_type',
			'member_digitals.phone_number',
			'member_digitals.wablas_phone_number_receiver',
			'member_digital_transactions.order_id',
			'member_digital_transactions.store_name',
			'member_digital_transactions.source_name',
			'member_digital_transactions.payment_amount',
			'member_digital_transactions.amount',
			'member_digital_transactions.transfer_bank_name',
			'member_digital_transactions.transfer_bank_account_number',
			'member_digital_transactions.transfer_bank_account_name',
			'member_digital_transactions.referred_by_member_digital_transaction_id',
			'referred_member_digital_transactions.payment_amount as referred_member_digital_transactions_payment_amount',
			'referred_member_digital_transactions.id_member_digital as referred_member_digital_transactions_id_member_digital',
			'referred_member_digitals.name as referred_member_digital_name',
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
		$products = $this->repository->findPaginated('member_digital_transactions', $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue, null, $join);
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
		$existsMember = $this->repository->findOne('member_digitals', ['id_auth_api' => $this->auth['id_auth']], $argsOrWhere);
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
		$newAction = $this->repository->update('member_digitals', $newPayload, ['id' => $existsMember->id]);

		$transactionResult = $this->repository->findOne('member_digital_transactions', ['id' => $action]);
		$marketingHandler = new MemberDigitalMarketingHandler($this->repository);
		$marketingResult = $marketingHandler->handleTransaction($transactionResult);
		$transactionResult->marketing = $marketingResult->data;
		if (!empty($existsMember->wablas_phone_number_receiver)) {
			// $wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $existsMember->wablas_phone_number_receiver]);
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => ($existsMember->wablas_phone_number_receiver == '62895383334783' ? self::MAIN_WABLAS : $existsMember->wablas_phone_number_receiver)]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				$this->delivery->addError(400, 'Wablas Config is required');
				return $this->delivery;
			}
			$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
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

	public function getMemberDigitalVouchers ($filters = null) {
		$join = [
			'member_digitals' => 'member_digitals.id = member_digital_vouchers.id_member_digital',
		];

		$args = [
			'member_digital_vouchers.id_auth_api' => $this->auth['id_auth']
		];

		if (isset($filters['id_member_digital']) && !empty($filters['id_member_digital'])) {
			$args['member_digital_vouchers.id_member_digital'] = $filters['id_member_digital'];
		}

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['member_digitals.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['member_code']) && !empty($filters['member_code'])) {
			$args['member_digitals.member_code'] = [
				'condition' => 'like',
				'value' => $filters['member_code']
			];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_digitals.name'] = [
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
			'member_digital_vouchers.id',
			'member_digital_vouchers.voucher_code',
			'member_digital_vouchers.discount_amount',
			'member_digital_vouchers.id_member_digital',
			'member_digitals.name',
			'member_digitals.phone_number',
			'member_digitals.member_code',
			'member_digital_vouchers.created_at',
			'member_digital_vouchers.updated_at'
		];
		$orderKey = 'member_digital_vouchers.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$products = $this->repository->findPaginated('member_digital_vouchers', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function notifyFinish () {
		$result = [];
		$memberDigitals = $this->repository->find('member_digitals', ['member_code' => null, 'notify_finish <=' => 3]);
		foreach ($memberDigitals as $memberDigital) {
			// $wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $memberDigital->wablas_phone_number_receiver]);
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => ($memberDigital->wablas_phone_number_receiver == '62895383334783' ? self::MAIN_WABLAS : $memberDigital->wablas_phone_number_receiver)]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				continue;
				// $this->delivery->addError(400, 'Wablas Config is required');
				// return $this->delivery;
			}

			$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
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
			$action = $this->repository->update('member_digitals', $data, ['id' => $memberDigital->id]);
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
			'member_digitals' => 'member_digitals.id = member_digital_transactions.id_member_digital'
		];

		$select = [
			'member_digital_transactions.id',
			'member_digital_transactions.id_member_digital',
			'member_digitals.name as member_name',
			'member_digitals.phone_number',
			'member_digitals.wablas_phone_number_receiver',
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
				// $wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $transaction->wablas_phone_number_receiver]);
				$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => ($transaction->wablas_phone_number_receiver == '62895383334783' ? self::MAIN_WABLAS : $transaction->wablas_phone_number_receiver)]);
				if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
					$this->delivery->addError(400, 'Wablas Config is required');
					return $this->delivery;
				}
				$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
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
		$members = $this->repository->find('member_digitals', $filters);

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

	public function validateKyc ($payload) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$phoneNumberUtil = PhoneNumberUtil::getInstance();
			$phoneNumber = $phoneNumberUtil->parse($payload['phone_number'], "ID");
		    $payload['phone_number'] = '62'.$phoneNumber->getNationalNumber();
		} catch (\libphonenumber\NumberParseException $e) {
		    $this->delivery->addError(500, $e->getMessage());
		    return $this->delivery;
		}

		$existsMemberDigital = $this->getMemberDigital(['phone_number' => $payload['phone_number']])->data;
		if (empty($existsMemberDigital)) {
			$this->delivery->addError(400, 'Member digital is required');
		}

		if (!isset($payload['ktp_id_number']) || empty($payload['ktp_id_number'])) {
			$this->delivery->addError(400, 'KTP ID number is required');
		}

		if (!isset($_FILES['ktp_photo']) || empty($_FILES['ktp_photo']['tmp_name'])) {
			$this->delivery->addError(400, 'KTP Photo is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$this->delivery->data = 'ok';
		return $this->delivery;
	}

	public function handleKyc ($payload) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$phoneNumberUtil = PhoneNumberUtil::getInstance();
			$phoneNumber = $phoneNumberUtil->parse($payload['phone_number'], "ID");
		    $payload['phone_number'] = '62'.$phoneNumber->getNationalNumber();
		} catch (\libphonenumber\NumberParseException $e) {
		    $this->delivery->addError(500, $e->getMessage());
		    return $this->delivery;
		}
		$validResult = $this->validateKyc($payload);
		if ($validResult->hasErrors()) {
			return $validResult;
		}

		if (!isset($payload['otp']) || empty($payload['otp'])) {
			$this->delivery->addError(400, 'OTP is required');
			return $this->delivery;
		}

		$existsMemberDigital = $this->getMemberDigital(['phone_number' => $payload['phone_number']])->data;

		// validate OTP
		$argsOtp = [
			'member_digital_id' => $existsMemberDigital->id,
			'used_at' => null,
			'expired_at >=' => date('Y-m-d H:i:s')
		];
		$lastOtpAlive = $this->repository->findOne('member_digital_otps', $argsOtp);
		if (empty($lastOtpAlive)) {
			$this->delivery->addError(400, 'No OTP found. Please request a new OTP.');
			return $this->delivery;
		}

		if ($lastOtpAlive->otp != $payload['otp']) {
			$this->delivery->addError(400, 'OTP is incorrect.');
			return $this->delivery;
		}

		$actionOtp = $this->repository->update('member_digital_otps', ['used_at' => date('Y-m-d H:i:s')], ['id' => $lastOtpAlive->id]);

		try {
			$memberData = [];
			$memberData['ktp_id_number'] = $payload['ktp_id_number'];
			$digitalOceanService = new DigitalOceanService();
			$uploadResult = $digitalOceanService->upload($payload, 'ktp_photo');
			$ktpPhoto = $uploadResult['cdn_url'];
			$memberData['ktp_photo_url'] = $ktpPhoto;

			if (isset($_FILES['npwp_photo']) && !empty($_FILES['npwp_photo']['tmp_name'])) {
				$uploadResult = $digitalOceanService->upload($payload, 'npwp_photo');
				$npwpUrl = $uploadResult['cdn_url'];
				$memberData['npwp_photo_url'] = $npwpUrl;
			}
			$action = $this->updateMemberDigitals($memberData, ['id' => $existsMemberDigital->id]);
			return $this->getMemberDigital(['id' => $existsMemberDigital->id]);
		} catch (\Exception $e) {
			$this->delivery->data = $e->getMessage();
		}

		return $this->delivery;

	}

	public function handleSendOTP ($payload) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$phoneNumberUtil = PhoneNumberUtil::getInstance();
			$phoneNumber = $phoneNumberUtil->parse($payload['phone_number'], "ID");
		    $payload['phone_number'] = '62'.$phoneNumber->getNationalNumber();
		} catch (\libphonenumber\NumberParseException $e) {
		    $this->delivery->addError(500, $e->getMessage());
		    return $this->delivery;
		}
		$existsMemberDigital = $this->getMemberDigital(['phone_number' => $payload['phone_number']])->data;
		
		// validate OTP
		$argsOtp = [
			'member_digital_id' => $existsMemberDigital->id,
			'used_at' => null,
			'expired_at >=' => date('Y-m-d H:i:s')
		];
		$lastOtpAlive = $this->repository->findOne('member_digital_otps', $argsOtp);
		if (!empty($lastOtpAlive)) {
			$this->delivery->addError(400, 'OTP has been sent. Try again in 5 minutes.');
			return $this->delivery;
		}

		$otp = generateRandomDigit(6);
        $message = sprintf('Kode OTP: %s', $otp);
        $currentDate = date('Y-m-d H:i:s');
        $futureDate = strtotime($currentDate) + (60 * 5);
        $expiredAt = date('Y-m-d H:i:s', $futureDate);

		$dataOtp = [
			'member_digital_id' => $existsMemberDigital->id,
			'otp' => $otp,
			'used_at' => null,
			'expired_at' => $expiredAt,
			'created_at' => $currentDate,
			'updated_at' => $currentDate
		];
		$otpAction = $this->repository->insert('member_digital_otps', $dataOtp);
		$message = sprintf('Kode OTP anda: %s', $otp);
		$this->sendWablasToMember($existsMemberDigital, $message);
		$this->delivery->data = 'ok';
		return $this->delivery;
	}

	public function createMemberCard ($member) {
		if (!empty($member->member_card_url)) {
			$old = 'https://cdn.1itmedia.co.id/';
			$new = 'https://file.1itmedia.co.id/';
			$config = 'old';
			if (strpos($member->member_card_url, $new) !== false) {
			    $oldFilename = str_replace($new, '', $member->member_card_url);
				$action = delete_from_cloud($oldFilename, 'new');
			} else {
			    $oldFilename = str_replace($old, '', $member->member_card_url);
				$action = delete_from_cloud($oldFilename, 'old');
			}

		}

		$filename = sprintf('%s%s%s.png', $member->member_code, time(), generateRandomString(5));
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

	public function createDiscountCard ($member, $voucherCode) {

		$filename = sprintf('%s%s%s.jpg', generateRandomString(10), time(), generateRandomString(5));
		$formattedPath = sprintf('%s/%s', $this->uploadPath, $filename);
		$image = new Image([
			// 'binary' => 'C:\Program Files\wkhtmltopdf\bin\wkhtmltoimage.exe',
    		'commandOptions' => ['useExec' => true],
    		'ignoreWarnings' => true
		]);		
		$image->setPage($this->generateHTMLDiscountCard($member, $voucherCode));
		$image->saveAs($formattedPath);

		/* $im = imagecreatefrompng($formattedPath);
		  
		// find the size of image
		$size = min(imagesx($im), imagesy($im));
		  
		// Set the crop image size 
		$im2 = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => 1080, 'height' => 1080]);
		if ($im2 !== FALSE) {
			unlink($formattedPath);
	      	imagepng($im2, $formattedPath);
		    imagedestroy($im2);
		}
		imagedestroy($im); */

		$uploadToDigitalOcean = upload_to_cloud($formattedPath, $filename);
		unlink($formattedPath);
		return $uploadToDigitalOcean;
	}

	public function sendWablasToMember ($member, $message, $additionalMessage = null) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}
		$result = null;
		if (!empty($member->wablas_phone_number_receiver)) {
			// $wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $member->wablas_phone_number_receiver]);
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => ($member->wablas_phone_number_receiver == '62895383334783' ? self::MAIN_WABLAS : $member->wablas_phone_number_receiver)]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				$this->delivery->addError(400, 'Config error');
				return $this->delivery;
			}
			$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
			$sendWa = $this->waService->publishMessage('send_message', $member->phone_number, $message);
			$result[] = $sendWa;

			if (!empty($additionalMessage)) {
				foreach ($additionalMessage as $key => $additional) {
					if ($key == 'voucher_rabbani') {
						$barcode = str_pad(time(), 12, '0', STR_PAD_LEFT);
						$sendImage = $this->waService->publishMessage('send_voucher_rabbani', $member->phone_number, null, null, null, null, null, $additional['amount'], $additional['code']);
						$result[] = $sendImage;
					}
				}


			}
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

	private function createMemberCode ($id) {
		return sprintf('%s%s', date('YmdH'), str_pad($id, 4, '0', STR_PAD_LEFT));
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

	private function generateHTMLDiscountCard ($member, $voucherCode) {
		$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
		$bgPath = $this->uploadPath.'/bg_card.png';
		//background-image: url("https://cdn.1itmedia.co.id/3d2ac206631aace116bde27f9e1190a6.jpeg");
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
					    	width: 1080px;
			        		height: 1080px;
							background-image: url("https://file.1itmedia.co.id/rabbani/05-2023.jpg");
						 	background-repeat: no-repeat;
						}
	  
				        .voucher-code {
							position: absolute;
							top: 940px;
							left: 520px;
							height: 80px;
							width: 460px;
							font-size: 36px;
							color: black;
							text-align: center;
							vertical-align: middle;
							line-height: 80px;
				        }
					</style>
				</head>
				<body>
					<div class="container" id="print-area">
						<div class="voucher-code">
							<b>'.$voucherCode.'</b>
						</div>
					</div>
				</body>
			</html>
		';

		return $html;
	}

	private function sendScheduledWablasToMember ($member, $message, $date, $time) {
		$result = null;
		if (!empty($member->wablas_phone_number_receiver)) {
			// $wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $member->wablas_phone_number_receiver]);
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => ($member->wablas_phone_number_receiver == '62895383334783' ? self::MAIN_WABLAS : $member->wablas_phone_number_receiver)]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				return $result;
			}
			$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
			// $sendWa = $this->waService->createScheduledMessage($member->phone_number, $message, $date, $time);
			$sendWa = $this->waService->publishMessage('scheduled_message', $member->phone_number, $message, null, $date, $time);
			return $sendWa;
		}
		return $result;
	}

	private function sendBirthdayCardToMember ($member, $message = '') {
		$producer = new MemberDigitalBirthdayCardProducer;
		$result = $producer->sendBirthdayCard($member, $message);
		return $result;
	}

	private function sendBulkWablasToMember ($members, $message, $dictionary) {
		$wablasTargets = [];
		foreach ($members as $mem) {
			$formattedMessage = strtr($message, $dictionary);
			$wablasTargets[] = [
				'phone' => $mem->phone_number,
				'message' => $formattedMessage
			];
		}

		$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => self::MAIN_WABLAS]);
		if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
			return $result;
		}
		$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
		// $sendWa = $this->waService->sendBulk($wablasTargets);
		$sendWa = $this->waService->publishMessage('send_bulk', null, null, null, null, null, null, null, null, json_encode($wablasTargets));
		return $sendWa;
	}

	public function generateAndPublishBirthdayCardToMember ($member, $message = '') {
		$filename = sprintf('%s%s%s.png', 'hbdcard', time(), generateRandomString(5));
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
			// $wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $member->wablas_phone_number_receiver]);
			$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => ($member->wablas_phone_number_receiver == '62895383334783' ? self::MAIN_WABLAS : $member->wablas_phone_number_receiver)]);
			if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
				return $result;
			}
			$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
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

	public function generateVoucherRabbani ($amount = 0, $barcode) {
		$filename = sprintf('%s%s%s.png', 'voucher_rabbani', time(), generateRandomString(5));
		$formattedPath = sprintf('%s/%s', $this->uploadPath, $filename);
		$image = new Image([
			// 'binary' => 'C:\Program Files\wkhtmltopdf\bin\wkhtmltoimage.exe',
    		'commandOptions' => ['useExec' => true],
    		'ignoreWarnings' => true
		]);
		$image->setPage($this->generateHTMLVoucherRabbani($amount, $barcode));
		$image->saveAs($formattedPath);

		$im = imagecreatefrompng($formattedPath);
		// find the size of image
		$size = min(imagesx($im), imagesy($im));
		  
		// Set the crop image size 
		$im2 = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => 680, 'height' => 340]);
		if ($im2 !== FALSE) {
			unlink($formattedPath);
	      	imagepng($im2, $formattedPath);
		    imagedestroy($im2);
		}
		imagedestroy($im);
		$uploadToDigitalOcean = upload_to_cloud($formattedPath, $filename);
		unlink($formattedPath);

		$result = null;

		return $uploadToDigitalOcean;
	}

	private function generateHTMLVoucherRabbani ($amount, $barcode) {
		$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
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
				    	width: 680px;
		        		height: 340px;
						background-image: url("https://cdn.1itmedia.co.id/fc97f0c9b9707154867d81da3bbda556.png");
					 	background-repeat: no-repeat;
					}

          			.amount {
						position: absolute;
						top: 250px;
						left: 20px;
						width: 250px;
						height: 60px;
						text-align: left;
						font-size: 40px;
						display: table;
					}

          			.amount > span {
						display: table-cell;
						vertical-align: middle;
					}

			        .barcode-image {
						position: absolute;
						top: 230px;
						left: 340px;
						width: 320px;
						height: 80px;
						text-align: center;
					}

					img {
				    	text-align: center;
				    }

					.barcode-text {
						position: absolute;
						top: 310px;
						left: 340px;
						width: 320px;
						height: 20px;
						text-align: center;
					}
				</style>
			</head>
			<body>
				<div class="container" id="print-area">
					<div class="amount">
						<span>'.toRupiahFormat($amount).'</span>
					</div>
		          	<div class="barcode-image">
		          	'.'<img src="data:image/png;base64,' . base64_encode($generator->getBarcode($barcode, $generator::TYPE_CODE_128, 1, 80)) . '">'.'
		            </div>
		          	<div class="barcode-text">
		          	'.$barcode.'
		          	</div>
				</div>
			</body>
		</html>
		';

		return $html;
	}
	//$member->name , $voucherCode
	private function generateDiscountCardCaption ($member, $voucherCode) {
		$text = '*Alhamdulillah... Selamat ya kak!*

Sudah dapet voucher belanja *20% All Item* dari Rabbani. Ini kode vouchernya Kak '.$member->name.'

*Kode Penukaran: '.$voucherCode.'* 

Cara penukarannya gampang! Kak '.$member->name.' tinggal tunjukan aja voucher digitalnya dikasir toko Rabbani.

*PROMO INI BERLAKU S.D 31 MEI 2023.*
_Bulan syawal selalu dinanti,_
_Puasa sunnahnya banyak keberkahan_
_Ayo dong kita ke Rabbani.._
_Jangan lupa ajak keluarga, saudara, dan teman2_

*Info lebih lanjut hubungi:*
📞 0811248838';
		return $text;
	}

}