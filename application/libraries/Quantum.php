<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Quantum {

    function callAPi($skuDitails = '', $type = '') {
        $key = '1dac4c079bb9a4528efa5d1014675092';
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
                'w' => 'M001-O0042'
            );
            $url_api = 'https://103.14.21.57/back_end/quantum/stock_api.php';
        } else {
            $arrayData = array(
                'u' => 'userrmo',
                'j' => 'gs',
                'b' => $skuDitails,
                'c' => $key,
                'w' => 'M001-O0042'
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
