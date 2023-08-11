<?php

class Entry extends CI_Controller {

	const EMAIL_FROM = 'admin@sandboxf3886f47d27e4805afad07cae49e7cc5.mailgun.org';

	public function __construct () {
		parent::__construct();
		$this->load->library('Mailgun');
		$this->load->library('Sms');
		$this->load->model('MainModel');
	}

	public function forgot_password () {
		$username = $this->input->post('username');

		$args = [
			'email' => $username,
			'phone' => $username
		];
		$orWhere[] = $args;
		$existsUser = $this->MainModel->findOne('users', null, $orWhere);
		

		if (empty($existsUser)) {
			$data = [
				'success' => false,
				'code' => 404
			];
			return $this->returnJSON($data);
		}

		$newPassword = generateRandomString(6);
		$newData = [
			'password' => password_hash($newPassword, PASSWORD_DEFAULT)
		];
		$args = [
			'id' => $existsUser->id
		];
		$action = $this->MainModel->update('users', $newData, $args);
		$sendEmail = $this->mailgun->send(sprintf('Admin <%s>', self::EMAIL_FROM), $existsUser->email, 'Reset Password', 'Your new password is '.$newPassword);
		$sendSms = $this->sms->send($existsUser->phone, 'Your new password is '. $newPassword);

		$data = [
			'sendEmail' => $sendEmail,
			'sendSms' => $sendSms
		];
		return $this->returnJSON($data);
	}

	public function login () {

	}


	/*     * ************************************************************************************** */

    public function returnJSON($data, $statusCode = 200) {

        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }
}