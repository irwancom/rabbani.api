<?php
namespace Service\CLM\Handler;

use Service\CLM\Model\OrderInfo;
use Service\CLM\Calculator;

class OrderInfoHandler {

	private $orderResult;

	public function __construct ($repository) {
		$this->repository = $repository;
	}

	public function setOrderResult ($orderResult) {
		$this->orderResult = $orderResult;
	}

	public function create () {
		$infos = [];
		if (empty($this->orderResult)) {
			return $infos;
		}
		$detail = $this->orderResult->detail;
		if (!empty($detail->shopping_price)) {
			$info = new OrderInfo;
			$info->setLabel('Total Belanja');
			$info->setValue(toRupiahFormat($detail->shopping_price));
			$infos[] = $info->format();
		}

		if (!empty($detail->donation)) {
			$info = new OrderInfo;
			$info->setLabel('Donasi');
			$info->setValue(toRupiahFormat($detail->donation));
			$infos[] = $info->format();
		}

		if (isset($detail->discount_books) && !empty($detail->discount_books)) {
			$books = $detail->discount_books;
			foreach ($books as $book) {
				if ($book['type'] == Calculator::DISCOUNT_TYPE_VOUCHER) {
					$voucher = $book['voucher'];
					$info = new OrderInfo;
					$info->setLabel($voucher->name);
					$info->setValue(toRupiahFormat($book['discount_amount']));
					$infos[] = $info->format();
				}
			}
		}

		if (!empty($detail->shipping_cost)) {
			$info = new OrderInfo;
			$info->setLabel('Biaya Pengiriman');
			$info->setValue(toRupiahFormat($detail->shipping_cost));
			$infos[] = $info->format();
		}

		if (!empty($detail->payment_fee_total)) {
			$info = new OrderInfo;
			$info->setLabel('Biaya Transaksi');
			$info->setValue(toRupiahFormat($detail->payment_fee_customer));

			$forAttribute = [
				'value_merchant' => toRupiahFormat($detail->payment_fee_merchant),
				'value_total' => toRupiahFormat($detail->payment_fee_total),
			];
			$info->setAttribute($forAttribute);
			$infos[] = $info->format();
		}

		return $infos;
	}


}