<?php
namespace Service;

class Delivery {

	public $headers;
	public $type;
	public $data;
	public $errors;
	public $success;
	public $statusCode;
	public $filename;
	public $file;

	public function __construct () {
		$this->type = 'json';
		$this->data = [];
		$this->errors = [];
		$this->success = true;
		$this->headers = [];
		$this->statusCode = 200;
		$this->filename = '';
		$this->file = '';
	}

	public function getStatusCode () {
		return $this->statusCode;
	}

	public function addError ($statusCode, $detail) {
		$error = [
			'code' => $statusCode,
			'detail' => $detail
		];
		$this->errors[] = $error;
		$this->success = false;
		$this->statusCode = $statusCode;
	}

	public function addHeader($content) {
		$this->headers[] = $content;
	}

	public function hasErrors () {
		if (count($this->errors) > 0) {
			return true;
		}
		return false;
	}

	public function getFirstError () {
		return $this->errors[0];
	}

	public function format () {
		$payload = [];
		if ($this->hasErrors()) {
			$payload = [
				'errors' => $this->errors,
				'success' => $this->success
			];
		} else {
			$payload = [
				'data' => $this->data,
				'success' => $this->success
			];
		}
		return $payload;
	}

	public function plainText () {
		$payload = '';
		if ($this->hasErrors()) {
			$error = $this->getFirstError();
			return $error['detail'];
		}
		return $this->data;
	}

}