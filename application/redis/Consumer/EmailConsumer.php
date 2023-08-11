<?php
namespace Redis\Consumer;

use Library\MailgunService;
use Service\MemberDigital\MemberDigitalHandler;

class EmailConsumer {

    public function setUp () {
        // ... Set up environment for this job
    }

    public function perform () {
        // .. Run job
        echo 'Receive a job'.PHP_EOL;
        $result = null;
        $emailId = $this->args['email_id'];
        $ci = &get_instance();
        $ci->load->model('MainModel');
        $email = $ci->MainModel->findOne('emails', ['id' => $emailId]);
        if (empty($email)) {
            return $result;
        }


        $mailgun = new MailgunService;
        $result = $mailgun->send($email->api_key, $email->domain, $email->from, $email->to, $email->subject, $email->text);

        if (!empty($result)) {
            $payload = [];
            if ($result->id == true) {
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
            }
            $action = $ci->MainModel->update('emails', $payload, ['id' => $emailId]);
        }


    }

    public function tearDown () {
        // ... Remove environment for this job
    }
}