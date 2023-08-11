<?php
namespace Service\Poll;

use Service\Entity;
use Service\Delivery;
use Library\DigitalOceanService;
use Library\WablasService;
use Library\XenditService;
use Library\TripayGateway;

class PollWablasHandler {

	const APP_ENV = 'dev';
	const MAIN_WABLAS = '6289674545000';

	const WABLAS_MENU_STATE_MEMBER_MENU = 'member_menu';
	const WABLAS_MENU_STATE_ASK_PHOTO = 'ask_photo';
	const WABLAS_MENU_STATE_ASK_VIDEO = 'ask_video';
	const WABLAS_MENU_QUESTION_LVL_1_1 = 'question_lvl_1_1';
	const WABLAS_MENU_QUESTION_LVL_1_2 = 'question_lvl_1_2';
	const WABLAS_MENU_QUESTION_LVL_1_3 = 'question_lvl_1_3';
	const WABLAS_MENU_QUESTION_LVL_1_4 = 'question_lvl_1_4';
	const WABLAS_MENU_QUESTION_LVL_1_5 = 'question_lvl_1_5';
	const WABLAS_MENU_QUESTION_LVL_1_6 = 'question_lvl_1_6';
	const WABLAS_MENU_QUESTION_LVL_1_7 = 'question_lvl_1_7';
	const WABLAS_MENU_QUESTION_LVL_1_8 = 'question_lvl_1_8';

	const WABLAS_MENU_QUESTION_LVL_2_1 = 'question_lvl_2_1';
	const WABLAS_MENU_QUESTION_LVL_2_2 = 'question_lvl_2_2';
	const WABLAS_MENU_QUESTION_LVL_2_3 = 'question_lvl_2_3';
	const WABLAS_MENU_QUESTION_LVL_2_4 = 'question_lvl_2_4';
	const WABLAS_MENU_QUESTION_LVL_2_5 = 'question_lvl_2_5';
	const WABLAS_MENU_QUESTION_LVL_2_6 = 'question_lvl_2_6';
	const WABLAS_MENU_QUESTION_LVL_2_7 = 'question_lvl_2_7';
	const WABLAS_MENU_QUESTION_LVL_2_8 = 'question_lvl_2_8';
	const WABLAS_MENU_QUESTION_LVL_2_9 = 'question_lvl_2_9';

	const WABLAS_MENU_QUESTION_LVL_3_1 = 'question_lvl_3_1';
	const WABLAS_MENU_QUESTION_LVL_3_2 = 'question_lvl_3_2';
	const WABLAS_MENU_QUESTION_LVL_3_3 = 'question_lvl_3_3';
	const WABLAS_MENU_QUESTION_LVL_3_4 = 'question_lvl_3_4';
	const WABLAS_MENU_QUESTION_LVL_3_5 = 'question_lvl_3_5';
	const WABLAS_MENU_QUESTION_LVL_3_6 = 'question_lvl_3_6';
	const WABLAS_MENU_QUESTION_LVL_3_7 = 'question_lvl_3_7';
	const WABLAS_MENU_QUESTION_LVL_3_8 = 'question_lvl_3_8';
	const WABLAS_MENU_QUESTION_LVL_3_9 = 'question_lvl_3_9';

	private $auth;
	private $delivery;
	private $uploadPath;
	private $repository;
	private $waService;

