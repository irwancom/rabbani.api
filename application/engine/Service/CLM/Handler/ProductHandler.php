<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Calculator;
use Library\DigitalOceanService;

class ProductHandler {

	private $delivery;
	private $repository;

	private $productDetailHandler;
	private $showProductDetails = true;
	private $showProductRates = true;
	private $showProductRateImages = true;
	private $showProductImages = true;

	private $user;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function setUser ($user) {
		$this->user = $user;
	}

	public function getUser () {
		return $this->user;
	}

	public function getProducts ($filters = null) {
		$args = [];
		$argsOrWhere = [];

		if (isset($filters['id_category']) && !empty($filters['id_category'])) {
			$args['product.id_category'] = (int)$filters['id_category'];
		}

		if (isset($filters['category_slug']) && !empty($filters['category_slug'])) {
			$args['category.category_slug'] = $filters['category_slug'];
		}

		if (isset($filters['published'])) {
			$args['product.published'] = (int)$filters['published'];
		}

		if (isset($filters['is_recommended'])) {
			$args['product.is_recommended'] = (int)$filters['is_recommended'];
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			$argsOrWhere['product.product_name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		if (isset($filters['min_weight']) && !empty($filters['min_weight'])) {
			$args['product.weight >'] = (int)$filters['min_weight'];
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
			'product.product_slug',
			'product.id_category',
			'category.category_name',
			'product.published',
			'product.sku',
			'product.weight',
			'product.length',
			'product.width',
			'product.height',
			'product.created_at',
			'product.thumbnail_url',
			'product.is_recommended',
			'product.price',
			'product.total_purchased',
			'CAST(AVG(product_rates.rate) AS FLOAT) as rating',
			'MIN(product_details.price) as min_price_product_detail',
			'MAX(product_details.price) as max_price_product_detail',
		];
		$orderKey = 'product.id_product';
		$orderValue = 'DESC';
		$multiSort = null;
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$isOrderValue = strtoupper($filters['order_value']);
			$orderValue = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : 'DESC';
		}
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
			$isOrderKey = strtolower($filters['order_key']);
			$isRandom = ($isOrderKey=='rand()' || $isOrderKey=='random') ? true : false;
			if(!$isRandom){
				$isOrderKey = strtolower($filters['order_key']);
				if($isOrderKey=='best_sell') $orderKey = 'product.total_purchased';
				if($isOrderKey=='created_at' || $isOrderKey=='created' || $isOrderKey=='latest') $orderKey = 'product.created_at';
				if($isOrderKey=='rating' || $isOrderKey=='like' || $isOrderKey=='favorite') $orderKey = 'rating';
				if($isOrderKey=='popular' || $isOrderKey=='popularity'){
					$multiSort = array('product.total_purchased'=>$orderValue, 'rating'=>$orderValue);
				}
			}
		}
		
		$join = [
			'category' => [
				'type' => 'left',
				'value' => 'product.id_category = category.id_category'
			],
			'product_details' => [
				'type' => 'left',
				'value' => 'product_details.id_product = product.id_product'
			],
			'product_rates' => [
				'type' => 'left',
				'value' => 'product.id_product = product_rates.id_product'
			],
		];

		$forSumStock = 'product_details.stock';
		if (!empty($this->getUser()) && $this->getUser()['is_reseller'] == 1) {
			$join['store_product_detail_stocks'] = [
				'type' => 'left',
				'value' => "product_details.sku_code = store_product_detail_stocks.sku_code AND store_product_detail_stocks.store_code = '".$this->getUser()['store_code']."'"
			];
			$forSumStock = 'store_product_detail_stocks.stock';
			$filters['stock'] = 1;
			$filters['stock_type'] = 'plus';
			//$select[] = 'COALESCE(SUM(store_product_detail_stocks.stock), 0) as stock';
		} else {
			$join['store_product_detail_stocks'] = [
				'type' => 'left',
				'value' => 'product_details.sku_code = store_product_detail_stocks.sku_code AND store_product_detail_stocks.deleted_at is null'
			];
			$forSumStock = 'store_product_detail_stocks.stock';
		}
		//else {
			//$select[] = 'COALESCE(SUM(product_details.stock), 0) as stock';
		//}
		$select[] = 'COALESCE(SUM('.$forSumStock.'), 0) as stock';

