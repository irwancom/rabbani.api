<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;
use Service\CLM\Calculator;
use Library\TripayGateway;
use Library\MailgunService;
use Library\WablasService;
use Library\OneSignalService;

class NotificationHandler {

	const TYPE_NORMAL = 'normal';
	const TYPE_INFO = 'info';

	const WABLAS_MAIN_NUMBER = '62895383334783';
	const WABLAS_MAIN_DOMAIN = 'https://solo.wablas.com';
	const WABLAS_MAIN_TOKEN = 'CZrRIT5qo1GNYdiFXySxc0oW4oINZ5WZmLi40HlHHAushg4S1GlSfnSTHQfJEQgs';

	private $repository;
	private $sendWhatsapp = false;
	private $sendEmail = false;
	private $sendPushNotification = false;
	private $user;

	public function __construct ($repository) {
		$this->type = self::TYPE_NORMAL;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function setUser ($user) {
		$this->user = $user;
	}

	public function getUser () {
		return $this->user;
	}

	public function setSendWhatsapp ($sendWhatsapp) {
		$this->sendWhatsapp = $sendWhatsapp;
	}

	public function getSendWhatsapp () {
		return $this->sendWhatsapp;
	}

	public function isSendWhatsapp () {
		return $this->getSendWhatsapp();
	}

	public function setSendPushNotification ($sendPushNotification) {
		$this->sendPushNotification = $sendPushNotification;
	}

	public function getSendPushNotification () {
		return $this->sendPushNotification;
	}

	public function isSendPushNotification () {
		return $this->getSendPushNotification();
	}

	public function setSendEmail ($sendEmail) {
		$this->sendEmail = $sendEmail;
	}

	public function getSendEmail () {
		return $this->sendEmail;
	}

	public function isSendEmail () {
		return $this->getSendEmail();
	}

	public function setTypeToInfo () {
		$this->type = self::TYPE_INFO;
	}

	public function sendToUser ($title, $message) {
		if (empty($this->user)) {
			$this->delivery->addError(400, 'User is required');
			return $this->delivery;
		}

		$this->user = (array)$this->user;

		if ($this->isSendWhatsapp()) {
			if (!isset($this->user['phone']) || empty($this->user['phone'])) {
				$this->delivery->addError(400, 'User phone is required');
				return $this->delivery;
			}
		}

		$notificationData = [
			'user_id' => $this->user['id'],
			'type' => $this->type,
			'title' => $title,
			'message' => $message,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		];

		try {
			$action = $this->repository->insert('notifications', $notificationData);
			$notificationData['id'] = $action;
			$notificationData['extras'] = [];

			if ($this->isSendWhatsapp()) {
				$waService = new WablasService(self::WABLAS_MAIN_DOMAIN, self::WABLAS_MAIN_TOKEN);
				$sendWa = $waService->publishMessage('send_message', $this->user['phone'], $message, null, null, null, null, null, null, null, $notificationData['id']);
				$notificationData['extras']['wablas'] = $sendWa;
			}

			if ($this->isSendEmail()) {
				$mailgun = new MailgunService;
				$sendEmail = $mailgun->publishEmail('key-0d89204653627cc8cbba67684cfff390', 'mg.rabbani.id', $this->user['id'], 'no-reply@rabbani.id', $this->user['email'], $title, $message, $notificationData['id']);
				$notificationData['extras']['email'] = $sendEmail;
			}

			if ($this->isSendPushNotification()) {
				$extras = [
					'data' => [
						'type' => 'transaction_status',
					],
				];

				$onesignal = new OneSignalService;
				$pushNotifAction = $onesignal->publishPushNotification('db861fb4-3979-4003-878c-f8317276673c', 'MTI1YTdlMTMtMmNjZi00NTBmLTkwMDUtYzczMTcxYzgyZjgx', $this->user['id'], $title, $message, $notificationData['id'], $extras);
				$notificationData['extras']['push_notification'] = $pushNotifAction;
			}

		} catch (\Exception $e) {
			$this->delivery->addError(500, $e->getMessage());
			return $this->delivery;
		}

		$this->delivery->data = $notificationData;
		return $this->delivery;

	}


}