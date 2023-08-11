<?php
namespace Service\MemberLaundry;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;
use Library\WablasService;
use Library\TripayGateway;

class MemberLaundryHandler {

	const APP_ENV = 'prod';
	const MAIN_WABLAS = '6289674545000';
	const STATUS_LIST_PICKUP = 'list_pickup'; // orderan yang bisa dipickup agent
	const STATUS_AGENT_IN_PROCESS_CHECKOUT = 'agent_in_process_checkout'; // orderan sedang diproses oleh kurir/agent
	const STATUS_CHECKOUT_DONE = 'checkout_done'; // orderan sudah dipickup kurir/agent dan sedang menuju gudang
	const STATUS_IN_WAREHOUSE = 'in_warehouse'; // semua satu tag code sudah masuk di warehouse
	const STATUS_WASHING_FINISHED = 'washing_finished'; // semua tag code selesai dicuci
	const STATUS_IN_DELIVERY = 'in_delivery'; // pengiriman kembali ke user
	const STATUS_COMPLETED = 'completed';
	const STATUS_CANCELED = 'canceled';

	const STATUS_RUNNING = [
		self::STATUS_LIST_PICKUP,
		self::STATUS_AGENT_IN_PROCESS_CHECKOUT,
		self::STATUS_CHECKOUT_DONE,
		self::STATUS_IN_WAREHOUSE,
		self::STATUS_WASHING_FINISHED,
		self::STATUS_IN_DELIVERY
	];

	const PAYMENT_METHOD_CODE_COD = 'cod';
	const PAYMENT_METHOD_NAME_COD = 'COD';
	const PAYMENT_METHOD_TRIPAY = 'tripay';

	const WABLAS_MENU_REQUEST_BOOKING_DESTINATION_COORDINATE = 'request_booking_destination_coordinate';
	const WABLAS_MENU_REQUEST_BOOKING_PAYMENT_METHOD = 'request_booking_payment_method';

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;

	private $welcomeText;
	private $generalMenuText;
	private $orderStillRunningText;
	private $requestBookingDestinationCoordinateText;

	private $tripayCallbackUrl;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->uploadPath = getenv('UPLOAD_PATH') ? getenv('UPLOAD_PATH') . '/images' : "upload/images";
		$this->welcomeText = 'Selamat datang di Layanan Whatsapp Laundry. Silahkan isi nama lengkap:';
		$this->generalMenuText = '1. Profil'.PHP_EOL.'2. Order/Booking'.PHP_EOL.'3. Transaksi'.PHP_EOL.'4. Batalkan pickup'.PHP_EOL.PHP_EOL.'Jika memiliki kesulitan silahkan hubungi admin'.PHP_EOL.'https://wa.me/62818555082';
		$this->orderStillRunningText = 'Anda tidak bisa melakukan order lagi dikarenakan ada orderan yang sedang berjalan.';
		$this->requestBookingDestinationCoordinateText = 'Silahkan kirim lokasi pengiriman melalui tombol Whatsapp Kirim Lokasi';

