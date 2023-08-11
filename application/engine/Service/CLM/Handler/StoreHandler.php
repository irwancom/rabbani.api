<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Handler\ShippingLocationHandler;
use Library\JNEService;

class StoreHandler {

	private $delivery;
	private $repository;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->shippingLocationHandler = new ShippingLocationHandler($this->repository);
	}

	public function listStatusOrderStore(){
		$isStatus = array();
		$isStatus[] = ['name'=>'On Cart', 'slug'=>'on_cart', 'style'=>'light'];
		$isStatus[] = ['name'=>'By Order', 'slug'=>'by_order', 'style'=>'info'];
		$isStatus[] = ['name'=>'Process', 'slug'=>'in_process', 'style'=>'info'];
		$isStatus[] = ['name'=>'Shipment', 'slug'=>'in_shipment', 'style'=>'primary'];
		$isStatus[] = ['name'=>'Delivered', 'slug'=>'delivered', 'style'=>'teal'];
		$isStatus[] = ['name'=>'Finish', 'slug'=>'completed', 'style'=>'success'];
		$isStatus[] = ['name'=>'Cancel', 'slug'=>'canceled', 'style'=>'danger'];
		return $isStatus;
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

	public function getProvinces ($filters = null) {
		$join = null;
		$select = null;
		$groupBy = null;
		if (empty($this->getAdmin())) {
			$select = [
				'provinces.id',
				'provinces.name',
				'COUNT(stores.id) as total_store'
			];
			$join = [
				'districts' => 'provinces.id = districts.id_prov',
				'stores' => 'stores.id_kab = districts.id_kab'
			];
			$filters['stores.deleted_at'] = NULL;
			$groupBy = 'provinces.id';

		}
		$orderKey = 'provinces.name';
		$orderValue = 'ASC';
		$provinces = $this->repository->find('provinces', $filters, null, $join, $select, $groupBy, null, $orderKey, $orderValue);
		$this->delivery->data = $provinces;
		return $this->delivery;
	}

	public function getDistricts ($filters = null) {
		$join = null;
		$select = null;
		$groupBy = null;
		if (empty($this->getAdmin())) {
			$select = [
				'districts.id_prov',
				'districts.id_kab',
				'districts.nama',
				'COUNT(stores.id) as total_store'
			];
			$join = [
				'stores' => 'stores.id_kab = districts.id_kab'
			];
			$filters['stores.deleted_at'] = NULL;
			$groupBy = 'districts.id_kab';
		}

		if (isset($filters['id_prov']) && !empty($filters['id_prov'])) {
			$filters['districts.id_prov'] = $filters['id_prov'];
			unset($filters['id_prov']);
		}

		$orderKey = 'districts.nama';
		$orderValue = 'ASC';

		$provinces = $this->repository->find('districts', $filters, null, $join, $select, $groupBy, null, $orderKey, $orderValue);
		$this->delivery->data = $provinces;
		return $this->delivery;
	}

	public function getSubDistricts ($filters = null) {
		$join = null;
		$select = null;
		$groupBy = null;
		if (empty($this->getAdmin())) {
		}

		if (isset($filters['id_kab']) && !empty($filters['id_kab'])) {
			$filters['sub_district.id_kab'] = $filters['id_kab'];
			unset($filters['id_prov']);
		}

		$orderKey = 'sub_district.nama';
		$orderValue = 'ASC';

		$provinces = $this->repository->find('sub_district', $filters, null, $join, $select, $groupBy, null, $orderKey, $orderValue);
		$this->delivery->data = $provinces;
		return $this->delivery;
	}

	public function getUrbanVillages ($filters = null) {
		$join = null;
		$select = null;
		$groupBy = null;
		if (empty($this->getAdmin())) {
		}

		if (isset($filters['id_kec']) && !empty($filters['id_kec'])) {
			$filters['urban_village.id_kec'] = $filters['id_kec'];
			unset($filters['id_kec']);
		}

		$orderKey = 'urban_village.nama';
		$orderValue = 'ASC';

		$provinces = $this->repository->find('urban_village', $filters, null, $join, $select, $groupBy, null, $orderKey, $orderValue);
		$this->delivery->data = $provinces;
		return $this->delivery;
	}

	public function getStores ($filters = null, $isRaw = false, $groupByLocation = false) {
		$orderKey = 'stores.name';
		$orderValue = 'ASC';

		$filterOrWhere = [];

		if (isset($filters['id_kab']) && !empty($filters['id_kab'])) {
			$filters['stores.id_kab'] = $filters['id_kab'];
			unset($filters['id_kab']);
		}

		if (isset($filters['id']) && !empty($filters['id'])) {
			$filters['stores.id'] = $filters['id'];
			unset($filters['id']);
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			$filterOrWhere['stores.name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$filterOrWhere['stores.address'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}
		unset($filters['q']);
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
			unset($filters['order_key']);
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
			unset($filters['order_value']);
		}
		$join = [
			'districts' => [
				'value' => 'districts.id_kab = stores.id_kab',
				'type' => 'left'
			],
			'provinces' => [
				'value' => 'provinces.id = districts.id_prov',
				'type' => 'left'
			]
		];
		$select = [
			'stores.*',
			'provinces.name as nama_provinsi',
			'districts.nama as nama_kabupaten'
		];
		$stores = $this->repository->find('stores', $filters, $filterOrWhere, $join, $select, null, null, $orderKey, $orderValue);
		if (!$isRaw) {
			foreach($stores as $store){
				$store->location = $this->handleLocationStore($store);
			}
		}
		if ($groupByLocation) {
			$groupStore = [];
			$centralStores = [];
			foreach ($stores as $store) {
				$rawStore = [
					'id' => $store->id,
					'name' => $store->name,
					'address' => $store->address,
					'kabupaten' => $store->nama_kabupaten,
				];
				if ($store->is_central == 1) {
					$centralStores[] = $rawStore;
					continue;
				}
				$key = array_search($store->nama_provinsi, array_column($groupStore, 'provinsi'));
				if ($key === false) {
					$group = [
						'provinsi' => $store->nama_provinsi,
						'stores' => [$rawStore]
					];
					$groupStore[] = $group;
				} else {
					$groupStore[$key]['stores'][] = $rawStore;
				}
			}
			$groupStore[] = [
				'provinsi' => 'ONLINE',
				'stores' => $centralStores,
			];
			$stores = $groupStore;
		}
		$this->delivery->data = $stores;
		return $this->delivery;
	}

	public function getStoresPage ($filters = null) {
		$args = [];
		$filterOrWhere = [];

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['name'] = [
				'condition' => 'like',
				'value' => $filters['name']
			];
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			$filterOrWhere['stores.name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$filterOrWhere['stores.address'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}
		unset($filters['q']);

		if (isset($filters['id_kab']) && !empty($filters['id_kab'])) {
			$args['id_kab'] = (int)$filters['id_kab'];
		}

		if (isset($filters['is_publish']) && in_array($filters['is_publish'], ['0','1'])) {
			$args['is_publish'] = (int)$filters['is_publish'];
		}
		if (isset($filters['is_publish_multi']) && in_array($filters['is_publish_multi'], ['0','1'])) {
			$args['is_publish_multi'] = (int)$filters['is_publish_multi'];
		}

		if (isset($filters['is_use_central_stock']) && in_array($filters['is_use_central_stock'], ['0','1'])) {
			$args['is_use_central_stock'] = (int)$filters['is_use_central_stock'];
		}

		if (isset($filters['is_central']) && in_array($filters['is_central'], ['0','1'])) {
			$args['is_central'] = $filters['is_central'];
		}

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			unset($args['data']);
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			unset($args['page']);
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$select = [
			'stores.id',
			'stores.id_kab',
			'stores.name',
			'stores.address',
			'stores.target',
			'stores.code',
			'stores.is_publish',
			'stores.is_central',
			'stores.is_use_central_stock',
			'stores.subdistrict_id',
			'stores.created_at',
			'stores.updated_at',
		];
		$orderKey = 'stores.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$stores = $this->repository->findPaginated('stores', $args, $filterOrWhere, null, $select, $offset, $limit, $orderKey, $orderValue);
		foreach($stores['result'] as $store){
			$store->location = $this->handleLocationStore($store);
		}
		$this->delivery->data = $stores;
		return $this->delivery;
	}

	public function getStore ($filters = null) {
		$store = $this->repository->findOne('stores', $filters);
		$this->delivery->data = $store;
		return $this->delivery;
	}

	public function createStore ($payload) {
		if (!empty($this->getAdmin())) {
			$payload['id_auth'] = $this->admin['id_auth'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['id_kab']) || empty($payload['id_kab'])) {
			$this->delivery->addError(400, 'Kabupaten is required');
		}

		if (!isset($payload['name']) || empty($payload['name'])) {
			$this->delivery->addError(400, 'Store is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$kabupaten = $this->repository->findOne('districts', ['id_kab' => $payload['id_kab']]);
		if (empty($kabupaten)) {
			$this->delivery->addError(400, 'Kabupaten is required');
			return $this->deivery;
		}

		$existsStore = $this->repository->findOne('stores', ['name' => $payload['name']]);
		if (!empty($existsStore)) {
			$this->delivery->addError(400, 'Store already exists');
			return $this->delivery;
		}

		try {
			unset($payload['id_auth']);
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('stores', $payload);
			$covers = $this->repository->findOne('stores', ['id' => $action]);
			$this->delivery->data = $covers;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateStore ($payload, $filters = null) {
		try {
			if (isset($payload['is_central']) && (int)$payload['is_central'] == 1) {
				$action = $this->repository->update('stores', ['is_central' => 0]);
			}
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('stores', $payload, $filters);
			$result = $this->repository->findOne('stores', $filters);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteStore ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('stores', $payload, ['id' => $id]);
			$result = $this->repository->findOne('stores', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function getAgents ($filters = null) {
		$args = [];
		$args = $filters;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			unset($args['data']);
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			unset($args['page']);
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$select = [
			'store_agents.id',
			'store_agents.store_code',
			'store_agents.name',
			'store_agents.nik',
			'store_agents.phone_number',
			'store_agents.referral_code',
			'store_agents.created_at',
			'store_agents.updated_at',
		];
		$orderKey = 'store_agents.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			unset($args['order_key']);
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			unset($args['order_value']);
			$orderValue = $filters['order_value'];
		}

		$stores = $this->repository->findPaginated('store_agents', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $stores;
		return $this->delivery;
	}

	public function getAgent ($filters = null) {
		$store = $this->repository->findOne('store_agents', $filters);
		$this->delivery->data = $store;
		return $this->delivery;
	}

	public function createAgent ($payload) {
		if (!empty($this->getAdmin())) {
			$payload['id_auth'] = $this->admin['id_auth'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['store_code']) || empty($payload['store_code'])) {
			$this->delivery->addError(400, 'Store is required');
		}

		if (isset($payload['nik'])) {
			$existsByNik = $this->repository->findOne('store_agents', ['nik' => $payload['nik']]);
			if (!empty($existsByNik)) {
				$this->delivery->addError(400, 'Agent already exists');
				return $this->delivery;
			}
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		try {
			unset($payload['id_auth']);
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('store_agents', $payload);
			$covers = $this->repository->findOne('store_agents', ['id' => $action]);
			$updateData['referral_code'] = $this->generateReferralCode($payload['name'], $action);
			$referralAction = $this->updateAgent($updateData, ['id' => $action]);
			$this->delivery->data = $covers;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateAgent ($payload, $filters = null) {
		try {
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('store_agents', $payload, $filters);
			$result = $this->repository->findOne('store_agents', $filters);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteAgent ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('store_agents', $payload, ['id' => $id]);
			$result = $this->repository->findOne('store_agents', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function getStocks ($filters = null) {
		$args = [];
		$args = $filters;
		$argsOrWhere = [];

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			unset($args['data']);
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			unset($args['page']);
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			unset($args['q']);
			$argsOrWhere['store_product_detail_stocks.sku_code'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		$select = [
			'store_product_detail_stocks.id',
			'stores.id as store_id',
			'store_product_detail_stocks.store_code',
			'store_product_detail_stocks.sku_code',
			'store_product_detail_stocks.stock',
			'store_product_detail_stocks.created_at',
			'store_product_detail_stocks.updated_at',
			'product_details.variable as product_detail_variable',
			'product_details.image_path as product_detail_image',
			'product_details.price as product_detail_price',
			'product.product_name',
			'product.sku as product_sku',
			'product.specifications as product_specification',
		];
		$orderKey = 'store_product_detail_stocks.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			unset($args['order_key']);
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			unset($args['order_value']);
			$orderValue = $filters['order_value'];
		}

		$join = [
			'stores' => [
				'type' => 'left',
				'value' => 'stores.code = store_product_detail_stocks.store_code AND stores.deleted_at IS NULL'
			],
			'product_details' => [
				'type' => 'left',
				'value' => 'product_details.sku_code = store_product_detail_stocks.sku_code'
			],
			'product' => [
				'type' => 'left',
				'value' => 'product.id_product = product_details.id_product'
			]
		];

		$stores = $this->repository->findPaginated('store_product_detail_stocks', $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue);
		foreach($stores['result'] as $store){
			if($store->product_detail_variable && isJson($store->product_detail_variable)){
                $store->product_detail_variable = json_decode($store->product_detail_variable);
            }
            if($store->product_specification && isJson($store->product_specification)){
                $store->product_specification = json_decode($store->product_specification);
            }
		}
		$this->delivery->data = $stores;
		return $this->delivery;
	}

	public function getStock ($filters = null) {
		$store = $this->repository->findOne('store_product_detail_stocks', $filters);
		$this->delivery->data = $store;
		return $this->delivery;
	}

	public function createStock ($payload) {
		if (!empty($this->getAdmin())) {
			$payload['id_auth'] = $this->admin['id_auth'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (!isset($payload['store_code']) || empty($payload['store_code'])) {
			$this->delivery->addError(400, 'Store is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$existsStock = $this->repository->findOne('store_product_detail_stocks', ['store_code' => $payload['store_code'], 'sku_code' => $payload['sku_code']]);
		if (!empty($existsStock)) {
			$this->delivery->addError(400, 'Stock already exists');
			return $this->delivery;
		}

		try {
			unset($payload['id_auth']);
			$payload['created_at'] = date('Y-m-d H:i:s');
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('store_product_detail_stocks', $payload);
			$covers = $this->repository->findOne('store_product_detail_stocks', ['id' => $action]);

			$store = $this->repository->findOne('stores', ['code' => $payload['store_code']]);
			if (!empty($store) && $store->is_central == 1) {
				$productDetail = $this->repository->findOne('product_details', ['sku_code' => $payload['sku_code']]);
				if (!empty($productDetail)) {
					$productData = [
						'stock' => $payload['stock']
					];
					$action = $this->repository->update('product_details', $productData, ['id_product_detail' => $productDetail->id_product_detail]);
				}
			}

			$this->delivery->data = $covers;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateStock ($payload, $filters = null) {
		try {
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('store_product_detail_stocks', $payload, $filters);
			$result = $this->repository->find('store_product_detail_stocks', $filters);

			$centralStore = $this->repository->findOne('stores', ['is_central' => 1]);
			foreach ($result as $res) {
				if ($centralStore->code == $res->store_code) {
					$productDetail = $this->repository->findOne('product_details', ['sku_code' => $res->sku_code]);
					if (!empty($productDetail)) {
						$productData = [
							'stock' => $res->stock
						];
						$action = $this->repository->update('product_details', $productData, ['id_product_detail' => $productDetail->id_product_detail]);
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

	public function deleteStock ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('store_product_detail_stocks', $payload, ['id' => $id]);
			$result = $this->repository->findOne('store_product_detail_stocks', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	private function generateReferralCode ($name, $id) {
		return strtoupper(generateRandomString(6));
	}

	public function handleLocationStore($store){
		$resultLoc = null;
		if($store->subdistrict_id && !empty($store->subdistrict_id) && !is_null($store->subdistrict_id)){
			$joinSubdistrict = [
				'districts' => [
					'type' => 'left',
					'value' => 'districts.id_kab = sub_district.id_kab'
				],
				'provinces' => [
					'type' => 'left',
					'value' => 'provinces.id = districts.id_prov'
				]
			];
			$selectSubdistrict = [
	            'sub_district.id_kec as sub_district_id', 'sub_district.nama as sub_district_name',
	            'districts.id_kab as district_id','districts.nama as district_name',
	            'provinces.id as province_id','provinces.name as province_name'
	        ];
			$location = $this->repository->findOne('sub_district', ['sub_district.id_kec'=>$store->subdistrict_id], null, $joinSubdistrict, $selectSubdistrict);
		}else{
			$selectDistrict = [
	            'districts.id_kab as district_id','districts.nama as district_name',
	            'provinces.id as province_id','provinces.name as province_name'
	        ];
	        $joinDistrict = ['provinces' => 'provinces.id = districts.id_prov'];
			$location = $this->repository->findOne('districts', ['districts.id_kab'=>$store->id_kab], null, $joinDistrict, $selectDistrict);
		}
		
		if($location && !is_null($location)){
			$subdistrictId = null; $subdistrictName = null; $originStore = null;
			if(isset($location->sub_district_id)){
				$cekStoreOrigin = $this->shippingLocationHandler->originFromSubdistrict($location->sub_district_id);
				$originStore = $cekStoreOrigin->data;
				$subdistrictId = $location->sub_district_id;
				$subdistrictName = $location->sub_district_name;
			}

			if(!$originStore || is_null($originStore)){
				$cekStoreOrigin = $this->shippingLocationHandler->originFromDistrict($location->district_id);
				if(!$cekStoreOrigin->data || empty($cekStoreOrigin->data) || is_null($cekStoreOrigin->data)){
					if(isset($location->sub_district_id)){
						$cekStoreOrigin = $this->shippingLocationHandler->destinationFromSubdistrict($location->sub_district_id);
						if(!$cekStoreOrigin->data || empty($cekStoreOrigin->data) || is_null($cekStoreOrigin->data)){
							$cekStoreOrigin = $this->shippingLocationHandler->destinationFromDistrict($location->district_id);
						}
					}
				}
				$originStore = $cekStoreOrigin->data;
			}
			$location->sub_district_id = $subdistrictId;
			$location->sub_district_name = $subdistrictName;
			$location->origin = $originStore;
			$resultLoc = $location;
		}
		return $resultLoc;
	}

	public function getOrderStore($storeId = null, $orderCode = null, $payload = [], $withProduct = true, $skipStore = false){
		$statusList = $this->listStatusOrderStore();

		if(!$skipStore){
			if(!$storeId || empty($storeId) || is_null($storeId)){
	            $this->delivery->addError(400, 'Store ID is required'); return $this->delivery;
	        }
		}
		
        $isDetail = ($orderCode && !empty($orderCode) && !is_null($orderCode)) ? true : false;

        $sort = array('id'=>'crst_id','updated'=>'updated_at','created'=>'created_at');
        $orderBy = 'updated_at'; $orderVal = 'DESC';

        if(isset($payload['order_by']) && $payload['order_by'] && !empty($payload['order_by']) && !is_null($payload['order_by'])){
            $isOrderBy = strtolower($payload['order_by']);
            $orderBy = (isset($sort[$isOrderBy])) ? $sort[$isOrderBy] : $sortBy;
        }
        if(isset($payload['order_value']) && $payload['order_value'] && !empty($payload['order_value']) && !is_null($payload['order_value'])){
            $isOrderValue = strtoupper($payload['order_value']);
            $orderVal = ($isOrderValue=='ASC' || $isOrderValue=='DESC') ? $isOrderValue : $sortVal;
        }

        $offset = 0; $limit = 20;
		if (isset($payload['data']) && $payload['data'] && !empty($payload['data']) && !is_null($payload['data'])) {
			$limit = intval($payload['data']);
		}
		if (isset($payload['page']) && $payload['page'] && !empty($payload['page']) && !is_null($payload['page'])) {
			$offset = (intval($payload['page'])-1) * $limit;
		}

        $select = [
            'cart_stores.crst_id as id',
            'cart_stores.crst_note as note',

            'cart_stores.crst_weight as total_weight',
            'cart_stores.crst_qty as total_qty',
            'cart_stores.crst_subtotal as subtotal',
            'cart_stores.crst_discount as discount',
            'cart_stores.crst_shipping as shipping',
            'cart_stores.crst_total as total',

            'cart_stores.crst_status as status',
            'cart_stores.crst_awb_no as awb_nomor',
            'cart_stores.created_at as created',
            'cart_stores.updated_at as updated',

            'cart_stores.crst_store as store_id',
            'cart_stores.crst_store_detail as store_detail',

            'cart_stores.crst_user as user_id',
            'users.phone as user_phone',
            'users.username as user_username',
            'users.first_name as user_name',
            'cart_stores.crst_address as user_address',
            'cart_stores.crst_address_detail as user_address_detail',

            'orders.id_order as order_id',
            'cart_stores.crst_order as order_code',
            'orders.status as order_status',
            'orders.invoice_number as order_invoice_number',
            'orders.order_source as order_source',
            'orders.order_info as order_info',
            'orders.created_at as order_created',
            'orders.updated_at as order_updated',
            'orders.is_paid as order_paid',
            'orders.paid_at as order_paid_at',
            'orders.is_completed as order_completed',
            'orders.completed_at as order_completed_at',
            'orders.donation as order_donation',

            'orders.payment_method_code as payment_method',
            'orders.payment_method_name as payment_name',
            'orders.payment_reference_no as payment_reference',
            'orders.payment_fee_total as payment_fee',
            'orders.payment_method_instruction as payment_instruction',

            'cart_stores.crst_shipment as shipment',
            'orders.voucher_discount_amount as voucher_discount_amount',
            'cart_stores.crst_item as products',
        ];

        $condition = [
            'cart_stores.crst_order !='=>NULL,
            //'cart_stores.crst_store'=>$storeId,
            'cart_stores.crst_status >'=>0,
            'cart_stores.deleted_at'=>NULL
        ];
        if(!$skipStore){
        	$condition['cart_stores.crst_store'] = $storeId;
        }
        if($isDetail){
            $condition['cart_stores.crst_order'] = $orderCode;
        }

        if(isset($payload['date_from']) && $payload['date_from'] && !empty($payload['date_from']) && !is_null($payload['date_from']) && strtotime($payload['date_from'])){
            $condition['DATE(cart_stores.created_at) >='] = date('Y-m-d H:i:s', strtotime($payload['date_from']));
        }
        if(isset($payload['date_to']) && $payload['date_to'] && !empty($payload['date_to']) && !is_null($payload['date_to']) && strtotime($payload['date_to'])){
            $condition['DATE(cart_stores.created_at) <='] = date('Y-m-d H:i:s', strtotime($payload['date_to']));
        }

        if (isset($payload['status']) && $payload['status'] && !empty($payload['status']) && !is_null($payload['status'])) {
        	if(is_numeric($payload['status']) && intval($payload['status'])>0){
        		$condition['cart_stores.crst_status'] = intval($payload['status']);
        	}else{
        		$cekFilterStatus = array_search($payload['status'], array_column($statusList, 'slug'));
        		if($cekFilterStatus && intval($cekFilterStatus)>1){
        			$condition['cart_stores.crst_status'] = intval($cekFilterStatus);
        		}else{
        			$condition['orders.status'] = $payload['status'];
        		}
        	}
        }

        $join = [
			'users' => [
				'type' => 'left',
				'value' => 'users.id=cart_stores.crst_user'
			],
			'orders' => [
				'type' => 'left',
				'value' => 'orders.order_code=cart_stores.crst_order'
			]
		];

		$conditionOr = [];
		if (isset($payload['q']) && $payload['q'] && !empty($payload['q']) && !is_null($payload['q'])) {
			$conditionOr['cart_stores.crst_order'] = [
				'condition' => 'like',
				'value' => $payload['q']
			];
			$conditionOr['orders.invoice_number'] = [
				'condition' => 'like',
				'value' => $payload['q']
			];
			$conditionOr['users.first_name'] = [
				'condition' => 'like',
				'value' => $payload['q']
			];
		}

		if($isDetail){
			$orders = $this->repository->find('cart_stores', $condition, $conditionOr, $join, $select);
			$loadOrder = $orders;
		}else{
			$orders = $this->repository->findPaginated('cart_stores', $condition, $conditionOr, $join, $select, $offset, $limit, 'cart_stores.'.$orderBy, $orderVal, null, $join);
			$loadOrder = $orders['result'];
		}
		if(!$loadOrder || is_null($loadOrder)){
			$this->delivery->addError(400, 'Transaction not found'); return $this->delivery;
		}

        foreach($loadOrder as $order){
        	$cekTrackingOrder = $this->checkTrackingOrderStore($order);
        	if($cekTrackingOrder && !is_null($cekTrackingOrder)){
        		if(isset($cekTrackingOrder['status']) && $cekTrackingOrder['status'] && !is_null($cekTrackingOrder['status']) && is_numeric($cekTrackingOrder['status'])){
        			$order->status = intval($cekTrackingOrder['status']);
        		}
        	}
        	
        	$order->order_code_store = $order->order_code.'-'.$order->id;
        	
			if($order->shipment && !empty($order->shipment) && !is_null($order->shipment)){
                $order->shipment = json_decode($order->shipment);
                if(!isset($order->shipment->tracking)){
                	$order->shipment->tracking = array('type'=>null, 'awb_nomor'=>null);
                }
            }
            if($order->store_detail && !empty($order->store_detail) && !is_null($order->store_detail)){
                $order->store_detail = json_decode($order->store_detail);
            }
            if($order->user_address_detail && !empty($order->user_address_detail) && !is_null($order->user_address_detail)){
                $order->user_address_detail = json_decode($order->user_address_detail);
            }
            if($order->payment_instruction && !empty($order->payment_instruction) && !is_null($order->payment_instruction)){
                if(isJson($order->payment_instruction)){
                    $order->payment_instruction = json_decode($order->payment_instruction);
                }
            }

            $discountVoucher = 0;
           	if(isset($order->voucher_discount_amount) && $order->voucher_discount_amount && !empty($order->voucher_discount_amount) && !is_null($order->voucher_discount_amount)){
           		$discountVoucher = abs(intval($order->voucher_discount_amount));
           	}

            $setQty = 0; $setSubtotal = 0; $setPrice = 0; $setDiscount = 0; $setTotal = 0; 
            $orderProduct = array();
            if($order->products && !empty($order->products) && !is_null($order->products)){
                $order->products = json_decode($order->products);
                foreach($order->products as $item){
                	$itemQty = $item->qty;
                    $itemPrice = $item->price;
                    $itemSubtotal = $item->subtotal;
                    $itemDiscount = ($item->discount && !empty($item->discount) && !is_null($item->discount)) ? abs(intval($item->discount)) : 0;
                    $itemTotal = $item->total;

                    if($withProduct){
                    	$detailId = $item->id_product_detail;
	                    $selectDetail = [
	                        'order_details.id_order_detail',
	                        'order_details.id_product_detail',
	                        'product_details.id_product as id_product',
	                        'order_details.qty',
	                        'order_details.price',
	                        'order_details.discount_amount',
	                        'order_details.discount_type',
	                        'order_details.discount_source',
	                        'order_details.discount_value',
	                        'order_details.subtotal',
	                        'order_details.total',
	                        'product_details.sku_code as product_detail_sku_code',
	                        'product_details.sku_barcode as product_detail_sku_barcode',
	                        'product_details.host_path as product_detail_host_path',
	                        'product_details.image_path as product_detail_image_path',
	                        'product_details.variable as product_detail_variable',
	                        'product.product_name as product_name',
	                        'product.sku as product_sku',

	                        'product.weight as product_weight',
							'product.length as product_length',
							'product.height as product_height',

	                        'order_details.sku_code',
	                    ];
	                    $conditionDetail = [
	                        'order_details.order_code' => $order->order_code,
	                        'order_details.id_product_detail'=>$detailId,
	                        'order_details.deleted_at'=>NULL,
	                    ];
	                    $joinDetail = [
							'product_details' => [
								'type' => 'left',
								'value' => 'product_details.id_product_detail = order_details.id_product_detail OR product_details.sku_code = order_details.sku_code'
							],
							'product' => [
								'type' => 'left',
								'value' => 'product.id_product = product_details.id_product'
							]
						];
	                    $cekDetail = $this->repository->findOne('order_details', $conditionDetail, null, $joinDetail, $selectDetail);
	                    if($cekDetail && !is_null($cekDetail)){
	                        if($cekDetail->product_detail_variable && !empty($cekDetail->product_detail_variable) && !is_null($cekDetail->product_detail_variable)){
	                            if(isJson($cekDetail->product_detail_variable)){
	                                $cekDetail->product_detail_variable = json_decode($cekDetail->product_detail_variable);
	                            }
	                        }
	                        $orderProduct[] = $cekDetail;
	                        $itemQty = $cekDetail->qty;
	                        $itemPrice = $cekDetail->price;
	                        $itemSubtotal = $cekDetail->subtotal;
	                        $itemDiscount = 0;
	                        if($cekDetail->discount_amount && !empty($cekDetail->discount_amount) && !is_null($cekDetail->discount_amount)){
	                            $itemDiscount = abs(intval($cekDetail->discount_amount));
	                        }
	                        $itemTotal = $cekDetail->total;
	                    }
                    }

                    $setQty += $itemQty;
                    $setPrice += $itemPrice;
                    $setSubtotal += $itemSubtotal;
                    $setDiscount += $itemDiscount; 
                    $setTotal += $itemTotal; 
                }
            }

            if($withProduct){
            	$order->products = ($orderProduct && !is_null($orderProduct)) ? $orderProduct : null;
            }

            $totalDiscount = intval($setDiscount) + intval($discountVoucher);
            $isTotalOrder = intval($setTotal) - intval($discountVoucher);
            $finalTotal = intval($isTotalOrder)+intval($order->shipping);
            
            $order->detail = array();
            $order->detail['qty'] = $setQty;
            $order->detail['price'] = $setPrice;
            $order->detail['subtotal'] = $setSubtotal;
            $order->detail['discount_item'] = $setDiscount;
            $order->detail['discount_voucher'] = $discountVoucher;
            $order->detail['discount'] = $totalDiscount;
            $order->detail['total'] = $setTotal;
            $order->detail['shipping'] = $order->shipping;
            $order->detail['weight'] = $order->total_weight;
            $order->detail['final_total'] = $finalTotal;
            //unset($order->shipping, $order->total_weight);
		}

		if(!$isDetail){
			$orders['result'] = $loadOrder;
		}
		$isResult = ($isDetail) ? $loadOrder[0] : $orders;
		$this->delivery->data = $isResult;
		return $this->delivery;
	}

	public function checkTrackingOrderStore($order = null){
		$resTracking = null; $statusData = null;
		if($order->status==3 && ($order->awb_nomor && !empty($order->awb_nomor) && !is_null($order->awb_nomor)) ){
			$jneService = new JNEService();
	        $jneService->setEnv('production');
	        $tracking = $jneService->getTraceTracking($order->awb_nomor);

	        if(isset($tracking['cnote']) && $tracking['cnote'] && !empty($tracking['cnote']) && !is_null($tracking['cnote'])){
	        	if(isset($tracking['cnote']['pod_status'])){
	        		$statusTrack = $tracking['cnote']['pod_status'];
	        		if($statusTrack=='DELIVERED'){
	        			$statusData = 4;
	        		}
	        	}

	        	if($statusData && !is_null($statusData) && is_numeric($statusData)){
		        	$action = $this->repository->update('cart_stores', ['crst_status'=>$statusData], ['crst_id'=>$order->id]);
		        }
		        $resTracking =  array('status'=>$statusData, 'data'=>$tracking);
	        }
    	}
    	return $resTracking;
	}

	//======================== Admin Store ========================//

	public function getAdminList ($filters = null) {
		if(!isset($filters['admin_store']) || !$filters['admin_store'] || empty($filters['admin_store']) || is_null($filters['admin_store'])){
            $this->delivery->addError(400, 'Store ID is required'); return $this->delivery;
        }

		if(isset($filters['id']) && $filters['id'] && !empty($filters['id']) && !is_null($filters['id'])){
			return $this->getAdminDetail($filters);
		}
		$args = [];
		$args = $filters;
		$filterOrWhere = [];

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			unset($args['data']);
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			unset($args['page']);
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$select = [
			'admins.id',
			'admins.username',
			'admins.email',
			'admins.phone',
			'admins.first_name',
			'admins.last_name',
			'admins.company',
			'admins.inventory_role',
			'admins.admin_store',
		];
		$orderKey = 'admins.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			unset($args['order_key']);
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			unset($args['order_value']);
			$orderValue = $filters['order_value'];
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			$filterOrWhere['admins.first_name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$filterOrWhere['admins.phone'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$filterOrWhere['admins.email'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}
		unset($args['q']);

		$adminStore = $this->repository->findPaginated('admins', $args, $filterOrWhere, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $adminStore;
		return $this->delivery;
	}

	public function getAdminDetail ($filters = null) {
		$adminStore = $this->repository->findOne('admins', $filters);
		$this->delivery->data = $adminStore;
		return $this->delivery;
	}

	public function updateAdminStore ($payload) {
		if(!isset($payload['admin_store']) || !$payload['admin_store'] || empty($payload['admin_store']) || is_null($payload['admin_store'])){
            $this->delivery->addError(400, 'Store ID is required'); return $this->delivery;
        }
        $storeId = intval($payload['admin_store']);
        $sendData = array('admin_store'=>$storeId);

        if (empty($this->admin)) {
			$this->delivery->addError(400, 'Auth required'); return $this->delivery;
		} else {
			$sendData['id_auth'] = $this->admin['id_auth'];
		}

		if ($this->admin['inventory_role'] != 'head') {
			$this->delivery->addError(400, 'Not Allowed, access only for head role'); return $this->delivery;
		}

        $staf = false; $isAdminId = null;
        if(isset($payload['admin_id']) && $payload['admin_id'] && !empty($payload['admin_id']) && !is_null($payload['admin_id'])){
            $staf = $this->repository->findOne('admins', ['id'=>$payload['admin_id'],'admin_store'=>$payload['admin_store']]);
            if(!$staf || empty($staf) || is_null($staf)){
            	$this->delivery->addError(400, 'Admin/Staf store not found'); return $this->delivery;
            }
            $isAdminId = $staf->id;
        }

        if(!isset($payload['first_name']) || !$payload['first_name'] || empty($payload['first_name']) || is_null($payload['first_name'])){
        	$this->delivery->addError(400, 'Name is required'); return $this->delivery;
        }
        $sendData['first_name'] = $payload['first_name'];
        if(isset($payload['last_name']) && $payload['last_name'] && !empty($payload['last_name']) && !is_null($payload['last_name'])){
        	$sendData['last_name'] = $payload['last_name'];
        }

        if(!isset($payload['email']) || !$payload['email'] || empty($payload['email']) || is_null($payload['email'])){
        	$this->delivery->addError(400, 'Email is required'); return $this->delivery;
        }

        $filterEmail = ['email' => $payload['email']];
        if($staf){
        	$filterEmail['id !='] = $staf->id; 
        }
        $existEmail = $this->repository->findOne('admins', $filterEmail);
        if (!empty($existEmail)) {
            $this->delivery->addError(409, 'Email already taken'); return $this->delivery;
        }
        $sendData['email'] = $payload['email'];

        if(!isset($payload['phone']) || !$payload['phone'] || empty($payload['phone']) || is_null($payload['phone'])){
        	$this->delivery->addError(400, 'Phone is required'); return $this->delivery;
        }

        $filterPhone = ['phone' => $payload['phone']];
        if($staf){
        	$filterPhone['id !='] = $staf->id; 
        }
        $existPhone = $this->repository->findOne('admins', $filterPhone);
        if (!empty($existPhone)) {
            $this->delivery->addError(409, 'Phone already taken'); return $this->delivery;
        }
        $sendData['phone'] = $payload['phone'];

        $roleAccess = ['head','manager','staff'];
        if(!isset($payload['inventory_role']) || !in_array($payload['inventory_role'], $roleAccess) ){
        	$this->delivery->addError(400, 'Role is not valid'); return $this->delivery;
        }
        $sendData['inventory_role'] = $payload['inventory_role'];

        $company = 'Rabbani';
        if(isset($payload['company']) && $payload['company'] && !empty($payload['company']) && !is_null($payload['company'])){
        	$company = $payload['company'];
        }
        $sendData['company'] = $company;

        if(!$staf){
        	if(!isset($payload['username']) || !$payload['username'] || empty($payload['username']) || is_null($payload['username'])){
	        	$this->delivery->addError(400, 'Username is required'); return $this->delivery;
	        }
	        $existUsername = $this->repository->findOne('admins', ['username' => $payload['username']]);
	        if (!empty($existUsername)) {
	            $this->delivery->addError(409, 'Username already taken'); return $this->delivery;
	        }
	        $sendData['username'] = $payload['username'];

	        if(!isset($payload['password']) || !$payload['password'] || empty($payload['password']) || is_null($payload['password'])){
	        	$this->delivery->addError(400, 'Password is required'); return $this->delivery;
	        }
	        if (strlen($payload['password']) != 6) {
	            $this->delivery->addError(409, 'Password is not correct (6 character)');
	        }
	        $sendData['password'] = password_hash($payload['password'], PASSWORD_DEFAULT);
	        $sendData['created_on'] = time();
	        $sendData['secret'] = md5($sendData['username'] . $sendData['created_on']) . md5($sendData['created_on'] . $sendData['password']);
	        $action = $this->repository->insert('admins', $sendData);
	        $isAdminId = $action;
        }else{
        	if(isset($payload['password']) && $payload['password'] && !empty($payload['password']) && !is_null($payload['password'])){
	        	$sendData['password'] = password_hash($payload['password'], PASSWORD_DEFAULT);
	        }
	        $action = $this->repository->update('admins', $sendData, ['id'=>$isAdminId]);
        }
        return $this->getAdminDetail(['id'=>$isAdminId,'admin_store'=>$storeId]);
	}

	public function deleteAdminStore ($payload = []) {
		$currentDate = date('Y-m-d H:i:s');
		$sendData = array('deleted_at'=>$currentDate);
		if (empty($this->admin)) {
			$this->delivery->addError(400, 'Auth required'); return $this->delivery;
		} else {
			$sendData['id_auth'] = $this->admin['id_auth'];
		}

		$staf = $this->repository->findOne('admins', $payload);
        if(!$staf || empty($staf) || is_null($staf)){
        	$this->delivery->addError(400, 'Admin/Staf store not found'); return $this->delivery;
        }
        $action = $this->repository->update('admins', $sendData, ['id'=>$staf->id]);

        $staf->deleted_at = $currentDate;
        $this->delivery->data = $staf;
		return $this->delivery;
	}

//======================== End Line ========================//
}