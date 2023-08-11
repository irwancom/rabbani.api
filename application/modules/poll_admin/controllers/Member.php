
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Poll\PollMemberHandler;
use Service\Poll\PollMemberRatingHandler;
use \libphonenumber\PhoneNumberUtil;

class Member extends REST_Controller {

    private $validator;
    private $delivery;
    private $pollHandler;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMembers($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($pollMemberId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMemberProfile(['id' => $pollMemberId]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function verify_post ($pollMemberId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMemberProfile(['id' => $pollMemberId]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        if (empty($result->data)) {
            $result->addError(400, 'Member is required');
            $this->response($result->format(), $result->getStatusCode());
        }

        $member = $result->data;

        $result = $handler->verifyPollMember($member->id);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($pollMemberId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->updatePollMember ($payload, ['id' => $pollMemberId]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function level_up_post ($pollMemberId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMemberProfile(['id' => $pollMemberId]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        if (empty($result->data)) {
            $result->addError(400, 'Member is required');
            $this->response($result->format(), $result->getStatusCode());
        }

        $member = $result->data;

        $result = $handler->levelUpMember($member);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function fail_post ($pollMemberId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMemberProfile(['id' => $pollMemberId]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        if (empty($result->data)) {
            $result->addError(400, 'Member is required');
            $this->response($result->format(), $result->getStatusCode());
        }

        $member = $result->data;

        $result = $handler->failMember($member);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function rate_post ($pollMemberId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMemberProfile(['id' => $pollMemberId]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        if (empty($result->data)) {
            $result->addError(400, 'Member is required');
            $this->response($result->format(), $result->getStatusCode());
        }

        $member = $result->data;
        $level = $member->level;
        $currentLevel = $member->current_level;
        $ratingHandler = new PollMemberRatingHandler($this->MainModel);
        if ($currentLevel == 1) {
            $result = $ratingHandler->rateLevel1Member($member, $payload);
        } else if ($currentLevel == 2) {
            $result = $ratingHandler->rateLevel2Member($member, $payload);
        } else if ($currentLevel == 3) {
            // $result = $ratingHandler->rateLevel1Member($member, $payload);
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function rate_get ($pollMemberId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMemberProfile(['id' => $pollMemberId]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        if (empty($result->data)) {
            $result->addError(400, 'Member is required');
            $this->response($result->format(), $result->getStatusCode());
        }

        $member = $result->data;

        $ratingHandler = new PollMemberRatingHandler($this->MainModel);
        $result = $ratingHandler->getPollMemberRating(['poll_member_id' => $member->id]);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_final_post () {
        die();
        set_time_limit(0);
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $result = new Delivery;
        $level = $this->input->post('level');
        if (!in_array($level, [1, 2, 3])) {
            $result->addError(400, 'Level is required');
            $this->response($result->format(), $result->getStatusCode());   
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $data = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][3])) {
                    $data[] = [
                        'poll_member_registration_number' => $sheetData[$i][3],
                        'send_notif' => true
                    ];
                }
            }
        }

        /* $sheetData = $spreadsheet->setActiveSheetIndex(1)->toArray();
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][3])) {
                    $data[] = [
                        'poll_member_registration_number' => $sheetData[$i][3],
                        'send_notif' => false
                    ];
                }
            }
        } */

        $filters = [];
        if ($level == 1) {
            $filters['level_1_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        } else if ($level == 2) {
            $filters['level_2_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        } else if ($level == 3) {
            $filters['level_3_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        }
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $memberResult = $handler->getPollMembers($filters, false);
        $memberData = $memberResult->data['result'];
        $processedData = 0;
        $totalMemberPending = count($memberData);
        // proses dulu dari excel
        foreach ($data as $d) {
            $registrationNumber = $d['poll_member_registration_number'];
            $sendNotif = $d['send_notif'];
            $existsKey = array_search($registrationNumber, array_column($memberData, 'registration_number'));
            if ($existsKey !== false) {
                $processedData++;
                // proses berhasil
                // $lvlResult = $handler->levelUpMember($memberData[$existsKey], $sendNotif);
                array_splice($memberData, $existsKey, 1);
            } else {
                $dataFailed[] = $d;
            }
        }

        // id yang tersisa difail
        foreach ($memberData as $member) {
            // $lvlResult = $handler->failMember($member);
        }
        $format = [
            'total_member_pending' => $totalMemberPending,
            'total_member_fail' => count($memberData),
            'total_member_pass' => $processedData,
            'failed_data' => $dataFailed,
        ];
        $result = new Delivery;
        $result->data = $format;
        $this->response($result->format(), $result->getStatusCode());
    }

    public function import_final_individual_post () {
        set_time_limit(0);
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $result = new Delivery;
        $level = $this->input->post('level');
        if (!in_array($level, [1, 2, 3])) {
            $result->addError(400, 'Level is required');
            $this->response($result->format(), $result->getStatusCode());   
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $dataPass = [];
        $dataNotPass = [];
        $dataFailed = [];
        if (!empty($sheetData)) {
            for ($i=1; $i<count($sheetData); $i++) { //skipping first row
                if (!empty($sheetData[$i][3])) {
                    $state = $sheetData[$i][0];
                    if (strtolower($state) == 'lolos') {
                        $dataPass[] = [
                            'poll_member_registration_number' => $sheetData[$i][3],
                            'send_notif' => true
                        ];
                    } else if (strtolower($state) == 'tidak lolos') {
                        $dataNotPass[] = [
                            'poll_member_registration_number' => $sheetData[$i][3],
                            'send_notif' => true,
                        ];
                    } else {
                        $dataFailed[] = [
                            'poll_member_registration_number' => $sheetData[$i][3],
                            'send_notif' => true,
                            'reason' => 'excel incorrect'
                        ];
                    }
                }
            }
        }

        $filters = [];
        if ($level == 1) {
            $filters['level_1_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        } else if ($level == 2) {
            $filters['level_2_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        } else if ($level == 3) {
            $filters['level_3_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        }
        $handler = new PollMemberHandler($this->MainModel);
        $totalMemberPass = 0;
        $totalMemberNotPass = 0;

        // proses lolos
        foreach ($dataPass as $data) {
            $filters['registration_number'] = $data['poll_member_registration_number'];
            $memberResult = $handler->getPollMemberProfile($filters, false);
            $member = $memberResult->data;
            if (!empty($member)) {
                // go
                $lvlResult = $handler->levelUpMember($member, true);
                $totalMemberPass++;
            } else {
                $data['reason'] = 'member not found/already processed';
                $dataFailed[] = $data;
            }
        }
        // proses tidak lolos
        foreach ($dataNotPass as $data) {
            $filters['registration_number'] = $data['poll_member_registration_number'];
            $memberResult = $handler->getPollMemberProfile($filters, false);
            $member = $memberResult->data;
            if (!empty($member)) {
                // go
                $lvlResult = $handler->failMember($member, true);
                $totalMemberNotPass++;
            } else {
                $data['reason'] = 'member not found/already processed';
                $dataFailed[] = $data;
            }
        }
        
        $format = [
            'total_member_processed' => count($dataPass) + count($dataNotPass),
            'total_member_fail' => $totalMemberNotPass,
            'total_member_pass' => $totalMemberPass,
            'failed_data' => $dataFailed,
        ];
        $result = new Delivery;
        $result->data = $format;
        $this->response($result->format(), $result->getStatusCode());
    }

}
