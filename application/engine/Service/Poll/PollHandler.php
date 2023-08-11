<?php
namespace Service\Poll;

use Library\WablasService;
use Service\Delivery;
use \libphonenumber\PhoneNumberUtil;

class PollHandler {

	const MAIN_WABLAS = '6285624856016';

	private $delivery;
	private $repository;
	private $waService;
	
	public function __construct ($repository, $auth = null) {
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

	public function authorizeSendOTP ($phoneNumber, $payload) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$phoneNumberUtil = PhoneNumberUtil::getInstance();
			$phoneNumber = $phoneNumberUtil->parse($phoneNumber, "ID");
		    $phoneNumber = '62'.$phoneNumber->getNationalNumber();
		} catch (\libphonenumber\NumberParseException $e) {
		    $this->delivery->addError(500, $e->getMessage());
		    return $this->delivery;
		}

		if (strlen($payload['password']) < 6) {
			$this->delivery->addError(400, 'Password minimum 6 characters');
		}
		
		if ($payload['password'] != $payload['cpassword']) {
			$this->delivery->addError(400, 'Password and confirm password not equal');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}


		$memberHandler = new PollMemberHandler($this->repository);
		$pollMember = $memberHandler->getPollMember(['phone_number' => $phoneNumber]);
		if ($pollMember->hasErrors()) {
			return $pollMember;
		}

		$dataPollMember = $pollMember->data;
		if (!empty($dataPollMember)) {
			$this->delivery->addError(400, 'Member already exists');
			return $this->delivery;
		}

		// validate OTP
		$argsOtp = [
			'phone_number' => $phoneNumber,
			'used_at' => null,
			'expired_at >=' => date('Y-m-d H:i:s')
		];
		$lastOtpAlive = $this->repository->findOne('otp', $argsOtp);
		if (!empty($lastOtpAlive)) {
			$this->delivery->addError(400, 'OTP has been sent. Try again in 5 minutes.');
			return $this->delivery;
		}

		$otp = generateRandomDigit(6);
        $message = sprintf('OTP : %s', $otp).PHP_EOL.PHP_EOL.'Untuk informasi lebih lanjut seputar *Duta Pelajar Rabbani* silahkan gabung group whatsapp di https://rmall.id/dpr.php , Balas pesan ini jika link tidak bisa di klik.';
        $currentDate = date('Y-m-d H:i:s');
        $futureDate = strtotime($currentDate) + (60 * 5);
        $expiredAt = date('Y-m-d H:i:s', $futureDate);

