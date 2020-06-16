<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Quantum {

    function callAPi($skuDitails = '', $type = '', $idStore = '') {
        if (!empty($idStore)) {
            $idStore = $idStore;
        } else {
            $idStore = 'M001-O0042';
        }
        $key = '68a780149f6c4d5eaca5573ac10ad4f3';
        if ($type == 1) {
            $arrayData = array(
                'p' => 'rabbani',
                'u' => 'userrmo',
                'j' => 'gk'
            );
//            $url_api = 'https://103.14.21.57/back_end/quantum/request_acces_api.php';
            $url_api = '';
        } elseif ($type == 2) {
            $arrayData = array(
                'u' => 'userrmo',
                'j' => 'gs',
                'b' => $skuDitails,
                'c' => $key,
                'j' => 'js',
//                'w' => 'M001-O0042'
                'w' => $idStore
            );
            $url_api = 'https://103.14.21.57/back_end/quantum/stock_api.php';
        } elseif ($type == 3) {
            $arrayData = array(
                'u' => 'userrmo',
                'j' => 'gs',
                'b' => $skuDitails,
                'c' => $key,
//                'w' => 'M001-O0042'
                'w' => $idStore
            );
            $url_api = 'https://103.14.21.57/back_end/quantum/stock_api.php';
        } elseif ($type == 4) {
            $arrayData = array(
                'u' => 'userrmo',
                'j' => 'gsw',
                'b' => $skuDitails,
                'c' => $key,
                'w' => $idStore
            );
            $url_api = 'https://103.14.21.57/back_end/quantum/stock_api.php';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_api);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arrayData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);

//        echo "response " . $server_output . "\n";
        $result = json_decode($server_output);
        return $result;
    }

}
