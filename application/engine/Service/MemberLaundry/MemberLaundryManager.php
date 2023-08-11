<?php
namespace Service\MemberLaundry;

use Library\WablasService;
use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;

class MemberLaundryManager {

	const MAIN_WABLAS = '62895383334783';

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

	public function getMemberLaundries ($filters = null) {
		$args = [
			'member_laundries.id_auth_api' => $this->auth['id_auth']
		];

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['member_laundries.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_laundries.name'] = [
				'condition' => 'like',
				'value' => $filters['name']
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
			'member_laundries.id',
			'member_laundries.name',
			'member_laundries.phone_number',
			'member_laundries.provinsi',
			'member_laundries.kabupaten',
			'member_laundries.kecamatan',
			'member_laundries.kelurahan',
			'member_laundries.address',
			'member_laundries.wablas_phone_number_receiver',
			'member_laundries.created_at',
			'member_laundries.updated_at',
		];
		$orderKey = 'member_laundries.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$members = $this->repository->findPaginated('member_laundries', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberLaundry ($filters = null) {
		$argsOrWhere = null;
		if (isset($filters['iden'])) {
			$argsOrWhere = [
				'phone_number' => $filters['iden'],
				'id' => $filters['iden']
			];
			unset($filters['iden']);
		}
		$member = $this->repository->findOne('member_laundries', $filters, $argsOrWhere);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberLaundry ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];
		$existsMember = $this->repository->findOne('member_laundries', ['id_auth_api' => $this->auth['id_auth'], 'phone_number' => $payload['phone_number']]);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Member already exists.');
			return $this->delivery;
		}
		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('member_laundries', $payload);
		$result = $this->repository->findOne('member_laundries', ['id' => $action]);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberLaundries ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_laundries', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		unset($payload['phone_number']);
		unset($payload['id_auth_api']);
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundries', $payload, $filters);
		$result = $this->repository->find('member_laundries', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberLaundries ($filters = null) {
		$existsMembers = $this->repository->find('member_laundries', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundries', $payload, $filters);
		$result = $this->repository->find('member_laundries', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberLaundryAgents ($filters = null) {
		$args = $filters;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		if (!empty($this->auth)) {
			$args['id_auth_api'] = $this->auth['id_auth'];
		}
		$select = [
			'member_laundry_agents.id',
			'member_laundry_agents.phone_number',
			'member_laundry_agents.wablas_phone_number_receiver',
			'member_laundry_agents.active_booking_code',
			'member_laundry_agents.created_at',
			'member_laundry_agents.updated_at',
		];
		$orderKey = 'member_laundry_agents.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$members = $this->repository->findPaginated('member_laundry_agents', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberLaundryAgent ($filters = null) {
		$member = $this->repository->findOne('member_laundry_agents', $filters);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberLaundryAgent ($payload) {
		$args = [
			'phone_number' => $payload['phone_number']
		];
		if (!empty($this->auth)) {
			$args['id_auth_api'] = $this->auth['id_auth'];
			$payload['id_auth_api'] = $this->auth['id_auth'];
		}
		$existsMember = $this->repository->findOne('member_laundry_agents', $args);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Agent already exists.');
			return $this->delivery;
		}
		try {
			$payload['created_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('member_laundry_agents', $payload);
			$result = $this->repository->findOne('member_laundry_agents', ['id' => $action]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(400, $e->getMessage());
			return $this->delivery;
		}
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberLaundryAgents ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_laundry_agents', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No agent found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundry_agents', $payload, $filters);
		$result = $this->repository->find('member_laundry_agents', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberLaundryAgents ($filters = null) {
		$existsMembers = $this->repository->find('member_laundry_agents', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No agent found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundry_agents', $payload, $filters);
		$result = $this->repository->find('member_laundry_agents', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberLaundryPricelists ($filters = null) {
		$args = $filters;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		if (!empty($this->auth)) {
			$args['id_auth_api'] = $this->auth['id_auth'];
		}
		$select = [
			'member_laundry_pricelists.id',
			'member_laundry_pricelists.name',
			'member_laundry_pricelists.is_weight_required',
			'member_laundry_pricelists.is_width_required',
			'member_laundry_pricelists.is_length_required',
			'member_laundry_pricelists.created_at',
			'member_laundry_pricelists.updated_at',
		];
		$orderKey = 'member_laundry_pricelists.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$members = $this->repository->findPaginated('member_laundry_pricelists', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberLaundryPricelist ($filters = null) {
		$member = $this->repository->findOne('member_laundry_pricelists', $filters);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberLaundryPricelist ($payload) {
		if (!empty($this->auth)) {
			$payload['id_auth_api'] = $this->auth['id_auth'];
		}
		try {
			$payload['created_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('member_laundry_pricelists', $payload);
			$result = $this->repository->findOne('member_laundry_pricelists', ['id' => $action]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(400, $e->getMessage());
			return $this->delivery;
		}
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberLaundryPricelists ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_laundry_pricelists', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No pricelists found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundry_pricelists', $payload, $filters);
		$result = $this->repository->find('member_laundry_pricelists', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberLaundryPricelists ($filters = null) {
		$existsMembers = $this->repository->find('member_laundry_pricelists', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No pricelists found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundry_pricelists', $payload, $filters);
		$result = $this->repository->find('member_laundry_pricelists', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberLaundryBookings ($filters = null) {
		$args = $filters;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		if (!empty($this->auth)) {
			$args['id_auth_api'] = $this->auth['id_auth'];
		}
		$select = [
			'member_laundry_bookings.id',
			'member_laundry_bookings.booking_code',
			'member_laundry_bookings.status',
			'member_laundry_bookings.total_price',
			'member_laundry_bookings.payment_amount',
			'member_laundry_bookings.picked_up_at',
			'member_laundry_bookings.finish_checkout_at',
			'member_laundry_bookings.created_at',
			'member_laundry_bookings.updated_at',
		];
		$orderKey = 'member_laundry_bookings.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$members = $this->repository->findPaginated('member_laundry_bookings', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberLaundryBooking ($filters = null) {
		$member = $this->repository->findOne('member_laundry_bookings', $filters);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberLaundryBooking ($payload) {
		if (!empty($this->auth)) {
			$payload['id_auth_api'] = $this->auth['id_auth'];
		}
		try {
			$payload['created_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('member_laundry_bookings', $payload);
			$result = $this->repository->findOne('member_laundry_bookings', ['id' => $action]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(400, $e->getMessage());
			return $this->delivery;
		}
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberLaundryBookings ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_laundry_bookings', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No bookings found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundry_bookings', $payload, $filters);
		$result = $this->repository->find('member_laundry_bookings', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberLaundryBookings ($filters = null) {
		$existsMembers = $this->repository->find('member_laundry_bookings', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No bookings found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundry_bookings', $payload, $filters);
		$result = $this->repository->find('member_laundry_bookings', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberLaundryCarts ($filters = null) {
		$args = [];

		if (isset($filters['member_laundry_booking_status']) && !empty($filters['member_laundry_booking_status'])) {
			$args['member_laundry_bookings.status'] = $filters['member_laundry_booking_status'];
		}

		if (isset($filters['tag_code']) && !empty($filters['tag_code'])) {
			$args['member_laundry_carts.tag_code'] = $filters['tag_code'];
		}

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$join = [
			'member_laundry_bookings' => 'member_laundry_carts.member_laundry_booking_id = member_laundry_bookings.id',
			'member_laundry_pricelists' => 'member_laundry_carts.member_laundry_pricelist_id = member_laundry_pricelists.id'
		];

		/* if (!empty($this->auth)) {
			$args['id_auth_api'] = $this->auth['id_auth'];
		} */
		$select = [
			'member_laundry_carts.id',
			'member_laundry_carts.member_laundry_booking_id',
			'member_laundry_bookings.booking_code as member_laundry_booking_code',
			'member_laundry_carts.tag_code',
			'member_laundry_carts.member_laundry_pricelist_id',
			'member_laundry_pricelists.name as member_laundry_pricelist_name',
			'member_laundry_pricelists.price as member_laundry_pricelist_price',
			'member_laundry_carts.price',
			'member_laundry_carts.final_price',
			'member_laundry_carts.weight',
			'member_laundry_carts.length',
			'member_laundry_carts.width',
			'member_laundry_carts.status',
			'member_laundry_carts.in_warehouse_at',
			'member_laundry_carts.washing_finished_at',
			'member_laundry_carts.in_delivery_at',
			'member_laundry_carts.completed_at',
			'member_laundry_carts.created_at',
			'member_laundry_carts.updated_at',
		];
		$orderKey = 'member_laundry_carts.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$members = $this->repository->findPaginated('member_laundry_carts', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberLaundryCart ($filters = null) {
		$member = $this->repository->findOne('member_laundry_carts', $filters);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberLaundryCart ($payload) {
		/*  if (!empty($this->auth)) {
			$payload['id_auth_api'] = $this->auth['id_auth'];
		} */
		try {
			$payload['created_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('member_laundry_carts', $payload);
			$result = $this->repository->findOne('member_laundry_carts', ['id' => $action]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(400, $e->getMessage());
			return $this->delivery;
		}
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberLaundryCarts ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_laundry_carts', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No carts found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundry_carts', $payload, $filters);
		$result = $this->repository->find('member_laundry_carts', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberLaundryCarts ($filters = null) {
		$existsMembers = $this->repository->find('member_laundry_carts', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No carts found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_laundry_carts', $payload, $filters);
		$result = $this->repository->find('member_laundry_carts', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

}