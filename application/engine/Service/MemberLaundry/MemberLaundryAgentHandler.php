<?php
namespace Service\MemberLaundry;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;
use Library\WablasService;
use Library\XenditService;
use Library\TripayGateway;

class MemberLaundryAgentHandler {

	const APP_ENV = 'prod';
	const MAIN_WABLAS = '6289674545000';
	const WABLAS_MENU_STATE_REQUEST_BOOKING_CODE = 'request_booking_code';
	const WABLAS_MENU_STATE_IN_CART = 'in_cart';
	const WABLAS_MENU_STATE_FIND_PRICELIST = 'find_pricelist';
	const WABLAS_MENU_STATE_INPUT_DETAIL_CART = 'input_detail_cart';
	const WABLAS_MENU_STATE_REQUEST_TAG_CODE = 'request_tag_code';
	const WABLAS_MENU_STATE_REQUEST_PAYMENT_METHOD = 'request_payment_method';

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;

	private $generalMenuText;
	private $unauthorizedText;
	private $requestTagCodeText;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->uploadPath = getenv('UPLOAD_PATH') ? getenv('UPLOAD_PATH') . '/images' : "upload/images";
		$this->unauthorizedText = 'Anda tidak terdaftar pada sistem Whatsapp Laundry Kurir';
		$this->generalMenuText = '1. List Pickup'.PHP_EOL.'2. Proses Order';
		$this->requestBookingCodeText = 'Silahkan masukkan 4 digit kode booking: (Ketik 9 untuk kembali)';
		$this->inCartText = '1. Tambah'.PHP_EOL.'2. Selesai'.PHP_EOL.'3. Batal';
		$this->requestTagCodeText = 'Silahkan masukkan nomor tag untuk produk ini:';
		$this->requestPaymentMethodText = 'Silahkan pilih metode pembayaran yang tersedia:'.PHP_EOL.'1. COD (Dapat dilakukan sekarang/setelah pengiriman)'.PHP_EOL.'2. Online';
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

		$existsAgent = $this->repository->findOne('member_laundry_agents', ['phone_number' => $payload['phone'], 'id_auth_api' => $authApi->id_auth_api]);
		if (empty($existsAgent)) {
			die();
			$this->delivery->addError(409, $this->unauthorizedText);
			return $this->delivery;
		}

		if (self::APP_ENV != 'dev') {
			if ($existsAgent->last_wablas_id == $payload['id']) {
				die();
			} else {
				$action = $this->repository->update('member_laundry_agents', ['last_wablas_id' => $payload['id']], ['id' => $existsAgent->id]);
			}
		}

		if (!empty($existsAgent->wablas_menu_state)) {
			$this->handleWablasMenu($existsAgent, $payload['message']);
			return $this->delivery;
		}

		$message = strtolower($payload['message']);
		if (!in_array($message, ['1', '2'])) {
			$this->delivery->addError(400, $this->generalMenuText);
			return $this->delivery;
		} else {
			if ($message == '1') {
				$this->handleMenuListPickup($existsAgent);
				return $this->delivery;
			} else if ($message == '2') {
				$this->handleMenuOrder($existsAgent);
				return $this->delivery;
			}
		}