		$altJoin = null;
		$altGroupBy = null;
		$altSelect = null;
		$havingBy = array();
		if (isset($filters['format']) && $filters['format'] == 'v3') {
			$join['product_details'] = 'product_details.id_product = product.id_product';
			$join['store_product_detail_stocks'] = 'store_product_detail_stocks.sku_code = product_details.sku_code';

			//define filter location
			if (isset($filters['location_id']) && !empty($filters['location_id'])) {
				$join['stores'] = 'stores.code = store_product_detail_stocks.store_code';
				$args['stores.id'] = $filters['location_id'];
			}
			$altJoin = $join;
			$altGroupBy = 'product.id_product';
			$altSelect = 'COUNT(distinct product.id_product) as total';
			if (isset($filters['store_code']) && !empty($filters['store_code'])) {
				$args['store_product_detail_stocks.store_code'] = $filters['store_code'];
			}
		}

		if (isset($filters['stock']) && !empty($filters['stock']) && is_numeric($filters['stock'])) {
			$forCekStock = '=';
			if(isset($filters['stock_type']) && ($filters['stock_type']=='minus' || $filters['stock_type']=='plus')){
				$forCekStock = ($filters['stock_type']=='minus') ? '<=' : '>=';
			}
			array_push($havingBy, 'SUM('.$forSumStock.')'.$forCekStock.$filters['stock']);
		}

		$groupBy = 'product.id_product';
		$products = $this->repository->findPaginated('product', $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy, $altJoin, null, $altSelect, $havingBy, $multiSort);

		if ($this->isShowProductImages()) {
			foreach ($products['result'] as $product) {
				$product->product_images = $this->repository->find('product_images', ['id_product' => $product->id_product]);
				if (!empty($product->product_images)) {
					$product->thumbnail_url = $product->product_images[0]->image_path;
				}
				$calculator = new Calculator($this->repository, false);
				$details = $calculator->getDetailsForProduct($product, 1);
				$product->details = $details;
			}
		}
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function setShowProductDetails ($showProductDetails) {
		$this->showProductDetails = $showProductDetails;
	}

	public function isShowProductDetails () {
		return $this->showProductDetails;
	}

	public function setShowProductRates ($showProductRates) {
		$this->showProductRates = $showProductRates;
	}

	public function isShowProductRates () {
		return $this->showProductRates;
	}

	public function setShowProductImages ($showProductImages) {
		$this->showProductImages = $showProductImages;
	}

	public function getShowProductImages () {
		return $this->showProductImages;
	}

	public function isShowProductImages () {
		return $this->getShowProductImages();
	}

	public function setShowProductRateImages ($showProductRateImages) {
		$this->showProductRateImages = $showProductRateImages;
	}

	public function isShowProductRatesImages () {
		return $this->showProductRateImages;
	}

	public function getProduct ($filters = null) {
		$argsOrWhere = [];

		if (isset($filters['id_product']) && !empty($filters['id_product'])) {
			$filters['product.id_product'] = $filters['id_product'];
			unset($filters['id_product']);
		}

		if (isset($filters['q'])) {
			$argsOrWhere['product.id_product'] = $filters['q'];
			$argsOrWhere['product.product_slug'] = strtolower($filters['q']);
			unset($filters['q']);
		}


		$select = [
			'product.id_product',
			'product.product_name',
			'product.product_slug',
			'product.id_category',
			'product.thumbnail_url',
			'category.category_name',
			'product.is_recommended',
			'product.desc',
			'product.sku',
			'product.weight',
			'product.length',
			'product.width',
			'product.height',
			'product.created_at',
			'product.price',
			'product.total_purchased',
			'product.specifications',
			'CAST(AVG(product_rates.rate) AS FLOAT) as rating',
			'COUNT(product_rates.id) as total_review',
			'MIN(product_details.price) as min_price_product_detail',
			'MAX(product_details.price) as max_price_product_detail',
		];
		$join = [
			'product_details' => [
				'type' => 'left',
				'value' => 'product_details.id_product = product.id_product'
			],
			'product_rates' => [
				'type' => 'left',
				'value' => 'product.id_product = product_rates.id_product'
			],
			'category' => [
				'type' => 'left',
				'value' => 'category.id_category = product.id_category'
			],
		];
		if (!empty($this->getUser()) && $this->getUser()['is_reseller'] == 1) {
			$join['store_product_detail_stocks'] = [
				'type' => 'left',
				'value' => 'product_details.sku_code = store_product_detail_stocks.sku_code AND store_product_detail_stocks.store_code = "'.$this->getUser()['store_code'].'"'
			];
			$select[] = 'COALESCE(SUM(store_product_detail_stocks.stock),0) as stock';
		} else {
			$select[] = 'COALESCE(SUM(product_details.stock), 0) as stock';
		}
		$groupBy = 'product.id_product';
		$product = $this->repository->findOne('product', $filters, $argsOrWhere, $join, $select, null, null, $groupBy);
		if (!empty($product)) {
			$calculator = new Calculator($this->repository, false);
			$product->specifications = (isJson($product->specifications) ? json_decode($product->specifications) : null);
			$product->details = $calculator->getDetailsForProduct($product, 1);
			$product->product_images = $this->repository->find('product_images', ['id_product' => $product->id_product]);
			if ($this->isShowProductDetails()) {
				$this->productDetailHandler = new ProductDetailHandler($this->repository);
				$this->productDetailHandler->setUser($this->getUser());
				$findProductDetails = $this->productDetailHandler->getProductDetails(['id_product' => $product->id_product]);
				$productDetails = $findProductDetails->data;
				foreach ($productDetails as $productDetail) {
					$productDetail->price = $product->price;
					$productDetail->weight = $product->weight;
				}
				$product->product_details = $productDetails;
			}

			if ($this->isShowProductRates()) {
				$productRates = $this->repository->find('product_rates', ['id_product' => $product->id_product]);
				$product->product_rates = $productRates;	
			}

			if ($this->isShowProductRatesImages()) {
				$productRateImages = $this->repository->find('product_rate_images', ['id_product' => $product->id_product]);
				$product->product_rate_images = $productRateImages;
			}

		}
		$this->delivery->data = $product;
		return $this->delivery;
	}

