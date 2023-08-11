<?php
namespace Redis\Producer;

use Redis\Redis;

class WablasProducer extends Redis {

    public $type;
    public $status;
    public $phoneNumber;
    public $message;
    public $image;
    public $date;
    public $time;
    public $wablasAuthorizationToken;
    public $wablasDomain;
    public $notificationId;

    private $repository;

    public function __construct () {
        parent::__construct();
        $this->status = 'pending';
    }

    public function send () {
        $payload = [
            'type' => $this->type,
            'status' => $this->status,
            'phone_number' => $this->phoneNumber,
            'message' => $this->message,
            'image' => $this->image,
            'date' => $this->date,
            'time' => $this->time,
            'video' => $this->video,
            'send_at' => null,
            'amount' => $this->amount,
            'code' => $this->code,
            'wablas_authorization_token' => $this->wablasAuthorizationToken,
            'wablas_domain' => $this->wablasDomain,
            'notification_id' => $this->notificationId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
            'deleted_at' => null,
            'targets' => $this->targets
        ];

        $ci = &get_instance();
        $ci->load->model('MainModel');
        $action = $ci->MainModel->insert('wablas', $payload);
        $wablas = $ci->MainModel->findOne('wablas', ['id' => $action]);
        try {
            $pushToRedis = \Resque::enqueue('wablas', 'Redis\Consumer\WablasConsumer', ['wablas_id' => $wablas->id]);
        } catch (\Exception $e) {
            
        }
        return $wablas;
    }
}