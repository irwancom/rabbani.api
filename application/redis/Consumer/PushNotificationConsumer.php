<?php
namespace Redis\Consumer;

use Library\OneSignalService;
use Service\MemberDigital\MemberDigitalHandler;

class PushNotificationConsumer {

    public function setUp () {
        // ... Set up environment for this job
    }

    public function perform () {
        // .. Run job
        echo 'Receive a job'.PHP_EOL;
        $result = null;
        $pushId = $this->args['push_notification_id'];
        $ci = &get_instance();
        $ci->load->model('MainModel');
        $push = $ci->MainModel->findOne('push_notifications', ['id' => $pushId]);
        if (empty($push)) {
            return $result;
        }

        if (empty($push->player_ids) && !empty($push->user_id)) {
            $args = [
                'user_id' => $push->user_id,
                'is_active' => 1,
            ];
            $playerIds = $ci->MainModel->find('user_onesignal_player_ids', $args);
            $myIds = [];
            foreach ($playerIds as $playerId) {
                $myIds[] = $playerId->player_id;
            }
            $push->player_ids = json_encode($myIds);
        }

        $targets = [];
        $push->player_ids = json_decode($push->player_ids, true);
        $targets['playerIds'] = $push->player_ids;
        $extras = json_decode($push->extras, true);
        $onesignal = new OneSignalService;
        $result = $onesignal->pushNotification($push->title, $push->message, $targets, $extras);

        // if (!empty($result)) {
            $payload = [];
            $payload = [
                'status' => 'success',
                'player_ids' => json_encode($push->player_ids),
                'send_at' => date('Y-m-d H:i:s'),
                'provider_response' => json_encode($result)
            ];
            /* if ($result->status == true) {
                $payload = [
                    'status' => 'success',
                    'send_at' => date('Y-m-d H:i:s'),
                    'provider_response' => json_encode($result)
                ];
            } else {
                $payload = [
                    'status' => 'failed',
                    'send_at' => date('Y-m-d H:i:s'),
                    'provider_response' => json_encode($result)
                ];
            } */
            $action = $ci->MainModel->update('push_notifications', $payload, ['id' => $pushId]);
        // }


    }

    public function tearDown () {
        // ... Remove environment for this job
    }
}