		$this->tripayCallbackUrl = 'https://api.1itmedia.co.id/callbacks/tripay/laundry';
	}

	/**
	 * Minta nama, tanggal lahir, jenis kelamin, alamat, provinsi, kabupaten, kota. Setelah itu generate image dan member id
	 * 
	 * 
	 **/
	public function callbackAction ($payload) {
		$result = '';

		if (empty(trim($payload['message']))) {
			$this->delivery->addError(409, 'Silahkan masukkan pesan');
			return $this->delivery;
		}

		$authApi = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $payload['receiver']]);
		if (empty($authApi)) {
			$this->delivery->addError(409, 'Config error!');
			return $this->delivery;
		}

		$existsMember = $this->repository->findOne('member_laundries', ['phone_number' => $payload['phone'], 'id_auth_api' => $authApi->id_auth_api]);
		if (empty($existsMember)) {
			$newMember = [
				'id_auth_api' => $authApi->id_auth_api,
				'phone_number' => $payload['phone'],
				'created_at' => date('Y-m-d H:i:s'),
				'wablas_phone_number_receiver' => $payload['receiver']
			];
			$action = $this->repository->insert('member_laundries', $newMember);
			$newMember['id'] = $action;
			$existsMember = $this->repository->findOne('member_laundries', ['id' => $newMember['id']]);
			$this->delivery->addError(409, $this->welcomeText);
			return $this->delivery;
		}
		if (self::APP_ENV != 'dev') {
			if ($existsMember->last_wablas_id == $payload['id']) {
				die();
			} else {
				$action = $this->repository->update('member_laundries', ['last_wablas_id' => $payload['id']], ['id' => $existsMember->id]);
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
			$action = $this->repository->update('member_laundries', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi tanggal lahir anda: (cth: 01-05-1970)');
			return $this->delivery;
		}

		if (empty($existsMember->birthday)) {
			if (!preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/", $payload['message'])) {
			    $this->delivery->addError(409, 'Silahkan isi tanggal lahir anda: (cth: 01-05-1970)');
			    return $this->delivery;
			}
			$formattedDate = date('Y-m-d', strtotime($payload['message']));
			if (age($formattedDate) <= 21) {
				$this->delivery->addError(400, 'Anda masih dibawah umur yang disarankan. Silahkan isi tanggal lahir anda: (cth: 01-05-1970)');
				return $this->delivery;
			}
			$data = [
				'birthday' => $formattedDate,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundries', $data, ['id' => $existsMember->id]);
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
			$action = $this->repository->update('member_laundries', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi provinsi anda:');
			return $this->delivery;
		}

		if (empty($existsMember->provinsi)) {
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
				'provinsi' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundries', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi kota/kabupaten anda:');
			return $this->delivery;
		}

		if (empty($existsMember->kabupaten)) {
			// untuk isi `kabupaten` cari di table `districts`
			$argsKabupaten['nama'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findProvince = $this->repository->findOne('provinces', ['name' => $existsMember->provinsi]);
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
						break;
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
				'kabupaten' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundries', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi kecamatan anda:');
			return $this->delivery;
		}

		if (empty($existsMember->kecamatan)) {
			// untuk isi `kecamatan` cari di table `sub_district`
			$argsKecamatan['nama'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findKabupaten = $this->repository->findOne('districts', ['nama' => $existsMember->kabupaten]);
			$argsKecamatan['id_kab'] = $findKabupaten->id_kab;
			$findKecamatan = $this->repository->find('sub_district', $argsKecamatan);
			if (count($findKecamatan) > 1) {
				$isExact = false;
				$message = 'Kami menemukan '.count($findKecamatan).' kecamatan dengan nama tersebut. Silahkan ketik salah satu kecamatan dengan lengkap.';
				foreach ($findKecamatan as $kecamatan) {
					$message .= PHP_EOL.'- '.$kecamatan->nama;
					if (strtolower($payload['message']) == strtolower($kecamatan->nama)) {
						$isExact = true;
						$findKecamatan[0] = $kecamatan;
						break;
					}
				}
				if (!$isExact) {
					$this->delivery->addError(400, $message);
					return $this->delivery;
				}
			} else if (empty($findKecamatan)) {
				$allKecamatan = $this->repository->find('sub_district', ['id_kab' => $findKabupaten->id_kab]);
				$kecamatanMessage = 'Silahkan pilih dan ketik dengan lengkap salah satu kecamatan berikut:';
				foreach ($allKecamatan as $kecamatan) {
					$kecamatanMessage .= PHP_EOL.'- '.$kecamatan->nama;
				}
				$this->delivery->addError(400, $kecamatanMessage);
				return $this->delivery;
			}

			$message = $findKecamatan[0]->nama;
			$data = [
				'kecamatan' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundries', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi desa/kelurahan anda:');
			return $this->delivery;
		}

		if (empty($existsMember->kelurahan)) {
			// untuk isi `kelurahan` cari di table `urban_village`
			$argsKelurahan['nama'] = [
				'condition' => 'like',
				'value' => $payload['message']
			];
			$findKecamatan = $this->repository->findOne('sub_district', ['nama' => $existsMember->kecamatan]);
			$argsKelurahan['id_kec'] = $findKecamatan->id_kec;
			$findKelurahan = $this->repository->find('urban_village', $argsKelurahan);
			if (count($findKelurahan) > 1) {
				$isExact = false;
				$message = 'Kami menemukan '.count($findKelurahan).' desa/kelurahan dengan nama tersebut. Silahkan ketik salah satu desa/kelurahan dengan lengkap.';
				foreach ($findKelurahan as $kelurahan) {
					$message .= PHP_EOL.'- '.$kelurahan->nama;
					if (strtolower($payload['message']) == strtolower($kelurahan->nama)) {
						$isExact = true;
						$findKelurahan[0] = $kelurahan;
						break;
					}
				}
				if (!$isExact) {
					$this->delivery->addError(400, $message);
					return $this->delivery;
				}
			} else if (empty($findKelurahan)) {
				$allKelurahan = $this->repository->find('urban_village', ['id_kec' => $findKecamatan->id_kec]);
				$kelurahanMessage = 'Silahkan pilih dan ketik dengan lengkap salah satu desa/kelurahan berikut:';
				foreach ($allKelurahan as $kelurahan) {
					$kelurahanMessage .= PHP_EOL.'- '.$kelurahan->nama;
				}
				$this->delivery->addError(400, $kelurahanMessage);
				return $this->delivery;
			}

			$message = $findKelurahan[0]->nama;
			$data = [
				'kelurahan' => $message,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundries', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, 'Silahkan isi alamat lengkap anda:');
			return $this->delivery;
		}

		if (empty($existsMember->address)) {
			$data = [
				'address' => $payload['message'],
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundries', $data, ['id' => $existsMember->id]);
			$this->delivery->addError(409, $this->generalMenuText);
			return $this->delivery;
		}

		if ($existsMember->wablas_menu_state == self::WABLAS_MENU_REQUEST_BOOKING_DESTINATION_COORDINATE) {
			$this->handleCallbackMenuOrder($existsMember, $payload['message']);
			return $this->delivery;
		} else if ($existsMember->wablas_menu_state == self::WABLAS_MENU_REQUEST_BOOKING_PAYMENT_METHOD) {
			$this->handleRequestBookingPaymentMethod($existsMember, $payload['message']);
			return $this->delivery;
		}

		$message = strtolower($payload['message']);
		if (!in_array($message, ['1', '2', '3', '4'])) {
			$this->delivery->addError(400, $this->generalMenuText);
			return $this->delivery;
		} else {
			if ($message == '1') {
				$this->getMe($existsMember);
				return $this->delivery;
			} else if ($message == '2') {
				$this->requestBookingDestinationCoordinate($existsMember);
				return $this->delivery;
			} else if ($message == '3') {
				$this->handleCallbackMenuHistory($existsMember);
				return $this->delivery;
			} else if ($message == '4') {
				$this->handleCancelRunningOrder($existsMember);
				return $this->delivery;
			}
		}

	}

	private function getMe ($member) {
		$genderText = [
			'male' => 'Laki-laki',
			'female' => 'Perempuan'
		];
		$meText = 'Nama: '.$member->name.PHP_EOL.'Tanggal Lahir: '.$member->birthday.PHP_EOL.'Umur: '.age($member->birthday).PHP_EOL.'Jenis Kelamin: '.$genderText[$member->gender].PHP_EOL.'Provinsi: '.$member->provinsi.PHP_EOL.'Kabupaten: '.$member->kabupaten.PHP_EOL.'Kecamatan: '.$member->kecamatan.PHP_EOL.'Kelurahan: '.$member->kelurahan.PHP_EOL.'Alamat: '.$member->address.PHP_EOL.PHP_EOL.$this->generalMenuText;
		$this->delivery->data = $meText;
	}

	private function requestBookingDestinationCoordinate ($member) {
		$argsRunning = [
			'member_laundry_id' => $member->id,
			sprintf('status <> "%s"', self::STATUS_COMPLETED),
			sprintf('status <> "%s"', self::STATUS_CANCELED),
		];
		$runningOrder = $this->repository->findOne('member_laundry_bookings', $argsRunning);
		if (!empty($runningOrder)) {
			$orderDesc = PHP_EOL.'Kode Booking: '.$runningOrder->booking_code.PHP_EOL.'Status: '.$runningOrder->status.PHP_EOL.PHP_EOL.$this->generalMenuText;
			$this->delivery->addError(400, $this->orderStillRunningText.$orderDesc);
			return false;
		}
		$memberData = [
			'wablas_menu_state' => self::WABLAS_MENU_REQUEST_BOOKING_DESTINATION_COORDINATE,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->update('member_laundries', $memberData, ['id' => $member->id]);
		$this->delivery->data = $this->requestBookingDestinationCoordinateText;
	}

	private function handleCallbackMenuOrder ($member, $message) {
		$argsRunning = [
			'member_laundry_id' => $member->id,
			sprintf('status <> "%s"', self::STATUS_COMPLETED),
			sprintf('status <> "%s"', self::STATUS_CANCELED),
		];
		$runningOrder = $this->repository->findOne('member_laundry_bookings', $argsRunning);
		if (!empty($runningOrder)) {
			$orderDesc = PHP_EOL.'Kode Booking: '.$runningOrder->booking_code.PHP_EOL.'Status: '.$runningOrder->status.PHP_EOL.PHP_EOL.$this->generalMenuText;
			$this->delivery->addError(400, $this->orderStillRunningText.$orderDesc);
			return false;
		}

		$coordinate = str_replace("##", "", $message);
		$lat = null;
		$long = null;
		try {
			$explodeCoordinate = explode("#", $coordinate);
			if (count($explodeCoordinate) < 2) {
				throw new \Exception('Invalid coordinate');
			}
			$lat = $explodeCoordinate[0];
			$long = $explodeCoordinate[1];
			$valid = validateLatLong($lat, $long);
			if (!$valid) {
				throw new \Exception('Invalid coordinate');
			}
		} catch (\Exception $e) {
			$lat = null;
			$long = null;
		}

		if (empty($lat) || empty($long)) {
			$this->delivery->data = $this->requestBookingDestinationCoordinateText;
			return false;
		}

		$destinationCoordinate = $lat.','.$long;
		$searchingOrderBooking = true;
		$bookingCode = null;

		while ($searchingOrderBooking) {
			$bookingCode = generateRandomString(4, 'alphanumeric_uppercase');
			$args = [
				'id_auth_api' => $member->id_auth_api,
				'booking_code' => $bookingCode
			];
			$existsBookingCode = $this->repository->findOne('member_laundry_bookings', $args);
			if (empty($existsBookingCode)) {
				$searchingOrderBooking = false;
			}
		}
		$data = [
			'id_auth_api' => $member->id_auth_api,
			'member_laundry_id' => $member->id,
			'booking_code' => $bookingCode,
			'destination_coordinate' => $destinationCoordinate,
			'status' => self::STATUS_LIST_PICKUP,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date("Y-m-d H:i:s")
		];
		$action = $this->repository->insert('member_laundry_bookings', $data);

		$memberData = [
			'wablas_menu_state' => null,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->update('member_laundries', $memberData, ['id' => $member->id]);
		$text = 'Berikut kode booking pickup cucian anda '.$bookingCode.'. Silahkan informasikan kode tersebut pada kurir.'.PHP_EOL.PHP_EOL.$this->generalMenuText;
		$this->delivery->data = $text;
	}

	private function handleCallbackMenuHistory ($member) {
		// $bookings = $this->repository->find('member_laundry_bookings', ['member_laundry_id' => $member->id]);
		$bookings = $this->repository->findPaginated('member_laundry_bookings', ['member_laundry_id' => $member->id], null, null, null, 0, 5, 'member_laundry_bookings.created_at', 'DESC');
		$message = '';
		if (empty($bookings['result'])) {
			$message = 'Anda belum memiliki transaksi'.PHP_EOL;
		} else {
			$message = '*List Transaksi*'.PHP_EOL;
			foreach ($bookings['result'] as $booking) {
				$message .= 'Kode Booking: '.$booking->booking_code.PHP_EOL.'Waktu Pemesanan: '.date('d M Y', strtotime($booking->created_at)).PHP_EOL.'Status: '.$booking->status.PHP_EOL;
			}
		}
		$this->delivery->data = $message.PHP_EOL.$this->generalMenuText;

	}

	private function handleRequestBookingPaymentMethod ($member, $message) {
		$argsRunning = [
			'member_laundry_id' => $member->id,
			sprintf('status <> "%s"', self::STATUS_COMPLETED),
			sprintf('status <> "%s"', self::STATUS_CANCELED),
		];
		$runningOrder = $this->repository->findOne('member_laundry_bookings', $argsRunning);
		if (!empty($runningOrder)) {
			$carts = $this->repository->find('member_laundry_carts', ['member_laundry_booking_id' => $runningOrder->id]);
			$runningOrder->member_laundry_carts = $carts;
		}
		$handler = new MemberLaundryAgentHandler($this->repository);
		$paymentResult = $handler->generateTripayPaymentMethod();
		$paymentChannels = $paymentResult['data'];
		if (!isset($paymentChannels[(int)$message])) {
			$messageText = 'Kode Booking: '.$runningOrder->booking_code.PHP_EOL.'Nominal Pembayaran: '.toRupiahFormat($runningOrder->total_price).PHP_EOL;
			$this->delivery->data = $messageText.PHP_EOL.'Silahkan pilih metode pembayaran online yang tersedia:'.PHP_EOL.$paymentResult['message'];
		} else {
			$paymentData = $paymentResult['tripay'];
			$channel = $paymentData[(int)$message];
			$tripay = new TripayGateway();
			$tripay->setEnv('development');
			$orderItems = [];
			foreach ($runningOrder->member_laundry_carts as $cart) {
				$orderItems[] = [
					'sku' => $cart->tag_code,
					'name' => $cart->tag_code,
					'price' => $cart->final_price,
					'quantity' => 1
				];
			}
			$action = $tripay->requestTransaksi($channel->code, $runningOrder->booking_code, $runningOrder->total_price, $member->name, 'no-reply@1itmedia.co.id', $member->phone_number, $orderItems, null, $this->tripayCallbackUrl);
			$invoiceUrl = $action->data->checkout_url;
			$bookingData = [
				'payment_method_code' => $channel->code,
				'payment_method_name' => $channel->name,
				'payment_fees' => $channel->total_fee->flat,
				'payment_amount' => $runningOrder->total_price + $channel->total_fee->flat,
				'updated_at' => date('Y-m-d H:i:s'),
				'invoice_url' => $invoiceUrl,
			];
			$action = $this->repository->update('member_laundry_bookings', $bookingData, ['id' => $runningOrder->id]);
			$messageText = 'Silahkan klik link dibawah ini untuk instruksi pembayaran Kode Booking: '.$runningOrder->booking_code.PHP_EOL.$invoiceUrl.PHP_EOL.PHP_EOL.$this->generalMenuText;

			$memberData = [
				'wablas_menu_state' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundries', $memberData, ['id' => $member->id]);
			$this->delivery->data = $messageText;

		}
	}

	private function handleCancelRunningOrder ($member) {
		$runningOrder = $this->getRunningOrderOnThisMember($member);
		if (empty($runningOrder)) {
			$this->delivery->data = 'Anda belum memiliki orderan saat ini.'.PHP_EOL.PHP_EOL.$this->generalMenuText;
		} else {
			if ($runningOrder->status == self::STATUS_LIST_PICKUP) {
				$newData = [
					'status' => self::STATUS_CANCELED,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_laundry_bookings', $newData, ['id' => $runningOrder->id]);
				$this->delivery->data = 'Kode booking '.$runningOrder->booking_code.' berhasil dibatalkan'.PHP_EOL.PHP_EOL.$this->generalMenuText;
			} else {
				$this->delivery->data = 'Anda tidak bisa membatalkan orderan dengan kode booking '.$runningOrder->booking_code.' dikarenakan sedang diproses.'.PHP_EOL.'Status: '.$runningOrder->status.PHP_EOL.PHP_EOL.$this->generalMenuText;
			}
		}
	}

	private function getRunningOrderOnThisMember ($member) {
		$argsRunning = [
			'member_laundry_id' => $member->id,
			sprintf('status <> "%s"', self::STATUS_COMPLETED),
			sprintf('status <> "%s"', self::STATUS_CANCELED),
		];
		$runningOrder = $this->repository->findOne('member_laundry_bookings', $argsRunning);
		return $runningOrder;
	}

	public function sendWablasToMember ($member, $message) {
		$wablasNya = (!empty($member->wablas_phone_number_receiver) ? $member->wablas_phone_number_receiver : self::MAIN_WABLAS);
		$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $wablasNya]);
		if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
			return $result;
		}
		$waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
		$sendWa = $waService->publishMessage ('send_message', $member->phone_number, $message);
		return $sendWa;
	}

	public function onTripayCallback ($payload) {
		$booking = $this->repository->findOne('member_laundry_bookings', ['booking_code' => $payload->merchant_ref]);
		if (empty($booking)) {
			$this->delivery->addError(400, 'Booking is required');
			return $this->delivery;
		}

		$bookingData = [
			'payment_method_code' => $payload->payment_method_code,
			'payment_method_name' => $payload->payment_method,
			'updated_at' => date('Y-m-d H:i:s')
		];

		$sendNotif = false;
		if ($payload->status == 'PAID') {
			$bookingData['provider_transaction_id'] = $payload->reference;
			$bookingData['is_paid'] = 1;
			$bookingData['paid_at'] = date('Y-m-d H:i:s');
			$sendNotif = true;
		}
		$action = $this->repository->update('member_laundry_bookings', $bookingData, ['id' => $booking->id]);

		if ($sendNotif) {
			$memberText = 'Pembayaran anda sebesar '.toRupiahFormat($payload->total_amount).' telah diterima.';
			$member = $this->repository->findOne('member_laundries', ['id' => $booking->member_laundry_id]);
			$action = $this->sendWablasToMember($member, $memberText);
			$bookingData['extras'] = $action;
		}

		$this->delivery->data = $bookingData;
		return $this->delivery;
	}

}