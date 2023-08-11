<?php
namespace Service\MemberKb;

use Library\WablasService;
use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;

class MemberKbManager {

	const MAIN_WABLAS = '62895383334783';
	const WABLAS_MENU_STATE_TWO = 'menu_two';

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

	public function getMemberKbs ($filters = null) {
		$args = [
			'member_kbs.id_auth_api' => $this->auth['id_auth']
		];

		if (isset($filters['phone_number']) && !empty($filters['phone_number'])) {
			$args['member_kbs.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['phone_number']
			];
		}

		if (isset($filters['name']) && !empty($filters['name'])) {
			$args['member_kbs.name'] = [
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
			'member_kbs.id',
			'member_kbs.name',
			'member_kbs.phone_number',
			'member_kbs.provinsi',
			'member_kbs.kabupaten',
			'member_kbs.kecamatan',
			'member_kbs.kelurahan',
			'member_kbs.address',
			'member_kbs.wablas_phone_number_receiver',
			'member_kbs.created_at',
			'member_kbs.updated_at',
		];
		$orderKey = 'member_kbs.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$members = $this->repository->findPaginated('member_kbs', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberKb ($filters = null) {
		$argsOrWhere = null;
		if (isset($filters['iden'])) {
			$argsOrWhere = [
				'phone_number' => $filters['iden'],
				'id' => $filters['iden']
			];
			unset($filters['iden']);
		}
		$member = $this->repository->findOne('member_kbs', $filters, $argsOrWhere);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberKb ($payload) {
		$payload['id_auth_api'] = $this->auth['id_auth'];
		$existsMember = $this->repository->findOne('member_kbs', ['id_auth_api' => $this->auth['id_auth'], 'phone_number' => $payload['phone_number']]);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Member already exists.');
			return $this->delivery;
		}
		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('member_kbs', $payload);
		$result = $this->repository->findOne('member_kbs', ['id' => $action]);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberKbs ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_kbs', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		unset($payload['phone_number']);
		unset($payload['id_auth_api']);
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_kbs', $payload, $filters);
		$result = $this->repository->find('member_kbs', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberKbs ($filters = null) {
		$existsMembers = $this->repository->find('member_kbs', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_kbs', $payload, $filters);
		$result = $this->repository->find('member_kbs', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberKbRecord ($filters = null) {
		$record = $this->repository->findOne('member_kb_records', $filters);
		if (!empty($record)) {
			$record->member = $this->getMemberKb(['id' => $record->member_kb_id])->data;
		}
		$this->delivery->data = $record;
		return $this->delivery;
	}

	public function getMemberKbPenyuluhs ($filters = null) {
		$args = $filters;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'member_kb_penyuluhs.id',
			'member_kb_penyuluhs.whatsapp_number',
			'member_kb_penyuluhs.phone_number',
			'member_kb_penyuluhs.nama',
			'member_kb_penyuluhs.kabupaten',
			'member_kb_penyuluhs.kecamatan',
			'member_kb_penyuluhs.kelurahan',
			'member_kb_penyuluhs.address',
			'member_kb_penyuluhs.created_at',
			'member_kb_penyuluhs.updated_at',
		];
		$orderKey = 'member_kb_penyuluhs.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$members = $this->repository->findPaginated('member_kb_penyuluhs', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberKbPenyuluh ($filters = null) {
		$member = $this->repository->findOne('member_kb_penyuluhs', $filters);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberKbPenyuluh ($payload) {
		$args = [
			'kelurahan' => $payload['kelurahan'],
			'whatsapp_number' => $payload['whatsapp_number']
		];
		$existsMember = $this->repository->findOne('member_kb_penyuluhs', $args);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Penyuluh already exists.');
			return $this->delivery;
		}
		$findKabupaten = $this->repository->findOne('districts', ['nama' => $payload['kabupaten']]);
		$findKecamatan = $this->repository->findOne('sub_district', ['nama' => $payload['kecamatan']]);
		$findKelurahan = $this->repository->findOne('urban_village', ['nama' => $payload['kelurahan']]);
		try {
			if ($findKabupaten->id_kab == $findKecamatan->id_kab && $findKecamatan->id_kec == $findKelurahan->id_kec) {
				$payload['created_at'] = date('Y-m-d H:i:s');
				$action = $this->repository->insert('member_kb_penyuluhs', $payload);
				$result = $this->repository->findOne('member_kb_penyuluhs', ['id' => $action]);
				$this->delivery->data = $result;
				return $this->delivery;
			} else {
				$this->delivery->addError(400, 'Kabupaten, kecamatan and kelurahan are incorrect');
				return $this->delivery;
			}
		} catch (\Exception $e) {
			$this->delivery->addError(400, $e->getMessage());
			return $this->delivery;
		}
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberKbPenyuluhs ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_kb_penyuluhs', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_kb_penyuluhs', $payload, $filters);
		$result = $this->repository->find('member_kb_penyuluhs', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberKbPenyuluhs ($filters = null) {
		$existsMembers = $this->repository->find('member_kb_penyuluhs', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_kb_penyuluhs', $payload, $filters);
		$result = $this->repository->find('member_kb_penyuluhs', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberKbBidans ($filters = null) {
		$args = $filters;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'member_kb_bidans.id',
			'member_kb_bidans.whatsapp_number',
			'member_kb_bidans.phone_number',
			'member_kb_bidans.nama',
			'member_kb_bidans.kabupaten',
			'member_kb_bidans.kecamatan',
			'member_kb_bidans.kelurahan',
			'member_kb_bidans.address',
			'member_kb_bidans.created_at',
			'member_kb_bidans.updated_at',
		];
		$orderKey = 'member_kb_bidans.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$members = $this->repository->findPaginated('member_kb_bidans', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function getMemberKbBidan ($filters = null) {
		$member = $this->repository->findOne('member_kb_bidans', $filters);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberKbBidan ($payload) {
		$args = [
			'kelurahan' => $payload['kelurahan'],
			'whatsapp_number' => $payload['whatsapp_number']
		];
		$existsMember = $this->repository->findOne('member_kb_bidans', $args);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Bidan already exists.');
			return $this->delivery;
		}
		$findKabupaten = $this->repository->findOne('districts', ['nama' => $payload['kabupaten']]);
		$findKecamatan = $this->repository->findOne('sub_district', ['nama' => $payload['kecamatan']]);
		$findKelurahan = $this->repository->findOne('urban_village', ['nama' => $payload['kelurahan']]);
		try {
			if ($findKabupaten->id_kab == $findKecamatan->id_kab && $findKecamatan->id_kec == $findKelurahan->id_kec) {
				$payload['created_at'] = date('Y-m-d H:i:s');
				$action = $this->repository->insert('member_kb_bidans', $payload);
				$result = $this->repository->findOne('member_kb_bidans', ['id' => $action]);
				$this->delivery->data = $result;
				return $this->delivery;
			} else {
				$this->delivery->addError(400, 'Kabupaten, kecamatan and kelurahan are incorrect');
				return $this->delivery;
			}
		} catch (\Exception $e) {
			$this->delivery->addError(400, $e->getMessage());
			return $this->delivery;
		}
	}

	/**
	 * Batch update 
	 **/
	public function updateMemberKbBidans ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_kb_bidans', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_kb_bidans', $payload, $filters);
		$result = $this->repository->find('member_kb_bidans', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberKbBidans ($filters = null) {
		$existsMembers = $this->repository->find('member_kb_bidans', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No member found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_kb_bidans', $payload, $filters);
		$result = $this->repository->find('member_kb_bidans', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function getMemberKbClassifications ($filters = null) {
		$args = $filters;

		$offset = 0;
		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		$select = [
			'member_kb_classifications.id',
			'member_kb_classifications.name',
			'member_kb_classifications.gender',
			'member_kb_classifications.min_total_children',
			'member_kb_classifications.max_total_children',
			'member_kb_classifications.min_age',
			'member_kb_classifications.max_age',
			'member_kb_classifications.is_breastfeeding',
			'member_kb_classifications.allowed_criterias',
			'member_kb_classifications.not_allowed_criterias',
			'member_kb_classifications.created_at',
			'member_kb_classifications.updated_at',
		];
		$orderKey = 'member_kb_classifications.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$classifications = $this->repository->findPaginated('member_kb_classifications', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		foreach ($classifications['result'] as $classification) {
			$classification->allowed_criterias = json_decode($classification->allowed_criterias);
			$classification->not_allowed_criterias = json_decode($classification->not_allowed_criterias);
		}
		$this->delivery->data = $classifications;
		return $this->delivery;
	}

	public function getMemberKbClassification ($filters = null) {
		$member = $this->repository->findOne('member_kb_classifications', $filters);
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function createMemberKbClassification ($payload) {
		$args['name'] = $payload['name'];
		$existsMember = $this->repository->findOne('member_kb_classifications', $args);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Bidan already exists.');
			return $this->delivery;
		}
		try {
			if (isset($payload['allowed_criterias'])) {
				$payload['allowed_criterias'] = json_encode($payload['allowed_criterias']);	
			}
			if (isset($payload['not_allowed_criterias'])) {
				$payload['not_allowed_criterias'] = json_encode($payload['not_allowed_criterias']);	
			}
			$payload['created_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('member_kb_classifications', $payload);
			$result = $this->repository->findOne('member_kb_classifications', ['id' => $action]);
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
	public function updateMemberKbClassifications ($payload, $filters = null) {
		$existsMembers = $this->repository->find('member_kb_classifications', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No classifications found.');
			return $this->delivery;
		}
		if (isset($payload['allowed_criterias'])) {
			$payload['allowed_criterias'] = json_encode($payload['allowed_criterias']);	
		}
		if (isset($payload['not_allowed_criterias'])) {
			$payload['not_allowed_criterias'] = json_encode($payload['not_allowed_criterias']);	
		}
		$payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_kb_classifications', $payload, $filters);
		$result = $this->repository->find('member_kb_classifications', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function deleteMemberKbClassifications ($filters = null) {
		$existsMembers = $this->repository->find('member_kb_classifications', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(409, 'No classification found.');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('member_kb_classifications', $payload, $filters);
		$result = $this->repository->find('member_kb_classifications', $filters);
		$this->delivery->data = $result;
		return $this->delivery;
	}

}