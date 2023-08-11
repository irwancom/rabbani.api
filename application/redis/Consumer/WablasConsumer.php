<?php
namespace Redis\Consumer;

use Library\WablasService;
use Service\MemberDigital\MemberDigitalHandler;

class WablasConsumer {

    public function setUp () {
        // ... Set up environment for this job
    }

    public function perform () {
        // .. Run job
        echo 'Receive a job'.PHP_EOL;
        $result = null;
        $wablasId = $this->args['wablas_id'];
        $ci = &get_instance();
        $ci->load->model('MainModel');
        $wablas = $ci->MainModel->findOne('wablas', ['id' => $wablasId]);
        if (empty($wablas)) {
            return $result;
        }
        $wablasService = new WablasService($wablas->wablas_domain, $wablas->wablas_authorization_token);
        if ($wablas->type == 'send_message') {
            echo '-- send_message'.PHP_EOL;
            $result = $wablasService->sendMessage($wablas->phone_number, $wablas->message);
        } else if ($wablas->type == 'send_image') {
            echo '-- send_image'.PHP_EOL;
            $result = $wablasService->sendImage($wablas->phone_number, $wablas->message, $wablas->image);
        } else if ($wablas->type == 'scheduled_message') {
            echo '-- scheduled_message'.PHP_EOL;
            $result = $wablasService->createScheduledMessage($wablas->phone_number, $wablas->message, $wablas->date, $wablas->time);
        } else if ($wablas->type == 'send_video') {
            echo '-- send_video'.PHP_EOL;
            $result = $wablasService->sendVideo($wablas->phone_number, $wablas->message, $wablas->video);
        } else if ($wablas->type == 'send_voucher_rabbani') {
            echo '-- send_voucher_rabbani'.PHP_EOL;
            $handler = new MemberDigitalHandler($ci->MainModel);
            $imageVoucher = $handler->generateVoucherRabbani($wablas->amount, $wablas->code);
            $result = $wablasService->sendImage($wablas->phone_number, '', $imageVoucher['cdn_url']);
        } else if ($wablas->type == 'send_bulk') {
            echo '-- send_bulk'.PHP_EOL;
            $result = $wablasService->sendBulk(json_decode($wablas->targets, true));
        } else if ($wablas->type == 'send_bulk_image') {
            echo '-- send_bulk_image'.PHP_EOL;
            $result = $wablasService->sendBulkImage(json_decode($wablas->targets, true));
        }

        if (!empty($result)) {
            $payload = [];
            if ($result->status == true) {
                $payload = [
                    'status' => 'success',
                    'send_at' => date('Y-m-d H:i:s'),
                    'wablas_response' => json_encode($result)
                ];
            } else {
                $payload = [
                    'status' => 'failed',
                    'send_at' => date('Y-m-d H:i:s'),
                    'wablas_response' => json_encode($result)
                ];
            }
            $action = $ci->MainModel->update('wablas', $payload, ['id' => $wablasId]);
        }


    }

    public function tearDown () {
        // ... Remove environment for this job
    }
}