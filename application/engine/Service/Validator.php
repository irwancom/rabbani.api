<?php
namespace Service;

class Validator {

	private $repository;
	private $delivery;
	private $entity;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->entity = new Entity;
	}

	public function validateAuth ($secret) {
		$args = [
			'secret' => $secret
		];
		$auth = $this->repository->findOne('auth_user', $args);
		if (!empty($auth)) {
			$result = [
				'id' => $auth->id_auth_user,
				'id_auth' => $auth->id_auth,
				'type' => 'auth_user',
				'uname' => $auth->uname,
				'name' => $auth->name,
				'email' => $auth->email,
				'phone' => $auth->phone,
				'services' => $this->formatAuthServices($auth)
			];
			//additional
			$args = [
				'id_auth' => $result['id_auth']
			];
			$detail = $this->repository->findOne('auth_api', $args);
			if (empty($detail)) {
				$this->delivery->addError(401, 'Unauthorized');
				return $this->delivery;
			}
			$result['company'] = $detail->corp;
			$result['balance'] = $detail->balance;

			$this->delivery->data = $result;
		} else {
			$this->delivery->addError(401, 'Unauthorized');
		}
		return $this->delivery;
	}

	public function validateAuthAdmin ($secret, $requiredRole = []) {
		$args = [
			'secret' => $secret
		];
		$auth = $this->repository->findOne('admins', $args);
		if (!empty($auth)) {
			$result = [
				'id' => $auth->id,
				'id_auth' => $auth->id_auth,
				'type' => 'admins',
				'username' => $auth->username,
				'email' => $auth->email,
				'first_name' => $auth->first_name,
				'last_name' => $auth->last_name,
				'company' => $auth->company,
				'phone' => $auth->phone,
				'inventory_role' => $auth->inventory_role
			];

			if (!empty($requiredRole)) {
				foreach ($requiredRole as $key => $value) {
					if (is_array($value) && !in_array($auth->{$key}, $value)) {
						$this->delivery->addError(401, 'Unauthorized');
					} else if ($auth->{$key} != $value) {
						$this->delivery->addError(401, 'Unauthorized');
					}
				}
			}

			$this->delivery->data = $result;
		} else {
			$this->delivery->addError(401, 'Unauthorized');
		}
		return $this->delivery;
	}

	public function validateUser ($secret) {
		$join = [
			'store_agents' => [
				'type' => 'left',
				'value' => 'store_agents.id = users.referred_by_store_agent_id AND store_agents.deleted_at IS NULL'
			],
			'stores' => [
				'type' => 'left',
				'value' => 'store_agents.store_code = stores.code AND stores.deleted_at IS NULL'
			]
		];
		$args = [
			'secret' => $secret
		];
		$select = [
			'users.id',
			'users.id_auth',
			'users.username',
			'users.email',
			'users.first_name',
			'users.last_name',
			'users.company',
			'users.phone',
			'users.picImage',
			'users.referral_code',
			'users.clm_referrer_by_user_id',
			'users.clm_level',
			'users.can_order',
			'users.is_reseller',
			'users.referred_by_store_agent_id',
			'store_agents.store_code',
			'stores.is_use_central_stock',
		];
		$auth = $this->repository->findOne('users', $args, null, $join, $select);
		if (!empty($auth)) {
			$result = [
				'id' => $auth->id,
				'id_auth' => $auth->id_auth,
				'type' => 'users',
				'username' => $auth->username,
				'email' => $auth->email,
				'first_name' => $auth->first_name,
				'last_name' => $auth->last_name,
				'company' => $auth->company,
				'phone' => $auth->phone,
				'pic_image' => $auth->picImage,
				'referral_code' => $auth->referral_code,
				'clm_referrer_by_user_id' => $auth->clm_referrer_by_user_id,
				'clm_level' => $auth->clm_level,
				'is_reseller' => $auth->is_reseller,
				'can_order' => $auth->can_order,
				'referred_by_store_agent_id' => $auth->referred_by_store_agent_id,
				'store_code' => $auth->store_code,
			];
			if ($auth->is_use_central_stock == 1) {
				$centralStore = $this->repository->findOne('stores', ['is_central' => 1]);
				$result['store_code'] = $centralStore->code;
			}
			$this->delivery->data = $result;
		} else {
			$this->delivery->addError(401, 'Unauthorized');
		}
		return $this->delivery;
	}

	public function validateAuthApi ($secret) {
		if (empty($secret)) {
			$this->delivery->addError(401, 'Unauthorized');
			return $this->delivery;
		}
		$args = [
			'secret' => $secret
		];
		$auth = $this->repository->findOne('auth_api', $args);
		if (!empty($auth)) {
			$result = [
				'id_auth' => $auth->id_auth,
				'corp' => $auth->corp,
				'type' => 'auth_api',
			];
			$this->delivery->data = $result;
		} else {
			$this->delivery->addError(401, 'Unauthorized');
		}
		return $this->delivery;
	}

	public function validateAuthAll ($secret) {
		if (!$secret || empty($secret) || is_null($secret)) return false;

		$authApi = $this->validateAuthApi($secret);
		if($authApi && isset($authApi->data) && !empty($authApi->data) && !is_null($authApi->data)){
			return $authApi->data;
		}

		$authAdmin = $this->validateAuthAdmin($secret);
		if($authAdmin && isset($authAdmin->data) && !empty($authAdmin->data) && !is_null($authAdmin->data)){
			return $authAdmin->data;
		}

		$authUser = $this->validateUser($secret);
		if($authUser && isset($authUser->data) && !empty($authUser->data) && !is_null($authUser->data)){
			return $authUser->data;
		}
		return false;
	}

	private function formatAuthServices ($auth) {
		$args = [
			'id_auth_user' => $auth->id_auth_user,
			'service_product_type' => Entity::TYPE_SERVICE
		];
		$serviceUserCollections = $this->repository->find('service_user_collections', $args);
		return $this->entity->formatAuth($serviceUserCollections);
	}

	public function validateAuthHandler ($auth, $mandatoryService) {
		$services = $auth['services'];
		foreach ($mandatoryService as $mandatory) {
			if (!$services[$mandatory]['is_active']) {
				$this->delivery->addError(401, 'Unauthorized');
				return $this->delivery;
			}
		}
		return $this->delivery;
	}

	public function validateIntegrationHeader ($service, $requestHeaders) {
		$services = [
			Entity::SERVICE_MAILGUN => ['X-Mailgun-Domain', 'X-Mailgun-Key'],
			Entity::SERVICE_TRIPAY_PAYMENT => ['X-Tripay-Env', 'X-Tripay-Api-Key', 'X-Tripay-Private-Key', 'X-Tripay-Merchant-Code'],
			Entity::SERVICE_XENDIT => ['X-Xendit-Api-Key'],
			Entity::SERVICE_DIGITAL_OCEAN => ['X-Digital-Ocean-Cdn-Link', 'X-Digital-Ocean-Key', 'X-Digital-Ocean-Secret', 'X-Digital-Ocean-Space-Name', 'X-Digital-Ocean-Region'],
			Entity::SERVICE_WABLAS => ['X-Wablas-Domain', 'X-Wablas-Token'],
			Entity::SERVICE_JNE => ['X-Jne-Env', 'X-Jne-Username', 'X-Jne-Api-Key']
		];

		if (!isset($services[$service])) {
			$this->delivery->addError(409, 'Service not found');
			return $this->delivery;
		}

		$mandatoryHeaders = $services[$service];
		foreach ($mandatoryHeaders as $value) {
			if (!isset($requestHeaders[$value]) || empty($requestHeaders[$value])) {
				$this->delivery->addError(409, sprintf('Missing header %s', $value));
				return $this->delivery;
			}
		}

		return $this->delivery;

	}

	public function validatePollMember ($secret) {
		$auth = $this->repository->findOne('poll_members', ['secret' => $secret]);
		if (!empty($auth)) {
			$result = [
				'id' => $auth->id,
				'phone_number' => $auth->phone_number,
				'registration_number' => $auth->registration_number,
				'name' => $auth->name,
				'status' => $auth->status,
			];
			$this->delivery->data = $result;
		} else {
			$this->delivery->addError(401, 'Unauthorized');
		}
		return $this->delivery;
	}
}