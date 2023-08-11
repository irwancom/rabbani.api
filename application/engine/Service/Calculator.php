<?php
namespace Service;

use Library\TripayGateway;

class Calculator {
	
	const PAYMENT_METHOD_TYPE_MANUAL = 'MANUAL';

	const TYPE_PAYMENT_STATUS_WAITING = 'waiting'; // service_invoices use this for waiting payment to be happen
	const TYPE_PAYMENT_STATUS_PENDING = 'pending'; // service transactions use this for waiting payment to be happen
	const TYPE_PAYMENT_STATUS_PAID = 'paid';
	const TYPE_PAYMENT_STATUS_CANCELED = 'canceled';
	
	private $price;
	private $discount;
	private $promocode;
	private $total;
	private $items;
	private $extras;
	private $paymentMethods;

	public function __construct () {
		$this->price = 0;
		$this->discount = 0;
		$this->promocode = null;
		$this->total = 0;
		$this->items = null;
		$this->extras = null;
		$this->paymentStatus = self::TYPE_PAYMENT_STATUS_PENDING;
		$this->paymentMethods = [];
	}

	public function getPrice () {
		return $this->price;
	}

	public function getDiscount () {
		return $this->discount;
	}

	public function getPromocode () {
		return $this->promocode;
	}

	public function getTotal () {
		return $this->total;
	}

	public function getItems () {
		return $this->items;
	}

	public function getExtras () {
		return $this->extras;
	}

	public function getPaymentStatus () {
		return $this->paymentStatus;
	}

	public function getPaymentMethods () {
		return $this->paymentMethods;
	}

	public function addService($product, $term, $title = null) {
		$this->price += $term['price_before_discount'];
		$this->discount += $term['price_before_discount'] - $term['price_after_discount'];
		$this->total += $term['price_after_discount'];
		$this->items[] = [
			'type' => $product['type'],
			'code' => $product['code'],
			'name' => $product['name'],
			'detail' => $term,
			'price' => $term['price_before_discount'],
			'discount' => $term['price_before_discount'] - $term['price_after_discount'],
			'payment_period' => $term['code'],
			'total' => $term['price_after_discount'],
			'title' => $title
		];
	}

	public function addCloud ($cloud, $term, $title = null) {
		$this->price += $term['price_before_discount'];
		$this->discount += $term['price_before_discount'] - $term['price_after_discount'];
		$this->total += $term['price_after_discount'];
		$this->items[] = [
			'type' => $cloud['type'],
			'code' => $cloud['code'],
			'name' => $cloud['name'],
			'sld' => $cloud['sld'],
			'tld' =>  $cloud['tld'],
			'detail' => $term,
			'price' => $term['price_before_discount'],
			'discount' => $term['price_before_discount'] - $term['price_after_discount'],
			'payment_period' => $term['code'],
			'total' => $term['price_after_discount'],
			'title' => $title
		];
	}

	public function addDomain ($cloud, $term) {
		$this->price += $term['price_before_discount'];
		$this->discount += $term['price_before_discount'] - $term['price_after_discount'];
		$this->total += $term['price_after_discount'];
		$this->items[] = [
			'type' => $cloud['type'],
			'code' => $cloud['code'],
			'name' => $cloud['name'],
			'detail' => $term,
			'price' => $term['price_before_discount'],
			'discount' => $term['price_before_discount'] - $term['price_after_discount'],
			'payment_period' => $term['code'],
			'total' => $term['price_after_discount'],
			'sld' => $cloud['sld'],
			'tld' =>  $cloud['tld'],
			'title' => sprintf('Domain %s%s', $cloud['sld'], $cloud['tld'])
		];
	}

	public function addManualPaymentMethod () {
		$paymentMethod = [
			'group' => self::PAYMENT_METHOD_TYPE_MANUAL,
			'code' => self::PAYMENT_METHOD_TYPE_MANUAL,
			'name' => 'Transfer Manual',
			'type' => 'transfer',
			'charged_to' => 'merchant',
			'fee' => [
				'flat' => 0,
				'percent' => 0
			],
			'active' => true,
			'message' => 'Silahkan transfer ke rekening XXX sebesar XXX dengan deskripsi XXX'
		];
		$this->paymentMethods[] = $paymentMethod;
	}

	public function addDirectPaymentMethod () {
		$tripay = new TripayGateway;
		$result = $tripay->channelPembayaran();
		if (isset($result->data) && !empty($result->data)) {
			$datas = $result->data;
			foreach ($datas as $data) {
				$this->paymentMethods[] = (array)$data;
			}
		}
	}

	public function checkCart () {
		return [
			'price' => $this->price,
			'discount' => $this->discount,
			'promocode' => $this->promocode,
			'total' => $this->total,
			'items' => $this->items,
			'payment_methods' => $this->paymentMethods
		];
	}
}