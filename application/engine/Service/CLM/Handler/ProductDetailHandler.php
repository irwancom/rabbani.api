<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Calculator;

class ProductDetailHandler {

	const PRODUCT_STATUS_READY_STOCK = array('code'=>'ready_stock','label'=>'Tersedia');
	const PRODUCT_STATUS_SOLD_OUT = array('code'=>'sold_out','label'=>'Habis');
	const PRODUCT_STATUS_PROCESS_SOLD_OUT = array('code'=>'process_sold_out','label'=>'Hampir Habis');

	private $delivery;
	private $repository;

	private $productDetail;

	private $useCalculator;
	private $user;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->useCalculator = false;
	}

	public function setUser ($user) {
		$this->user = $user;
	}

	public function getUser () {
		return $this->user;
	}

	public function setUseCalculator ($useCalculator) {
		$this->useCalculator = $useCalculator;
	}

	public function getUseCalculator () {
		return $this->useCalculator;
	}

	public function useCalculator () {
		$this->useCalculator = true;
	}

	public function isUseCalculator () {
		return $this->useCalculator;
	}

	public function getProductDetails ($filters = null) {
		$select = [
			'product_details.id_product',
			'product_details.id_product_detail',
			'product_details.sku_code',
			'product_details.host_path',
			'product_details.image_path',
			'product_details.variable',
			'product_details.reseller_discount_percentage_amount',
			'product_details.price',
			'product_details.total_purchased',
			'product_details.deleted_at',
		];

		$join = [];
		if (!empty($this->getUser()) && $this->getUser()['is_reseller'] == 1) {
			$join = [
				'store_product_detail_stocks' => 'store_product_detail_stocks.sku_code = product_details.sku_code AND store_product_detail_stocks.store_code ="'.$this->user['store_code'].'"'
			];
			$select[] = 'store_product_detail_stocks.stock as stock';
		} else {
			$select[] = 'product_details.stock as stock';
		}

		
		$productDetails = $this->repository->find('product_details', $filters, null, $join, $select);
		foreach ($productDetails as $productDetail) {
			if (!empty($productDetail) && isJson($productDetail->variable)) {
				$productDetail->variable = json_decode($productDetail->variable);
			}
		}
		$this->delivery->data = $productDetails;
		return $this->delivery;
	}

	public function getProductDetailsForReseller ($filters = null) {
		if (empty($this->user)) {
			$this->delivery->addError(400, 'User is required');
			return $this->delivery;
		}

		if (empty($this->user['referred_by_store_agent_id'])) {
			$this->delivery->addError(400, 'Not Allowed');
			return $this->delivery;
		}
		$args = [];
		$argsOrWhere = [];

		if (isset($filters['id_category'])) {
			$args['product.id_category'] = (int)$filters['id_category'];
		}

		if (isset($filters['from_stock'])) {
			$args['product_details.stock >='] = (int)$filters['from_stock'];
		}

		if (isset($filters['q'])) {
			$argsOrWhere['product.product_name'] = [
				'condition' => 'like',
				'value' => $filters['q']
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
			'product.id_product',
			'product.product_name',
			'product.id_category',
			'product.sku',
			'product.weight',
			'product.length',
			'product.width',
			'product.height',
			'product.created_at',
			'product.price as product_price',
			'product.total_purchased as product_total_purchased',
			'product_details.id_product_detail',
			'product_details.sku_code',
			'product_details.host_path',
			'product_details.image_path',
			'product_details.variable',
			'product_details.reseller_discount_percentage_amount',
			'product_details.price',
			'product_details.stock',
			'store_product_detail_stocks.store_code',
			'store_product_detail_stocks.stock as store_stock',
			'product_details.total_purchased',
			'product_details.deleted_at',
		];
		$orderKey = 'product.id_product';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'product' => 'product.id_product = product_details.id_product',
			'product_rates' => [
				'type' => 'left',
				'value' => 'product_details.id_product = product_rates.id_product'
			],
			'store_product_detail_stocks' => 'store_product_detail_stocks.sku_code = product_details.sku_code AND store_product_detail_stocks.store_code ="'.$this->user['store_code'].'"'
		];

		// $groupBy = 'product_details.id_product_detail';
		$products = $this->repository->findPaginated('product_details', $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($products['result'] as $product) {
			$product->product_images = $this->repository->find('product_images', ['id_product' => $product->id_product]);
			$product->variable = isJson($product->variable) ? json_decode($product->variable) : $product->variable;
			if (empty($product->variable)) {
				$variables = [];
				$variables['COLOR'] = '';
				$variables['SIZE'] = '';
				$product->variable = $variables;
			}

			$calculator = new Calculator($this->repository, false);
			$calculator->setCalculateForReseller(true);
			$product->details = $calculator->getDetails($product, 1);
		}
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function getProductDetail ($filters = null) {
		$select = [
			'product_details.id_product',
			'product_details.id_product_detail',
			'product_details.sku_code',
			'product_details.host_path',
			'product_details.image_path',
			'product_details.variable',
			'product_details.reseller_discount_percentage_amount',
			'product_details.price',
			'product_details.total_purchased',
			'product_details.deleted_at',
			'store_product_detail_stocks.stock as store_product_detail_stocks_stock',
		];
		$join = [];
		if (!empty($this->getUser()) && $this->getUser()['is_reseller'] == 1) {
			$join = [
				'store_product_detail_stocks' => "store_product_detail_stocks.sku_code = product_details.sku_code AND store_product_detail_stocks.store_code ='".$this->user['store_code']."'"
			];
			$select[] = 'store_product_detail_stocks.stock as stock';
		} else {
			$join = [
				'store_product_detail_stocks' => [
					'type' => 'left',
					'value' => 'store_product_detail_stocks.sku_code = product_details.sku_code',
				],
				'stores' => [
					'type' => 'left',
					'value' => 'stores.code = store_product_detail_stocks.store_code and stores.is_central = 1'
				]
			];
			$select[] = 'product_details.stock as stock';
		}
		$productDetail = $this->repository->findOne('product_details', $filters, null, $join, $select);
		if (!empty($productDetail) && isJson($productDetail->variable)) {
			$productDetail->variable = json_decode($productDetail->variable);
		}

		if (!empty($productDetail->store_product_detail_stocks_stock)) {
			$productDetail->sale_status = 'exclusive_online';
		}

		if (!empty($productDetail->id_product)) {
			$product = $this->repository->findOne('product', ['id_product' => $productDetail->id_product]);
			$productDetail->weight = $product->weight;
			$productDetail->product_name = $product->product_name;
			$productDetail->product_slug = $product->product_slug;
			$productDetail->product_thumbnail_url = $product->thumbnail_url;
			if ($this->isUseCalculator()) {
				$calculator = new Calculator($this->repository);
				$productDetail->details = $calculator->getDetails($productDetail, 1);
			}
		}
		$this->delivery->data = $productDetail;
		return $this->delivery;
	}

	public function getQtyInProcess ($skuCode) {
		$args = [
			'transaction_item.item_code' => $skuCode,
			'transaction.status' => 'PROCESSING'
		];

		$join = [
			'transaction' => [
				'value' => 'transaction.salesorder_id = transaction_item.salesorder_id',
				'type' => 'inner'
			]
		];

		$select = [
			'transaction.status as transaction_status',
			'transaction_item.item_code',
			'SUM(transaction_item.qty) as qty_total'
		];

		$groupBy = ['transaction_item.item_code', 'transaction.status'];

		$result = $this->repository->find('transaction_item', $args, null, $join, $select, $groupBy);
		return $result; 
	}

	public function getAvailableOnStores ($idProductDetail) {
		$findProductDetail = $this->getProductDetail(['product_details.id_product_detail' => $idProductDetail]);
		$productDetail = $findProductDetail->data;
		if (empty($productDetail)) {
			$this->delivery->addError(400, 'Product detail is required');
		}

		$select = [
			'stores.id as store_id',
			'stores.name as store_name',
			'stores.address as store_address',
			'stores.code as store_code',
			'store_product_detail_stocks.stock'
		];

		$join = [
			'stores' => 'stores.code = store_product_detail_stocks.store_code AND stores.deleted_at IS NULL'
		];

		$filters = [
			'store_product_detail_stocks.sku_code' => $productDetail->sku_code,
			'store_product_detail_stocks.stock !=' => NULL,
			'store_product_detail_stocks.stock >' => 0,
		];

		$all = $this->repository->find('store_product_detail_stocks', $filters, null, $join, $select);
		// usort($all, function($a, $b) {return strcmp($b->stock, $a->stock);});
		foreach ($all as $a) {
			$a->stock = (int)$a->stock;
			$avStock = ($a->stock <= 5) ? self::PRODUCT_STATUS_PROCESS_SOLD_OUT : self::PRODUCT_STATUS_READY_STOCK;
			$a->stock_status = $avStock['code'];
			$a->stock_status_label = $avStock['label'];
		}
		$this->delivery->data = $all;
		return $this->delivery;
	}

}