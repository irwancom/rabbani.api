<?php

class Whatsapp extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('session');
        $this->load->model('WhatsappModel');
        $this->load->model('MainModel');
        $this->token = 'RrlxUeh9Y53DBGK7J2qcQr7N9yLiQLkUqfBm7F1qpLYVf3loICKTz8GmLHTc6iHn';
    }

    public function sendChat() {
        $phone = $this->input->post('phone');
        $message = $this->input->post('message');
        $image = $this->input->post('image');
        $send = $this->WhatsappModel->SendChat($phone, $message, $this->token, $image);
        echo $send;
    }

    public function sendChat2($phone, $message, $token, $image) {
        $send = $this->WhatsappModel->SendChat($phone, $message, $token, $image);
        echo $send;
    }

    public function callback() {
        header("Content-Type: text/plain");

        $id = $this->input->post('id');
        $phone = $this->input->post('phone');
        $message = $this->input->post('message');
        $pushName = $this->input->post('pushName');
        $thumbProfile = $this->input->post('thumbProfile');
        $timestamp = $this->input->post('timestamp');
        $category = $this->input->post('category');
        $receiver = $this->input->post('receiver');

        $request = array('id' => $id, 'phone' => $phone, 'message' => $message, 'pushName' => $pushName, 'thumbProfile' => $thumbProfile, 'timestamp' => $timestamp, 'category' => $category, 'receiver' => $receiver);
        $callback = [
            'fromcall' => 'WA',
            'dataJson' => json_encode($request)
        ];

        $this->MainModel->insert('logcallback', $callback);
        if ($message == 'daftar') {
//            $filterWhere = [
//                'phone' => $phone
//            ];
//            $findOne = $this->MainModel->findOne('memberCard', $filterWhere);
//            if (!empty($findOne)) {
//                $data = [
//                    'phone' => $phone
//                ];
//                $this->MainModel->update('memberCard', $data);
//            } else {
                $idmemberCard = date('Ym') . sprintf("%05d", 54321);
                $memberCard = [
                    'idAuth' => 1,
                    'memberCardID' => $idmemberCard,
                    'dateJoin' => time(),
                    'phone' => $phone,
                    'namePhone' => $pushName
                ];

                $this->MainModel->insert('memberCard', $memberCard);
//            }
//            echo date('Ym') . sprintf("%05d", 54321);
            echo 'Pendaftaran no ' . $phone . ' telah berhasil, berikut no kartu digital anda ' . $idmemberCard . ' ' . $id;
        } else {
            echo 'bukan daftar';
        }
    }

}
