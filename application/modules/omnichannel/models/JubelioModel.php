<?php

class JubelioModel extends CI_Model {

	public function __construct () {
		
		parent::__construct();

		$this->load->database();
	}
}