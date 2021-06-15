<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Courir {

    function getCostExpedition($key = '', $origin = '', $destination = '', $weight = '', $courier = '') {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rajaongkir.com/starter/cost",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => array(
                'key' => $key,
                'origin' => $origin,
                'destination' => $destination,
                'weight' => $weight,
                'courier' => $courier
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    function getCostExpedition2($key = '', $origin = '', $destination = '', $weight = '', $courier = '') {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rajaongkir.com/starter/cost",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => array(
                'key' => $key,
                'origin' => $origin,
                'destination' => $destination,
                'weight' => $weight,
                'courier' => $courier
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    function jne($type = '', $var1 = '', $var2 = '') {
        $dev = 1;

        if ($dev == 1) {
            $username = 'RABBANIASYSA';
            $api_key = 'e072b9ac674b405ab58a5982fb79232b';
            $url = 'http://apiv2.jne.co.id:10101/';
        } else {
            $username = 'TESTAPI';
            $api_key = '25c898a9faea1a100859ecd9ef674548';
            $url = 'http://apiv2.jne.co.id:10102/';
        }

        $curl = curl_init();
        if ($type == 1) {

            $from = 'BDO10000';
            $CURLOPT_URL = $url . 'tracing/api/pricedev';
            $CURLOPT_POSTFIELDS = "username=" . $username . "&api_key=" . $api_key . "&from=" . $from . "&thru=" . $var1 . "&weight=" . $var2;
        } elseif ($type == 2) {

            $CURLOPT_URL = $url . "tracing/api/list/v1/cnote/" . $var1;
            $CURLOPT_POSTFIELDS = "username=" . $username . "&api_key=" . $api_key;
        } elseif ($type == 3) {
            // print_r($var1);
            // exit;
            $CURLOPT_URL = $url . "tracing/api/generatecnote";
            $CURLOPT_POSTFIELDS = "username=" . $username . "&api_key=" . $api_key . "&OLSHOP_BRANCH=" . $var1['OLSHOP_BRANCH'] . "&OLSHOP_CUST=" . $var1['OLSHOP_CUST'] . "&OLSHOP_ORDERID=" . $var1['OLSHOP_ORDERID'] . "&OLSHOP_SHIPPER_NAME=" . $var1['OLSHOP_SHIPPER_NAME'] . "&OLSHOP_SHIPPER_ADDR1=" . $var1['OLSHOP_SHIPPER_ADDR1'] . "&OLSHOP_SHIPPER_ADDR2=" . $var1['OLSHOP_SHIPPER_ADDR2'] . "&OLSHOP_SHIPPER_ADDR3=" . $var1['OLSHOP_SHIPPER_ADDR3'] . "&OLSHOP_SHIPPER_CITY=" . $var1['OLSHOP_SHIPPER_CITY'] . "&OLSHOP_SHIPPER_REGION=" . $var1['OLSHOP_SHIPPER_REGION'] . "&OLSHOP_SHIPPER_ZIP=" . $var1['OLSHOP_SHIPPER_ZIP'] . "&OLSHOP_SHIPPER_PHONE=" . $var1['OLSHOP_SHIPPER_PHONE'] . "&OLSHOP_RECEIVER_NAME=" . $var1['OLSHOP_RECEIVER_NAME'] . "&OLSHOP_RECEIVER_ADDR1=" . $var1['OLSHOP_RECEIVER_ADDR1'] . "&OLSHOP_RECEIVER_ADDR2=" . $var1['OLSHOP_RECEIVER_ADDR2'] . "&OLSHOP_RECEIVER_ADDR3=" . $var1['OLSHOP_RECEIVER_ADDR3'] . "&OLSHOP_RECEIVER_CITY=" . $var1['OLSHOP_RECEIVER_CITY'] . "&OLSHOP_RECEIVER_REGION=" . $var1['OLSHOP_RECEIVER_REGION'] . "&OLSHOP_RECEIVER_ZIP=" . $var1['OLSHOP_RECEIVER_ZIP'] . "&OLSHOP_RECEIVER_PHONE=" . $var1['OLSHOP_RECEIVER_PHONE'] . "&OLSHOP_QTY=" . $var1['OLSHOP_QTY'] . "&OLSHOP_WEIGHT=" . $var1['OLSHOP_WEIGHT'] . "&OLSHOP_GOODSDESC=" . $var1['OLSHOP_GOODSDESC'] . "&OLSHOP_GOODSVALUE=" . $var1['OLSHOP_GOODSVALUE'] . "&OLSHOP_GOODSTYPE=" . $var1['OLSHOP_GOODSTYPE'] . "&OLSHOP_INST=" . $var1['OLSHOP_INST'] . "&OLSHOP_INS_FLAG=" . $var1['OLSHOP_INS_FLAG'] . "&OLSHOP_ORIG=" . $var1['OLSHOP_ORIG'] . "&OLSHOP_DEST=" . $var1['OLSHOP_DEST'] . "&OLSHOP_SERVICE=" . $var1['OLSHOP_SERVICE'] . "&OLSHOP_COD_FLAG=" . $var1['OLSHOP_COD_FLAG'] . "&OLSHOP_COD_AMOUNT=" . $var1['OLSHOP_COD_AMOUNT'];
        }
        // echo $CURLOPT_URL.'<br>';
        // echo $CURLOPT_POSTFIELDS;
        // exit;

        curl_setopt_array($curl, array(
            CURLOPT_URL => $CURLOPT_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $CURLOPT_POSTFIELDS,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json",
                "User-Agent: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
         //echo $response;
         //exit;
        return $response;
    }

}
