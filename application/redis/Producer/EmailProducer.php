<?php
namespace Redis\Producer;

use Redis\Redis;

class EmailProducer extends Redis {

    public $userId;
    public $status;
    public $apiKey;
    public $domain;
    public $from;
    public $to;
    public $subject;
    public $text;
    public $notificationId;

    private $repository;

    public function __construct () {
        parent::__construct();
        $this->status = 'pending';
    }

    public function send () {
        $payload = [
            'api_key' => $this->apiKey,
            'domain' => $this->domain,
            'user_id' => $this->userId,
            'status' => $this->status,
            'from' => $this->from,
            'to' => $this->to,
            'subject' => $this->subject,
            'text' => $this->text,
            'notification_id' => $this->notificationId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
            'deleted_at' => null,
        ];

        $ci = &get_instance();
        $ci->load->model('MainModel');
        $action = $ci->MainModel->insert('emails', $payload);
        $email = $ci->MainModel->findOne('emails', ['id' => $action]);
        try {
            $pushToRedis = \Resque::enqueue('email', 'Redis\Consumer\EmailConsumer', ['email_id' => $email->id]);
        } catch (\Exception $e) {
            
        }
        return $email;
    }
}