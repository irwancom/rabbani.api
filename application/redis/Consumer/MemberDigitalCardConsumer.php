<?php
namespace Redis\Consumer;

use Library\WablasService;
use Service\MemberDigital\MemberDigitalHandler;


class MemberDigitalCardConsumer {

    public function setUp () {
        // ... Set up environment for this job
    }

    public function perform () {
        // .. Run job
        $result = null;
        $memberDigitalId = $this->args['member_digital_id'];
        $ci = &get_instance();
        $ci->load->model('MainModel');
        $member = $ci->MainModel->findOne('member_digitals', ['id' => $memberDigitalId]);
        if (empty($member)) {
            return $result;
        }

        $handler = new MemberDigitalHandler($ci->load->model('MainModel'));
        $memberCard = $handler->createMemberCard($member);
        $data = [
            'member_card_url' => $memberCard['cdn_url'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $action = $ci->MainModel->update('member_digitals', $data, ['id' => $member->id]);
        $member = $ci->MainModel->findOne('member_digitals', ['id' => $member->id]);

        if (!empty($member->wablas_phone_number_receiver)) {
            $wablasConfig = $ci->MainModel->findOne('auth_api_wablas', ['wablas_phone_number' => $member->wablas_phone_number_receiver]);
            if (empty($wablasConfig) || empty($wablasConfig->wablas_token)) {
                return false;
            }

            $wablasService = new WablasService('https://selo.wablas.com', $wablasConfig->wablas_token);
            $message = sprintf('Kartu kak %s sudah selesai dibuat. Silahkan pilih menu 4 untuk mendapatkan kartu digital. %s%s', $member->name, PHP_EOL, PHP_EOL);
            $generalMenuText = sprintf('%sStatus kak %s saat ini sudah menjadi member digital kami, silahkan ketik angka untuk mengetahui informasi yang dibutuhkan.%s1. POIN : format untuk mengetahui total poin%s2. UPDATE : format untuk perubahan data%s3. TRANSAKSI : format untuk mengetahui histori transaksi%s4. CETAK : format untuk mendapatkan / cetak ulang kartu digital', $message, $member->name, PHP_EOL, PHP_EOL, PHP_EOL, PHP_EOL);
            $sendWa3 = $wablasService->publishMessage('send_message', $member->phone_number, $generalMenuText);
        }

        return $result;
    }

    public function tearDown () {
        // ... Remove environment for this job
    }
}