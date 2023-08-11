<?php

class Whatsappblas {

    public function __construct() {
        $this->url = 'https://selo.wablas.com/api/';
    }

    public function Send($type, $data, $token) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER,
                array(
                    "Authorization: $token",
                )
        );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_URL, $this->url . $type);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            $status = array(
                'status' => false,
                'message' => 'Send message failed.',
            );
            $data = json_encode($status, true);
        } else {
            $data = $result;
        }
        return $data;
    }

}
