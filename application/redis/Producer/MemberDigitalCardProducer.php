<?php
namespace Redis\Producer;

use Redis\Redis;

class MemberDigitalCardProducer extends Redis {

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

    public function createCard ($member) {
        // $pushToRedis = \Resque::enqueue('member_digital_card', 'Redis\Consumer\MemberDigitalCardConsumer', ['member_digital_id' => $member->id]);
        // return $pushToRedis;
    }
}