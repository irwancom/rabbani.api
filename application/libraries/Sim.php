<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Sim {

    function addUpdate($data) {
//        print_r($data);
//        exit;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.1itmedia.co.id/product/addUpdate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'sku='.urlencode($data['sku']).'&product_name='.urlencode($data['product_name']).'&desc='.urlencode($data['desc']).'&sku_code='.urlencode($data['sku_code']).'&variable='.urlencode($data['variable']).'&price='.urlencode($data['price']).'',
            CURLOPT_HTTPHEADER => array(
                'X-Token-KeyCode: 5bda62e71cd63f3f5fc2c76f9a37894f',
                'X-Token-Secret: 2dc2968735c4fa0b047834a73ce5dff7a46a73871a37265a35e1e3eff8df72c3',
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

}
