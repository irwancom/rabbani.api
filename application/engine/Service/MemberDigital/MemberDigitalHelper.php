<?php
namespace Service\MemberDigital;

class MemberDigitalHelper {

	public function __construct () {

	}

	public static function generateReferralCode ($member) {
		$prefix = strtolower(substr(str_replace(' ', '', mb_convert_encoding($member->name, 'UTF-8')), 0, 4));
		$suffix = str_pad($member->id, 3, 0, STR_PAD_LEFT);
		return sprintf('%s%s', $prefix, $suffix);
	}
}