		$this->delivery->data = $this->generalMenuText;
		return $this->delivery;
	}

	private function handleMenuListPickup($agent) {
		$join = [
			'member_laundries' => 'member_laundries.id = member_laundry_bookings.member_laundry_id'
		];
		$args = [
			'status' => MemberLaundryHandler::STATUS_LIST_PICKUP
		];
		$bookings = $this->repository->find('member_laundry_bookings', $args, null, $join);
		$messageText = '';
		if (empty($bookings)) {
			$messageText = 'Tidak ada orderan untuk saat ini'.PHP_EOL;
		} else {
			foreach ($bookings as $booking) {
				$messageText .= 'Kode Booking: '.$booking->booking_code.PHP_EOL.'Nama Customer: '.$booking->name.PHP_EOL.'No HP: '.$booking->phone_number.PHP_EOL.'Alamat: '.$booking->address.PHP_EOL.sprintf('https://maps.google.com/maps?q=%s&z=17&hl=en', $booking->destination_coordinate).PHP_EOL.PHP_EOL;
			}
		}
		$messageText .= $this->generalMenuText;
		$this->delivery->data = $messageText;
	}

	private function handleMenuOrder ($agent) {
		$agentData = [
			'wablas_menu_state' => self::WABLAS_MENU_STATE_REQUEST_BOOKING_CODE,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);
		$this->delivery->data = $this->requestBookingCodeText;
	}

	private function handleWablasMenu ($agent, $message) {
		if ($agent->wablas_menu_state == self::WABLAS_MENU_STATE_REQUEST_BOOKING_CODE) {
			$this->handleRequestBookingCode($agent, $message);
		} else if ($agent->wablas_menu_state == self::WABLAS_MENU_STATE_IN_CART) {
			$this->handleInCart($agent, $message);
		} else if ($agent->wablas_menu_state == self::WABLAS_MENU_STATE_FIND_PRICELIST) {
			$this->handleFindPricelist($agent, $message);
		} else if ($agent->wablas_menu_state == self::WABLAS_MENU_STATE_INPUT_DETAIL_CART) {
			$this->handleInputDetailCart($agent, $message);
		} else if ($agent->wablas_menu_state == self::WABLAS_MENU_STATE_REQUEST_TAG_CODE ) {
			$this->handleRequestTagCode($agent, $message);
		} else if ($agent->wablas_menu_state == self::WABLAS_MENU_STATE_REQUEST_PAYMENT_METHOD) {
			$this->handleRequestPaymentMethod($agent, $message);
		} else {
			$this->delivery->data = '';
		}
	}

	private function handleRequestBookingCode ($agent, $message) {
		$bookingCode = $message;
		$existsBookingCode = $this->findBookingCode($agent, $bookingCode, MemberLaundryHandler::STATUS_LIST_PICKUP);
		if (empty($existsBookingCode) && $message != '9') {
			$message = 'Kode booking tidak ditemukan atau sudah digunakan.'.PHP_EOL.PHP_EOL.$this->requestBookingCodeText;
			$this->delivery->addError(400, $message);
			return false;
		} else if ($message == '9') {
			$agentData = [
				'wablas_menu_state' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);
			$this->delivery->addError(400, $this->generalMenuText);
			return false;
		}

		$agentData = [
			'wablas_menu_state' => self::WABLAS_MENU_STATE_IN_CART,
			'active_booking_code' => $bookingCode,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);

		$bookingData = [
			'status' => MemberLaundryHandler::STATUS_AGENT_IN_PROCESS_CHECKOUT,
			'picked_up_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->update('member_laundry_bookings', $bookingData, ['id' => $existsBookingCode->id]);
		$text = 'Kode Booking: '.$existsBookingCode->booking_code.PHP_EOL.'Nama: '.$existsBookingCode->name.PHP_EOL.'Nomor HP: '.$existsBookingCode->phone_number.PHP_EOL.PHP_EOL.$this->inCartText;
		$this->delivery->data = $text;
	}

	private function handleInCart ($agent, $message) {
		$existsBookingCode = $this->findBookingCode($agent, $agent->active_booking_code);
		if ($message == '1') {
			$agentData = [
				'wablas_menu_state' => self::WABLAS_MENU_STATE_FIND_PRICELIST,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$pricelistResult = $this->generatePricelist();
			$this->delivery->data = $pricelistResult['message'];
			$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);
		} else if ($message == '2') {
			// hitung orderan
			if (empty($existsBookingCode->member_laundry_carts)) {
				$cartData = [
					'tag_code' => null,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_laundry_carts', $cartData, ['member_laundry_booking_id' => $existsBookingCode->id]);

				$bookingData = [
					'status' => MemberLaundryHandler::STATUS_CANCELED,
					'canceled_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_laundry_bookings', $bookingData, ['id' => $existsBookingCode->id]);

				$agentData = [
					'wablas_menu_state' => null,
					'active_booking_code' => null,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);

				$messageText = 'Kode Booking '.$existsBookingCode->booking_code.' berhasil dibatalkan.';
				$this->delivery->data = $messageText.PHP_EOL.PHP_EOL.$this->generalMenuText;
			} else {
				$agentData = [
					'wablas_menu_state' => self::WABLAS_MENU_STATE_REQUEST_PAYMENT_METHOD,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);

				$messageText = 'Kode Booking: '.$existsBookingCode->booking_code.PHP_EOL;
				$cartText = '';
				$carts = $existsBookingCode->member_laundry_carts;
				$index = 1;
				$totalPrice = 0;
				foreach ($carts as $cart) {
					$totalPrice += $cart->final_price;
					$cartText .= sprintf('> Kode %s - %s', $cart->tag_code, $this->generateCartMessage($cart)).PHP_EOL;
				}
				$messageText .= 'Total Pembayaran: '.toRupiahFormat($totalPrice).PHP_EOL.$cartText.PHP_EOL;
				$this->delivery->data = $messageText.PHP_EOL.$this->requestPaymentMethodText;	
			}
		} else if ($message == '3') {
			$cartData = [
				'tag_code' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundry_carts', $cartData, ['member_laundry_booking_id' => $existsBookingCode->id]);

			$bookingData = [
				'status' => MemberLaundryHandler::STATUS_CANCELED,
				'canceled_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundry_bookings', $bookingData, ['id' => $existsBookingCode->id]);

			$agentData = [
				'wablas_menu_state' => null,
				'active_booking_code' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);

			$messageText = 'Kode Booking '.$existsBookingCode->booking_code.' berhasil dibatalkan.';
			$this->delivery->data = $messageText.PHP_EOL.PHP_EOL.$this->generalMenuText;
		} else {
			$this->delivery->data = $this->inCartText;
		}
	}

	private function handleFindPricelist ($agent, $message) {
		$pricelistResult = $this->generatePricelist();
		if (!isset($pricelistResult['data'][$message])) {
			$this->delivery->data = $pricelistResult['message'];
		} else {
			// masukkan ke cart
			$existsBookingCode = $this->findBookingCode($agent, $agent->active_booking_code);
			$existsPricelist = $this->repository->findOne('member_laundry_pricelists', ['id' => (int)$message]);
			$cartData = [
				'member_laundry_booking_id' => $existsBookingCode->id,
				'member_laundry_pricelist_id' => $existsPricelist->id,
				'price' => $existsPricelist->price,
				'is_finish_checkout' => 0,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('member_laundry_carts', $cartData);
			$agentData = [
				'wablas_menu_state' => self::WABLAS_MENU_STATE_INPUT_DETAIL_CART,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$agentAction = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);
			$cartResult = $this->generateCartDetailQuestion($existsPricelist, $cartData);
			$this->delivery->data = $cartResult['message'];
		}

	}

	private function handleInputDetailCart ($agent, $message) {
		$value = (float)$message;
		$existsBookingCode = $this->findBookingCode($agent, $agent->active_booking_code);
		$cartActive = $this->repository->findOne('member_laundry_carts', ['member_laundry_booking_id' => $existsBookingCode->id, 'is_finish_checkout' => 0]);
		$pricelist = $this->repository->findOne('member_laundry_pricelists', ['id' => $cartActive->member_laundry_pricelist_id]);
		$cartResult = $this->generateCartDetailQuestion($pricelist, $cartActive);
		if (empty($value)) {
			$this->delivery->data = $cartResult['message'];
		} else {
			$cartData[$cartResult['column']] = $value;
			$cartData['updated_at'] = date('Y-m-d H:i:s');
			$cartAction = $this->repository->update('member_laundry_carts', $cartData, ['id' => $cartActive->id]);
			$cartActive->{$cartResult['column']} = $value;
			$cartResult = $this->generateCartDetailQuestion($pricelist, $cartActive);
			if (!empty($cartResult['message'])) {
				// beri pertanyaan lagi
				$this->delivery->data = $cartResult['message'];
			} else {
				// tutup dan hitung cart ini
				$agentData = [
					'wablas_menu_state' => self::WABLAS_MENU_STATE_REQUEST_TAG_CODE,
					'updated_at' => date('Y-m-d H:i:s')
				];
				$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);
				$this->delivery->data = $this->requestTagCodeText;
			}
		}
	}

	private function handleRequestTagCode ($agent, $message) {
		$tagCode = $message;

		// find used tag_code
		$args = [
			'member_laundry_booking_status' => [
				'condition' => 'where_in',
				'value' => MemberLaundryHandler::STATUS_RUNNING
			],
			'tag_code' => $tagCode
		];
		$manager = new MemberLaundryManager($this->repository);
		$tagCodeUsed = $manager->getMemberLaundryCarts($args)->data;
		if (isset($tagCodeUsed['result']) && count($tagCodeUsed['result']) > 0) {
			$this->delivery->data = 'Kode Tag ini sudah digunakan untuk kode booking yang lain. Mohon gunakan tag code yang lain.'.PHP_EOL.$this->requestTagCodeText;
			return false;
		}

		$existsBookingCode = $this->findBookingCode($agent, $agent->active_booking_code);
		$cartActive = $this->repository->findOne('member_laundry_carts', ['member_laundry_booking_id' => $existsBookingCode->id, 'is_finish_checkout' => 0]);
		// isi tag code, tutup dan hitung cart ini
		$totalPrice = $cartActive->price;
		if (!empty($cartActive->weight)) {
			$totalPrice *= $cartActive->weight;
		}
		if (!empty($cartActive->length)) {
			$totalPrice *= $cartActive->length;
		}
		if (!empty($cartActive->width)) {
			$totalPrice *= $cartActive->width;
		}
		$newCartData = [
			'tag_code' => $tagCode,
			'final_price' => $totalPrice,
			'is_finish_checkout' => 1,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$cartAction = $this->repository->update('member_laundry_carts', $newCartData, ['id' => $cartActive->id]);

		$existsBookingCode = $this->findBookingCode($agent, $agent->active_booking_code);
		$agentData = [
			'wablas_menu_state' => self::WABLAS_MENU_STATE_IN_CART,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);
		$messageText = 'Kode Tag '.$tagCode.' berhasil ditambahkan ke keranjang.'.PHP_EOL.'Total nilai belanja saat ini: '.toRupiahFormat($existsBookingCode->total_price).PHP_EOL;
		$carts = $existsBookingCode->member_laundry_carts;
		foreach ($carts as $cart) {
			$messageText .= sprintf('> %s - %s', $cart->tag_code, $this->generateCartMessage($cart)).PHP_EOL;
		}
		$this->delivery->data = $messageText.PHP_EOL.$this->inCartText;
	}

	private function handleRequestPaymentMethod ($agent, $message) {
		$existsBookingCode = $this->findBookingCode($agent, $agent->active_booking_code);
		$carts = $existsBookingCode->member_laundry_carts;
		$cartText = '';
		$totalPrice = 0;
		foreach ($carts as $cart) {
			$totalPrice += $cart->final_price;
			$cartText .= sprintf('> Kode %s - %s', $cart->tag_code, $this->generateCartMessage($cart)).PHP_EOL;
		}
		if ($message == '1') {
			$bookingData = [
				'status' => MemberLaundryHandler::STATUS_CHECKOUT_DONE,
				'total_price' => $totalPrice,
				'payment_amount' => $totalPrice,
				'payment_method_code' => MemberLaundryHandler::PAYMENT_METHOD_CODE_COD,
				'payment_method_name' => MemberLaundryHandler::PAYMENT_METHOD_NAME_COD,
				'finish_checkout_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundry_bookings', $bookingData, ['id' => $existsBookingCode->id]);

			$agentData = [
				'wablas_menu_state' => null,
				'active_booking_code' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);
			$messageText = 'Kode Booking: '.$agent->active_booking_code.PHP_EOL.'Total Pembayaran: '.$totalPrice.PHP_EOL;

			$memberHandler = new MemberLaundryHandler($this->repository);
			$member = $this->repository->findOne('member_laundries', ['id' => $existsBookingCode->member_laundry_id]);
			$memberText = 'Total tagihan untuk Kode Booking '.$existsBookingCode->booking_code.' sebesar '.toRupiahFormat($totalPrice).PHP_EOL.$cartText.PHP_EOL.'Anda memilih metode pembayaran COD. Harap melakukan pembayaran dengan kurir kami sekarang atau setelah pengiriman. Terima kasih.';
			$action = $memberHandler->sendWablasToMember($member, $memberText);

			$this->delivery->data = $messageText.$cartText.PHP_EOL.$this->generalMenuText;
		} else if ($message == '2') {
			$bookingData = [
				'status' => MemberLaundryHandler::STATUS_CHECKOUT_DONE,
				'total_price' => $totalPrice,
				'payment_amount' => $totalPrice,
				'finish_checkout_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundry_bookings', $bookingData, ['id' => $existsBookingCode->id]);

			$agentData = [
				'wablas_menu_state' => null,
				'active_booking_code' => null,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundry_agents', $agentData, ['id' => $agent->id]);

			$memberHandler = new MemberLaundryHandler($this->repository);
			$member = $this->repository->findOne('member_laundries', ['id' => $existsBookingCode->member_laundry_id]);
			$messageText = 'Kode Booking: '.$agent->active_booking_code.PHP_EOL.$cartText.'Total Pembayaran: '.toRupiahFormat($totalPrice).PHP_EOL.'Invoice tagihan sudah dikirim ke customer '.$member->name.PHP_EOL;
			$memberData = [
				'wablas_menu_state' => MemberLaundryHandler::WABLAS_MENU_REQUEST_BOOKING_PAYMENT_METHOD,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('member_laundries', $memberData, ['id' => $member->id]);
			$paymentResult = $this->generateTripayPaymentMethod();
			$memberText = 'Berikut tagihan pembayaran anda untuk Kode Booking: '.$existsBookingCode->booking_code.PHP_EOL.$cartText.PHP_EOL.'Silahkan pilih metode pembayaran online yang tersedia: '.PHP_EOL.$paymentResult['message'];
			$action = $memberHandler->sendWablasToMember($member, $memberText);


			$this->delivery->data = $messageText.PHP_EOL.$this->generalMenuText;
		} else {
			$messageText = 'Kode Booking: '.$agent->active_booking_code.PHP_EOL.'Total Pembayaran: '.toRupiahFormat($totalPrice).PHP_EOL.$cartText.PHP_EOL;
			$this->delivery->data = $messageText.$this->requestPaymentMethodText;
		}
	}

	private function generatePricelist () {
		$message = '';
		$pricelists = $this->repository->find('member_laundry_pricelists');
		$result = [];
		$index = 1;
		foreach ($pricelists as $pricelist) {
			$result[$pricelist->id] = $index.'. '.$pricelist->name.' - ('.toRupiahFormat($pricelist->price).')';
			$index++;
		}

		$res = [
			'data' => $result,
			'message' => join(PHP_EOL, $result)
		];
		return $res;
	}

	private function generateCartMessage ($cart) {
		$details = [];
		if (!empty($cart->weight)) {
			$details[] = $cart->weight.'Kg';
		}
		if (!empty($cart->length)) {
			$details[] = $cart->length.'CM';
		}
		if (!empty($cart->width)) {
			$details[] = $cart->width.'CM';
		}

		return sprintf('%s %s Total %s', $cart->member_laundry_pricelist_name, join(" X ", $details), toRupiahFormat($cart->final_price));
	}

	private function generateCartDetailQuestion ($pricelist, $cart) {
		$message = '';
		$cart = (object)$cart;
		$column = null;
		if ($pricelist->is_weight_required && empty($cart->weight)) {
			$column = 'weight';
			$message = 'Silahkan masukkan berat (Kg): ';
		} else if ($pricelist->is_length_required && empty($cart->length)) {
			$column = 'length';
			$message = 'Silahkan masukkan panjang (Cm): ';
		} else if ($pricelist->is_width_required && empty($cart->width)) {
			$column = 'width';
			$message = 'Silahkan masukkan lebar (Cm): ';
		}

		$result = [
			'column' => $column,
			'message' => $message
		];
		return $result;
	}

	private function findBookingCode ($agent, $bookingCode, $status = null) {
		$args = [
			'booking_code' => $bookingCode,
			'member_laundry_bookings.id_auth_api' => $agent->id_auth_api
		];
		if (!empty($status)) {
			$args['member_laundry_bookings.status'] = $status;
		}
		$select = [
			'member_laundry_bookings.id as id',
			'member_laundry_bookings.id_auth_api as id_auth_api',
			'member_laundry_bookings.member_laundry_id as member_laundry_id',
			'member_laundries.name as name',
			'member_laundries.phone_number as phone_number',
			'member_laundry_bookings.booking_code as booking_code',
			'member_laundry_bookings.status as status',
			'member_laundry_bookings.total_price as total_price',
			'member_laundry_bookings.payment_amount as payment_amount',
			'member_laundry_bookings.invoice_url as invoice_url',
			'member_laundry_bookings.is_paid as is_paid',
			'member_laundry_bookings.payment_method_code as payment_method_code',
			'member_laundry_bookings.payment_method_name as payment_method_name',
			'member_laundry_bookings.destination_coordinate as destination_coordinate',
			'member_laundry_bookings.payment_fees as payment_fees',
			'member_laundry_bookings.picked_up_at as picked_up_at',
			'member_laundry_bookings.created_at as created_at',
			'member_laundry_bookings.updated_at as updated_at',
		];
		$join = [
			'member_laundries' => 'member_laundries.id = member_laundry_bookings.member_laundry_id'
		];
		$existsBookingCode = $this->repository->findOne('member_laundry_bookings', $args, null, $join, $select);
		if (!empty($existsBookingCode)) {
			$joinCart = [
				'member_laundry_bookings' => 'member_laundry_carts.member_laundry_booking_id = member_laundry_bookings.id',
				'member_laundry_pricelists' => 'member_laundry_carts.member_laundry_pricelist_id = member_laundry_pricelists.id'
			];
			$selectCart = [
				'member_laundry_carts.id',
				'member_laundry_carts.member_laundry_booking_id',
				'member_laundry_bookings.booking_code as member_laundry_booking_code',
				'member_laundry_carts.tag_code',
				'member_laundry_carts.member_laundry_pricelist_id',
				'member_laundry_pricelists.name as member_laundry_pricelist_name',
				'member_laundry_pricelists.price as member_laundry_pricelist_price',
				'member_laundry_carts.price',
				'member_laundry_carts.final_price',
				'member_laundry_carts.weight',
				'member_laundry_carts.length',
				'member_laundry_carts.width',
				'member_laundry_carts.status',
				'member_laundry_carts.in_warehouse_at',
				'member_laundry_carts.washing_finished_at',
				'member_laundry_carts.in_delivery_at',
				'member_laundry_carts.completed_at',
				'member_laundry_carts.created_at',
				'member_laundry_carts.updated_at',
			];
			$carts = $this->repository->find('member_laundry_carts', ['member_laundry_carts.member_laundry_booking_id' => $existsBookingCode->id], null, $joinCart, $selectCart);
			$totalPrice = 0;
			foreach ($carts as $cart) {
				$totalPrice += $cart->final_price;
			}
			$existsBookingCode->total_price = $totalPrice;
			$existsBookingCode->member_laundry_carts = $carts;
		}
		return $existsBookingCode;
	}

	public function generateTripayPaymentMethod () {
		$tripay = new TripayGateway();
		$tripay->setEnv('development');
		$tripayResult = $tripay->channelPembayaran();
		$paymentChannels = $tripayResult->data;
		$result = [];
		$tripay = [];
		$index = 1;
		foreach ($paymentChannels as $channel) {
			$result[$index] = $index.'. '.$channel->name;
			$tripay[$index] = $channel;
			$index++;
		}
		$res = [
			'tripay' => $tripay,
			'data' => $result,
			'message' => join(PHP_EOL, $result)
		];
		return $res;
	}

}