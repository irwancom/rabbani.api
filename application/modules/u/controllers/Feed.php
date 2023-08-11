<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\FeedHandler;

class Feed extends REST_Controller {

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
        $auth = $this->validator->validateUser($secret);

        $filters = $this->input->get();
        $handler = new FeedHandler($this->MainModel);
        $result = $handler->getFeeds($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function post_post () {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $handler = new FeedHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->createFeed($payload);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function like_post ($feedId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = [
            'is_like' => 1,
        ];
        $handler = new FeedHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->createFeedLike($feedId, $payload);

        $this->response($result->format(), $result->getStatusCode());   
    }

    public function unlike_post ($feedId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = [
            'is_like' => 0,
        ];
        $handler = new FeedHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->createFeedLike($feedId, $payload);

        $this->response($result->format(), $result->getStatusCode());   
    }

    public function comments_get () {
        $filters = $this->input->get();
        $handler = new FeedHandler($this->MainModel);
        $result = $handler->getFeedComments($filters);

        $this->response($result->format(), $result->getStatusCode());   
    }

    public function comment_post ($feedId) {
        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $this->response($auth->format(), $auth->getStatusCode());
        }

        $payload = $this->input->post();
        $payload['user_feed_id'] = $feedId;
        $handler = new FeedHandler($this->MainModel);
        $handler->setUser($auth->data);
        $result = $handler->createFeedComment($payload);

        $this->response($result->format(), $result->getStatusCode());   
    }

    public function view_post ($userFeedId) {
        $payload = [
            'user_feed_id' => $userFeedId,
            'user_id' => null
        ];

        $secret = $this->input->get_request_header('X-Token-Secret');
        $auth = $this->validator->validateUser($secret);
        if ($auth->hasErrors()) {
            $payload['user_id'] = null;
        } else {
            $authData = $auth->data;
            $payload['user_id'] = $authData['id'];
        }
        

        $filters = $this->input->get();
        $handler = new FeedHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->viewFeed($payload['user_feed_id'], $payload['user_id']);

        $this->response($result->format(), $result->getStatusCode());
    }

}
