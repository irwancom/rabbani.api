<?php
namespace Redis\Producer;

use Redis\Redis;

class MemberDigitalBirthdayCardProducer extends Redis {

    public $type;
    public $status;
    public $phoneNumber;
    public $message;
    public $image;
    public $date;
    public $time;
    public $wablasAuthorizationToken;
    public $wablasDomain;

    private $repository;

    public function __construct () {
        parent::__construct();
    }

    public function sendBirthdayCard ($member, $message = null) {
        $pushToRedis = \Resque::enqueue('member_digital_birthday_card', 'Redis\Consumer\MemberDigitalBirthdayCardConsumer', ['member_digital_id' => $member->id, 'message' => $message]);
        return $pushToRedis;
    }
}