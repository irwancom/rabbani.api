<?php
namespace Service\Poll;

use Library\WablasService;
use Service\Delivery;

class PollMemberRatingHandler {

	const MAIN_WABLAS = PollHandler::MAIN_WABLAS;

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

	public function getPollMemberRating ($filters = null) {
		$argsOrWhere = null;
		$args = [];
		if (isset($filters['poll_member_id'])) {
			$args['poll_member_ratings.poll_member_id'] = $filters['poll_member_id'];
			unset($filters['poll_member_id']);
		}
		$join = [
		];
		$select = [
			'poll_member_ratings.id',
			'poll_member_ratings.poll_member_id',
			'poll_member_ratings.level_1_data_school_recommendation_letter',
			'poll_member_ratings.level_1_data_parental_certificate',
			'poll_member_ratings.level_1_data_invoice_number',
			'poll_member_ratings.level_1_data_certificate',
			'poll_member_ratings.level_1_additional_total_likes',
			'poll_member_ratings.level_1_additional_total_comments',
			'poll_member_ratings.level_1_look_photo',
			'poll_member_ratings.level_1_look_wear_rabbani_product',
			'poll_member_ratings.level_1_talent_read_al_quran',
			'poll_member_ratings.level_1_talent_talent_show',
			'poll_member_ratings.level_1_talent_communication_skill',
			'poll_member_ratings.level_1_data_total',
			'poll_member_ratings.level_1_additional_total',
			'poll_member_ratings.level_1_look_total',
			'poll_member_ratings.level_1_talent_total',
			'poll_member_ratings.level_1_total_rating',
			'poll_member_ratings.level_1_final_rate',
			'poll_member_ratings.level_1_final_status',
			'poll_member_ratings.level_2_look_veil',
			'poll_member_ratings.level_2_look_wardrobe',
			'poll_member_ratings.level_2_look_makeup',
			'poll_member_ratings.level_2_talent_talent_show',
			'poll_member_ratings.level_2_talent_fashion_show',
			'poll_member_ratings.level_2_talent_jury',
			'poll_member_ratings.level_2_talent_testimony',
			'poll_member_ratings.level_2_support_polling',
			'poll_member_ratings.level_2_support_personal',
			'poll_member_ratings.level_2_total_look',
			'poll_member_ratings.level_2_total_talent',
			'poll_member_ratings.level_2_total_support',
			'poll_member_ratings.level_2_total_rating',
			'poll_member_ratings.level_3_look_veil',
			'poll_member_ratings.level_3_look_wardrobe',
			'poll_member_ratings.level_3_look_makeup',
			'poll_member_ratings.level_3_talent_talent_show',
			'poll_member_ratings.level_3_talent_fashion_show',
			'poll_member_ratings.level_3_talent_jury',
			'poll_member_ratings.level_3_talent_testimony',
			'poll_member_ratings.level_3_support_polling',
			'poll_member_ratings.level_3_support_personal',
			'poll_member_ratings.level_3_total_look',
			'poll_member_ratings.level_3_total_talent',
			'poll_member_ratings.level_3_total_support',
			'poll_member_ratings.level_3_total_rating',
			'poll_member_ratings.created_at',
		];
		$rating = $this->repository->findOne('poll_member_ratings', $filters, $argsOrWhere, $join, $select);
		if (!empty($rating)) {
		}
		$this->delivery->data = $rating;
		return $this->delivery;
	}

