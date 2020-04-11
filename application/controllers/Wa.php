<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Wa extends CI_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('wa_model');
//        $this->load->library('wa');

        $this->load->helper(array('form', 'url'));
    }

    function index() {
        $data = $this->wa_model->dataCashier();
        print_r($data);
    }

    function webhook() {
        extract($this->input->post());
        if (!empty($message)) {
            #echo "('$id', '$phone','server', '$message', '$pushName', '$groupId', '$timestamp')";
            if (!empty($pushName)) {
                $data = array(
                    'message_id' => $id,
                    'fromphone' => $phone,
                    'to' => 'server',
                    'message' => $message,
                    'pushName' => $pushName,
                    'groupId' => $groupId,
                    'timestamp' => $timestamp
                );
            } else {
                $data = array(
                    'message_id' => $id,
                    'fromphone' => $phone,
                    'to' => 'server',
                    'message' => $message,
                    'pushName' => 'tidak terbaca',
                    'groupId' => $groupId,
                    'timestamp' => $timestamp
                );
            }
            #$this->wa_model->chatFunction($data);
            $messageq = explode('#', strtolower($message));
            if (strtolower($messageq[0]) == 'cek') {
                $dataCPhone = $this->wa_model->verfyCashierPhone($phone);
                if (!empty($dataCPhone)) {
                    $dataPhone = $this->wa_model->verfyPhone($messageq[1]);
                    print_r($dataPhone);
                } else {
                    echo 'No belum terdaftar.';
                }
            } elseif (strtolower($messageq[0]) == 'reg') {
//                echo $phone;
                if (empty($messageq[2])) {
                    echo 'Format salah, silahkan di sesuaikan.';
                    exit;
                }
                $checkStore = $this->wa_model->checkStore($messageq[2]);
                if (!empty($checkStore)) {
                    $dataPhone = $this->wa_model->joinCashierPhone($messageq[1], $phone, $messageq[2]);
                    $dataPhone = 1;
                    if (empty($dataPhone)) {
                        echo 'No sudah berhasil di registrasi.';
                    } else {
                        #echo 'Pendaftaran no kasir sudah di tutup.';
                        echo 'No gagal di registrasi di karenakan sudah terdaftar';
                    }
                } else {
                    echo 'Toko tidak di kenali.';
                }
            } elseif (strtolower($messageq[0]) == 'update') {
//                echo $phone;
                if (empty($messageq[2])) {
                    echo 'Format salah, silahkan di sesuaikan.';
                    exit;
                }
                $checkStore = $this->wa_model->checkStore($messageq[2]);
                if (!empty($checkStore)) {
                    $dataPhone = $this->wa_model->joinCashierPhone($messageq[1], $phone, $messageq[2]);
                    if (!empty($dataPhone)) {
                        $this->wa_model->updateCashier($messageq[1], $phone, $messageq[2]);
                        echo 'Data untuk no ' . $phone . ' sudah berhasil di perbaharua.';
                    } else {
                        echo 'No gagal di perbaharui.';
                    }
                } else {
                    echo 'Toko tidak di kenali.';
                }
            } elseif (strtolower($messageq[0]) == 'dev') {
//                $data = array(
//                    'fromphone' => 'server',
//                    'to' => $phone,
//                    'message' => $message
//                );
//                $this->wa_model->chatFunction($data);
                echo 'Dev versi. ' . $message;
            } else {
//                echo "('$id', '$phone','server', '$message', '$pushName', '$groupId', '$timestamp')";
            }
            $this->wa_model->chatFunction($data);
//            echo "('$id', '$phone','server', '$message', '$pushName', '$groupId', '$timestamp')";
        }
    }

}