	private $generalMenuText;
	private $unauthorizedText;
	private $requestTagCodeText;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
		$this->generalMenuText = 'Masukkan no registrasi calon DPR';
		$this->unauthorizedText = 'Anda tidak terdaftar pada sistem Whatsapp Rabbani Store';
		$this->memberMenuText = '1. Profil'.PHP_EOL.'2. Isi Penilaian'.PHP_EOL.'3. Upload Foto Peserta'.PHP_EOL.'4. Upload Video Peserta'.PHP_EOL.'5. Keluar';
	}

	/**
	 * Minta nama, tanggal lahir, jenis kelamin, alamat, provinsi, kabupaten, kota. Setelah itu generate image dan member id
	 * 
	 * 
	 **/
	public function callbackAction ($payload) {
		$result = '';

		if (empty(trim($payload['message']))) {
			$this->delivery->addError(409, 'Silahkan masukkan pesan');
			return $this->delivery;
		}
		$authApi = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => $payload['receiver']]);
		if (empty($authApi)) {
			$this->delivery->addError(409, 'Config error!');
			return $this->delivery;
		}


		$sender = getFormattedPhoneNumber($payload['phone']);
		$sender = '0'.substr($sender, 2);
		$existsStore = $this->repository->findOne('admins', ['phone' => $sender]);
		if (empty($existsStore)) {
			die();
			$this->delivery->addError(409, $this->unauthorizedText);
			return $this->delivery;
		}

		if (self::APP_ENV != 'dev') {
			if ($existsStore->last_wablas_id == $payload['id']) {
				die();
			} else {
				$action = $this->repository->update('admins', ['last_wablas_id' => $payload['id']], ['id' => $existsStore->id]);
			}
		}

		$memberHandler = new PollMemberHandler($this->repository);
		if (!empty($existsStore->dpr_wablas_menu_state) && !empty($existsStore->dpr_poll_member_id)) {
			$filters = ['id' => $existsStore->dpr_poll_member_id];
			$memberResult = $memberHandler->getPollMemberProfile($filters);
			$member = $memberResult->data;

			$ratingHandler = new PollMemberRatingHandler($this->repository);
			$ratingResult = $ratingHandler->getPollMemberRating(['poll_member_id' => $member->id]);
			$rating = $ratingResult->data;
			$this->handleWablasMenu($existsStore, $member, $rating, $payload['message']);
			return $this->delivery;
		}

		$message = strtolower($payload['message']);
		$filters = [
			'registration_number' => $message,
		];
		$memberResult = $memberHandler->getPollMemberProfile($filters);
		$member = $memberResult->data;
		if (!empty($member)) {
			$this->delivery->data = $this->memberMenuText;
			$payloadStore = [
				'dpr_wablas_menu_state' => self::WABLAS_MENU_STATE_MEMBER_MENU,
				'dpr_poll_member_id' => $member->id,
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->update('admins', $payloadStore, ['id' => $existsStore->id]);
			return $this->delivery;
		}

		$this->delivery->data = $this->generalMenuText;
		return $this->delivery;
	}

	private function handleWablasMenu ($store, $member, $rating, $message) {
		if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_STATE_MEMBER_MENU) {
			$this->handleMemberMenu($store, $member, $rating, $message);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_1) {
			$value = intval($message);
			$currentKey = 'level_1_additional_total_likes';
			$nextKey = 'level_1_additional_total_comments';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_2);
			$this->delivery->data = 'Screening Data Dukungan'.PHP_EOL.'Jumlah Komentar? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_2) {
			$value = intval($message);
			$currentKey = 'level_1_additional_total_comments';
			$nextKey = 'level_1_look_photo';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Look'.PHP_EOL.'Foto? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_3);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_3) {
			$value = intval($message);
			$currentKey = 'level_1_look_photo';
			$nextKey = 'level_1_look_wear_rabbani_product';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Look'.PHP_EOL.'Menggunakan Product Rabbani? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_4);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_4) {
			$value = intval($message);
			$currentKey = 'level_1_look_wear_rabbani_product';
			$nextKey = 'level_1_talent_read_al_quran';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Bacaan / Hafalan Al-Quran? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_5);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_5) {
			$value = intval($message);
			$currentKey = 'level_1_talent_read_al_quran';
			$nextKey = 'level_1_talent_talent_show';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Unjuk Bakat? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_6);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_6) {
			$value = intval($message);
			$currentKey = 'level_1_talent_talent_show';
			$nextKey = 'level_1_talent_communication_skill';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Skill Komunikasi? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_7);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_7) {
			$value = intval($message);
			$currentKey = 'level_1_talent_communication_skill';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$rating->level_1_talent_communication_skill = $value;
			$this->delivery->data = $this->generateFinalRate($member, $rating).PHP_EOL.$this->memberMenuText;
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_1) {
			// level 2 start
			$value = intval($message);
			$currentKey = 'level_2_look_veil';
			$nextKey = 'level_2_look_wardrobe';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_2);
			$this->delivery->data = 'Screening Look'.PHP_EOL.'Busana Rabbani / Dluha? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_2) {
			$value = intval($message);
			$currentKey = 'level_2_look_wardrobe';
			$nextKey = 'level_2_look_makeup';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Look'.PHP_EOL.'Makeup? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_3);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_3) {
			$value = intval($message);
			$currentKey = 'level_2_look_makeup';
			$nextKey = 'level_2_talent_talent_show';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Unjuk Bakat? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_4);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_4) {
			$value = intval($message);
			$currentKey = 'level_2_talent_talent_show';
			$nextKey = 'level_2_talent_fashion_show';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Fashion Show? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_5);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_5) {
			$value = intval($message);
			$currentKey = 'level_2_talent_fashion_show';
			$nextKey = 'level_2_talent_jury';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Tanya jawab juri? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_6);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_6) {
			$value = intval($message);
			$currentKey = 'level_2_talent_jury';
			$nextKey = 'level_2_talent_testimony';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Testimoni? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_7);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_7) {
			$value = intval($message);
			$currentKey = 'level_2_talent_testimony';
			$nextKey = 'level_2_support_polling';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Support Netizen'.PHP_EOL.'Dukungan by Polling Tools? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_8);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_8) {
			$value = intval($message);
			$currentKey = 'level_2_support_polling';
			$nextKey = 'level_2_support_personal';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Support Netizen'.PHP_EOL.'Dukungan by Postingan pribadi? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_9);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_2_9) {
			$value = intval($message);
			$currentKey = 'level_2_support_personal';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$rating->level_2_support_personal = $value;
			$this->delivery->data = $this->generateFinalRate($member, $rating).PHP_EOL.$this->memberMenuText;
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_1) {
			// level 3 start
			$value = intval($message);
			$currentKey = 'level_3_look_veil';
			$nextKey = 'level_3_look_wardrobe';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_2);
			$this->delivery->data = 'Screening Look'.PHP_EOL.'Busana Rabbani / Dluha? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_2) {
			$value = intval($message);
			$currentKey = 'level_3_look_wardrobe';
			$nextKey = 'level_3_look_makeup';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Look'.PHP_EOL.'Makeup? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_3);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_3) {
			$value = intval($message);
			$currentKey = 'level_3_look_makeup';
			$nextKey = 'level_3_talent_talent_show';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Unjuk Bakat? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_4);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_4) {
			$value = intval($message);
			$currentKey = 'level_3_talent_talent_show';
			$nextKey = 'level_3_talent_fashion_show';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Fashion Show? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_5);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_5) {
			$value = intval($message);
			$currentKey = 'level_3_talent_fashion_show';
			$nextKey = 'level_3_talent_jury';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Tanya jawab juri? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_6);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_6) {
			$value = intval($message);
			$currentKey = 'level_3_talent_jury';
			$nextKey = 'level_3_talent_testimony';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Bakat'.PHP_EOL.'Testimoni? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_7);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_7) {
			$value = intval($message);
			$currentKey = 'level_3_talent_testimony';
			$nextKey = 'level_3_support_polling';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Support Netizen'.PHP_EOL.'Dukungan by Polling Tools? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_8);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_8) {
			$value = intval($message);
			$currentKey = 'level_3_support_polling';
			$nextKey = 'level_3_support_personal';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->delivery->data = 'Screening Support Netizen'.PHP_EOL.'Dukungan by Postingan pribadi? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_9);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_3_9) {
			$value = intval($message);
			$currentKey = 'level_3_support_personal';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$rating->level_3_support_personal = $value;
			$this->delivery->data = $this->generateFinalRate($member, $rating).PHP_EOL.$this->memberMenuText;
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_STATE_ASK_PHOTO) {
			if (strpos($message, 'https://solo.wablas.com') !== false) {
				try {
					$ci =& get_instance();
			        $image_path = $ci->config->item('image_path');
					$digitalOceanService = new DigitalOceanService;
					$extension = pathinfo($message);
					$filename = uniqid().uniqid().'.'.$extension['extension'];
					$localPath = $image_path.'/'.$filename;
					file_put_contents($localPath, fopen($message, 'r'));
					$action = $digitalOceanService->upload_to_cloud($localPath, $filename);
					unlink($localPath);
					$payload = [
						'poll_member_id' => $member->id,
						'type' => 'photo',
						'url' => $action['cdn_url'],
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s')
					];
					$action = $this->repository->insert('poll_member_rating_files', $payload);
					$this->delivery->data = 'Berhasil menyimpan'.PHP_EOL.$this->memberMenuText;
					$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
				} catch (\Exception $e) {
					$this->delivery->addError(500, $e->getMessage());
					$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
				}
			} else {
				$this->delivery->data = 'Gagal menyimpan'.PHP_EOL.$this->memberMenuText;
				$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
			}
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_STATE_ASK_VIDEO) {
			if (strpos($message, 'https://solo.wablas.com') !== false) {
				try {
					$ci =& get_instance();
			        $image_path = $ci->config->item('image_path');
					$digitalOceanService = new DigitalOceanService;
					$extension = pathinfo($message);
					$filename = uniqid().uniqid().'.'.$extension['extension'];
					$localPath = $image_path.'/'.$filename;
					file_put_contents($localPath, fopen($message, 'r'));
					$action = $digitalOceanService->upload_to_cloud($localPath, $filename);
					unlink($localPath);
					$payload = [
						'poll_member_id' => $member->id,
						'type' => 'video',
						'url' => $action['cdn_url'],
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s')
					];
					$action = $this->repository->insert('poll_member_rating_files', $payload);
					$this->delivery->data = 'Berhasil menyimpan'.PHP_EOL.$this->memberMenuText;
					$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
				} catch (\Exception $e) {
					$this->delivery->addError(500, $e->getMessage());
					$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
				}
			} else {
				$this->delivery->data = 'Gagal menyimpan'.PHP_EOL.$this->memberMenuText;
				$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_MEMBER_MENU);
			}
		} else {
			$this->delivery->data = 'Coba lagi';
		}

	}

	private function handleMemberMenu ($store, $member, $rating, $message) {
		if ($message == '1') {
			$profile = 'Nama: '.$member->name.PHP_EOL;
			$profile .= 'Status: '.$member->title.PHP_EOL;
			$profile .= 'Nomor Registrasi: '.$member->registration_number.PHP_EOL;
			$profile .= 'Asal Sekolah: '.$member->from_school.PHP_EOL;
			$profile .= 'Instagram: '.$member->instagram_username.PHP_EOL;
			$profile .= 'Tiktok: '.$member->tiktok_username.PHP_EOL;
			$profile .= 'Youtube: '.$member->youtube_username.PHP_EOL;
			$profile .= 'Facebook: '.$member->facebook_username.PHP_EOL;
			$profile .= 'Twitter: '.$member->twitter_username.PHP_EOL;
			if (!empty($member->achievements)) {
				$profile .= 'Prestasi:'.PHP_EOL;
				foreach ($member->achievements as $achievement) {
					$profile .= '- '.$achievement.PHP_EOL;
				}
			} else {
				$profile .= 'Prestasi: -'.PHP_EOL;
			}
			$profile .= PHP_EOL.$this->memberMenuText;
			$formattedPayload = [];
			
			$args = [
				'type' => 'photo',
				'poll_member_id' => $member->id,
			];
			$photos = $this->repository->find('poll_member_rating_files', $args);
			foreach ($photos as $photo) {
				$formattedPayload[] = [
					'category' => 'image',
					'urlFile' => $photo->url
				];
			}
			
			$args = [
				'type' => 'video',
				'poll_member_id' => $member->id,
			];
			$videos = $this->repository->find('poll_member_rating_files', $args);
			foreach ($videos as $video) {
				$formattedPayload[] = [
					'category' => 'video',
					'urlFile' => $video->url
				];
			}
			$formattedPayload[] = [
				'category' => 'text',
				'message' => $profile,
			];
			header('Content-Type:application/json');
			$this->delivery->data = json_encode(['data' => $formattedPayload]);
		} else if ($message == '2') {
			if ($member->current_level == 1) {
				$this->delivery->data = 'Screening Data Dukungan'.PHP_EOL.'Jumlah Likes? '.$this->generateMessageExistsRatingByKey($rating, 'level_1_additional_total_likes');
				$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_1);
			} else if ($member->current_level == 2) {
				$this->delivery->data = 'Screening Look'.PHP_EOL.'Kerudung Rabbani SEGIEMPAT/INSTANT? '.$this->generateMessageExistsRatingByKey($rating, 'level_2_look_veil');
				$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_2_1);
			} else if ($member->current_level == 3) {
				$this->delivery->data = 'Screening Look'.PHP_EOL.'Kerudung Rabbani SEGIEMPAT/INSTANT? '.$this->generateMessageExistsRatingByKey($rating, 'level_3_look_veil');
				$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_3_1);
			}
		} else if ($message == '3') {
			$this->delivery->data = 'Silahkan upload foto peserta';
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_ASK_PHOTO);
		} else if ($message == '4') {
			$this->delivery->data = 'Silahkan upload video peserta';
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_STATE_ASK_VIDEO);
		} else if ($message == '5') {
			$payloadStore = [
				'dpr_wablas_menu_state' => null,
				'dpr_poll_member_id' => null,
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->update('admins', $payloadStore, ['id' => $store->id]);
			$this->delivery->data = $this->generalMenuText;
		} else {
			$this->delivery->data = $this->memberMenuText;
		}
	}

	/* private function handleQuestionMemberLevel ($store, $member, $rating, $message) {
		if ($member->current_level == 1) {
			$this->delivery = $this->handleQuestionMemberLevel1($store, $member, $rating, $message);
			return $this->delivery;
		} else if ($member->current_level == 2) {

		} else if ($member->current_level == 3) {

		} else {

		}
		return $this->delivery;
	} */

	/* private function handleQuestionMemberLevel1 ($store, $member, $rating, $message) {
		if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_STATE_MEMBER_MENU) {
			$this->delivery->data = 'Screening Data Dukungan'.PHP_EOL.'Jumlah Likes? '.$this->generateMessageExistsRatingByKey($rating, 'level_1_additional_total_likes');
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_1);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_1) {
			$value = intval($message);
			$currentKey = 'level_1_additional_total_likes';
			$nextKey = 'level_1_additional_total_comments';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_2);
			$this->delivery->data = 'Screening Data Dukungan'.PHP_EOL.'Jumlah Komentar? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
		} else if ($store->dpr_wablas_menu_state == self::WABLAS_MENU_QUESTION_LVL_1_2) {
			$rating = intval($message);
			$currentKey = 'level_1_additional_total_comments';
			$nextKey = 'level_1_look_photo';
			$this->updateRatingMember($member, $rating, $currentKey, $value);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_2);
			$this->delivery->data = 'Screening Look'.PHP_EOL.'Foto? '.$this->generateMessageExistsRatingByKey($rating, $nextKey);
			$this->updateStoreWablasMenuState($store, self::WABLAS_MENU_QUESTION_LVL_1_3);
		}

		return $this->delivery;
	} */

	private function updateStoreWablasMenuState ($store, $state) {
		$payloadStore = [
			'dpr_wablas_menu_state' => $state,
			'updated_at' => date("Y-m-d H:i:s"),
		];
		$action = $this->repository->update('admins', $payloadStore, ['id' => $store->id]);
		return $action;
	}

	private function generateMessageExistsRatingByKey ($rating, $key) {
		$arrRating = (array)$rating;
		if (isset($arrRating[$key])) {
			return '(Penilaian saat ini: '.$arrRating[$key].')';
		} else {
			return '';
		}
		return '';
	}

	private function updateRatingMember ($member, $rating, $key, $value) {
		if (empty($rating)) {
			// insert
			$payload = [
				'poll_member_id' => $member->id,
				$key => $value,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->insert('poll_member_ratings', $payload);
		} else {
			// update
			$payload = [
				$key => $value,
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->update('poll_member_ratings', $payload, ['id' => $rating->id]);
		}
	}

	private function generateFinalRate ($member, $rating) {
		$ratingHandler = new PollMemberRatingHandler($this->repository);
		$message  = '';
		if ($member->current_level == 1) {
			$payload = [
				'level_1_additional_total_likes' => $rating->level_1_additional_total_likes,
				'level_1_additional_total_comments' => $rating->level_1_additional_total_comments,
				'level_1_look_photo' => $rating->level_1_look_photo,
				'level_1_look_wear_rabbani_product' => $rating->level_1_look_wear_rabbani_product,
				'level_1_talent_read_al_quran' => $rating->level_1_talent_read_al_quran,
				'level_1_talent_talent_show' => $rating->level_1_talent_talent_show,
				'level_1_talent_communication_skill' => $rating->level_1_talent_communication_skill,
			];
			$ratingResult = $ratingHandler->rateLevel1Member($member, $payload);
			$ratingData = $ratingResult->data;
			$message = 'Surat Rekomendasi Sekolah: '. $ratingData['school_recommendation_letter'].PHP_EOL;
			$message .= 'Surat Ijin Orang Tua: '. $ratingData['parental_certificate'].PHP_EOL;
			$message .= 'Struk Belanja Kas: '. $ratingData['invoice_number'].PHP_EOL;
			$message .= 'Sertifikat / Piagam: '. $ratingData['certificate'].PHP_EOL;
			$message .= 'Jumlah Likes: '. $ratingData['total_likes'].PHP_EOL;
			$message .= 'Jumlah Comment: '. $ratingData['total_comments'].PHP_EOL;
			$message .= 'Foto: '. $ratingData['photo'].PHP_EOL;
			$message .= 'Menggunakan Product Rabbani: '. $ratingData['wear_rabbani_product'].PHP_EOL;
			$message .= 'Bacaan / Hafalan Al-Quran: '. $ratingData['read_al_quran'].PHP_EOL;
			$message .= 'Unjuk Bakat: '. $ratingData['talent_show'].PHP_EOL;
			$message .= 'Skill Komunikasi: '. $ratingData['communication_skill'].PHP_EOL;
			$message .= 'Nilai Screening Data: '. $ratingData['data_total'].PHP_EOL;
			$message .= 'Nilai Screening Dukungan: '. $ratingData['additional_total'].PHP_EOL;
			$message .= 'Nilai Screening Look: '. $ratingData['look_total'].PHP_EOL;
			$message .= 'Nilai Screening Bakat: '. $ratingData['talent_total'].PHP_EOL;
			$message .= 'Total Penilaian: '. $ratingData['total_rating'].PHP_EOL;
			$message .= 'Hasil Penilaian: '. $ratingData['final_rate'].PHP_EOL;
		} else if ($member->current_level == 2) {
			$payload = [
				'level_2_look_veil' => $rating->level_2_look_veil,
				'level_2_look_wardrobe' => $rating->level_2_look_wardrobe,
				'level_2_look_makeup' => $rating->level_2_look_makeup,
				'level_2_talent_talent_show' => $rating->level_2_talent_talent_show,
				'level_2_talent_fashion_show' => $rating->level_2_talent_fashion_show,
				'level_2_talent_jury' => $rating->level_2_talent_jury,
				'level_2_talent_testimony' => $rating->level_2_talent_testimony,
				'level_2_support_polling' => $rating->level_2_support_polling,
				'level_2_support_personal' => $rating->level_2_support_personal,
			];
			$ratingResult = $ratingHandler->rateLevel2Member($member, $payload);
			$ratingData = $ratingResult->data;
			$message = 'Kerudung Rabbani SEGIEMPAT/INSTANT: '. $ratingData['veil'].PHP_EOL;
			$message .= 'Busana Rabbani/Dluha: '. $ratingData['wardrobe'].PHP_EOL;
			$message .= 'Makeup: '. $ratingData['makeup'].PHP_EOL;
			$message .= 'Unjuk Bakat: '. $ratingData['talent_show'].PHP_EOL;
			$message .= 'Fashion Show: '. $ratingData['fashion_show'].PHP_EOL;
			$message .= 'Tanya Jawab Juri: '. $ratingData['jury'].PHP_EOL;
			$message .= 'Testimoni: '. $ratingData['testimony'].PHP_EOL;
			$message .= 'Dukungan by polling tools: '. $ratingData['polling'].PHP_EOL;
			$message .= 'Dukungan by postingan pribadi: '. $ratingData['personal'].PHP_EOL;
			$message .= 'Nilai Screening Look: '. $ratingData['look_total'].PHP_EOL;
			$message .= 'Nilai Screening Bakat: '. $ratingData['talent_total'].PHP_EOL;
			$message .= 'Nilai Screening Support Netizen: '. $ratingData['support_total'].PHP_EOL;
			$message .= 'Total Penilaian: '. $ratingData['total_rating'].PHP_EOL;
		} else if ($member->current_level == 3) {
			$payload = [
				'level_3_look_veil' => $rating->level_3_look_veil,
				'level_3_look_wardrobe' => $rating->level_3_look_wardrobe,
				'level_3_look_makeup' => $rating->level_3_look_makeup,
				'level_3_talent_talent_show' => $rating->level_3_talent_talent_show,
				'level_3_talent_fashion_show' => $rating->level_3_talent_fashion_show,
				'level_3_talent_jury' => $rating->level_3_talent_jury,
				'level_3_talent_testimony' => $rating->level_3_talent_testimony,
				'level_3_support_polling' => $rating->level_3_support_polling,
				'level_3_support_personal' => $rating->level_3_support_personal,
			];
			$ratingResult = $ratingHandler->rateLevel3Member($member, $payload);
			$ratingData = $ratingResult->data;
			$message = 'Kerudung Rabbani SEGIEMPAT/INSTANT: '. $ratingData['veil'].PHP_EOL;
			$message .= 'Busana Rabbani/Dluha: '. $ratingData['wardrobe'].PHP_EOL;
			$message .= 'Makeup: '. $ratingData['makeup'].PHP_EOL;
			$message .= 'Unjuk Bakat: '. $ratingData['talent_show'].PHP_EOL;
			$message .= 'Fashion Show: '. $ratingData['fashion_show'].PHP_EOL;
			$message .= 'Tanya Jawab Juri: '. $ratingData['jury'].PHP_EOL;
			$message .= 'Testimoni: '. $ratingData['testimony'].PHP_EOL;
			$message .= 'Dukungan by polling tools: '. $ratingData['polling'].PHP_EOL;
			$message .= 'Dukungan by postingan pribadi: '. $ratingData['personal'].PHP_EOL;
			$message .= 'Nilai Screening Look: '. $ratingData['look_total'].PHP_EOL;
			$message .= 'Nilai Screening Bakat: '. $ratingData['talent_total'].PHP_EOL;
			$message .= 'Nilai Screening Support Netizen: '. $ratingData['support_total'].PHP_EOL;
			$message .= 'Total Penilaian: '. $ratingData['total_rating'].PHP_EOL;
		}
		return $message;
	}

}