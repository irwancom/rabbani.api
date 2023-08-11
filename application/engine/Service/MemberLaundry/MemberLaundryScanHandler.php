<?php
namespace Service\MemberLaundry;

use Library\WablasService;
use Service\Entity;
use Service\Delivery;

class MemberLaundryScanHandler {

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;

	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->uploadPath = getenv('UPLOAD_PATH') ? getenv('UPLOAD_PATH') . '/images' : "upload/images";
	}

	public function onScan ($tagCode) {
		$handle = $this->findBookingByTagCode($tagCode);
		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$tagCode = $handle['tag_code'];
		$booking = $handle['booking'];

		$fromStatus = $tagCode->status;
		$toStatus = null;
		$cartData = [];
		if (empty($fromStatus)) {
			$cartData = [
				'status' => MemberLaundryHandler::STATUS_IN_WAREHOUSE,
				'in_warehouse_at' => date('Y-m-d H:i:s')
			];
			$toStatus = MemberLaundryHandler::STATUS_IN_WAREHOUSE;
		} else if ($fromStatus == MemberLaundryHandler::STATUS_IN_WAREHOUSE) {
			$cartData = [
				'status' => MemberLaundryHandler::STATUS_WASHING_FINISHED,
				'washing_finished_at' => date('Y-m-d H:i:s')
			];
			$toStatus = MemberLaundryHandler::STATUS_WASHING_FINISHED;
		} else if ($fromStatus == MemberLaundryHandler::STATUS_WASHING_FINISHED) {
			$cartData = [
				'status' => MemberLaundryHandler::STATUS_IN_DELIVERY,
				'in_delivery_at' => date('Y-m-d H:i:s')
			];
			$toStatus = MemberLaundryHandler::STATUS_IN_DELIVERY;
		} else if ($fromStatus == MemberLaundryHandler::STATUS_IN_DELIVERY) {
			$cartData = [
				'status' => MemberLaundryHandler::STATUS_COMPLETED,
				'completed_at' => date('Y-m-d H:i:s')
			];
			$toStatus = MemberLaundryHandler::STATUS_COMPLETED;
		}

		$action = $this->repository->update('member_laundry_carts', $cartData, ['id' => $tagCode->id]);
		try {
			$handler = new MemberLaundryHandler($this->repository);
			$member = $this->repository->findOne('member_laundries', ['id' => $booking->member_laundry_id]);
			$message = 'Kode Booking: '.$booking->booking_code.PHP_EOL.'Kode Tag: '.$tagCode->tag_code.PHP_EOL.'Nama Produk: '.$tagCode->member_laundry_pricelist_name.PHP_EOL;
			if ($toStatus == MemberLaundryHandler::STATUS_IN_WAREHOUSE) {
				$message .= 'Telah sampai di gudang pencucian kami';
			} else if ($toStatus == MemberLaundryHandler::STATUS_WASHING_FINISHED) {
				$message .= 'Telah selesai dicuci';
			} else if ($toStatus == MemberLaundryHandler::STATUS_IN_DELIVERY) {
				$message .= 'Sedang diantar ke lokasi pengiriman';
			} else if ($toStatus == MemberLaundryHandler::STATUS_COMPLETED) {
				$message .= 'Orderan selesai. Terima kasih.';
			}
			$action = $handler->sendWablasToMember($member, $message);
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
		}

		$isUpdateBooking = true;
		$carts = $booking->member_laundry_carts;
		foreach ($carts as $cart) {
			if ($cart->id != $tagCode->id && $cart->status != $toStatus) {
				$isUpdateBooking = false;
			}
		}

		if ($isUpdateBooking) {
			$action = $this->repository->update('member_laundry_bookings', $cartData, ['id' => $booking->id]);
		}

		$historyPayload = [
			'member_laundry_booking_id' => $booking->id,
			'member_laundry_cart_id' => $tagCode->id,
			'member_laundry_cart_tag_code' => $tagCode->tag_code,
			'from_status' => $fromStatus,
			'to_status' => $toStatus,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->insert('member_laundry_scan_histories', $historyPayload);


		$payload = [
			'tag_code' => $tagCode,
			'booking' => $booking,
			'to_status' => $toStatus,
			'update_booking' => $isUpdateBooking,
			'history' => $historyPayload
		];
		$this->delivery->data = $payload;
		return $this->delivery;
	}

	public function findBookingByTagCode ($tagCode) {
		$manager = new MemberLaundryManager($this->repository);
		$args = [
			'member_laundry_booking_status' => [
				'condition' => 'where_in',
				'value' => MemberLaundryHandler::STATUS_RUNNING
			],
			'tag_code' => $tagCode
		];
		$existsTagCode = $manager->getMemberLaundryCarts($args);
		$result = $existsTagCode->data['result'];
		if (empty($result)) {
			$this->delivery->addError(400, 'Tag code is required');
			return $this->delivery;
		} else if (count($result) > 1) {
			$this->delivery->addError(400, 'Found tag code in more than 1 bookings running');
			return $this->delivery;
		}
		$result = $result[0];

		$booking = $this->repository->findOne('member_laundry_bookings', ['id' => $result->member_laundry_booking_id]);
		$booking->member_laundry_carts = $this->repository->find('member_laundry_carts', ['member_laundry_booking_id' => $result->member_laundry_booking_id]);

		$payload = [
			'tag_code' => $result,
			'booking' => $booking
		];
		return $payload;
	}

}