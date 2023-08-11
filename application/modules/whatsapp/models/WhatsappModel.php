<?php

class WhatsappModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library(['device', 'whatsappblas']);
    }

    public function SendChat($phone, $message, $token, $image) {
        if (!$image || is_nul($image) || empty($image) || $image == 0) {
            $type = 'send-message';
            $data = [
                'phone' => $phone,
                'message' => $message,
            ];
        } else {
            $type = 'send-image';
            $data = [
                'phone' => $phone,
                'caption' => $message,
                'image' => $image,
            ];
        }

        $proses = $this->whatsappblas->Send($type, $data, $token);
        return $proses;
    }

}
