
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\Poll\PollMemberHandler;
use \libphonenumber\PhoneNumberUtil;

class Me extends REST_Controller {

    private $validator;
    private $delivery;
    private $pollHandler;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function index_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        $this->response($auth->format(), $auth->getStatusCode());
    }

    public function profile_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getPollMemberProfile();
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }
        $choices = $handler->generateTalentChoices();
        $result->data->talent_choices = $choices;

        $this->response($result->format(), $result->getStatusCode());
    }

    public function profile_picture_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateFileMemberProfile('profile_picture_url', $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function parental_certificate_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateFileMemberProfile('parental_certificate_url', $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function school_recommendation_letter_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->updateFileMemberProfile('school_recommendation_letter_url', $payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function profile_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        unset($payload['registration_number']);
        unset($payload['status']);
        
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $filter = [
            'id' => $auth->data['id']
        ];
        $result = $handler->updatePollMember($payload, $filter);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function files_get () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }
        
        $filters = $this->input->get();

        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->getFiles($filters);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function file_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->addFile($payload);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

    public function file_delete_post ($id) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validatePollMember($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        
        $handler = new PollMemberHandler($this->MainModel, $auth->data);
        $result = $handler->deleteFile(['id' => $id]);
        if ($result->hasErrors()) {
            $this->response($result->format(), $result->getStatusCode());
        }

        $this->response($result->format(), $result->getStatusCode());
    }

}