	public function rateLevel1Member ($member, $payload) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}

		$schoolRecommendationLetter = isset($payload['level_1_data_school_recommendation_letter']) ? $payload['level_1_school_recommendation_letter'] : 0;
		$parentalCertificate = isset($payload['level_1_data_parental_certificate']) ? $payload['level_1_data_parental_certificate'] : 0;
		$invoiceNumber = isset($payload['level_1_data_invoice_number']) ? $payload['level_1_data_invoice_number'] : 0;
		$certificate = isset($payload['level_1_data_certificate']) ? $payload['level_1_data_certificate'] : 0;
		$totalComments = isset($payload['level_1_additional_total_comments']) && !empty($payload['level_1_additional_total_comments']) ? intval($payload['level_1_additional_total_comments']) : 0;
		$totalLikes = isset($payload['level_1_additional_total_likes']) && !empty($payload['level_1_additional_total_likes']) ? intval($payload['level_1_additional_total_likes']) : 0;
		$photo = isset($payload['level_1_look_photo']) && !empty($payload['level_1_look_photo']) ? intval($payload['level_1_look_photo']) : 0;
		$wearRabbaniProduct = isset($payload['level_1_look_wear_rabbani_product']) && !empty($payload['level_1_look_wear_rabbani_product']) ? intval($payload['level_1_look_wear_rabbani_product']) : 0;
		$readAlQuran = isset($payload['level_1_talent_read_al_quran']) && !empty($payload['level_1_talent_read_al_quran']) ? intval($payload['level_1_talent_read_al_quran']) : 0;
		$talentShow = isset($payload['level_1_talent_talent_show']) && !empty($payload['level_1_talent_talent_show']) ? intval($payload['level_1_talent_talent_show']) : 0;
		$communicationSkill = isset($payload['level_1_talent_communication_skill']) && !empty($payload['level_1_talent_communication_skill']) ? intval($payload['level_1_talent_communication_skill']) : 0;


		if (empty($schoolRecommendationLetter) && !empty($member->school_recommendation_letter_url)) {
			$schoolRecommendationLetter = 20;
		}
		if (empty($parentalCertificate) && !empty($member->parental_certificate_url)) {
			$parentalCertificate = 15;
		}

		if (empty($invoiceNumber) && $member->status == PollMemberHandler::STATUS_VERIFIED) {
			$invoiceNumber = 40;
		}
		if (empty($certificate) && !empty($member->achievements)) {
			$certificate = 25;
		}
		$dataTotal = ceil(($schoolRecommendationLetter + $parentalCertificate + $invoiceNumber + $certificate));
		$additionalTotal = ceil(($totalLikes + $totalComments)/2);
		$lookTotal = ceil(($photo + $wearRabbaniProduct) / 2);
		$talentTotal = ceil(($readAlQuran + $talentShow + $communicationSkill) / 3);

		$totalRating = ($dataTotal * 10 / 100) + ($additionalTotal * 10 / 100) + ($lookTotal * 40 / 100) + ($talentTotal * 40 / 100);
		$totalRating = intval($totalRating);
		$finalRate = 'TIDAK LULUS';
		$finalStatus = PollMemberHandler::LEVEL_STATUS_FAIL;
		if ($totalRating >= 85) {
			$finalStatus = PollMemberHandler::LEVEL_STATUS_PASS;
			$finalRate = 'EXCELLENT';
		} else if ($totalRating < 85 && $totalRating >= 75) {
			$finalStatus = PollMemberHandler::LEVEL_STATUS_FAIL;
			$finalRate = 'LULUS';
		} else if ($totalRating < 75 && $totalRating >= 65) {
			$finalStatus = PollMemberHandler::LEVEL_STATUS_PENDING;
			$finalRate = 'PERTIMBANGAN';
		} else {
			$finalStatus = PollMemberHandler::LEVEL_STATUS_FAIL;
			$finalRate = 'TIDAK LULUS';
		}
		$result = [
			'school_recommendation_letter' => $schoolRecommendationLetter,
			'parental_certificate' => $parentalCertificate,
			'invoice_number' => $invoiceNumber,
			'certificate' => $certificate,
			'total_comments' => $totalComments,
			'total_likes' => $totalLikes,
			'photo' => $photo,
			'wear_rabbani_product' => $wearRabbaniProduct,
			'read_al_quran' => $readAlQuran,
			'talent_show' => $talentShow,
			'communication_skill' => $communicationSkill,
			'data_total' => $dataTotal,
			'additional_total' => $additionalTotal,
			'look_total' => $lookTotal,
			'talent_total' => $talentTotal,
			'total_rating' => intval($totalRating),
			'final_rate' => $finalRate,
			'final_status' => $finalStatus,
		];

		$rate = $this->repository->findOne('poll_member_ratings', ['poll_member_id' => $member->id]);
		if (empty($rate)) {
			// insert
			$payload = [
				'poll_member_id' => $member->id,
				'level_1_data_school_recommendation_letter' => $schoolRecommendationLetter,
				'level_1_data_parental_certificate' => $parentalCertificate,
				'level_1_data_invoice_number' => $invoiceNumber,
				'level_1_data_certificate' => $certificate,
				'level_1_additional_total_comments' => $totalComments,
				'level_1_additional_total_likes' => $totalLikes,
				'level_1_look_photo' => $photo,
				'level_1_look_wear_rabbani_product' => $wearRabbaniProduct,
				'level_1_talent_read_al_quran' => $readAlQuran,
				'level_1_talent_talent_show' => $talentShow,
				'level_1_talent_communication_skill' => $communicationSkill,
				'level_1_data_total' => $dataTotal,
				'level_1_additional_total' => $additionalTotal,
				'level_1_look_total' => $lookTotal,
				'level_1_talent_total' => $talentTotal,
				'level_1_total_rating' => $totalRating,
				'level_1_final_rate' => $finalRate,
				'level_1_final_status' => $finalStatus,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
			];
			$action = $this->repository->insert('poll_member_ratings', $payload);
		} else {
			// update
			$payload = [
				'level_1_data_school_recommendation_letter' => $schoolRecommendationLetter,
				'level_1_data_parental_certificate' => $parentalCertificate,
				'level_1_data_invoice_number' => $invoiceNumber,
				'level_1_data_certificate' => $certificate,
				'level_1_additional_total_comments' => $totalComments,
				'level_1_additional_total_likes' => $totalLikes,
				'level_1_look_photo' => $photo,
				'level_1_look_wear_rabbani_product' => $wearRabbaniProduct,
				'level_1_talent_read_al_quran' => $readAlQuran,
				'level_1_talent_talent_show' => $talentShow,
				'level_1_talent_communication_skill' => $communicationSkill,
				'level_1_data_total' => $dataTotal,
				'level_1_additional_total' => $additionalTotal,
				'level_1_look_total' => $lookTotal,
				'level_1_talent_total' => $talentTotal,
				'level_1_total_rating' => $totalRating,
				'level_1_final_rate' => $finalRate,
				'level_1_final_status' => $finalStatus,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('poll_member_ratings', $payload, ['poll_member_id' => $member->id]);
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function rateLevel2Member ($member, $payload) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}

		$veil = isset($payload['level_2_look_veil']) && !empty($payload['level_2_look_veil']) ? intval($payload['level_2_look_veil']) : 0;
		$wardrobe = isset($payload['level_2_look_wardrobe']) && !empty($payload['level_2_look_wardrobe']) ? intval($payload['level_2_look_wardrobe']) : 0;
		$makeup = isset($payload['level_2_look_makeup']) && !empty($payload['level_2_look_makeup']) ? intval($payload['level_2_look_makeup']) : 0;
		$talentShow = isset($payload['level_2_talent_talent_show']) && !empty($payload['level_2_talent_talent_show']) ? intval($payload['level_2_talent_talent_show']) : 0;
		$fashionShow = isset($payload['level_2_talent_fashion_show']) && !empty($payload['level_2_talent_fashion_show']) ? intval($payload['level_2_talent_fashion_show']) : 0;
		$jury = isset($payload['level_2_talent_jury']) && !empty($payload['level_2_talent_jury']) ? intval($payload['level_2_talent_jury']) : 0;
		$testimony = isset($payload['level_2_talent_testimony']) && !empty($payload['level_2_talent_testimony']) ? intval($payload['level_2_talent_testimony']) : 0;
		$polling = isset($payload['level_2_support_polling']) && !empty($payload['level_2_support_polling']) ? intval($payload['level_2_support_polling']) : 0;
		$personal = isset($payload['level_2_support_personal']) && !empty($payload['level_2_support_personal']) ? intval($payload['level_2_support_personal']) : 0;


		$lookTotal = ceil(($veil + $wardrobe + $makeup) / 3);
		$talentTotal = ceil(($fashionShow + $talentShow + $jury + $testimony) / 4);
		$supportTotal = intval(($polling * 90 /100) + ($personal * 10 /100));

		$totalRating = ($lookTotal * 30 / 100) + ($talentTotal * 30 / 100) + ($supportTotal * 40 / 100);
		$totalRating = $totalRating;
		$result = [
			'veil' => $veil,
			'wardrobe' => $wardrobe,
			'makeup' => $makeup,
			'talent_show' => $talentShow,
			'fashion_show' => $fashionShow,
			'jury' => $jury,
			'testimony' => $testimony,
			'polling' => $polling,
			'personal' => $personal,
			'look_total' => $lookTotal,
			'talent_total' => $talentTotal,
			'support_total' => $supportTotal,
			'total_rating' => intval($totalRating),
		];

		$rate = $this->repository->findOne('poll_member_ratings', ['poll_member_id' => $member->id]);
		if (empty($rate)) {
			// insert
			$payload = [
				'poll_member_id' => $member->id,
				'level_2_look_veil' => $veil,
				'level_2_look_wardrobe' => $wardrobe,
				'level_2_look_makeup' => $makeup,
				'level_2_talent_talent_show' => $talentShow,
				'level_2_talent_fashion_show' => $fashionShow,
				'level_2_talent_jury' => $jury,
				'level_2_talent_testimony' => $testimony,
				'level_2_support_polling' => $polling,
				'level_2_support_personal' => $personal,
				'level_2_total_look' => $lookTotal,
				'level_2_total_talent' => $talentTotal,
				'level_2_total_support' => $supportTotal,
				'level_2_total_rating' => $totalRating,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('poll_member_ratings', $payload);
		} else {
			// update
			$payload = [
				'level_2_look_veil' => $veil,
				'level_2_look_wardrobe' => $wardrobe,
				'level_2_look_makeup' => $makeup,
				'level_2_talent_talent_show' => $talentShow,
				'level_2_talent_fashion_show' => $fashionShow,
				'level_2_talent_jury' => $jury,
				'level_2_talent_testimony' => $testimony,
				'level_2_support_polling' => $polling,
				'level_2_support_personal' => $personal,
				'level_2_total_look' => $lookTotal,
				'level_2_total_talent' => $talentTotal,
				'level_2_total_support' => $supportTotal,
				'level_2_total_rating' => $totalRating,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('poll_member_ratings', $payload, ['poll_member_id' => $member->id]);
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}

	public function rateLevel3Member ($member, $payload) {
		if (empty($member)) {
			$this->delivery->addError(400, 'Member is required');
			return $this->delivery;
		}

		$veil = isset($payload['level_3_look_veil']) && !empty($payload['level_3_look_veil']) ? intval($payload['level_3_look_veil']) : 0;
		$wardrobe = isset($payload['level_3_look_wardrobe']) && !empty($payload['level_3_look_wardrobe']) ? intval($payload['level_3_look_wardrobe']) : 0;
		$makeup = isset($payload['level_3_look_makeup']) && !empty($payload['level_3_look_makeup']) ? intval($payload['level_3_look_makeup']) : 0;
		$talentShow = isset($payload['level_3_talent_talent_show']) && !empty($payload['level_3_talent_talent_show']) ? intval($payload['level_3_talent_talent_show']) : 0;
		$fashionShow = isset($payload['level_3_talent_fashion_show']) && !empty($payload['level_3_talent_fashion_show']) ? intval($payload['level_3_talent_fashion_show']) : 0;
		$jury = isset($payload['level_3_talent_jury']) && !empty($payload['level_3_talent_jury']) ? intval($payload['level_3_talent_jury']) : 0;
		$testimony = isset($payload['level_3_talent_testimony']) && !empty($payload['level_3_talent_testimony']) ? intval($payload['level_3_talent_testimony']) : 0;
		$polling = isset($payload['level_3_support_polling']) && !empty($payload['level_3_support_polling']) ? intval($payload['level_3_support_polling']) : 0;
		$personal = isset($payload['level_3_support_personal']) && !empty($payload['level_3_support_personal']) ? intval($payload['level_3_support_personal']) : 0;


		$lookTotal = ceil(($veil + $wardrobe + $makeup) / 3);
		$talentTotal = ceil(($fashionShow + $talentShow + $jury + $testimony) / 4);
		$supportTotal = intval(($polling * 90 /100) + ($personal * 10 /100));

		$totalRating = ($lookTotal * 30 / 100) + ($talentTotal * 30 / 100) + ($supportTotal * 40 / 100);
		$totalRating = $totalRating;
		$result = [
			'veil' => $veil,
			'wardrobe' => $wardrobe,
			'makeup' => $makeup,
			'talent_show' => $talentShow,
			'fashion_show' => $fashionShow,
			'jury' => $jury,
			'testimony' => $testimony,
			'polling' => $polling,
			'personal' => $personal,
			'look_total' => $lookTotal,
			'talent_total' => $talentTotal,
			'support_total' => $supportTotal,
			'total_rating' => intval($totalRating),
		];

		$rate = $this->repository->findOne('poll_member_ratings', ['poll_member_id' => $member->id]);
		if (empty($rate)) {
			// insert
			$payload = [
				'poll_member_id' => $member->id,
				'level_3_look_veil' => $veil,
				'level_3_look_wardrobe' => $wardrobe,
				'level_3_look_makeup' => $makeup,
				'level_3_talent_talent_show' => $talentShow,
				'level_3_talent_fashion_show' => $fashionShow,
				'level_3_talent_jury' => $jury,
				'level_3_talent_testimony' => $testimony,
				'level_3_support_polling' => $polling,
				'level_3_support_personal' => $personal,
				'level_3_total_look' => $lookTotal,
				'level_3_total_talent' => $talentTotal,
				'level_3_total_support' => $supportTotal,
				'level_3_total_rating' => $totalRating,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->insert('poll_member_ratings', $payload);
		} else {
			// update
			$payload = [
				'level_3_look_veil' => $veil,
				'level_3_look_wardrobe' => $wardrobe,
				'level_3_look_makeup' => $makeup,
				'level_3_talent_talent_show' => $talentShow,
				'level_3_talent_fashion_show' => $fashionShow,
				'level_3_talent_jury' => $jury,
				'level_3_talent_testimony' => $testimony,
				'level_3_support_polling' => $polling,
				'level_3_support_personal' => $personal,
				'level_3_total_look' => $lookTotal,
				'level_3_total_talent' => $talentTotal,
				'level_3_total_support' => $supportTotal,
				'level_3_total_rating' => $totalRating,
				'updated_at' => date('Y-m-d H:i:s')
			];
			$action = $this->repository->update('poll_member_ratings', $payload, ['poll_member_id' => $member->id]);
		}

		$this->delivery->data = $result;
		return $this->delivery;
	}
}