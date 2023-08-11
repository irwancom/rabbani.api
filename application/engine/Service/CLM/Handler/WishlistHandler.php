<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;

class WishlistHandler {

	private $delivery;
	private $repository;

	private $user;
	private $admin;
	
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

	public function setAdmin ($admin) {
		$this->admin = $admin;
	}

	public function getAdmin () {
		return $this->admin;
	}

	public function getWishlists ($filters = null) {
		$args = [];

		if (!empty($this->getUser())) {
			$args['user_wishlists.user_id'] = $this->user['id'];
		}

		$offset = 0;
		$limit = 20;

		if (isset($filters['is_wishlist'])) {
			$args['is_wishlist'] = $filters['is_wishlist'];
		}

		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'user_wishlists.id',
			'user_wishlists.id_product',
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
			'product.thumbnail_url',
			'product.is_recommended',
			'product.price',
			'product.total_purchased',
			'user_wishlists.is_wishlist',
			'user_wishlists.created_at',
		];
		//$select = [
			//'user_wishlists.id',
			//'user_wishlists.id_product',
			//'product.product_name',
			//'product.sku',
			//'product.desc',
			//'product.weight',
			//'user_wishlists.is_wishlist',
			//'user_wishlists.created_at',
		//];
		$orderKey = 'user_wishlists.created_at';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'product' => 'product.id_product = user_wishlists.id_product',
			'category' => [
				'type' => 'left',
				'value' => 'product.id_category = category.id_category'
			]
		];
		
		$forSumStock = 'product_details.stock'; $joinStock = null;
		if (!empty($this->getUser()) && $this->getUser()['is_reseller'] == 1) {
			$joinStock['store_product_detail_stocks'] = [
				'type' => 'left',
				'value' => 'product_details.sku_code = store_product_detail_stocks.sku_code AND store_product_detail_stocks.store_code = "'.$this->getUser()['store_code'].'"'
			];
			$forSumStock = 'store_product_detail_stocks.stock';
		}

		$wishlists = $this->repository->findPaginated('user_wishlists', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($wishlists['result'] as $wishlist) {
			$cekRating = $this->repository->findOne('product_rates', ['id_product' => $wishlist->id_product], null, null, 'AVG(rate) as rating');
			$wishlist->rating = ($cekRating->rating && !empty($cekRating->rating)) ? floatval($cekRating->rating) : null;

			$cekDetail = $this->repository->findOne('product_details', ['id_product' => $wishlist->id_product], null, null, ['MIN(product_details.price) as min_price_product_detail','MAX(product_details.price) as max_price_product_detail']);
			$wishlist->min_price_product_detail = $cekDetail->min_price_product_detail;
			$wishlist->max_price_product_detail = $cekDetail->max_price_product_detail;

			$cekStock = $this->repository->findOne('product_details', ['id_product' => $wishlist->id_product], null, $joinStock, 'COALESCE(SUM('.$forSumStock.'), 0) as stock');
			$wishlist->stock = $cekStock->stock;

			$wishlist->product_images = $this->repository->find('product_images', ['id_product' => $wishlist->id_product]);
			if (!empty($wishlist->product_images)) {
				$wishlist->thumbnail_url = $wishlist->product_images[0]->image_path;
			}
		}
		$this->delivery->data = $wishlists;
		return $this->delivery;
	}

	public function createWishlist ($idProduct, $payload) {
		$filters = [
			'user_id' => $this->user['id'],
			'id_product' => $idProduct
		];
		$exists = $this->repository->findOne('user_wishlists', $filters);
		if (empty($exists)) {
			$formattedPayload = array_merge($filters, $payload);
			$formattedPayload['created_at'] = date('Y-m-d H:i:s');
			$formattedPayload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('user_wishlists', $formattedPayload);
		} else {
			$payload['updated_at'] = date('Y-m--d H:i:s');
			$action = $this->repository->update('user_wishlists', $payload, $filters);
		}
		$this->delivery->data = 'ok';
		return $this->delivery;
	}

	public function handleRemoveWishlist ($isType, $isMulti) {
		$filters = ['user_id' => $this->user['id']];
		$payload = ['is_wishlist' => 0];
		if($isType=='all'){
			$payload['updated_at'] = date('Y-m--d H:i:s');
			$action = $this->repository->update('user_wishlists', $payload, $filters);
		}else{
			$items = explode(',', $isMulti);
			foreach($items as $item){
				$resAction = $this->createWishlist($item, $payload);
			}
		}
		$this->delivery->data = 'ok';
		return $this->delivery;
	}

}