<?php
namespace Service;

use Library\MailgunService;
use Library\SMSService;

class MeHandler {

	private $auth;
	private $validator;
	private $delivery;
	private $entity;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->validator = new Validator($repository);
		$this->delivery = new Delivery;
		$this->entity = new Entity;
	}

	public function changeProfile ($payload) {
		$updateData = [];
		if (isset($payload['name']) && !empty($payload['name'])) {
			$updateData['name'] = $payload['name'];
		}

		if (isset($payload['born']) && !empty($payload['born'])) {
			$updateData['born'] = $payload['born'];
		}

		if (isset($payload['new_password']) && !empty($payload['new_password'])) {
			if (isset($payload['confirm_new_password']) && !empty($payload['confirm_new_password'])) {
				if (strlen($payload['new_password']) < 6) {
					$this->delivery->addError(400, 'Password must be at least 6 characters.');
					return $this->delivery;
				}

				if ($payload['new_password'] != $payload['confirm_new_password']) {
					$this->delivery->addError(400, 'Confirm new password is not same with new password.');
					return $this->delivery;
				}

				$updateData['paswd'] = password_hash($payload['new_password'], PASSWORD_DEFAULT);


			} else {
				$this->delivery->addError(400, 'Confirm new password is empty');
				return $this->delivery;
			}
		}

		if (isset($payload['email']) && !empty($payload['email'])) {
			$existsUser = $this->repository->findOne('auth_user', ['email' => $payload['email']]);
			if (!empty($existsUser)) {
				$this->delivery->addError(400, 'Email already taken');
				return $this->delivery;
			}

	        $otp = generateRandomDigit(6);
	        $message = sprintf('Kode OTP: %s', $otp);
	        $currentDate = date('Y-m-d H:i:s');
	        $futureDate = strtotime($currentDate) + (60 * 5);
	        $expiredAt = date('Y-m-d H:i:s', $futureDate);
			$newOtpData = [
	            'id_user' => $this->auth['id'],
	            'otp' => $otp,
	            'used_at' => null,
	            'expired_at' => $expiredAt,
	            'created_at' => $currentDate,
	            'updated_at' => $currentDate,
	            'deleted_at' => $currentDate,
	            'payload' => json_encode(['email' => $payload['email']]),
	            'type' => 'auth_user'
	        ];
            $action = $this->repository->insert('otp', $newOtpData);
			$mailgun = new MailgunService;
			$subject = 'OTP for Change Email';
			$text = 'Your OTP is '. $otp;
			$mailgun = $mailgun->send('', '', 'no-reply@1itmedia.co.id', $payload['email'], $subject, $text);
		}

		if (isset($payload['phone']) && !empty($payload['phone'])) {
			$existsUser = $this->repository->findOne('auth_user', ['phone' => $payload['phone']]);
			if (!empty($existsUser)) {
				$this->delivery->addError(400, 'Phone already taken');
				return $this->delivery;
			}

	        $otp = generateRandomDigit(6);
	        $message = sprintf('Kode OTP: %s', $otp);
	        $currentDate = date('Y-m-d H:i:s');
	        $futureDate = strtotime($currentDate) + (60 * 5);
	        $expiredAt = date('Y-m-d H:i:s', $futureDate);
			$newOtpData = [
	            'id_user' => $this->auth['id'],
	            'otp' => $otp,
	            'used_at' => null,
	            'expired_at' => $expiredAt,
	            'created_at' => $currentDate,
	            'updated_at' => $currentDate,
	            'deleted_at' => $currentDate,
	            'payload' => json_encode(['phone' => $payload['phone']]),
	            'type' => 'auth_user'
	        ];
            $action = $this->repository->insert('otp', $newOtpData);
            $sms = new SMSService;
            $text = 'Your OTP is '.$otp;
            $sms = $sms->send($payload['phone'], $text);
		}


		$action = $this->repository->update('auth_user', $updateData, ['id_auth_user' => $this->auth['id']]);
		$user = $this->repository->findOne('auth_user', ['id_auth_user' => $this->auth['id']]);
		$auth = $this->validator->validateAuth($user->secret);
		$this->delivery->data = $auth->data;
		return $this->delivery;
	}

	public function changeProfileOTP ($payload) {
		if (!isset($payload['otp']) || empty($payload['otp'])) {
			$this->delivery->addError(400, 'OTP must not be empty');
			return $this->delivery;
		}

		$availableOtp = $this->repository->findLastOtpByAuthUser($this->auth['id'], $payload['otp']);
        $currentTime = date('Y-m-d H:i:s');
        if ($availableOtp->otp != $payload['otp'] || $availableOtp->used_at != null || $availableOtp->expired_at < $currentTime) {
        	$this->delivery->addError(400, 'Incorrect OTP');
            return $this->delivery;
        }

        $updateData = [
            'used_at' => $currentTime
        ];
        $action = $this->repository->update('otp', $updateData, ['id' => $availableOtp->id]);

        $updateData = json_decode($availableOtp->payload, true);
        $action = $this->repository->update('auth_user', $updateData, ['id_auth_user' => $this->auth['id']]);
        $user = $this->repository->findOne('auth_user', ['id_auth_user' => $this->auth['id']]);
		$auth = $this->validator->validateAuth($user->secret);
		$this->delivery->data = $auth->data;
		return $this->delivery;
	}

}