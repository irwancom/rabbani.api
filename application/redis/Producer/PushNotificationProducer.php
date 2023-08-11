<?php
namespace Redis\Producer;

use Redis\Redis;

class PushNotificationProducer extends Redis {

    public $appId;
    public $userId;
    public $status;
    public $appKey;
    public $playerIds;
    public $title;
    public $message;
    public $notificationId;
    public $extras;

    private $repository;

    public function __construct () {
        parent::__construct();
        $this->status = 'pending';
    }

    public function send () {
        $payload = [
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
            'user_id' => $this->userId,
            'status' => $this->status,
            'title' => $this->title,
            'message' => $this->message,
            'notification_id' => $this->notificationId,
            'extras' => json_encode($this->extras),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => null,
            'deleted_at' => null,
        ];

        if (!empty($this->playerIds)) {
            $payload['player_ids'] = json_encode($this->playerIds);
        }

        $ci = &get_instance();
        $ci->load->model('MainModel');
        $action = $ci->MainModel->insert('push_notifications', $payload);
        $push = $ci->MainModel->findOne('push_notifications', ['id' => $action]);
        try {
            $pushToRedis = \Resque::enqueue('push_notification', 'Redis\Consumer\PushNotificationConsumer', ['push_notification_id' => $push->id]);
        } catch (\Exception $e) {
            
        }
        return $push;
    }
}