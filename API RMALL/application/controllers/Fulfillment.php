<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

class Fulfillment extends REST_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');
        $this->load->model('fulfillment_model');
        $this->load->library('wa');
        $this->load->library('sms');

        $this->load->helper(array('form', 'url'));
    }

    function index_get() {
        $data = $this->fulfillment_model->check();
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function stock_post() {
        $data = array(
            'keyCodeStaff' => $this->input->post('keyCodeStaff'),
            'secret' => $this->input->post('secret'),
            'rack' => $this->input->post('rack'),
            'sku' => $this->input->post('sku'),
            'type' => $this->input->post('type')
        );
        $data = $this->fulfillment_model->stock($data);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function updateProcess_post() {

        $this->fulfillment_model->updateProcess($this->input->post('noInvoice'));
    }

    function printBarcode_post($limit = '') {
        $data = array(
            'keyCodeStaff' => $this->input->post('keyCodeStaff'),
            'secret' => $this->input->post('secret')
        );
        $data = $this->fulfillment_model->printBarcode($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function getIdBigData_post() {
        $data = array(
            'keyCodeStaff' => $this->input->post('keyCodeStaff'),
            'secret' => $this->input->post('secret')
        );

        $x = 1;
        while ($x <= 500) {
            $data['dataBarcode'][][] = $this->fulfillment_model->getPrindID($data);
//            print_r($data);
            $x++;
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function addData_post() {

        $data = array(
            'keyCodeStaff' => $this->input->post('keyCodeStaff'),
            'secret' => $this->input->post('secret'),
            'rack' => $this->input->post('rack'),
            'dataBarcode' => $this->input->post('dataBarcode')
        );
        $data = $this->fulfillment_model->addData($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function productOut_post() {
        $data = array(
            'keyCodeStaff' => $this->input->post('keyCodeStaff'),
            'secret' => $this->input->post('secret'),
            'dataBarcode' => $this->input->post('dataBarcode')
        );
        $data = $this->fulfillment_model->outData($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function outData_post() {
        $data = array(
            'keyCodeStaff' => $this->input->post('keyCodeStaff'),
            'secret' => $this->input->post('secret'),
            'dataBarcode' => $this->input->post('dataBarcode')
        );
        $data = $this->fulfillment_model->outData($data);

        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function getRackID_get() {
        $this->wa->SendWa('08986002287', 'haloooo');
        exit;
        /* $data = $this->fulfillment_model->rack();
          echo '<center><table style="width: 35%";><tr>';
          echo'<td align="center">RABBANI<br><img src="http://barcodes4.me/barcode/c128b/rabbani.png" style="height: 60; width: 150;"><br></td>';
          echo'<td align="center">RABBANI<br><img src="http://barcodes4.me/barcode/c128b/rabbani.png" style="height: 60; width: 150;"><br></td>';
          echo '</tr>';
          foreach($data as $d){
          echo'<td align="center">'.$d->idBigdata.'<br><img src="http://barcodes4.me/barcode/c128b/'.$d->idBigdata.'.png" style="height: 60; width: 150;"><br></td>';
          if($d->idNumBig % 2 == 0){
          echo '</tr>';
          }
          }
          echo '</tr></table></center>';
          exit; */

        $data = $this->fulfillment_model->rack();
        echo '<center><table style="width: 35%";><tr>';
        echo'<td align="center">RABBANI<br><img src="http://barcodes4.me/barcode/c128b/rabbani.png" style="height: 60; width: 150;"><br></td>';
        echo'<td align="center">RABBANI<br><img src="http://barcodes4.me/barcode/c128b/rabbani.png" style="height: 60; width: 150;"><br></td>';
        echo '</tr>';
        foreach ($data as $d) {
            echo'<td align="center">' . $d->norack . '<br><img src="http://barcodes4.me/barcode/c128b/' . $d->norack . '.png" style="height: 60; width: 150;"><br></td>';
            if ($d->idrack % 2 == 0) {
                echo '</tr>';
            }
        }
        echo '</tr></table></center>';
        exit;
        $x = 1;
        while ($x <= 10) {
            if ($x < 10) {
                $x = '0' . $x;
            } else {
                $x = $x;
            }
            $noRack = '0D04' . $x;
            $this->fulfillment_model->rack($noRack);
            echo $noRack . '<br>';
            $x++;
        }
        exit;
        $x = 1;
        while ($x <= 5) {
            $id = time() . rand(00, 99);
            $data[] = array(
                'idRackdata' => $id,
                'urlimage' => 'http://barcodes4.me/barcode/c39/' . $id . '.png'
            );
            $x++;
        }
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function printBarcode_get() {
        $data = $this->fulfillment_model->printBarcoce(2);
        echo '<center><table style="width: 35%";><tr>';
        echo'<td align="center">RABBANI<br><img src="http://barcodes4.me/barcode/c128b/rabbani.png" style="height: 60; width: 150;"><br></td>';
        echo'<td align="center">RABBANI<br><img src="http://barcodes4.me/barcode/c128b/rabbani.png" style="height: 60; width: 150;"><br></td>';
        echo '</tr>';
        $i = 1;
        foreach ($data as $d) {
            echo'<td align="center">' . $d->idBigdata . '<br><img src="http://barcodes4.me/barcode/c128b/' . $d->idBigdata . '.png" style="height: 60; width: 150;"><br></td>';
            if ($i % 2 == 0) {
                echo '</tr>';
            }
            $i++;
        }
        echo '</tr></table></center>';
    }

    public function importDataMP_post() {
        $data = $this->input->post('data');
//        print_r($data);
//        exit;
        $this->fulfillment_model->importDataimportDataExel($data);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function outProduct_post() {
        $data = $this->input->post('awb');
        $data = $this->fulfillment_model->debitStock($data);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function importData_get() {
        $file = fopen("file/Dt_Stok-_1_.csv", "r");
        while (!feof($file)) {
            $data = fgetcsv($file);
            $this->fulfillment_model->importData2($data);
            print_r($data);
        }
        fclose($file);
    }

    public function updateEmpty_get($id = '') {
        $this->fulfillment_model->updateEmpty($id);
        header('Location: https://print.rmall.id/recheck.php');
        exit;
    }

    public function importTransaksi_get() {
        $this->fulfillment_model->sync();
        exit;
        $file = fopen("file/transaksi_shopeex.csv", "r");
        while (!feof($file)) {
            $data = fgetcsv($file);
//            $this->fulfillment_model->importData3($data,'2020-05-07');
            print_r($data);
        }
        fclose($file);
    }

    public function handOver_post() {
        $data = $this->fulfillment_model->handOver($this->input->post('awb'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function sendTransaction_post() {
        $data = $this->fulfillment_model->sendTransaction($this->input->post('awb'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function trx_post() {
        $data = $this->fulfillment_model->trx($this->input->post('awb'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function restore_post() {
        $data = $this->fulfillment_model->restore($this->input->post('awb'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function checkTransaction_post() {
        $data = $this->fulfillment_model->checkTransaction($this->input->post('awb'));
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    public function handOverDownload_get() {
        $this->fulfillment_model->handOverDownload();
    }

    public function notifDownload_get($id = '') {
        $data = $this->fulfillment_model->notifDownload($id);
        if ($data) {
            $this->response($data, 200);
        } else {
            $this->response(array('status' => 'fail', 502));
        }
    }

    function sendWa($phone = '', $msg = '') {
        $curl = curl_init();
        $token = "Z1ZrcYypiCPBJQ9NPAGc6SInTxj6dgiFZ8Km4c7EQziqKwNfa9pxlJpnZuI2QUpy";
        $data = [
            'phone' => $phone,
            'message' => $msg,
        ];

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $token",
                )
        );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_URL, "https://kemusu.wablas.com/api/send-message");
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($curl);
        curl_close($curl);

//    return $result;
    }

    function sendSms($phone = '', $msg = '') {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://sms241.xyz/sms/smsmasking.php?username=simsms&key=e31e22edcca0e660aa8c03555e2cbbad&number=" . $phone . "&message=" . urlencode($msg),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);

        curl_close($curl);
//        echo $response;
    }

    public function bcWa_get() {
        $data = $this->fulfillment_model->bcWaCashier();
//        print_r($data);
        if (!empty($data)) {
            foreach ($data as $dt) {
                $hp = $dt->phone;
//                echo $hp;
//                exit;
                $msg = 'Assalamualaikum _' . $dt->name . '_, mohon untuk melakukan *BLOCK WA* secara serentak pada no http://wa.me/628112370111, untuk tutorial melakukan bock no tersebut sebagai berikut https://youtu.be/2uyOWLNL2AY , karena no tersebut telah di retas dan merugikan banyak konsumen dengan modus mengatas namakan rabbani, mohon kerjasamanya dan lakukan sekarang, terimakasih.';
//                $msg2 = 'Pembaharuan data di buka dari jam 11:30 WIB - 14:00 WIB.';
//                echo $hp . ' - ' . $msg . ' - ' . $msg2;
//                $this->sendWa($hp, $msg);
//                $this->sendWa($hp, $msg2);
//                exit;
            }
        }
        echo 'xxx';

        exit;
//        $phoneNo = '6208986002287';
//        $hasil = substr($phoneNo, 0,3);
//        $no = substr($phoneNo, 3);
//        $awl = array('620','08');
//        $akhir = array('62','628');
//        echo str_replace($awl,$akhir,$hasil).$no;
//        exit();
        $data = $this->fulfillment_model->bcWa();

        if (!empty($data)) {
            foreach ($data as $d) {
                $phoneNo = $d->phone;
                $hasil = substr($phoneNo, 0, 3);
                $no = substr($phoneNo, 3);
                $awl = array('620', '08', '83', '82', '81', '84', '85', '86', '87', '88', '89');
                $akhir = array('62', '628', '6283', '6282', '6281', '6284', '6285', '6286', '6287', '6288', '6289');
                $phone = str_replace($awl, $akhir, $hasil) . $no;
//                echo $phone;
//                $this->fulfillment_model->updatePhone($d->idpeople, $phone);
                print_r($d);
//                $this->sendWa($d->phone, 'Alhamdulillah!
//Selamat kak _'.$d->name.'_, Kamu Dapat Voucher Rp50.000!
//Kode Voucher: *RABBBAGI2*
//
//Terima kasih karena sudah menjadi pelanggan setia Rabbani. Sebagai apresiasi, nikmati kode voucher sebesar Rp50.000 sekarang juga dengan minimal belanja hanya Rp200.000 berlaku di https://shopee.co.id/rabbani.official
//
//Tunggu apalagi tukarkan sekarang juga, Voucher berlaku sampai 29 Feb 2020.');
//                exit();
            }
        }
    }

}
