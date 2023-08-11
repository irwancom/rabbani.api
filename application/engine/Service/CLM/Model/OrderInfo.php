<?php
namespace Service\CLM\Model;

class OrderInfo {

	const COLOR_BLACK = '#000000';
	const COLOR_GREEN = '#10B981';
	const COLOR_RED = '#EF4444';

	private $label = '';

	private $Value = '';

	private $labelColor = self::COLOR_BLACK;

	private $valueColor = self::COLOR_BLACK;

	private $attribute = [];

	public function setLabel ($label) {
		$this->label = $label;
	}

	public function setLabelColor ($labelColor) {
		$this->labelColor = $labelColor;
	}

	public function setValue ($value) {
		$this->value = $value;
	}

	public function setValueColor ($valueColor) {
		$this->valueColor = $valueColor;
	}

	public function setAttribute ($attribute = []) {
		$this->attribute = $attribute;
	}

	public function format () {
		$result = new \stdClass;
		$result->label = $this->label;
		$result->label_color = $this->labelColor;
		$result->value = $this->value;
		$result->value_color = $this->valueColor;
		if($this->attribute && !empty($this->attribute) && !is_null($this->attribute)){
			foreach ($this->attribute as $k_attr => $attr) {
				$result->$k_attr = $attr;
			}
		}
		return $result;
	}


}