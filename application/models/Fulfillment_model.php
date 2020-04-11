<?php

class Fulfillment_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
        $this->load->helper(array('form', 'url'));
    }

    public function verfyAccount($keyCode = '', $secret = '') {
        $data = array(
            "keyCodeStaff" => $keyCode,
            "secret" => $secret
        );
        $this->db->select('c.namestore, a.*');
        $this->db->Join('store as c', 'c.idstore = a.idstore', 'left');
        $query = $this->db->get_where('apiauth_staff as a', $data)->result();
        return $query;
    }

    public function getPrindID($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCodeStaff'], $data['secret']);

        if (!empty($dataAccount)) {
            $id = time() . rand(00, 99);
            $checkIdBigData = $this->db->get_where('product_bigData', array('idBigdata' => $id))->result();
            if (empty($checkIdBigData)) {
                $data = array(
                    'idBigdata' => $id,
                    'timeCreate' => date('Y-m-d H:i:s'),
                    'urlimage' => 'http://barcodes4.me/barcode/c128b/' . $id . '.png',
                    'idstore' => $dataAccount[0]->idstore
                );

                $this->db->insert('product_bigData', $data);
            } else {
                $data = 0;
            }
//            return $data;
        }
        if ($dataAccount) {
            $response['status'] = 200;
//            $response['error'] = false;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function printBarcode($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCodeStaff'], $data['secret']);
        if (!empty($dataAccount)) {
            $data = $this->db->get_where('product_bigData', array('status' => 0, 'idstore' => $dataAccount[0]->idstore))->result();
        }

        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['totalData'] = count($data);
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function addData($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCodeStaff'], $data['secret']);
        $dataProduct = '';
        if (!empty($dataAccount)) {
            $dJson = json_decode($data['dataBarcode']);
            if (!empty($dJson)) {
                foreach ($dJson as $dJ) {
                    $checkData = $this->db->get_where('product_bigData', array('idBigdata' => $dJ->idData, 'status' => 0))->result();
                    if (!empty($checkData)) {
                        $checkDataProduct = $this->db->get_where('product_bigDataProduct', array('skuProduct' => $dJ->sku))->result();
                        if (!empty($checkDataProduct)) {
                            $set = array(
                                'rack' => $data['rack'],
                                'sku' => $dJ->sku,
                                'lastCheck' => date('Y-m-d H:i:s'),
                                'status' => 1
                            );
                            $this->db->set($set);
                            $this->db->where('idBigdata', $dJ->idData);
                            $dataAccount[] = $this->db->update('product_bigData');
                            $this->db->join('product_bigDataProduct as b', 'b.skuProduct = a.sku', 'left');
                            $dataProduct = $this->db->get_where('product_bigData as a', array('a.idBigdata' => $dJ->idData, 'a.status' => 1))->result();
                        } else {
                            $dataAccount = 0;
                            $dataProduct = 0;
                        }
                    } else {
                        $dataAccount = 0;
                        $dataProduct = 0;
                    }
                }
            }
        }

        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['dataProduct'] = $dataProduct;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to existing or data empty.';
            $response['dataProduct'] = $dataProduct;
            return $response;
        }
    }

    public function outData($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCodeStaff'], $data['secret']);
        if (!empty($dataAccount)) {
            $dJson = json_decode($data['dataBarcode']);
            if (!empty($dJson)) {
                foreach ($dJson as $dJ) {
                    $this->db->join('product_bigDataProduct as b', 'b.skuProduct = a.sku', 'left');
                    $checkData = $this->db->get_where('product_bigData as a', array('a.idBigdata' => $dJ->idData, 'a.status' => 1))->result();
//                    print_r($checkData);
//                    exit;
                    if (!empty($checkData)) {
                        $set = array(
                            'idBigdata' => $dJ->idData,
                            'lastCheck' => date('Y-m-d H:i:s'),
                            'status' => 2
                        );
                        $this->db->set($set);
                        $this->db->where('idBigdata', $dJ->idData);
                        $dataProductFUllfilment[] = array(
                            'message' => $this->db->update('product_bigData'),
                            'dataProduct' => $checkData
                        );
                    } else {
                        $dataProductFUllfilment[] = array(
                            'message' => 'ID ' . $dJ->idData . ' product no stay.',
                            'dataProduct' => 'empty'
                        );
                    }
                }
            }
        }

        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['dataProductFUllfilment'] = $dataProductFUllfilment;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function importData($data = '') {
        $checkProduct = $this->db->get_where('product', array('skuProduct' => $data[0]))->result();
//        print_r($checkProduct);
//        exit;
        if (empty($checkProduct)) {

            $datax = array(
                'skuProduct' => $data[0],
                'timeCreate' => date('H:i:s'),
                'dateCreate' => date('Y-m-d'),
                'productName' => $data[2],
                'idcategory' => 1,
                'idstore' => 1
            );

            $this->db->insert('product', $datax);
            $checkProduct[0]->idproduct = $this->db->insert_id();

            $datax = array(
                'idproduct' => $checkProduct[0]->idproduct,
                'urlImage' => 'http://office.rmall.id/public/images/global/logo_rabbani.png'
            );

            $this->db->insert('product_images', $datax);
        }
        $checkProductDItails = $this->db->get_where('product_ditails', array('skuPditails' => $data[0]))->result();
        if (empty($checkProductDItails)) {
            $datax = array(
                'idproduct' => $checkProduct[0]->idproduct,
                'skuPditails' => $data[1],
                'size' => 'S',
                'collor' => $data[3],
                'price' => $data[4],
                'stock' => $data[5]
            );

            $this->db->insert('product_ditails', $datax);
            $lastIdpDitails = $this->db->insert_id();

            $datax = array(
                'idpditails' => $lastIdpDitails,
                'urlImage' => 'http://office.rmall.id/public/images/global/logo_rabbani.png'
            );

            $this->db->insert('product_images_ditails', $datax);

            $datax = array(
                'idstore' => 1,
                'skuProduct' => $data[1],
                'nameProduct' => $data[2],
                'collor' => $data[3],
                'priceProduct' => 1
            );
            $this->db->insert('product_bigDataProduct', $datax);
        }
    }

    public function importDataimportDataExel($data = '') {
        $data = json_decode($data, TRUE);
        $checkTrxData = $this->db->get_where('transaction', array('noInvoice' => $data[0]))->result();
        if (empty($checkTrxData)) {
            $checkUserData = $this->db->get_where('apiauth_user', array('hp' => $data[3]))->result();
            if (!empty($checkUserData)) {
                $idapiauthuser = $checkUserData[0]->idauthuser;
            } else {
                $datax = array(
                    'firstname' => $data[2],
                    'hp' => $data[3]
                );
                $this->db->insert('apiauth_user', $datax);
                $idapiauthuser = $this->db->insert_id();
            }
            $checkPeopleData = $this->db->get_where('sensus_people', array('phone' => $data[9]))->result();
            if (empty($checkPeopleData)) {
                $datax = array(
                    'idauthuser' => $idapiauthuser,
                    'address' => $data[10] . ' ' . $data[11] . ' ' . $data[12] . ' ' . $data[13],
                    'name' => $data[8],
                    'phone' => $data[9]
                );
                $this->db->insert('sensus_people', $datax);
            }

//            $datax = array(
//                'idauthuser' => $idapiauthuser,
//                'idstore' => 1,
//                'noInvoice' => $data[0],
//                'orderBy' => $data[16],
//                'shipping' => $data[14],
//                'trackingCode' => $data[15],
//                'timeCreate' => date('H:i:s'),
//                'dateCreate' => date('Y-m-d')
//            );
//            $this->db->insert('transaction', $datax);
//            $datax = array(
//                'idtransaction' => $this->db->insert_id(),
//                'skuPditails' => $data[1]
//            );
//            $this->db->insert('transaction_details', $datax);
        } else {
//            $datax = array(
//                'idtransaction' => $checkTrxData[0]->idtransaction,
//                'skuPditails' => $data[1]
//            );
//            $this->db->insert('transaction_details', $datax);
        }
    }

    public function bcWa() {
        $this->db->select('idpeople, name, phone');
        $this->db->where('`idpeople` BETWEEN 536 AND 4895');
        $data = $this->db->get('sensus_people', 1000)->result();
        return $data;
    }

    public function updatePhone($idpeople = '', $phone = '') {
        $this->db->set('phone', $phone);
        $this->db->where('idpeople', $idpeople);
        $this->db->update('sensus_people');
    }

    public function rack($data = '') {
//        echo $data;
//        exit;
        $checkRackData = $this->db->get_where('rack', array('s' => 0))->result();
        if (!empty($checkRackData)) {
            foreach ($checkRackData as $cRg) {
                $this->db->set('s', 1);
                $this->db->where('idrack', $cRg->idrack);
                $this->db->update('rack');
            }
        }
        return $checkRackData;
        exit();
        $checkBigData = $this->db->get_where('product_bigData', array('sprint' => 1), 100)->result();
        if (!empty($checkBigData)) {
            foreach ($checkBigData as $cBg) {
                $this->db->set('sprint', 1);
                $this->db->where('idBigdata', $cBg->idBigdata);
                $this->db->update('product_bigData');
            }
        }
        return $checkBigData;
        exit();
        $checkRackData = $this->db->get_where('rack', array('norack' => $data))->result();
//        return $checkRackData;
//        exit();
        if (empty($checkRackData)) {
//            echo'insert';
            $datax = array(
                'norack' => $data
            );
            $this->db->insert('rack', $datax);
        } else {
            return $checkRackData;
        }
    }

    public function printBarcoce($type = '') {
        if ($type == 1) {
            $checkRackData = $this->db->get_where('rack', array('s' => 0), 1000)->result();
            if (!empty($checkRackData)) {
                foreach ($checkRackData as $cBg) {
                    $this->db->set('s', 1);
                    $this->db->where('idrack', $cBg->idrack);
                    $this->db->update('rack');
                }
            }
            return $checkRackData;
        }elseif ($type == 2) {
            //$db2 = $this->load->database('db2', TRUE);
            $checkRackData = $this->db->query('SELECT * FROM product_bigData WHERE`idNumBig` BETWEEN 101 AND 110')->result();
//            $checkRackData = $this->db->get('product_bigData')->result();
            if (!empty($checkRackData)) {
                foreach ($checkRackData as $cBg) {
                    $this->db->set('sprint', 1);
                    $this->db->where('idNumBig', $cBg->idNumBig);
                    $this->db->update('product_bigData');
                }
            }
            return $checkRackData;
        }
    }
    public function bcWaCashier() {
        $data = $this->db->get('store_cashier')->result();
        return $data;
    }

}
