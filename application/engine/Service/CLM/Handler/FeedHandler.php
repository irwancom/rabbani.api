<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;
use Service\CLM\Calculator;

class FeedHandler {

	private $delivery;
	private $repository;
	private $productHandler;
	private $productDetailHandler;
	private $discountHandler;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->productDetailHandler = new ProductDetailHandler($this->repository);
		$this->productHandler = new ProductHandler($this->repository);
		$this->discountHandler = new DiscountHandler($this->repository);
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

	public function getFeeds ($filters = null) {
		$args = [];

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'user_feeds.id',
			'user_feeds.user_id',
			'users.first_name',
			'users.last_name',
			'users.username',
			'user_feeds.thumbnail_type',
			'user_feeds.thumbnail_url',
			'user_feeds.caption',
			'COUNT(DISTINCT(user_feed_likes.id)) AS like_count',
			'COUNT(DISTINCT(user_feed_views.id)) AS view_count',
			'user_feeds.created_at',
		];
		$orderKey = 'user_feeds.created_at';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'user_feed_likes' => [
				'type' => 'left',
				'value' => 'user_feed_likes.user_feed_id = user_feeds.id AND user_feed_likes.is_like = 1'
			],
			'users' => 'users.id = user_feeds.user_id',
			'user_feed_views' => [
				'type' => 'left',
				'value' => 'user_feed_views.user_feed_id = user_feeds.id'
			]
		];

		$groupBy = 'user_feeds.id';
		$feeds = $this->repository->findPaginated('user_feeds', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		foreach ($feeds['result'] as $feed) {
			$feedProductResult = $this->getFeedProducts(['user_feed_id' => $feed->id]);
			$feed->products = $feedProductResult->data;
			$commentResult = $this->getFeedComments(['user_feed_id' => $feed->id]);
			$feed->comments = $commentResult->data;
			$filtersIsLiked = [
				'user_feed_id' => $feed->id
			];
			if (!empty($this->user)) {
				$filtersIsLiked['user_id'] = $this->user['id'];
			}
			$isLiked = $this->getFeedLike($filtersIsLiked)->data;
			if (!empty($isLiked) && $isLiked->is_like == 1) {
				$feed->is_liked = true;
			} else {
				$feed->is_liked = false;
			}
		}
		$this->delivery->data = $feeds;
		return $this->delivery;
	}

	public function getFeed ($filters = null) {
		$feed = $this->repository->findOne('user_feeds', $filters);
		if (!empty($feed)) {
			$feedProducts = $this->repository->find('user_feed_products', ['user_feed_id' => $feed->id]);
			foreach ($feedProducts as $feedProduct) {
				$productResult = $this->productHandler->getProduct(['id_product' => $feedProduct->id_product]);
				$feedProduct->product = $productResult->data;
			}
			$feed->feed_products = $feedProducts;
		}
		$this->delivery->data = $feed;
		return $this->delivery;
	}

	public function getFeedProducts ($filters = null) {
		$args = [];
		if (isset($filters['user_feed_id']) && !empty($filters['user_feed_id'])) {
			$args['user_feed_id'] = $filters['user_feed_id'];
		}

		$join = [
			'product' => 'product.id_product = user_feed_products.id_product',
			'user_wishlists' => [
				'type' => 'left',
				'value' => 'product.id_product = user_wishlists.id_product AND user_wishlists.is_wishlist = 1'
			],
			'product_details' => [
				'type' => 'left',
				'value' => 'product_details.id_product = product.id_product AND product_details.id_product = user_feed_products.id_product'
			]
		];

		$select = [
			'user_feed_products.id',
			'user_feed_products.id_product',
			'product.product_name',
			'product.desc',
			'product.weight',
			'product.price',
			'MIN(product_details.price) as min_price_product_detail',
			'MAX(product_details.price) as max_price_product_detail',
			'COUNT(distinct user_wishlists.id) AS wishlist_count',
			'user_feed_products.created_at',
		];

		$groupBy = 'user_feed_products.id';

		$feedProducts = $this->repository->find('user_feed_products', $args, null, $join, $select, $groupBy);
		foreach ($feedProducts as $product) {
			$product->images = $this->repository->find('product_images', ['id_product' => $product->id_product]);
			$findDiscountProduct = $this->discountHandler->getDiscountProduct(['id_product' => $product->id_product, 'current_time' => date('Y-m-d H:i:s')]);
			$calculator = new Calculator($this->repository);
			$product->details = $calculator->getDetailsForProduct($product, 1);
		}
		$this->delivery->data = $feedProducts;
		return $this->delivery;
	}

	public function getFeedLike ($filters = null) {
		$like = $this->repository->findOne('user_feed_likes', $filters);
		$this->delivery->data = $like;
		return $this->delivery;
	}

	public function createFeed ($payload) {
		if (!empty($this->getUser())) {

		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['caption']) || empty($payload['caption'])) {
			$this->delivery->addError(400, 'Caption is required');
		}

		if (empty($payload['product']) && empty($_FILES['thumbnail']['tmp_name'])) {
			$this->delivery->addError(400, 'Product or thumbnail is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		try {

			$validResult = $this->validateThumbnailFile('thumbnail');
			if ($validResult->hasErrors()) {
				return $validResult;
			}

			$products = [];
			if (isset($payload['product']) && !empty($payload['product'])) {
				foreach ($payload['product'] as $productId) {
					$productResult = $this->productHandler->getProduct(['id_product' => $productId]);
					if ($productResult->hasErrors() || empty($productResult->data)) {
						$this->delivery->addError(400, 'Product is required');
						return $this->delivery;
					}
					$products[] = $productResult->data;
				}
			}

			$thumbnailUrl = null;
			if (isset($_FILES['thumbnail']) && !empty($_FILES['thumbnail']['tmp_name'])) {
				$digitalOceanService = new DigitalOceanService();
				$uploadResult = $digitalOceanService->upload($payload, 'thumbnail');
				$thumbnailUrl = $uploadResult['cdn_url'];
			}

			$formattedPayload = [
				'user_id' => $this->user['id'],
				'thumbnail_type' => $validResult->data->file_type,
				'thumbnail_url' => $thumbnailUrl,
				'caption' => $payload['caption'],
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('user_feeds', $formattedPayload);
			foreach ($products as $product) {
				$productPayload = [
					'user_feed_id' => $action,
					'id_product' => $product->id_product,
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s')
				];
				$productAction = $this->repository->insert('user_feed_products', $productPayload);
			}

			$feed = $this->getFeed(['id' => $action]);
			$this->delivery->data = $feed->data;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}


	public function createFeedLike ($feedId, $payload) {
		$filters = [
			'user_feed_id' => $feedId,
			'user_id' => $this->user['id']
		];
		$exists = $this->repository->findOne('user_feed_likes', $filters);
		if (empty($exists)) {
			$formattedPayload = array_merge($filters, $payload);
			$formattedPayload['created_at'] = date('Y-m-d H:i:s');
			$formattedPayload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('user_feed_likes', $formattedPayload);
		} else {
			$payload['updated_at'] = date('Y-m--d H:i:s');
			$action = $this->repository->update('user_feed_likes', $payload, $filters);
		}
		$this->delivery->data = 'ok';
		return $this->delivery;
	}

	public function createFeedComment ($payload) {
		if (!empty($this->getUser())) {
			$payload['user_id'] = $this->user['id'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['user_feed_id']) || empty($payload['user_feed_id'])) {
			$this->delivery->addError(400, 'Feed is required');
		}

		if (!isset($payload['comment']) || empty($payload['comment'])) {
			$this->delivery->addError(400, 'Comment is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$existsFeed = $this->getFeed(['id' => $payload['user_feed_id']]);
		if (empty($existsFeed->data) || $existsFeed->hasErrors()) {
			$this->delivery->addError(400, 'Feed is required');
			return $this->delivery;
		}

		$payload['created_at'] = date('Y-m-d H:i:s');
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('user_feed_comments', $payload);
		$this->delivery->data = 'ok';
		return $this->delivery;
	}

	public function getFeedComments ($filters = null) {
		$args = [];

		$offset = 0;
		$limit = 20;

		if (isset($filters['user_feed_id']) && !empty($filters['user_feed_id'])) {
			$args['user_feed_id'] = $filters['user_feed_id'];
		}

		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'user_feed_comments.id',
			'user_feed_comments.user_id',
			'users.first_name as user_first_name',
			'users.last_name as user_last_name',
			'user_feed_comments.comment',
			'user_feed_comments.created_at'
		];
		$orderKey = 'user_feed_comments.created_at';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'users' => 'users.id = user_feed_comments.user_id'
		];
		$feeds = $this->repository->findPaginated('user_feed_comments', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $feeds;
		return $this->delivery;	
	}

	public function viewFeed ($userFeedId, $userId) {
		$existsFeed = $this->getFeed(['id' => $userFeedId]);
		if (empty($existsFeed->data)) {
			$this->delivery->addError(400, 'Feed is required');
			return $this->delivery;
		}

		$payload = [
			'user_feed_id' => $userFeedId,
			'user_id' => $userId,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		];
		$action = $this->repository->insert('user_feed_views', $payload);
		$this->delivery->data = ' ok';
		return $this->delivery;
	}

	private function validateThumbnailFile ($filename) {
		$result = new \stdClass;
		$result->file_type = null;
		if (!isset($_FILES[$filename]) || empty($_FILES[$filename]['tmp_name'])) {
			$result->file_type = 'product_feed';
			$this->delivery->data = $result;
			return $this->delivery;
		}

		$rawFile = $_FILES[$filename]['tmp_name'];
		$getID3 = new \getID3();
		$formattedFile = $getID3->analyze($rawFile);
		
		if (isset($formattedFile['error']) && !empty($formattedFile['error'])) {
			$this->delivery->addError(400, $formattedFile['error'][0]);
			return $this->delivery;
		}
		
		if (strpos($formattedFile['mime_type'], 'image') !== false) {
			$result->file_type = 'image';
		} else if (strpos($formattedFile['mime_type'], 'video') !== false) {
			$result->file_type = 'video';
		} else {
			$this->delivery->addError(400, 'File is not supported');
			return $this->delivery;
		}

		if ($result->file_type == 'video') {
			if ($formattedFile['playtime_seconds'] >= 30) {
				$this->delivery->addError(400, 'Video maximum duration is 30 seconds');
			}
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

}