		$dataOtp = [
			'id_user' => 0,
			'phone_number' => $phoneNumber,
			'otp' => $otp,
			'used_at' => null,
			'expired_at' => $expiredAt,
			'created_at' => $currentDate,
			'updated_at' => $currentDate
		];
		$otpAction = $this->repository->insert('otp', $dataOtp);
		$waAction = $this->sendWablasByPhoneNumber($phoneNumber, $message);
		$this->delivery->data = $waAction->data;
		return $this->delivery;
	}

	public function authorizeSubmitOTP ($phoneNumber, $otp, $payload) {
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$phoneNumberUtil = PhoneNumberUtil::getInstance();
			$phoneNumber = $phoneNumberUtil->parse($phoneNumber, "ID");
		    $phoneNumber = '62'.$phoneNumber->getNationalNumber();
		} catch (\libphonenumber\NumberParseException $e) {
		    $this->delivery->addError(500, $e->getMessage());
		    return $this->delivery;
		}

		if (strlen($payload['password']) < 6) {
			$this->delivery->addError(400, 'Password minimum 6 characters');
		}
		
		if ($payload['password'] != $payload['cpassword']) {
			$this->delivery->addError(400, 'Password and confirm password not equal');
		}

		if ($this->delivery->hasErrors()) {
			return $this->delivery;
		}


		$memberHandler = new PollMemberHandler($this->repository);
		$pollMember = $memberHandler->getPollMember(['phone_number' => $phoneNumber]);
		if ($pollMember->hasErrors()) {
			return $pollMember;
		}

		$dataPollMember = $pollMember->data;
		if (!empty($dataPollMember)) {
			$this->delivery->addError(400, 'Member already exists');
			return $this->delivery;
		}

		if (!isset($otp) || empty($otp)) {
			$this->delivery->addError(400, 'OTP is required');
			return $this->delivery;
		}

		// validate OTP
		$argsOtp = [
			'phone_number' => $phoneNumber,
			'used_at' => null,
			'expired_at >=' => date('Y-m-d H:i:s')
		];
		$lastOtpAlive = $this->repository->findOne('otp', $argsOtp);
		if (empty($lastOtpAlive)) {
			$this->delivery->addError(400, 'No OTP found. Please request a new OTP.');
			return $this->delivery;
		}

		if ($lastOtpAlive->otp != $otp) {
			$this->delivery->addError(400, 'OTP is incorrect.');
			return $this->delivery;
		}

		$actionOtp = $this->repository->update('otp', ['used_at' => date('Y-m-d H:i:s')], ['id' => $lastOtpAlive->id]);

		try {
			$memberHandler = new PollMemberHandler($this->repository);
			$pollMember = $memberHandler->getPollMember(['phone_number' => $phoneNumber]);
			if ($pollMember->hasErrors()) {
				return $pollMember;
			}

			$secret = $this->createSecret($phoneNumber, uniqid());
			// register
			$payload = [
				'phone_number' => $phoneNumber,
				'secret' => $secret,
				'password' => $this->generatePassword($payload['password'])
			];
			$registerAction = $memberHandler->createPollMember($payload);
			/* $member = $registerAction->data;
			$verifyAction = $memberHandler->verifyPollMember($member->id); */
			$result = [
				'secret' => $secret,
			];

			$this->delivery->data = $result;
		} catch (\Exception $e) {
			$this->delivery->data = $e->getMessage();
		}

		return $this->delivery;
	}

	public function login ($phoneNumber, $password) {
		try {
			$memberHandler = new PollMemberHandler($this->repository);
			$member = $this->repository->findOne('poll_members', ['phone_number' => $phoneNumber]);

			if (empty($member)) {
				$this->delivery->addError(400, 'Member is required');
				return $this->delivery;
			}

			if ($this->generatePassword($password) != $member->password) {
				$this->delivery->addError(400, 'Phone number or password is incorrect');
				return $this->delivery;
			}

			$secret = $this->createSecret($phoneNumber, uniqid());
			$payload = [
				'secret' => $secret,
			];
			$registerAction = $memberHandler->updatePollMember($payload, ['id' => $member->id]);
			$result = [
				'secret' => $secret,
			];

			$this->delivery->data = $result;
		} catch (\Exception $e) {
			$this->delivery->data = $e->getMessage();
		}
		return $this->delivery;
	}

	public function forgotPassword ($phoneNumber) {
		try {
			$phoneNumber = getFormattedPhoneNumber($phoneNumber);
			$memberHandler = new PollMemberHandler($this->repository);
			$member = $this->repository->findOne('poll_members', ['phone_number' => $phoneNumber]);

			if (empty($member)) {
				$this->delivery->addError(400, 'Member is required');
				return $this->delivery;
			}

			$newPassword = generateRandomDigit(6);

			$payload = [
				'password' => $this->generatePassword($newPassword),
			];
			$registerAction = $memberHandler->updatePollMember($payload, ['id' => $member->id]);
			$message = 'Password baru anda adalah: '.$newPassword;
			$notifAction = $this->sendWablasByPhoneNumber($phoneNumber, $message);

			$this->delivery->data = 'ok';
		} catch (\Exception $e) {
			$this->delivery->data = $e->getMessage();
		}
		return $this->delivery;
	}

	public function sendWablasByPhoneNumber ($phoneNumber, $message, $additionalMessage = null) {
		$result = null;
		$wablasConfig = $this->repository->findOne('auth_api_wablas', ['wablas_phone_number' => self::MAIN_WABLAS]);
		if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
			$this->delivery->addError(400, 'Config error');
			return $this->delivery;
		}
		$this->waService = new WablasService($wablasConfig->domain_wablas, $wablasConfig->wablas_token);
		$sendWa = $this->waService->publishMessage('send_message', $phoneNumber, $message);
		$result[] = $sendWa;

		$this->delivery->data = $result;
		return $this->delivery;
	}

	private function createSecret($phoneNumber, $unique) {
        return md5($phoneNumber . time()) . md5(time() . $unique);
    }

    private function generatePassword($password) {
        return md5(base64_encode($password));
    }
}