	public function viewProduct ($idProduct, $userId) {
		$existsProduct = $this->getProduct(['id_product' => $idProduct]);
		if (empty($existsProduct->data)) {
			$this->delivery->addError(400, 'Product is required');
			return $this->delivery;
		}

		$payload = [
			'id_product' => $idProduct,
			'user_id' => $userId,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->insert('product_views', $payload);
		$this->delivery->data = ' ok';
		return $this->delivery;
	}

	public function getProductReview ($q, $filters = null) {
		$existsProduct = $this->getProduct(['q' => $q]);
		if (empty($existsProduct->data)) {
			$this->delivery->addError(400, 'Product is required');
			return $this->delivery;
		}

		$idProduct = $existsProduct->data->id_product;
		$args = [
			'product_rates.id_product' => $idProduct,
		];

		if (isset($filters['rate']) && !empty($filters['rate'])) {
			$args['product_rates.rate'] = (int)$filters['rate'];
		}

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$orderKey = 'product_rates.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$select =[
			'product_rates.id',
			'product_rates.order_code',
			'users.id as user_id',
			'users.first_name',
			'users.picImage',
			'product_rates.id_product',
			'product_rates.message',
			'product_rates.rate',
			'product_rates.created_at',
			'product_rates.updated_at',
			'product_rates.deleted_at',
		];

		$join = [
			'orders' => 'orders.order_code = product_rates.order_code',
			'users' => 'users.id = orders.user_id',
		];

		if (isset($filters['has_media']) && !empty($filters['has_media'])) {
			if ($filters['has_media'] == 'true') {
				$join['product_rate_images'] = 'product_rate_images.id_product = product_rates.id_product and product_rate_images.order_code = product_rates.order_code';
			}
		}
		$groupBy = 'product_rates.id, users.id';
		$productRates = $this->repository->findPaginated('product_rates', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		foreach ($productRates['result'] as $rate) {
			$rate->image = $this->repository->find('product_rate_images', ['id_product' => $idProduct, 'order_code' => $rate->order_code]);
			$rate->order_details = $this->getOrderDetails($rate->order_code);
		}

		$listRate = array(); $countRateImg = 0;
		$joinCountImg = array('product_rate_images'=>'product_rate_images.id_product = product_rates.id_product and product_rate_images.order_code = product_rates.order_code');
		for($rt=1;$rt<=5;$rt++){
			$listRate[$rt] = array();
			$listRate[$rt]['label'] = $rt.' Bintang';
			$listRate[$rt]['count'] = $this->repository->findCountData('product_rates', ['id_product' => $idProduct,'rate'=>$rt]);
			$listRate[$rt]['image'] = $this->repository->findCountData(
				'product_rates', ['product_rates.id_product' => $idProduct,'product_rates.rate'=>$rt], null, $joinCountImg
			);
			if($listRate[$rt]['image'] > 0) $countRateImg = $countRateImg+1;
		}

		$listRate['image'] = array('label'=>'Dengan Media','count'=>$countRateImg);
		$productRates['rates'] = $listRate;

		$review = [
			'rating' => $existsProduct->data->rating,
			'product_review' => $productRates
		];
		$this->delivery->data = $productRates;
		return $this->delivery;
	}

	public function updateProduct ($payload, $filters = null) {
		try {
			if (isset($payload['specifications']) && !empty($payload['specifications'])) {
				$payload['specifications'] = json_encode($payload['specifications']);
			}

			if (isset($_FILES['thumbnail']) && !empty($_FILES['thumbnail']['tmp_name'])) {
				$digitalOceanService = new DigitalOceanService();
				$uploadResult = $digitalOceanService->upload($payload, 'thumbnail');
				$payload['thumbnail_url'] = $uploadResult['cdn_url'];
			}
			$action = $this->repository->update('product', $payload, $filters);
			$result = $this->getProduct($filters);
			return $result;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function map ($idProduct) {
		set_time_limit(0);
		$productResult = $this->getProduct(['id_product' => $idProduct]);
		if ($productResult->hasErrors()) {
			return $productResult;
		}

		$product = $productResult->data;
		if (empty($product)) {
			$this->delivery->addError(400, 'Product is required');
			return $this->delivery;
		}

		$updatedSku = [];

		// deprecated
		/* $productDetails = $this->repository->find('product_details', ['id_product' => $product->id_product]);
		foreach ($productDetails as $productDetail) {
			$cleanSku = str_replace(" ", "", $productDetail->sku_code);
			$productSku = substr($cleanSku, 0, 6);
			$existsProductBySku = $this->repository->findOne('product', ['sku' => $productSku]);
			if (!empty($existsProductBySku)) {
				$data = [
					'id_product' => $existsProductBySku->id_product
				];
				$args = [
					'id_product_detail' => $productDetail->id_product_detail
				];
				$action = $this->repository->update('product_details', $data, $args);
				$updatedSku[] = $productDetail->sku_code;
			}
		} */

		// new
		$parentSku = $product->sku;
		if (empty($parentSku)) {
			$this->delivery->addError(400, 'Product SKU is required');
			return $this->delivery;
		}

		$args = [
			'SUBSTR(product_details.sku_code, 1, '.strlen($parentSku).') =' => $parentSku
		];
		$productDetails = $this->repository->find('product_details', $args);
		foreach ($productDetails as $productDetail) {
			if ($productDetail->id_product != $product->id_product) {
				$data = [
					'id_product' => $product->id_product,
				];
				$filter = [
					'id_product_detail' => $productDetail->id_product_detail
				];
				$action = $this->repository->update('product_details', $data, $filter);
				$updatedSku[] = $productDetail->sku_code;
			}
		}

		$this->delivery->data = $updatedSku;
		return $this->delivery;
	}

	private function getOrderDetails ($orderCode) {
		$joinOrderDetail = [
			'product_details' => [
				'type' => 'left',
				'value' => 'product_details.id_product_detail = order_details.id_product_detail OR product_details.sku_code = order_details.sku_code'
			],
			'product' => [
				'type'=>'left',
				'value' => 'product.id_product = product_details.id_product'
			]
		];
		$select = [
			/* 'order_details.id_order_detail',
			'order_details.order_code',
			'order_details.id_product_detail',
			'product_details.id_product as id_product',
			'product_details.sku_code as product_detail_sku_code',
			'product_details.sku_barcode as product_detail_sku_barcode',
			'product_details.host_path as product_detail_host_path',
			'product_details.image_path as product_detail_image_path',
			'product_details.price as product_detail_price',
			'product_details.stock as product_detail_stock', */
			'product_details.variable as product_detail_variable',
			/* 'product.product_name as product_name',
			'product.sku as product_sku',
			'product.desc as product_desc',
			'product.weight as product_weight',
			'product.length as product_length',
			'product.height as product_height',
			'product.published as product_published',
			'product.price as product_price',
			'product.total_purchased as product_total_purchased',
			'order_details.discount_type',
			'order_details.discount_source',
			'order_details.discount_value',
			'order_details.price',
			'order_details.qty',
			'order_details.discount_amount',
			'order_details.subtotal',
			'order_details.total',
			'order_details.salesorder_detail_id',
			'order_details.sku_code',
			'order_details.picklist_status',
			'order_details.picklist_source_inventory_box_id',
			'order_details.picklist_picked_at',
			'order_details.picklist_is_picked',
			'order_details.deleted_at', */
		];
		$orderDetails = $this->repository->find('order_details', ['order_code' => $orderCode], null, $joinOrderDetail, $select);
		foreach ($orderDetails as $orderDetail) {
			$orderDetail->product_detail_variable = json_decode($orderDetail->product_detail_variable);
		}
		return $orderDetails;	
	}

	public function getProductReviewReply ($reviewId, $filters = null) {
		$args = [
			'product_rate_reply.rate_reply_rates' => $reviewId,
		];
		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$sort = array('created'=>'created_at');
         
		$orderKey = 'created_at';
        if(isset($filters['order_by']) && $filters['order_by'] && !empty($filters['order_by']) && !is_null($filters['order_by'])){
            $isOrderBy = strtolower($filters['order_by']);
            $orderKey = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $orderKey;
        }
        $orderKey = 'product_rate_reply.'.$orderKey;

        $orderValue = 'DESC';
        if(isset($filters['order_value']) && $filters['order_value'] && !empty($filters['order_value']) && !is_null($filters['order_value'])){
            $isOrderValue = strtoupper($filters['order_value']);
            $orderValue = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $orderValue;
        }

		$select =[
			'product_rate_reply.rate_reply_id as reply_id',
			'product_rate_reply.rate_reply_by as reply_by',
			'product_rate_reply.rate_reply_auth as reply_auth',
			'product_rate_reply.rate_reply_message as reply_message',
			'admins.first_name as reply_name',
			'users.picImage as reply_pic',
			'product_rate_reply.created_at as reply_created',
			'product_rate_reply.rate_reply_file as reply_media',
			'admins.last_name as admin_last_name',
			'users.first_name as user_first_name',
			'users.last_name as user_last_name',
		];

		$join = [
			'admins' => [
				'type' => 'left',
				'value' => 'admins.id = product_rate_reply.rate_reply_auth'
			],
			'users' => [
				'type' => 'left',
				'value' => 'users.id = product_rate_reply.rate_reply_auth'
			],
		];

		$productRateReply = $this->repository->findPaginated('product_rate_reply', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($productRateReply['result'] as $reply) {
			$firstName = ($reply->reply_by=='admin') ? $reply->reply_name : $reply->user_first_name;
			$lastName = ($reply->reply_by=='admin') ? $reply->admin_last_name : $reply->user_last_name;
			$reply->reply_name = $firstName.' '.$lastName;
			$reply->reply_pic = ($reply->reply_by=='admin') ? null : $reply->reply_pic;

			$isMedia = null;
			if($reply->reply_media && !empty($reply->reply_media) && !is_null($reply->reply_media)){
				$detailMedia = json_decode($reply->reply_media, true);
				$isMedia = array();
				foreach($detailMedia as $k_media=>$dMedia){
					$isMedia[$k_media]['type'] = $dMedia['file_type'];
					$isMedia[$k_media]['path'] = $dMedia['cdn_url'];
				}
			}
			$reply->reply_media = $isMedia;
			unset($reply->admin_last_name, $reply->user_first_name, $reply->user_last_name);
		}
		$this->delivery->data = $productRateReply;
		return $this->delivery;
	}

	public function handleListProductVoucher ($idProduct = null) {
		$currentDate = date('Y-m-d h:i:s');
        $conditionVoucher = [
            'voucher_products.id_product'=>$idProduct,
            'voucher_products.is_active'=>1,
            'voucher_products.deleted_at'=>NULL,
            'vouchers.deleted_at'=>NULL,
            'vouchers.start_time <='=> $currentDate,
            'vouchers.end_time >='=> $currentDate,
        ];
        $selectVoucher = [
            'vouchers.id as id', 'vouchers.code as code', 'vouchers.name as name',
            'vouchers.discount_type as type', 'vouchers.discount_value as value',
            'vouchers.min_shopping_amount as min_shopping', 'vouchers.max_discount_amount as min_discount',
        ];
        $orConditionVoucher = ['vouchers.type'=>'all','vouchers.type'=>'product'];
        $joinVoucher = [
        	'vouchers' => ['type'=>'left','value'=>'vouchers.id=voucher_products.voucher_id'],
        ];

        $tagVoucher = $this->repository->find('voucher_products', $conditionVoucher, $orConditionVoucher, $joinVoucher, $selectVoucher);
        return $tagVoucher;
	}

}