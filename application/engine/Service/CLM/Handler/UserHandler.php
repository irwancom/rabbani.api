<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\Validator;
use Library\WablasService;
use Library\DigitalOceanService;
use Library\OneSignalService;
use \libphonenumber\PhoneNumberUtil;

class UserHandler {

	const WABLAS_MAIN_NUMBER = '62895383334783';
	const WABLAS_MAIN_DOMAIN = 'https://solo.wablas.com';
	const WABLAS_MAIN_TOKEN = 'CZrRIT5qo1GNYdiFXySxc0oW4oINZ5WZmLi40HlHHAushg4S1GlSfnSTHQfJEQgs';
	const defaultUserPic = 'https://file.1itmedia.co.id/32a20631ee2001484de8a23d03094c29.png';
	const defaultOldUserPic = 'https://cdn.1itmedia.co.id/icon/profileDefault.png';

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

	public function getUsers ($filters) {
		$args = [
			'users.id_auth' => $this->admin['id_auth']
		];

		if (isset($filters['phone']) && !empty($filters['phone'])) {
			$args['users.phone'] = [
				'condition' => 'like',
				'value' => $filters['phone']
			];
		}

		if (isset($filters['username']) && !empty($filters['username'])) {
			$args['users.username'] = [
				'condition' => 'like',
				'value' => $filters['username']
			];
		}

		if (isset($filters['first_name']) && !empty($filters['first_name'])) {
			$args['users.first_name'] = [
				'condition' => 'like',
				'value' => $filters['first_name']
			];
		}

		if (isset($filters['is_reseller']) && in_array($filters['is_reseller'], ['0','1'])) {
			$args['users.is_reseller'] = $filters['is_reseller'];
		}

		if (isset($filters['can_order']) && in_array($filters['is_reseller'], ['0','1'])) {
			$args['users.can_order'] = $filters['can_order'];
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
			'users.id',
			'users.username',
			'users.phone',
			'users.email',
			'users.balance',
			'users.first_name',
			'users.last_name',
			'users.referral_code',
			'users.clm_referrer_by_user_id',
			'users.clm_level',
			'users.bank_name',
			'users.bank_account_number',
			'users.bank_account_name',
			'users.picImage',
			'users.can_order',
			'users.is_reseller',
			'users.referred_by_store_agent_id',
			'store_agents.name as store_agent_name',
			'store_agents.store_code',
			'store_agents.nik as store_agent_nik',
			'stores.name as store_name',
			'stores.is_central',
			'stores.is_publish',
			'stores.is_use_central_stock',
			'users.created_on',
		];

		if (isset($filters['select_type']) && $filters['select_type'] == 'reseller') {
			$select = [
				'users.id',
				'users.username',
				'users.phone',
				'users.email',
				'users.first_name',
				'users.last_name',
				'users.bank_name',
				'users.bank_account_number',
				'users.bank_account_name',
				'users.picImage',
				'users.is_reseller',
				'users.referred_by_store_agent_id',
				'store_agents.name as store_agent_name',
				'store_agents.store_code',
				'store_agents.nik as store_agent_nik',
				'stores.name as store_name',
				'stores.is_central',
				'stores.is_publish',
				'stores.is_use_central_stock',
				'users.created_on',
			];
		}
		$orderKey = 'users.id';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'store_agents' => [
				'type' => 'left',
				'value' => 'store_agents.id = users.referred_by_store_agent_id'
 			],
 			'stores' => [
 				'type' => 'left',
 				'value' => 'store_agents.store_code = stores.code AND stores.deleted_at IS NULL'
 			]
		];
		$users = $this->repository->findPaginated('users', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue);

