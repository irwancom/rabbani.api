<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Calculator;
use Library\DigitalOceanService;

class VoucherHandler {

	const TYPE_ALL = 'all';
	const TYPE_PRODUCT = 'product';
	const TYPE_ONGKIR = 'ongkir';
	const TYPE_SUBTOTAL = 'subtotal';

	private $types;
	private $delivery;
	private $repository;
	private $productDetailHandler;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->types = [
			self::TYPE_ALL,
			self::TYPE_PRODUCT,
			self::TYPE_ONGKIR,
			self::TYPE_SUBTOTAL,
		];
	}

	public function setUser ($user) {
		$this->user = $user;
	}

	public function getUser () {
		return $this->user;
	}

	public function setAdmin ($admin) {
		$this->admin = $admin;
	}

	public function getAdmin () {
		return $this->admin;
	}

	public function getVouchers ($filters = null) {
		$args = [];

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			$args['start_time <='] = $filters['current_time'];
			$args['end_time >='] = $filters['current_time'];
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
			'vouchers.id',
			'vouchers.name',
			'vouchers.code',
			'vouchers.type',
			'vouchers.discount_type',
			'vouchers.discount_value',
			'vouchers.min_shopping_amount',
			'vouchers.max_discount_amount',
			'vouchers.description',
			'vouchers.image_url',
			'vouchers.start_time',
			'vouchers.end_time',
			'vouchers.created_at',
		];
		$orderKey = 'vouchers.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$vouchers = $this->repository->findPaginated('vouchers', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($vouchers['result'] as $voucher) {
			$voucher->voucher_products = $this->repository->find('voucher_products', ['voucher_id' => $voucher->id]);
		}
		$this->delivery->data = $vouchers;
		return $this->delivery;
	}

	public function getVoucher ($filters = null) {

		$args = [];

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['vouchers.id'] = $filters['id'];
		}

		if (isset($filters['current_time']) && !empty($filters['current_time'])) {
			$args['start_time <='] = $filters['current_time'];
			$args['end_time >='] = $filters['current_time'];
		}

		if (isset($filters['code']) && !empty($filters['code'])) {
			$args['vouchers.code'] = $filters['code'];
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
			'vouchers.id',
			'vouchers.name',
			'vouchers.code',
			'vouchers.discount_type',
			'vouchers.discount_value',
			'vouchers.min_shopping_amount',
			'vouchers.max_discount_amount',
			'vouchers.start_time',
			'vouchers.end_time',
			'vouchers.description',
			'vouchers.image_url',
			'vouchers.created_at',
		];
		$orderKey = 'vouchers.id';
		$orderValue = 'DESC';
		$voucher = $this->repository->findOne('vouchers', $args);
		if (!empty($voucher)) {
			$joinProduct = [
				'product' => [
					'type' => 'left',
					'value' => 'product.id_product = voucher_products.id_product'
				],
			];
			$selectProduct = [
				'voucher_products.*',
				'product.product_name as product_name',
				'product.sku as sku',
				'product.price as price',
				//'COALESCE(SUM(store_product_detail_stocks.stock), 0) as stock'
			];
			//$groupBy = 'product.id_product';
			//$voucher->voucher_products = $this->repository->find('voucher_products', ['voucher_products.voucher_id' => $voucher->id], null, $joinProduct, $selectProduct, $groupBy);
			$voucherProducts = $this->repository->find('voucher_products', ['voucher_products.voucher_id' => $voucher->id], null, $joinProduct, $selectProduct);
			if($voucherProducts && !is_null($voucherProducts)){
				foreach($voucherProducts as $vPrd){
					$vPrd->product_image = $this->repository->findOne('product_images', ['id_product' => $vPrd->id_product], null, null, ['image_path']);
					$selectProductDetail = [
						'product_details.sku_code',
						'product_details.variable as variable',
						'product_details.price as price',
						'product_details.stock as stock',
						'store_product_detail_stocks.stock as stock_store',
					];
					$joinProductDetail = [
						'store_product_detail_stocks' => [
							'type' => 'left',
							'value' => 'product_details.sku_code = store_product_detail_stocks.sku_code AND store_product_detail_stocks.deleted_at is null'
						],
					];
					$detailProduct = $this->repository->find('product_details', ['product_details.id_product' => $vPrd->id_product], null, $joinProductDetail, $selectProductDetail);
					$vPrd->details = $detailProduct;
				}
			}
			$voucher->voucher_products = $voucherProducts;
		}
		$this->delivery->data = $voucher;
		return $this->delivery;
	}

	public function createVoucher ($payload) {
		if (empty($this->getAdmin())) {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['start_time']) || empty($payload['start_time'])) {
			$this->delivery->addError(400, 'Start time is required');
		}

		if (!isset($payload['end_time']) || empty($payload['end_time'])) {
			$this->delivery->addError(400, 'End time is required');
		}

		if (!isset($payload['discount_type']) || empty($payload['discount_type']) || !in_array($payload['discount_type'], ['amount', 'percentage'])) {
			$this->delivery->addError(400, 'Discount type is required');
		}

		if (!isset($payload['discount_type']) || empty($payload['discount_type']) || !in_array($payload['discount_type'], ['amount', 'percentage'])) {
			$this->delivery->addError(400, 'Discount type is required');
		}

		if (!isset($payload['type']) || empty($payload['type']) || !in_array($payload['type'], $this->types)) {
			$this->delivery->addError(400, 'Voucher type is required');
		}

		if (!isset($payload['discount_value']) || empty($payload['discount_value'])) {
			$this->delivery->addError(400, 'Discount value is required');
		}

		if (!isset($payload['min_shopping_amount']) || empty($payload['min_shopping_amount'])) {
			$this->delivery->addError(400, 'Minimum shopping amount is required');
		}

		if (!isset($payload['max_discount_amount']) || empty($payload['max_discount_amount'])) {
			$this->delivery->addError(400, 'Maximum discount amount is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$existsCode = $this->repository->findOne('vouchers', ['code' => $payload['code']]);
		if (!empty($existsCode)) {
			$this->delivery->addError(400, 'Code already exists');
			return $this->delivery;
		}

		try {
			if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
				$digitalOceanService = new DigitalOceanService();
				$uploadResult = $digitalOceanService->upload($payload, 'image');
				$payload['image_url'] = $uploadResult['cdn_url'];
			}
			$payload['code'] = strtoupper($payload['code']);
			$payloadProducts = [];
			if (isset($payload['products'])) {
				$payloadProducts = $payload['products'];
				unset($payload['products']);
			}

			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('vouchers', $payload);
			$voucher = $this->repository->findOne('vouchers', ['id' => $action]);
			if ($payload['type'] == self::TYPE_PRODUCT) {
				if (!empty($payloadProducts) && !empty($voucher)) {
					$products = $payloadProducts;
					$productHandler = new ProductHandler($this->repository);
					foreach ($products as $product) {
						$existsProduct = $productHandler->getProduct(['id_product' => $product['id_product']])->data;
						if (!empty($existsProduct)) {
							$action = $this->createOrUpdateVoucherProduct($voucher, $existsProduct, $product['is_active']);
						}
					}
				}
			}


			$this->delivery->data = $voucher;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateVoucher ($payload, $filters) {
		try {
			$payloadProducts = [];
			if (isset($payload['products'])) {
				$payloadProducts = $payload['products'];
				unset($payload['products']);
			}
			if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
				$digitalOceanService = new DigitalOceanService();
				$uploadResult = $digitalOceanService->upload($payload, 'image');
				$payload['image_url'] = $uploadResult['cdn_url'];
			}
			$action = $this->repository->update('vouchers', $payload, $filters);
			$result = $this->repository->findOne('vouchers', $filters);

			if (!empty($payloadProducts) && !empty($result) && $result->type == self::TYPE_PRODUCT) {
				$products = $payloadProducts;
				$productHandler = new ProductHandler($this->repository);
				$voucher = $result;
				foreach ($products as $product) {
					$existsProduct = $productHandler->getProduct(['id_product' => $product['id_product']])->data;
					if (!empty($existsProduct)) {
						$action = $this->createOrUpdateVoucherProduct($voucher, $existsProduct, $product['is_active']);
					}
				}
			}

			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteVoucher ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('vouchers', $payload, ['id' => $id]);
			$result = $this->repository->findOne('vouchers', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function createOrUpdateVoucherProduct ($voucher, $product, $isActive) {
		$args = [
			'voucher_id' => $voucher->id,
			'id_product' => $product->id_product, 
		];
		$exists = $this->repository->findOne('voucher_products', $args);
		if (empty($exists)) {
			$newData = [
				'voucher_id' => $voucher->id,
				'id_product' => $product->id_product,
				'is_active' => $isActive,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->insert('voucher_products', $newData);
		} else {
			$updateData = [
				'is_active' => $isActive,
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->update('voucher_products', $updateData, ['id' => $exists->id]);
		}
		$theData = $this->repository->findOne('voucher_products', $args);
		return $theData;

	}

}