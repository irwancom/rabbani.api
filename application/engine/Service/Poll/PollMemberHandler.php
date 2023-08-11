<?php
namespace Service\Poll;

use Library\WablasService;
use Service\Delivery;
use \libphonenumber\PhoneNumberUtil;
use Library\DigitalOceanService;

class PollMemberHandler {

	const MAIN_WABLAS = '62895383334783';

	const STATUS_PENDING = 'pending';
	const STATUS_VERIFIED = 'verified';

	const BASE_URL_PUBLIC_POLL = 'https://dpr.rabbani.id/';

	const TALENT_SINGER = 'singer';
	const TALENT_PUBLIC_SPEAKING = 'public_speaking';
	const TALENT_ACTING = 'acting';
	const TALENT_MUROTAL = 'murota';
	const TALENT_MODELING = 'modeling';
	const TALENT_OTHER = 'other';

	const LEVEL_STATUS_PENDING = 'pending';
	const LEVEL_STATUS_FAIL = 'fail';
	const LEVEL_STATUS_PASS = 'pass';

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;
	
	public function __construct ($repository, $auth = null) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
		if (!empty($auth)) {
			$this->auth = $auth;
		}
	}

	public function setAuth ($auth) {
		$this->auth = $auth;
	}

	public function getAuth () {
		return $this->auth;
	}

	public function getPollMember ($filters = null) {
		$argsOrWhere = null;
		if (isset($filters['iden'])) {
			$argsOrWhere = [
				'phone_number' => $filters['iden'],
				'id' => $filters['iden']
			];
			unset($filters['iden']);
		}

		if (isset($filters['id'])) {
			$filters['poll_members.id'] = $filters['id'];
			unset($filters['id']);
		}
		$join = [
			'provinces' => [
				'value' => 'provinces.id = poll_members.id_provinsi',
				'type' => 'left'
			],
			'districts' => [
				'value' => 'districts.id_kab = poll_members.id_kabupaten',
				'type' => 'left'
			],
			'sub_district' => [
				'value' => 'sub_district.id_kec = poll_members.id_kecamatan',
				'type' => 'left'
			],
			'stores' => [
				'value' => 'stores.id = poll_members.store_id',
				'type' => 'left'
			]
		];
		$select = [
			'poll_members.id',
			'poll_members.registration_number',
			'poll_members.profile_picture_url',
			'poll_members.name',
			'poll_members.birthdate',
			'poll_members.id_provinsi',
			'provinces.name as provinsi_nama',
			'poll_members.id_kabupaten',
			'districts.nama as kabupaten_nama',
			'poll_members.id_kecamatan',
			'sub_district.nama as kecamatan_nama',
			'poll_members.store_id',
			'stores.name as store_name',
			'poll_members.address',
			'poll_members.from_school',
			'poll_members.achievements',
			'poll_members.invoice_number',
			'poll_members.status',
			'poll_members.current_level',
			'poll_members.level_1_slug',
			'poll_members.level_1_status',
			'poll_members.level_1_total_votes',
			'poll_members.level_2_slug',
			'poll_members.level_2_status',
			'poll_members.level_2_total_votes',
			'poll_members.level_3_slug',
			'poll_members.level_3_status',
			'poll_members.level_3_total_votes',
			'poll_members.created_at',
		];
		$member = $this->repository->findOne('poll_members', $filters, $argsOrWhere, $join, $select);
		if (!empty($member)) {
			if (!empty($member->current_level)) {
				$url = null;
				$slug = null;
				$status = null;
				$totalVotes = null;

				if ($member->current_level == 1) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_1_status;
					$totalVotes = $member->level_1_total_votes;
				} else if ($member->current_level == 2) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_2_status;
					$totalVotes = $member->level_2_total_votes;
				} else if ($member->current_level == 3) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_3_status;
					$totalVotes = $member->level_3_total_votes;
				}

				$level = [
					'level' => $member->current_level,
					'url' => $url,
					'slug' => $slug,
					'status' => $status,
					// 'total_votes' => $totalVotes,
					'display_votes' => $this->generateDisplayTotalVotes($totalVotes)
				];

				$member->level = $level;
			}
			if (!empty($member->achievements)) {
				$member->achievements = json_decode($member->achievements);
			}
		}
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function getPollMembers ($filters = null, $paginated = true) {
		$args = [];
		$argsOrWhere = null;
		if (isset($filters['iden'])) {
			$argsOrWhere = [
				'phone_number' => $filters['iden'],
				'id' => $filters['iden']
			];
			unset($filters['iden']);
		}

		if (isset($filters['q']) && !empty($filters['q'])) {
			$argsOrWhere['poll_members.phone_number'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['poll_members.name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['poll_members.registration_number'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		if (isset($filters['q_name']) && !empty($filters['q_name'])) {
			$args['poll_members.name'] = [
				'condition' => 'like',
				'value' => $filters['q_name']
			];
		}

		if (isset($filters['status']) && !empty($filters['status'])) {
			$args['poll_members.status'] = $filters['status'];
		}

		if (isset($filters['registration_number']) && !empty($filters['registration_number'])) {
			if ($filters['registration_number'] == '~~') {
				$args['poll_members.registration_number <>'] = null;
			} else if ($filters['registration_number'] == '~') {
				$args['poll_members.registration_number'] = null;
			} else {
				$args['poll_members.registration_number'] = [
					'condition' => 'like',
					'value' => $filters['registration_number']
				];	
			}
		}

		if (isset($filters['m_status']) && !empty($filters['m_status'])) {
			$argsOrWhere = [
				'level_2_status' => $filters['m_status'],
				'level_3_status' => $filters['m_status'],
			];
			unset($filters['m_status']);
		}

		if (isset($filters['id_provinsi']) && !empty($filters['id_provinsi'])) {
			$args['poll_members.id_provinsi'] = $filters['id_provinsi'];
		}

		if (isset($filters['id_kabupaten']) && !empty($filters['id_kabupaten'])) {
			$args['poll_members.id_kabupaten'] = $filters['id_kabupaten'];
		}

		if (isset($filters['store_id']) && !empty($filters['store_id'])) {
			$args['poll_members.store_id'] = $filters['store_id'];
		}

		if (isset($filters['talent']) && !empty($filters['talent'])) {
			$args['poll_members.talent'] = $filters['talent'];
		}

		if (isset($filters['level_1_status']) && !empty($filters['level_1_status'])) {
			$args['poll_members.level_1_status'] = $filters['level_1_status'];
		}

		if (isset($filters['level_2_status']) && !empty($filters['level_2_status'])) {
			$args['poll_members.level_2_status'] = $filters['level_2_status'];
		}

		if (isset($filters['level_3_status']) && !empty($filters['level_3_status'])) {
			$args['poll_members.level_3_status'] = $filters['level_3_status'];
		}

		if (isset($filters['below_level_1_total_votes']) && !empty($filters['below_level_1_total_votes'])) {
			$args['poll_members.level_1_total_votes <='] = $filters['below_level_1_total_votes'];
		}
		if (isset($filters['below_level_2_total_votes']) && !empty($filters['below_level_2_total_votes'])) {
			$args['poll_members.level_2_total_votes <='] = $filters['below_level_2_total_votes'];
		}
		if (isset($filters['below_level_3_total_votes']) && !empty($filters['below_level_3_total_votes'])) {
			$args['poll_members.level_3_total_votes <='] = $filters['below_level_3_total_votes'];
		}
		$join = [
			'provinces' => [
				'value' => 'provinces.id = poll_members.id_provinsi',
				'type' => 'left'
			],
			'districts' => [
				'value' => 'districts.id_kab = poll_members.id_kabupaten',
				'type' => 'left'
			],
			'sub_district' => [
				'value' => 'sub_district.id_kec = poll_members.id_kecamatan',
				'type' => 'left'
			],
			'stores' => [
				'value' => 'stores.id = poll_members.store_id',
				'type' => 'left'
			]
		];
		$select = [
			'poll_members.id',
			'poll_members.phone_number',
			'poll_members.registration_number',
			'poll_members.name',
			'poll_members.birthdate',
			'poll_members.store_id',
			'stores.name as store_name',
			'poll_members.id_provinsi',
			'provinces.name as provinsi_nama',
			'poll_members.id_kabupaten',
			'districts.nama as kabupaten_nama',
			'poll_members.id_kecamatan',
			'sub_district.nama as kecamatan_nama',
			'poll_members.address',
			'poll_members.invoice_number',
			'poll_members.profile_picture_url',
			'poll_members.from_school',
			'poll_members.talent',
			'poll_members.achievements',
			'poll_members.status',
			'poll_members.current_level',
			'poll_members.level_1_slug',
			'poll_members.level_1_status',
			'poll_members.level_1_total_votes',
			'poll_members.level_2_slug',
			'poll_members.level_2_status',
			'poll_members.level_2_total_votes',
			'poll_members.level_3_slug',
			'poll_members.level_3_status',
			'poll_members.level_3_total_votes',
			'poll_members.created_at',
		];
		$offset = 0;
		$limit = 20;
		$orderKey = 'poll_members.id';
		$orderValue = 'ASC';
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}
		if ($paginated) {
			$members = $this->repository->findPaginated('poll_members', $args, $argsOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue);
		} else {
			$members = $this->repository->find('poll_members', $args, $argsOrWhere, $join, $select);
			$members['result'] = $members;
		}
		foreach ($members['result'] as $member) {
			if (isset($member->achievements) && isJson($member->achievements)) {
				$member->achievements = json_decode($member->achievements);
			}
			if (!empty($member->current_level)) {
				$url = null;
				$slug = null;
				$status = null;
				$totalVotes = null;

				if ($member->current_level == 1) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_1_status;
					$totalVotes = $member->level_1_total_votes;
				} else if ($member->current_level == 2) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_2_status;
					$totalVotes = $member->level_2_total_votes;
				} else if ($member->current_level == 3) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_3_status;
					$totalVotes = $member->level_3_total_votes;
				}
				
				$level = [
					'level' => $member->current_level,
					'url' => $url,
					'slug' => $slug,
					'status' => $status,
					'total_votes' => $totalVotes,
					'display_votes' => $this->generateDisplayTotalVotes($totalVotes)
				];
				$member->level = $level;
			}
			
			$storeName = $member->store_name;
			if (empty($storeName)) {
				$storeName = 'Outlet';
			}
			if ($member->current_level == 1) {
				$title = 'Peserta Audisi Tingkat '.$storeName;
			} else if ($member->current_level == 2) {
				$title = 'Peserta Audisi Tingkat Wilayah';
			} else {
				$title = 'Peserta Audisi Tingkat Nasional';
			}
			$member->title = $title;

			if (isset($filters['public']) && $filters['public'] == true) {
				$member = $this->cleanObjectMember($member);
			}
		}
		$this->delivery->data = $members;
		return $this->delivery;
	}

	public function createPollMember ($payload) {
		$existsMember = $this->repository->findOne('poll_members', ['phone_number' => $payload['phone_number']]);
		if (!empty($existsMember)) {
			$this->delivery->addError(409, 'Member already exists.');
			return $this->delivery;
		}
		$payload['status'] = self::STATUS_PENDING;
		$payload['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('poll_members', $payload);
		$result = $this->repository->findOne('poll_members', ['id' => $action]);
		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function updatePollMember ($payload, $filters) {
		$existsMembers = $this->repository->find('poll_members', $filters);
		if (empty($existsMembers)) {
			$this->delivery->addError(400, 'No member found.');
			return $this->delivery;
		}
		unset($payload['phone_number']);
		$payload['updated_at'] = date('Y-m-d H:i:s');
		try {
			if (isset($payload['achievements']) && is_array($payload['achievements'])) {
				$payload['achievements'] = json_encode($payload['achievements']);
			}
			if (isset($payload['talent']) && !empty($payload['talent'])) {
				if (empty($this->generateTalentChoices($payload['talent']))) {
					$this->delivery->addError(400, 'Wrong talent choices');
					return $this->delivery;
				} else {
					$payload['talent'] = $this->generateTalentChoices($payload['talent'])['key'];
				}
			}
			$action = $this->repository->update('poll_members', $payload, $filters);
			$result = $this->getPollMemberProfile($filters)->data;
			$this->delivery->data = $result;
		} catch (\Exception $e) {
			$this->delivery->addError(400, 'Missing required fields');
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function addTotalVoteToMember ($member, $totalVote) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}
		$level = $member->level;
		$currentLevel = $level['level'];
		$key = null;
		if ($currentLevel == 1) {
			$key = 'level_1_total_votes';
		} else if ($currentLevel == 2) {
			$key = 'level_2_total_votes';
		} else if ($currentLevel == 3) {
			$key = 'level_3_total_votes';
		}
		$query = sprintf("UPDATE poll_members SET poll_members.%s = poll_members.%s + %d WHERE poll_members.id = %d", $key, $key, (int)$totalVote, $member->id);
		$action = $this->repository->executeRawQuery($query);
		$this->delivery->data = $action;
		return $this->delivery;
	}

	public function levelUpMember ($member, $sendNotif = true) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}

		$level = $member->level;
		$currentLevel = $level['level'];
		$targetLevel = null;
		$key = null;
		$currentLevelStatus = null;
		$targetLevelStatus = null;
		$payload = [];
		if ($currentLevel == 1) {
			$payload = [
				'current_level' => 2,
				'level_1_status' => self::LEVEL_STATUS_PASS,
				'level_2_status' => self::LEVEL_STATUS_PENDING,
			];
			if (empty($member->level_2_slug)) {
				$payload['level_2_slug'] = $this->generateLevelSlug($targetLevel, $member->id);
			}

			if ($sendNotif) {
				$pollHandler = new PollHandler($this->repository);
				$message = 'Assalamualaikum '.$member->name.PHP_EOL.PHP_EOL.'*SELAMAT KAMU LOLOS MENJADI SEMIFINALIS DUTA PELAJAR RABBANI 2023*'.PHP_EOL.'Alhamdulillah'.PHP_EOL.PHP_EOL.'Segera masuk Group Whatsapp SEMIFINALIS DUTA PELAJAR RABBANI 2023 '.PHP_EOL.'https://chat.whatsapp.com/JVGXwxxTKxK4MOz86bLi4F'.PHP_EOL.PHP_EOL.'Yuk cari terus dukungan sebanyak-banyak, supaya kamu bisa melaju ke babak FINAL.'.PHP_EOL.PHP_EOL.'share ke semua contact kamu yaa :'.PHP_EOL.$level['url'].PHP_EOL.PHP_EOL.'Sampai bertemu di BABAK SEMIFINAL';
				$actionNotif = $pollHandler->sendWablasByPhoneNumber($member->phone_number, $message);
			}

		} else if ($currentLevel == 2) {
			$payload = [
				'current_level' => 3,
				'level_2_status' => self::LEVEL_STATUS_PASS,
				'level_3_status' => self::LEVEL_STATUS_PENDING,
			];
			if (empty($member->level_3_slug)) {
				$payload['level_3_slug'] = $this->generateLevelSlug($targetLevel, $member->id);
			}

			if ($sendNotif) {
				$pollHandler = new PollHandler($this->repository);
				$message = 'Assalamualaikum '.$member->name.PHP_EOL.PHP_EOL.'*SELAMAT!! KAMU LOLOS MENJADI FINALIS DUTA PELAJAR RABBANI 2023*'.PHP_EOL.'Alhamdulillah'.PHP_EOL.PHP_EOL.'Tinggal selangkah lagi menuju THE NEXT DUTA PELAJAR RABBANI 2023'.PHP_EOL.PHP_EOL.'Segera masuk Group Whatsapp FINALIS DUTA PELAJAR RABBANI 2023 '.PHP_EOL.'https://chat.whatsapp.com/E5YMTZV7xM61j2hsKqflaa'.PHP_EOL.PHP_EOL.'Yuk cari terus dukungan sebanyak-banyak ya, supaya kamu bisa menjadi THE NEXT DUTA PELAJAR RABBANI 2020.'.PHP_EOL.PHP_EOL.'share ke semua contact kamu yaa :'.PHP_EOL.$level['url'].PHP_EOL.PHP_EOL.'Sampai bertemu di BABAK FINAL';
				$actionNotif = $pollHandler->sendWablasByPhoneNumber($member->phone_number, $message);
			}
		} else if ($currentLevel == 3) {
			$payload = [
				'level_3_status' => self::LEVEL_STATUS_PASS,
			];
		}

		$this->delivery->data = $member;
		if (!empty($payload)) {
			$filters = ['id' => $member->id];
			$action = $this->updatePollMember($payload, $filters);
			$this->delivery->data = $action->data;
		}

		return $this->delivery;
	}

	public function failMember ($member) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}

		$level = $member->level;
		$currentLevel = $level['level'];
		$payload = [];
		if ($currentLevel == 1) {
			$payload = [
				'level_1_status' => self::LEVEL_STATUS_FAIL,
			];
		} else if ($currentLevel == 2) {
			$payload = [
				'level_2_status' => self::LEVEL_STATUS_FAIL,
			];
		} else if ($currentLevel == 3) {
			$payload = [
				'level_3_status' => self::LEVEL_STATUS_FAIL,
			];
		}

		$this->delivery->data = $member;
		if (!empty($payload)) {
			$filters = ['id' => $member->id];
			$action = $this->updatePollMember($payload, $filters);

			if ($currentLevel == 1) {
				$pollHandler = new PollHandler($this->repository);
				$message = 'Assalamualaikum '.$member->name.PHP_EOL.PHP_EOL.'Mohon maaf, kamu belum lolos ke babak selanjutnya.'.PHP_EOL.PHP_EOL.'Tapi jangan bersedih hati. Kamu masih bisa ikut serta dengan cara mendukung teman-teman kamu di Pemilihan Duta Pelajar Rabbani 2023'.PHP_EOL.PHP_EOL.'Terimakasih telah berpartisipasi dalam Pemilihan Duta Pelajar Rabbani 2023.';
				$actionNotif = $pollHandler->sendWablasByPhoneNumber($member->phone_number, $message);
				$this->delivery->data = $action->data;
			} else if ($currentLevel == 2) {
				$pollHandler = new PollHandler($this->repository);
				$message = 'Hallo '.$member->name.PHP_EOL.'terimakasih sudah berpartisipasi di Semifinal Duta Pelajar Rabbani 2023'.PHP_EOL.PHP_EOL.'Tetap semangat, jangan berkecil hati! Jadikan sebuah pengalaman yang sangat berharga. '.PHP_EOL.PHP_EOL.'Kalian masih bisa berkesempatan untuk berkontribusi di konten dan event RABBANI kota masing-masing.'.PHP_EOL.PHP_EOL.'Sampai bertemu di Duta Pelajar Rabbani selanjutnya';
				$actionNotif = $pollHandler->sendWablasByPhoneNumber($member->phone_number, $message);
				$this->delivery->data = $action->data;
			}

		}

		return $this->delivery;
	}

	public function updateFileMemberProfile ($key, $payload) {
		if (!empty($this->getAuth())) {
			$id  = $this->getAuth()['id'];
		} else {
			$this->delivery->addError(400, 'Auth is required');
			return $this->delivery;
		}
		$digitalOceanService = new DigitalOceanService;
		try {
			$action = $digitalOceanService->upload($payload, 'file');
			$payload = [
				$key => $action['cdn_url'],
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->update('poll_members', $payload, ['id' => $id]);
			$result = $this->getPollMember(['id' => $id])->data;
			$this->delivery->data = $result;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function verifyPollMember ($id) {
		$existsMembers = $this->repository->findOne('poll_members', ['id' => $id]);
		if (empty($existsMembers)) {
			$this->delivery->addError(400, 'No member found.');
			return $this->delivery;
		}

		/* if ($existsMembers->status != self::STATUS_PENDING) {
			$this->delivery->addError(400, 'Member already processed');
			return $this->delivery;
		} */

		try {
			$slug = $this->generateLevelSlug(1, $id);
			$payload = [
				'registration_number' => sprintf('%s%s', 'DPR', time()),
				'current_level' => 1,
				'level_1_slug' => $slug,
				'level_1_status' => self::LEVEL_STATUS_PENDING,
				'level_1_total_votes' => 0,
				'status' => self::STATUS_VERIFIED,
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->update('poll_members', $payload, ['id' => $id]);
			$result = $this->getPollMemberProfile(['id' => $id])->data;

			// notif
			$member = $result;
			$level = $member->level;
			$pollHandler = new PollHandler($this->repository);
			$message = 'Selamat data kamu berhasil diverifikasi.'.PHP_EOL.''.PHP_EOL.'Sekarang kamu resmi menjadi CDPR 2023, silahkan sebarkan profil '.$level['url'].' kamu untuk mendapatkan dukungan vote'.PHP_EOL.''.PHP_EOL.'agar link bisa di klik silahkan balas pesan ini';
			$actionNotif = $pollHandler->sendWablasByPhoneNumber($existsMembers->phone_number, $message);
			$this->delivery->data = $result;
		} catch (\Exception $e) {
			$this->delivery->addError(400, 'Missing required fields');
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function getPollMemberProfile ($filters = null) {
		$args = [];
		if (!empty($this->getAuth())) {
			$args['poll_members.id'] = $this->getAuth()['id'];
		}

		if (isset($filters['id']) && !empty($filters['id'])) {
			$args['poll_members.id'] = $filters['id'];
		}

		if (isset($filters['registration_number']) && !empty($filters['registration_number'])) {
			$args['poll_members.registration_number'] = $filters['registration_number'];
		}

		if (isset($filters['status']) && !empty($filters['status'])) {
			$args['poll_members.status'] = $filters['status'];
		}

		if (isset($filters['slug'])) {
			$slug = $this->decodeLevelSlug($filters['slug']);
			if (empty($slug['poll_member_id'])) {
				$this->delivery->addError(400, 'Incorrect slug');
				return $this->delivery;
			}
			$args['poll_members.id'] = $slug['poll_member_id'];
			unset($filters['slug']);
		}
		$join = [
			'provinces' => [
				'value' => 'provinces.id = poll_members.id_provinsi',
				'type' => 'left'
			],
			'districts' => [
				'value' => 'districts.id_kab = poll_members.id_kabupaten',
				'type' => 'left'
			],
			'sub_district' => [
				'value' => 'sub_district.id_kec = poll_members.id_kecamatan',
				'type' => 'left'
			],
			'stores' => [
				'value' => 'stores.id = poll_members.store_id',
				'type' => 'left'
			]
		];
		$select = [
			'poll_members.id',
			'poll_members.phone_number',
			'poll_members.registration_number',
			'poll_members.profile_picture_url',
			'poll_members.name',
			'poll_members.birthdate',
			'poll_members.store_id',
			'stores.name as store_name',
			'poll_members.id_provinsi',
			'provinces.name as provinsi_nama',
			'poll_members.id_kabupaten',
			'districts.nama as kabupaten_nama',
			'poll_members.id_kecamatan',
			'sub_district.nama as kecamatan_nama',
			'poll_members.address',
			'poll_members.from_school',
			'poll_members.talent',
			'poll_members.invoice_number',
			'poll_members.status',
			'poll_members.parental_certificate_url',
			'poll_members.school_recommendation_letter_url',
			'poll_members.instagram_username',
			'poll_members.instagram_followers',
			'poll_members.tiktok_username',
			'poll_members.tiktok_followers',
			'poll_members.twitter_username',
			'poll_members.twitter_followers',
			'poll_members.facebook_username',
			'poll_members.facebook_followers',
			'poll_members.youtube_username',
			'poll_members.youtube_followers',
			'poll_members.rating_photo_url',
			'poll_members.rating_video_url',
			'poll_members.current_level',
			'poll_members.level_1_slug',
			'poll_members.level_1_status',
			'poll_members.level_1_total_votes',
			'poll_members.level_2_slug',
			'poll_members.level_2_status',
			'poll_members.level_2_total_votes',
			'poll_members.level_3_slug',
			'poll_members.level_3_status',
			'poll_members.level_3_total_votes',
			'poll_members.achievements',
			'poll_members.created_at',
		];
		$member = $this->repository->findOne('poll_members', $args, null, $join, $select);
		if (!empty($member)) {
			if (!empty($member->current_level)) {
				$url = null;
				$slug = null;
				$status = null;
				$totalVotes = null;

				if ($member->current_level == 1) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_1_status;
					$totalVotes = $member->level_1_total_votes;
				} else if ($member->current_level == 2) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_2_status;
					$totalVotes = $member->level_2_total_votes;
				} else if ($member->current_level == 3) {
					$url = self::BASE_URL_PUBLIC_POLL.urlencode($member->registration_number);
					$slug = $member->registration_number;
					$status = $member->level_3_status;
					$totalVotes = $member->level_3_total_votes;
				}

				$storeName = $member->store_name;
				if (empty($storeName)) {
					$storeName = 'Outlet';
				}
				if ($member->current_level == 1) {
					$title = 'Peserta Audisi Tingkat '.$storeName;
				} else if ($member->current_level == 2) {
					$title = 'Peserta Audisi Tingkat Wilayah';
				} else {
					$title = 'Peserta Audisi Tingkat Nasional';
				}
				$member->title = $title;
				$member->level_1_title = 'Jumlah Dukungan Outlet';
				$member->level_2_title = 'Jumlah Dukungan Wilayah';
				$member->level_3_title = 'Jumlah Dukungan Nasional';

				
				$level = [
					'level' => $member->current_level,
					'url' => $url,
					'slug' => $slug,
					'status' => $status,
					// 'total_votes' => $totalVotes,
					'display_votes' => $this->generateDisplayTotalVotes($totalVotes)
				];

				if (!empty($this->getAuth())) {
					$level['total_votes'] = $totalVotes;
				}

				$member->level = $level;

				if (isset($filters['public']) && $filters['public'] == true) {
					$member = $this->cleanObjectMember($member);
				}
			}
			if (!empty($member->achievements) && isJson($member->achievements)) {
				$member->achievements = json_decode($member->achievements);
			}
		}
		$this->delivery->data = $member;
		return $this->delivery;
	}

	public function getFiles ($filters = null) {
		if (!empty($this->getAuth())) {
			$filters = [];
			$filters['poll_member_files.poll_member_id'] = $this->getAuth()['id'];
		}
		$orderKey = 'poll_member_files.id';
		$orderValue = 'ASC';
		$files = $this->repository->find('poll_member_files', $filters, null, null, null, null, null, $orderKey, $orderValue);
		$this->delivery->data = $files;
		return $this->delivery;
	}

	public function addFile ($payload) {
		if (!empty($this->getAuth())) {
		} else {
			$this->delivery->addError(400, 'Auth is required');
			return $this->delivery;
		}
		$digitalOceanService = new DigitalOceanService;
		try {
			$action = $digitalOceanService->upload($payload, 'file');
			$payload = [
				'poll_member_id' => $this->getAuth()['id'],
				'file_url' => $action['cdn_url'],
				'description' => $payload['description'],
				'type' => $payload['type'],
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->insert('poll_member_files', $payload);
			$file = $this->repository->findOne('poll_member_files', ['id' => $action]);
			$this->delivery->data = $file;
		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function deleteFile ($filters = null) {
		if (!empty($this->getAuth())) {
		} else {
			$this->delivery->addError(400, 'Auth is required');
			return $this->delivery;
		}
		$file = $this->repository->findOne('poll_member_files', $filters);
		if (empty($file)) {
			$this->delivery->addError(400, 'File is required');
			return $this->delivery;
		}
		if ($file->poll_member_id != $this->getAuth()['id']) {
			$this->delivery->addError(400, 'Auth is required');
			return $this->delivery;
		}
		$payload['deleted_at'] = date('Y-m-d H:i:s');
		try {
			$action = $this->repository->update('poll_member_files', $payload, $filters);
			$this->delivery->data = 'ok';
		} catch (\Exception $e) {
			$this->delivery->addError(400, 'Missing required fields');
			return $this->delivery;
		}
		return $this->delivery;
	}

	public function generateTalentChoices ($talent = null) {
		$choices = [];
		$choices[] = [
			'key' => self::TALENT_SINGER,
			'text' => 'Menyanyi / Singer'
		];
		$choices[] = [
			'key' => self::TALENT_PUBLIC_SPEAKING,
			'text' => 'Public Speaking'
		];
		$choices[] = [
			'key' => self::TALENT_ACTING,
			'text' => 'Akting / Kabaret'
		];
		$choices[] = [
			'key' => self::TALENT_MUROTAL,
			'text' => 'Murotal / hafidz'
		];
		$choices[] = [
			'key' => self::TALENT_MODELING,
			'text' => 'Modeling'
		];
		$choices[] = [
			'key' => self::TALENT_OTHER,
			'text' => 'Lainnya'
		];

		if (!empty($talent)) {
			$find = array_search($talent, array_column($choices, 'key'));
			if ($find !== false) {
				return $choices[$find];
			} else {
				return null;
			}
		}

		return $choices;
	}

	// use this so can find member with index faster
	private function generateLevelSlug ($level, $id) {
		return base64_encode(sprintf('level-%s-%s', $level, $id));
	}

	private function decodeLevelSlug ($slug) {
		$rawSlug = base64_decode($slug);
		$level = null;
		$slug = $slug;
		$pollMemberId = null;
		
		if (strpos($rawSlug, 'level-1-') !== false) {
			$level = 1;
			$slug = $slug;
			$pollMemberId = intval(str_replace('level-1-', '', $rawSlug));
		} else if (strpos($rawSlug, 'level-2-') !== false) {
			$level = 2;
			$slug = $slug;
			$pollMemberId = intval(str_replace('level-2-', '', $rawSlug));
		} else if (strpos($rawSlug, 'level-3-') !== false) {
			$level = 3;
			$slug = $slug;
			$pollMemberId = intval(str_replace('level-3-', '', $rawSlug));
		}
		$result = [
			'level' => $level,
			'slug' => $slug,
			'poll_member_id' => $pollMemberId,
		];
		return $result;
	}

	private function generateDisplayTotalVotes ($totalVotes) {
		$text = '1 - 100';
		if ($totalVotes <= 100) {
			$text  = '1 - 100';
		} else if ($totalVotes > 100 && $totalVotes <= 1000) {
			$divider = $totalVotes / 1000;
			$text = ' > '.intval($divider).'k';
			$text = '101 - 1k';
		} else if ($totalVotes > 1000) {
			$divider = $totalVotes / 1000;
			$min = intval($divider);
			$max = intval($divider + 1);
			$text = $min.'k - '.$max.'k';
		}
		return $totalVotes;
	}

	private function cleanObjectMember ($member) {
		/* unset($member->level_1_slug);
		unset($member->level_1_status);
		unset($member->level_1_total_votes);
		unset($member->level_2_slug);
		unset($member->level_2_status);
		unset($member->level_2_total_votes);
		unset($member->level_3_slug);
		unset($member->level_3_status);
		unset($member->level_3_total_votes);
		unset($member->level['total_votes']); */
		return $member;
	}

	// ==================== CRON ============================

	public function sendNotifyVote ($level = 1) {
		$totalVoteKey = 'level_1_total_votes';
		$statusKey = 'level_1_status';
		$filters = 'below_level_1_total_votes';
		if ($level == 1) {
			$totalVoteKey = 'level_1_total_votes';
			$statusKey = 'level_1_status';
			$filters = 'below_level_1_total_votes';
		} else if ($level == 2) {
			$totalVoteKey = 'level_2_total_votes';
			$statusKey = 'level_2_status';
			$filters = 'below_level_2_total_votes';
		} else if ($level == 3) {
			$totalVoteKey = 'level_3_total_votes';
			$statusKey = 'level_3_status';
			$filters = 'below_level_3_total_votes';
		} else {
			echo 'error';
			die();
		}
		$query = "SELECT AVG(".$totalVoteKey.") as average_total_votes FROM poll_members WHERE poll_members.".$statusKey." = 'pending' AND poll_members.status = 'verified'";
		$action = $this->repository->executeRawQuery($query);
		$result = $action->result();
		$avgTotalVotes = intval($result[0]->average_total_votes);
		if (empty($avgTotalVotes)) {
			echo 'wrong avg total votes';
			die();
		}
		$argsMembers = [
			'status' => self::STATUS_VERIFIED,
		];
		$argsMembers[$statusKey] = self::LEVEL_STATUS_PENDING;
		$argsMembers[$filters] = $avgTotalVotes;

		$memberResult = $this->getPollMembers($argsMembers, false);
		$members = $memberResult->data;
		$pollHandler = new PollHandler($this->repository);
		foreach ($members as $key => $member) {
			if (is_int($key)) {
				$level = $member->level;
				$message = "Assalamualaikum ".$member->name.PHP_EOL."*Waduh!*".PHP_EOL."Jumlah VOTE kamu masih minim nih".PHP_EOL.PHP_EOL."Yuk segera share Link ".$level['url']." untuk meminta dukungan dari keluarga, teman, sahabat, dan semuanya. ".PHP_EOL."Share sebanyak-banyaknya, supaya kamu bisa selangkah lebih depan , untuk melaju ke Babak selanjutnya";
				$actionNotif = $pollHandler->sendWablasByPhoneNumber($member->phone_number, $message);

				echo 'Send message to: '.$member->phone_number.'. Message: '.$message.PHP_EOL;
			}
		}


	}
}