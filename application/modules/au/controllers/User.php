
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\CLMHandler;
use Service\CLM\Handler\UserHandler;

class User extends REST_Controller {

    private $validator;
    private $delivery;

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
        $handler = new UserHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getUsers($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($idUser) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $handler = new UserHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $filters = [];
        $filters['users.id'] = $idUser;
        $result = $handler->getUserEntity($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function update_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new UserHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateUser($payload, ['users.id' => $id]);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new UserHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->deleteUser((int)$id);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function clm_standings_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CLMHandler($this->MainModel);
        $result = $handler->getCLMStandings();

        $this->response($result->format(), $result->getStatusCode());
    }

    public function clm_reward_fee_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = [
            'level' => $this->input->post('level'),
            'reward_fee' => $this->input->post('reward_fee')
        ];

        $handler = new CLMHandler($this->MainModel);
        $result = $handler->createCLMStanding($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function generate_referral_code_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new UserHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateBatchReferralCode();

        $this->response($result->format(), $result->getStatusCode());
    }

    public function unpaid_clm_fee_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $handler = new CLMHandler($this->MainModel);
        $filters = [
            'is_settled' => 0
        ];
        $result = $handler->getCLMTransactionGroupByUsers($filters);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function user_clm_transactions_get ($userId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $filters = $this->input->get();
        $filters['user_id'] = $userId;
        $handler = new CLMHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->getCLMTransactions($filters);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function settle_clm_transactions_post ($userId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        if (empty($this->input->post('from_created_at'))) {
            $this->delivery->addError(400, 'From created at is required');
            $this->response($this->delivery->format());
        }

        if (empty($this->input->post('until_created_at'))) {
            $this->delivery->addError(400, 'Until created at is required');
            $this->response($this->delivery->format());
        }

        $filters = [
            'user_id' => $userId,
            'is_settled' => 0,
            'created_at >=' => $this->input->post('from_created_at'),
            'created_at <=' => $this->input->post('until_created_at')
        ];

        $payload = [
            'is_settled' => 1,
            'settled_at' => date('Y-m-d H:i:s')
        ];

        $handler = new CLMHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateCLMTransactions($payload, $filters);
        $this->response($result->format(), $result->getStatusCode());
    }

    public function unsettle_clm_transactions_post ($userId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateAuthAdmin($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        if (empty($this->input->post('from_created_at'))) {
            $this->delivery->addError(400, 'From created at is required');
            $this->response($this->delivery->format());
        }

        if (empty($this->input->post('until_created_at'))) {
            $this->delivery->addError(400, 'Until created at is required');
            $this->response($this->delivery->format());
        }

        $filters = [
            'user_id' => $userId,
            'is_settled' => 1,
            'created_at >=' => $this->input->post('from_created_at'),
            'created_at <=' => $this->input->post('until_created_at')
        ];

        $payload = [
            'is_settled' => 0,
            'settled_at' => null
        ];

        $handler = new CLMHandler($this->MainModel);
        $handler->setAdmin($auth->data);
        $result = $handler->updateCLMTransactions($payload, $filters);
        $this->response($result->format(), $result->getStatusCode());
    }

}
