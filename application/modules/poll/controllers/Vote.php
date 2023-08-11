
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Poll\PollMemberHandler;
use Service\Poll\PollVoteHandler;

class Vote extends REST_Controller {

    private $validator;
    private $delivery;
    private $pollHandler;

    private $currentLevel = 3;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function members_get () {
        $filters = $this->input->get();
        $pollHandler = new PollMemberHandler($this->MainModel);
        $filters['status'] = PollMemberHandler::STATUS_VERIFIED;
        $filters['public'] = true;
        $filters['registration_number'] = '~~';
        if ($this->currentLevel == 1) {
            $filters['level_1_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        } else if ($this->currentLevel == 2) {
            $filters['level_2_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        } else if ($this->currentLevel == 3) {
            $filters['level_3_status'] = PollMemberHandler::LEVEL_STATUS_PENDING;
        }
        $filters['order_key'] = 'RAND()';
        $result = $pollHandler->getPollMembers($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($slug) {
        $slug = urldecode($slug);
        $pollHandler = new PollMemberHandler($this->MainModel);
        $result = $pollHandler->getPollMemberProfile(['registration_number' => $slug, 'public' => true, 'status' => PollMemberHandler::STATUS_VERIFIED]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $member = $result->data;
        if ($member->current_level != $this->currentLevel) {
            $result->addError(400, 'Incorrect level');
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function transactions_get ($slug) {
        $slug = urldecode($slug);
        $pollHandler = new PollVoteHandler($this->MainModel);
        $filters = [
            'registration_number' => $slug,
            'status' => PollVoteHandler::STATUS_PAID,
            'public' => true,
            'order_key' => 'poll_vote_transactions.paid_at',
            'order_value' => 'DESC',
            'page' => $this->input->get('page'),
            'data' => $this->input->get('data'),
            'same_level' => true,
        ];
        $result = $pollHandler->getVoteTransactions($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function check_post () {
        $pollHandler = new PollVoteHandler($this->MainModel);
        $slug = urldecode($this->input->post('registration_number'));
        $paymentMethod = $this->input->post('payment_method_code');
        $payload = [
            'registration_number' => $slug,
            'payment_method_code' => $paymentMethod,
            'total_votes' => (int)$this->input->post('total_votes'),
            'phone_number' => $this->input->post('phone_number'),
        ];
        $result = $pollHandler->checkVote($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function submit_post () {
        $pollHandler = new PollVoteHandler($this->MainModel);
        $slug = urldecode($this->input->post('registration_number'));
        $paymentMethod = $this->input->post('payment_method_code');
        $payload = [
            'registration_number' => $slug,
            'payment_method_code' => $paymentMethod,
            'total_votes' => (int)$this->input->post('total_votes'),
            'phone_number' => $this->input->post('phone_number'),
        ];
        $result = $pollHandler->createVote($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $this->response($result->format(), $result->getStatusCode());
    }

    public function callback_tripay_post () {
        $rawJson = file_get_contents("php://input");
        $body = json_decode($rawJson);
        $logData = [
            'fromcall' => 'TRIPAY_DPR',
            'dataJson' => json_encode($body),
            'dateTime' => date('Y-m-d H:i:s')
        ];
        $action = $this->MainModel->insert('logcallback', $logData);
        $handler = new PollVoteHandler($this->MainModel);
        $result = $handler->onTripayCallback($body);
        $this->response($result->format(), $result->getStatusCode());
    }

}
