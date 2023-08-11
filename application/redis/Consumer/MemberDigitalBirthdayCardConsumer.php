<?php
namespace Redis\Consumer;

use Library\WablasService;
use Service\MemberDigital\MemberDigitalHandler;


class MemberDigitalBirthdayCardConsumer {

    public function setUp () {
        // ... Set up environment for this job
    }

    public function perform () {
        // .. Run job
        $result = null;
        $memberDigitalId = $this->args['member_digital_id'];
        $message = $this->args['message'];
        $ci = &get_instance();
        $ci->load->model('MainModel');
        $member = $ci->MainModel->findOne('member_digitals', ['id' => $memberDigitalId]);
        if (empty($member)) {
            return $result;
        }

        $handler = new MemberDigitalHandler($ci->MainModel);
        $memberCard = $handler->generateAndPublishBirthdayCardToMember($member, $message);
        return $memberCard;
    }

    public function tearDown () {
        // ... Remove environment for this job
    }
}