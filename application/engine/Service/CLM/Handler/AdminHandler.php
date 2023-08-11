<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Library\MailgunService;
use Library\WablasService;

class AdminHandler {

	const INVENTORY_ROLE_HEAD = 'head';
	const INVENTORY_ROLE_MANAGER = 'manager';
	const INVENTORY_ROLE_STAFF = 'staff';

	const MAIN_WABLAS = '62895383334783';

	private $delivery;
	private $repository;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function setAdmin ($admin) {
		$this->admin = $admin;
	}

	public function getAdmin () {
		return $this->admin;
	}

	public function getAdmins ($filters = null) {
		$args = [];
		if (empty($this->admin)) {
			$this->delivery->addError(400, 'Auth required');
			return $this->delivery;
		} else {
			$args['admins.id_auth'] = $this->admin['id_auth'];
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
			'admins.id',
			'admins.username',
			'admins.email',
			'admins.first_name',
			'admins.last_name',
			'admins.phone',
			'admins.inventory_role',
			'admins.created_on',
			'admins.deleted_at',
		];
		$orderKey = 'admins.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		$admins = $this->repository->findPaginated('admins', $args, null, null, $select, $offset, $limit, $orderKey, $orderValue);
		$this->delivery->data = $admins;
		return $this->delivery;
	}

	public function getAdminEntity ($filters = null) {
		$args = [];
		$argsOrWhere = [];
		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['id'] = $filters['id'];
		}
		if (isset($filters['slug']) && !empty($filters['slug'])) {
			$argsOrWhere['username'] = $filters['slug'];
			$argsOrWhere['email'] = $filters['slug'];
			$argsOrWhere['phone'] = $filters['slug'];
		}
		$admin = $this->repository->findOne('admins', $args, $argsOrWhere);
		$adminStore = null;
		if($admin && !empty($admin) && !is_null($admin)){
	        if(isset($admin->admin_store) && $admin->admin_store && !empty($admin->admin_store) && !is_null($admin->admin_store)){
	            $adminStore = $this->repository->findOne('stores', ['stores.id'=>$admin->admin_store]);
	        }
		}
		$admin->store_detail = $adminStore;
		$this->delivery->data = $admin;
		return $this->delivery;
	}

	public function createAdmin ($payload) {
		if (empty($this->admin)) {
			$this->delivery->addError(400, 'Auth required');
			return $this->delivery;
		} else {
			$payload['id_auth'] = $this->admin['id_auth'];
		}

		if ($this->admin['inventory_role'] != self::INVENTORY_ROLE_HEAD) {
			$this->delivery->addError(400, 'Not Allowed');
			return $this->delivery;
		}

		$consRole = [
			self::INVENTORY_ROLE_HEAD,
			self::INVENTORY_ROLE_MANAGER,
			self::INVENTORY_ROLE_STAFF
		];

		if (!in_array($payload['inventory_role'], $consRole)) {
			$this->delivery->addError(400, 'Role is not valid');
			return $this->delivery;
		}

		$args = [
            'username' => $payload['username']
        ];
        $existUsername = $this->repository->findOne('admins', $args);
        if (!empty($existUsername)) {
            $this->delivery->addError(409, 'Username already taken');
        }

        $args = [
            'email' => $payload['email']
        ];
        $existsEmail = $this->repository->findOne('admins', $args);
        if (!empty($existsEmail)) {
            $this->delivery->addError(409, 'Email already taken');
        }

        $args = [
            'id_auth' => $payload['id_auth']
        ];
        $existsAuth = $this->repository->findOne('auth_api', $args);
        if (empty($existsAuth)) {
            $this->delivery->addError(409, 'Auth not found');
        }


        if (strlen($payload['password']) != 6) {
            $this->delivery->addError(409, 'Password is not correct');
        }

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		try {
			$payload['password'] = password_hash($payload['password'], PASSWORD_DEFAULT);
			$payload['created_on'] = time();
			// $payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->insert('admins', $payload);
			$keyword = $this->repository->findOne('admins', ['id' => $action]);
			$this->delivery->data = $keyword;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function updateAdmins ($payload, $filters = null) {
		if (empty($this->admin)) {
			$this->delivery->addError(400, 'Auth required');
			return $this->delivery;
		} else {
			$payload['id_auth'] = $this->admin['id_auth'];
		}

		if ($this->admin['inventory_role'] == self::INVENTORY_ROLE_STAFF) {
			$this->delivery->addError(400, 'Not Allowed');
			return $this->delivery;
		}
		unset($payload['username']);
		unset($payload['id_auth']);
		$existsKeyword = $this->repository->findOne('admins', $filters);
		if (empty($existsKeyword)) {
			$this->delivery->addError(409, 'No admin found.');
			return $this->delivery;
		}

		// $payload['updated_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->update('admins', $payload, $filters);
		$result = $this->getAdminEntity($filters);
		$this->delivery->data = $result->data;
		return $this->delivery;
	}

	public function deleteAdmin ($id) {
		if (empty($this->admin)) {
			$this->delivery->addError(400, 'Auth required');
			return $this->delivery;
		} else {
			$payload['id_auth'] = $this->admin['id_auth'];
		}

		if ($this->admin['inventory_role'] != self::INVENTORY_ROLE_HEAD) {
			$this->delivery->addError(400, 'Not Allowed');
			return $this->delivery;
		}
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('admins', $payload, ['id' => $id]);
			$result = $this->repository->findOne('admins', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function resetPassword ($admin) {

		if (!empty($this->getAdmin())) {
			if ($this->getAdmin()['inventory_role'] == self::INVENTORY_ROLE_STAFF) {
				$this->delivery->addErrors(409, 'Not Allowed');
				return $this->delivery;
			}
		} else {
			$this->admin = (array)$admin;
		}

		if (empty($admin)) {
			$this->delivery->addErrors(400, 'Tartget Admin is required');
			return $this->delivery;
		}
		$admin = (array)$admin;
		$newPassword = generateRandomString(6);
		$encrypted = password_hash($newPassword, PASSWORD_DEFAULT);
		$payload = [
			'password' => $encrypted
		];
		$filters = [
			'id' => $admin['id']
		];
		$action = $this->updateAdmins($payload, $filters);

		$mailgun = new MailgunService;
		$subject = 'Your New Password';
		$text = 'Your New Password is '. $newPassword;
		$mailgun = $mailgun->send('', '', 'no-reply@1itmedia.co.id', $admin['email'], $subject, $text);

		$data = [
			'message' => 'ok',
			'text' => $text,
			'mailgun' => $mailgun,
		];

		$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => self::MAIN_WABLAS]);
		if (!empty($wablasConfig)) {
			$waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
			$action = $waService->sendMessage($admin['phone'], $text);
			$data['wablas'] = $action;
		}
		
		$this->delivery->data = $data;
		return $this->delivery;
	}

}