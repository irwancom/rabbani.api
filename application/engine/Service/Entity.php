<?php

namespace Service;

class Entity {

	/* PACKAGE TYPE */
	const TYPE_HOSTING = 'hosting';
	const TYPE_DOMAIN = 'domain';
	const TYPE_JUBELIO = 'jubelio';
	const TYPE_SERVICE = 'service';

	const SERVICE_HOSTING = 'service_hosting';
	const SERVICE_DOMAIN = 'service_domain';
	const SERVICE_JNE = 'service_jne';
	const SERVICE_LIONPARCEL = 'service_lionparcel';
	const SERVICE_MAILGUN = 'service_mailgun';
	const SERVICE_TRIPAY_PAYMENT = 'service_tripay_payment';
	const SERVICE_XENDIT = 'service_xendit';
	const SERVICE_DIGITAL_OCEAN = 'service_digital_ocean';
	const SERVICE_WABLAS = 'service_wablas';

	const STATUS_ACTIVE = 'active';
	const STATUS_SUSPEND = 'suspend';
	const STATUS_TERMINATE = 'terminate';
	/* const PAYMENT_PERIODS = [
		'1M' => 1,
		'3M' => 3,
		'6M' => 6,
		'1Y' => 12,
		'2Y' => 24,
		'3Y' => 36
	]; */

	const PAYMENT_PERIODS = [
		'7D' => [
			'multiplier' => 1,
			'text' => '7 days'
		],
		'1M' => [
			'multiplier' => 1,
			'text' => '1 month'
		],
		'3M' => [
			'multiplier' => 3,
			'text' => '3 months'
		],
		'6M' => [
			'multiplier' => 6,
			'text' => '6 months'
		],
		'1Y' => [
			'multiplier' => 12,
			'text' => '1 year'
		],
		'2Y' => [
			'multiplier' => 24,
			'text' => '2 year'
		],
		'3Y' => [
			'multiplier' => 36,
			'text' => '3 year'
		],
	];

	public function __construct () {
		$this->types = [
			self::SERVICE_HOSTING,
			self::SERVICE_DOMAIN,
			self::SERVICE_JNE,
			self::SERVICE_LIONPARCEL,
			self::SERVICE_MAILGUN,
		];
	}

	public function getTypes () {
		return $this->types;
	}

	public function formatAuth ($services) {
		$boolean = [
			0 => false,
			1 => true
		];
		$result = [];
		foreach ($this->types as $type) {
			$result[$type]['is_active'] = false;
		}
		foreach ($services as $service) {
			$result[$service->service_product_code]['is_active'] = ($service->status == self::STATUS_ACTIVE ? true : false);
		}
		return $result;
	}

	public static function formatProduct ($cloud) {
		$result = [];
		$result = [
			'type' => $cloud->type,
			'code' => $cloud->code,
			'name' => $cloud->name,
			'description' => $cloud->description,
			'price' => $cloud->price,
		];
		if (isset($cloud->extras)) {
			foreach ($cloud->extras as $key => $value) {
				$result[$key] = $value;
			}
		}

		if ($validJson = isJson($cloud->payment_period)) {
			$terms = [];
			$periods = json_decode($cloud->payment_period);
			foreach ($periods as $period) {
				$term = [
					'code' => $period->period,
					'price' => $cloud->price,
					'discount' => $period->discount,
					'discount_price' => $cloud->price * $period->discount / 100,
					'price_before_discount' => (int) ($cloud->price * self::PAYMENT_PERIODS[$period->period]['multiplier']),
					'price_after_discount' => (int) ($cloud->price * self::PAYMENT_PERIODS[$period->period]['multiplier']) - ($cloud->price * self::PAYMENT_PERIODS[$period->period]['multiplier'] * $period->discount / 100),
					'price_after_discount_monthly' => (int) (($cloud->price * self::PAYMENT_PERIODS[$period->period]['multiplier']) - ($cloud->price * self::PAYMENT_PERIODS[$period->period]['multiplier'] * $period->discount / 100)) / self::PAYMENT_PERIODS[$period->period]['multiplier'],
				];
				if (isset($cloud->extras)) {
					foreach ($cloud->extras as $key => $value) {
						$term[$key] = $value;
					}
				}
				$terms[] = $term;
			}
			$result['payment_period'] = $terms;
		}
		return $result;
	}

	public static function modifyDate ($paymentPeriod, $date, $condition = 'add') {
		$conditions = [
			'add' => '+',
			'sub' => '-'
		];
		return date('Y-m-d H:i:s', strtotime(sprintf('%s%s', $conditions[$condition], self::PAYMENT_PERIODS[$paymentPeriod]['text']), strtotime($date)));

	}
}