		$argsBank = ['bank_id','bank_name','bank_account_name','bank_account_number'];
		foreach($users['result'] as $user){
			if(!$user->picImage || empty($user->picImage) || is_null($user->picImage) || $user->picImage==self::defaultOldUserPic){
				$user->picImage = null;
			}
			$user->bank = $this->repository->find('user_banks', ['bank_user'=>$user->id,'deleted_at'=>NULL], null, null, $argsBank);
		}
		$this->delivery->data = $users;
		return $this->delivery;
	}

	public function updateUser ($payload, $filters = null) {
		try {
			$payload['updated_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('users', $payload, $filters);
			$result = $this->getUserEntity($filters)->data;
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function deleteUser ($id) {
		try {
			$payload['deleted_at'] = date('Y-m-d H:i:s');
			$action = $this->repository->update('users', $payload, ['id' => $id]);
			$result = $this->repository->findOne('users', ['id' => $id]);
			$this->delivery->data = $result;
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function setAdmin ($admin) {
		$this->admin = $admin;
	}

	public function getAdmin () {
		return $this->admin;
	}

	public function getUserEntity ($filters = null) {
		$args = [];
		if (!empty($this->getUser())) {
			$args['users.id'] = $this->user['id'];
		} else if (!empty($this->getAdmin())) {
			$args = $filters;
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		$select = [
			'users.id',
			'users.id_auth',
			'users.username',
			'users.first_name',
			'users.last_name',
			'users.phone',
			'users.email',
			'users.referral_code',
			'users.clm_referrer_by_user_id',
			'users.clm_level',
			'users.is_reseller',
			'users.can_order',
			//'users.bank_name',
			//'users.bank_account_name',
			//'users.bank_account_number',
			'users.birthdate',
			'users.gender',
			'users.picImage as profile_picture',
			'users.can_order as can_order',
			'users.referred_by_store_agent_id',
			'SUM(clm_transactions.reward_fee) as clm_reward_fee'
		];

		$join = [
			'clm_transactions' => [
				'type' => 'left',
				'value' => 'clm_transactions.user_id = users.id'
			]
		];

		$groupBy = 'users.id';

		$user = $this->repository->findOne('users', $args, null, $join, $select, null, null, $groupBy);

		if(!$user->profile_picture || empty($user->profile_picture) || is_null($user->profile_picture) || $user->profile_picture==self::defaultOldUserPic){
			$user->profile_picture = self::defaultUserPic;
		}

		$argsBank = ['bank_id','bank_name','bank_account_name','bank_account_number'];
		$user->bank = $this->repository->find('user_banks', ['bank_user'=>$user->id,'deleted_at'=>NULL], null, null, $argsBank);

		$handler = new CartHandler($this->repository);
        $handler->setUser($this->getUser());
        $result = $handler->getCart();
        if (!$result->hasErrors()) {
        	$cartData = $result->data;
        	$user->cart = $cartData->details;
        }

		$this->delivery->data = $user;
		return $this->delivery;
	}

	public function updateBatchReferralCode () {
		$filters = [
			'referral_code' => null
		];
		$users = $this->repository->find('users', $filters);
		$resultData = [];
		foreach ($users as $user) {
			$referralCode = $this->generateReferralCode($user);
			$payloadUser = [
				'referral_code' => $referralCode
			];
			$filterUser = [
				'id' => $user->id
			];
			$action = $this->repository->update('users', $payloadUser, $filterUser);
			$resultData[] = [
				'id' => $user->id,
				'username' => $user->username,
				'referral_code' => $referralCode
			];
		}
		$this->delivery->data = $resultData;
		return $this->delivery;
	}

	public function generateReferralCode ($user) {
		$prefix = strtolower(substr(str_replace(' ', '', mb_convert_encoding($user->first_name, 'UTF-8')), 0, 4));
		$suffix = str_pad($user->id, 3, 0, STR_PAD_LEFT);
		return sprintf('%s%s', $prefix, $suffix);
	}

	public function getBanks () {
		$bankHandler = new BankHandler;
		$bankResult = $bankHandler->getBankChoices();
		return $bankResult;
	}

	public function update ($payload) {
		if (!empty($this->getUser())) {
			$args['users.id'] = $this->user['id'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		$action = $this->repository->update('users', $payload, ['id' => $this->user['id']]);
		$result = $this->getUserEntity();
		return $result;
	}

	public function updateBankProfile ($payload) {
		if (!empty($this->getUser())) {
			$args['users.id'] = $this->user['id'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		$bankHandler = new BankHandler;
		$bankResult = $bankHandler->getBankChoices();
		$bankData = $bankResult->data;
		if (!isset($payload['bank_name']) || !in_array($payload['bank_name'], $bankData)) {
			$this->delivery->addError(400, 'Bank is required');
		}

		if (!isset($payload['bank_account_number']) || empty($payload['bank_account_number'])) {
			$this->delivery->addError(400 ,'Bank account number is required');
		}

		if (!isset($payload['bank_account_name']) || empty($payload['bank_account_name'])) {
			$this->delivery->addError(400, 'Bank account name is required');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}

		$action = $this->repository->update('users', $payload, ['id' => $this->user['id']]);
		$result = $this->getUserEntity();
		return $result;
	}

	public function updateProfilePicture ($payload) {
		$digitalOceanService = new DigitalOceanService;
		try {
			$action = $digitalOceanService->upload($payload, 'image');
			$payload = [
				'picImage' => $action['cdn_url']
			];
			$action = $this->repository->update('users', $payload, ['id' => $this->user['id']]);
			$result = $this->getUserEntity();
			return $result;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
	}

	public function getLastViewProduct () {
		$args = [];
		if (!empty($this->getUser())) {
			$args['product_views.user_id'] = $this->user['id'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
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
			'product.price',
			'product.total_purchased',
			'AVG(product_rates.rate) as rating',
			'MIN(product_details.price) as min_price_product_detail',
			'MAX(product_details.price) as max_price_product_detail',
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
			'product' => 'product.id_product = product_views.id_product',
			'product_details' => [
				'type' => 'left',
				'value' => 'product_details.id_product = product.id_product'
			],
			'product_rates' => [
				'type' => 'left',
				'value' => 'product.id_product = product_rates.id_product'
			]
		];

		$groupBy = 'product_views.id_product';
		$products = $this->repository->findPaginated('product_views', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		foreach ($products['result'] as $product) {
			$product->product_images = $this->repository->find('product_images', ['id_product' => $product->id_product]);
		}
		$this->delivery->data = $products;
		return $this->delivery;
	}

	public function validateReseller ($payload) {
		try {
			if ($payload['password'] != $payload['cpassword']) {
				$this->delivery->addError(400, 'Password and confirm password is incorrect');
			}

			if (!isset($payload['first_name']) || empty($payload['first_name'])) {
				$this->delivery->addError(400, 'First name is required');
			}

			if (isset($payload['birthdate']) && !empty($payload['birthdate'])) {
				$birthdate = strtotime($payload['birthdate']);
				if (empty($birthdate)) {
					$this->delivery->addError(400, 'Invalid birth date format');
				}
				$payload['birthdate'] = date('Y-m-d', $birthdate);
			} else {
				unset($payload['birthdate']);
			}

			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$phoneNumberUtil = PhoneNumberUtil::getInstance();
			$phoneNumber = $phoneNumberUtil->parse($payload['phone'], "ID");
		    $payload['phone'] = '62'.$phoneNumber->getNationalNumber();
		    $payload['username'] = $payload['phone'];

			$existsAgent = $this->repository->findOne('store_agents', ['referral_code' => $payload['referral_code']]);
			if (empty($existsAgent)) {
				$this->delivery->addError(400, 'Agent is required');
				return $this->delivery;
			}

			$existUsername = $this->repository->findOne('users', ['username' => $payload['username']]);
	        if (!empty($existUsername)) {
	            $this->delivery->addError(400, 'Username already taken');
	        }

	        $existPhoneNumber = $this->repository->findOne('users', ['phone' => $payload['phone']]);
	        if (!empty($existPhoneNumber)) {
	            $this->delivery->addError(400, 'Phone number already taken');
	        }

	        if ($this->delivery->hasErrors()) {
				return $this->delivery;
			}

			$payload['referred_by_store_agent_id'] = $existsAgent->id;
			$payload['ip_address'] = $_SERVER['REMOTE_ADDR'];
			$payload['id_auth'] = 1;
			$payload['password'] = password_hash($payload['password'], PASSWORD_DEFAULT);
			$payload['created_on'] = time();
			unset($payload['cpassword']);
			unset($payload['otp']);

		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		$this->delivery->data = $payload;
		return $this->delivery;
	}

	public function registerReseller ($payload) {
		$validateResult = $this->validateReseller($payload);
		if ($validateResult->hasErrors()) {
			return $validateResult;
		}

		$userData = $validateResult->data;
		// validate OTP
		$argsOtp = [
			'phone_number' => $userData['phone'],
			'used_at' => null,
			'expired_at >=' => date('Y-m-d H:i:s')
		];
		$lastOtpAlive = $this->repository->findOne('otp', $argsOtp);
		if (empty($lastOtpAlive)) {
			$this->delivery->addError(400, 'Please request new OTP');
			return $this->delivery;
		}

		if ($lastOtpAlive->otp != $payload['otp']) {
			$this->delivery->addError(400, 'OTP is incorrect');
			return $this->delivery;
		}

		$paymentMethodCode = $payload['payment_method_code'];
		unset($userData['payment_method_code']);

		$actionOtp = $this->repository->update('otp', ['used_at' => date('Y-m-d H:i:s')], ['id' => $lastOtpAlive->id]);
		try {
			$this->repository->beginTransaction();
			$secret = md5($userData['username'] . time()) . md5(time() . $userData['password']);
			$userData['can_order'] = 0;
			$userData['secret'] = $secret;
			$userData['is_reseller'] = 1;
			$action = $this->repository->insert('users', $userData);
            $data = [
                'message' => 'Success create new user',
            ];
            $existsUser = $this->repository->findOne('users', ['id' => $action]);
            // update referral code
            $user = new \stdClass;
            $user->id = $action;
            $user->first_name = $userData['first_name'];
            $user->email = $existsUser->email;
            $user->phone = $existsUser->phone;
            $user->referral_code = $this->generateReferralCode($user);
            $user->username = $this->generateUsername($action);
            $payload = [
                'referral_code' => $user->referral_code,
                'username' => $user->username
            ];
            $referralCodeAction = $this->repository->update('users', $payload, ['id' => $action]);
            $data['user'] = $user;


            $validator = new Validator($this->repository);
            $auth = $validator->validateUser($secret);

            // hapus dibawah ini
			/* $this->waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
			$message = 'Selamat datang dalam program rabbani jamaah, berikut data ke anggotaan kaka'.PHP_EOL.PHP_EOL.'ID Keanggotaan: '.$user->username.PHP_EOL.'Nama: '.$userData['first_name'].PHP_EOL.'Status: Aktif'.PHP_EOL.PHP_EOL.'Terima kasih sudah bergabung';
			$sendWa = $this->waService->publishMessage('send_message', $userData['phone'], $message); */

			$orderHandler = new OrderHandler($this->repository);
			$orderHandler->setUser($auth->data);
			$action = $orderHandler->generateResellerRegistrationOrder($paymentMethodCode, 50000);
			if ($action->hasErrors()) {
				throw new \Exception($action->getFirstError()->detail);
			}
			$data['order'] = $action->data;
            $this->delivery->data = $data;
            if ($this->repository->statusTransaction() === FALSE) {
				throw new \Exception('Internal Server Error');
			} else {
				$this->repository->completeTransaction();
				$this->repository->commitTransaction();
			}
		} catch (\Exception $e) {
			$this->repository->rollbackTransaction();
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		return $this->delivery;

	}

	public function handleSendOTP ($payload) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$phoneNumberUtil = PhoneNumberUtil::getInstance();
			$phoneNumber = $phoneNumberUtil->parse($payload['phone'], "ID");
			if (substr($phoneNumber->getNationalNumber(), 0, 2) == '62') {
				$payload['phone'] = $phoneNumber->getNationalNumber();
			} else {
				$payload['phone'] = '62'.$phoneNumber->getNationalNumber();
			}
		} catch (\libphonenumber\NumberParseException $e) {
		    $this->delivery->addError(500, $e->getMessage());
		    return $this->delivery;
		}
		
		// validate OTP
		$argsOtp = [
			'phone_number' => $payload['phone'],
			'used_at' => null,
			'expired_at >=' => date('Y-m-d H:i:s')
		];
		$lastOtpAlive = $this->repository->findOne('otp', $argsOtp);
		if (!empty($lastOtpAlive)) {
			$this->delivery->addError(400, 'OTP has been sent. Try again in 5 minutes.');
			return $this->delivery;
		}

		$otp = generateRandomDigit(6);
		//$otp = '123456';
        $message = sprintf('Kode OTP: %s', $otp);
        $currentDate = date('Y-m-d H:i:s');
        $futureDate = strtotime($currentDate) + (60 * 5);
        $expiredAt = date('Y-m-d H:i:s', $futureDate);

		$dataOtp = [
			'phone_number' => $payload['phone'],
			'otp' => $otp,
			'used_at' => null,
			'expired_at' => $expiredAt,
			'created_at' => $currentDate,
			'updated_at' => $currentDate,
			'id_user' => (isset($payload['id_user'])) ? $payload['id_user'] : 0,
			'type' => (isset($payload['type'])) ? $payload['type'] : '',
		];
		$otpAction = $this->repository->insert('otp', $dataOtp);
		$message = sprintf('Kode OTP anda: %s', $otp);

		$this->waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
		$sendWa = $this->waService->publishMessage('send_message', $payload['phone'], $message);
		$this->delivery->data = $sendWa;
		return $this->delivery;
	}

	public function handleValidOTP ($payload) {
		$payload['phone'] = getFormattedPhoneNumber($payload['phone']);

		$argsOtp = [
			'phone_number' => $payload['phone'],
			'used_at' => null,
			'expired_at >=' => date('Y-m-d H:i:s')
		];
		$lastOtpAlive = $this->repository->findOne('otp', $argsOtp);
		if (empty($lastOtpAlive)) {
			$this->delivery->addError(400, 'Please request new OTP');
			return $this->delivery;
		}

		if ($lastOtpAlive->otp != $payload['otp']) {
			$this->delivery->addError(400, 'OTP is incorrect');
			return $this->delivery;
		}

		$actionOtp = $this->repository->update('otp', ['used_at' => date('Y-m-d H:i:s')], ['id' => $lastOtpAlive->id]);

		$this->delivery->data = 'ok';
		return $this->delivery;
	}

	private function generateUsername ($id) {
		return 'J'.sprintf('%s%s', date('YmdH'), str_pad($id, 4, '0', STR_PAD_LEFT));
	}

	public function createOneSignalPlayerId ($playerId) {
		if (!empty($this->getUser())) {
			$args['users.id'] = $this->user['id'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}

		if (empty($playerId)) {
			$this->delivery->addError(400, 'Player id is required');
			return $this->delivery;
		}

		$args = [
			'player_id' => $playerId,
		];
		$existsPlayerId = $this->repository->findOne('user_onesignal_player_ids', $args);
		if (empty($existsPlayerId)) {
			$data = [
				'user_id' => $this->user['id'],
				'player_id' => $playerId,
				'is_active' => 1,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('user_onesignal_player_ids', $data);
		} else {
			$updateData = [
				'user_id' => $this->user['id'],
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->update('user_onesignal_player_ids', $updateData, ['player_id' => $playerId]);
		}

		$this->delivery->data = 'ok';
		return $this->delivery;
	}

	public function sendPushNotif ($title, $message, $extras = []) {
		if (!empty($this->getUser())) {
			$args['users.id'] = $this->user['id'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}
		$onesignal = new OneSignalService;
		$playerIds = $this->repository->find('user_onesignal_player_ids', ['user_id' => $this->user['id'], 'is_active' => 1]);
		if (empty($playerIds)) {
			$this->delivery->addError(400, 'Player id is required');
			return $this->delivery;
		}
		/* $targets = [];
		foreach ($playerIds as $playerId) {
			$targets['playerIds'][] = $playerId;
		}
		$onesignalAction = $onesignal->pushNotification($title, $message, $targets, $extras); */

		$extras = [
			'data' => [
				'type' => 'transaction_status',
			],
		];

		$onesignal = new OneSignalService;
		$pushNotifAction = $onesignal->publishPushNotification('db861fb4-3979-4003-878c-f8317276673c', 'MTI1YTdlMTMtMmNjZi00NTBmLTkwMDUtYzczMTcxYzgyZjgx', $this->user['id'], 'Title Notif', 'Message Notif');
		$this->delivery->data = $pushNotifAction;
		return $this->delivery;
	}

	public function getNotifications ($filters = null) {
		$args = [];
		if (!empty($this->getUser())) {
			$args['notifications.user_id'] = $this->user['id'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
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
			'notifications.type',
			'notifications.title',
			'notifications.message',
			'notifications.created_at'
		];
		$orderKey = 'notifications.created_at';
		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [];

		$groupBy = '';
		$notifs = $this->repository->findPaginated('notifications', $args, null, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
		$this->delivery->data = $notifs;
		return $this->delivery;
	}

	public function closeAccount () {
		if (!empty($this->getUser())) {
			$args['users.id'] = $this->user['id'];
		} else {
			$this->delivery->addError(400, 'Not Allowed!');
			return $this->delivery;
		}
		try {
			$payload = [
				'secret' => null,
				'deleted_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('users', $payload, ['id' => $this->user['id']]);
			$this->delivery->data = 'ok';
			return $this->delivery;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		return $this->delivery;
	}

}