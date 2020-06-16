<?php

class Fulfillment_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
        $this->load->helper(array('form', 'url'));
    }

    private function verfyAccount($keyCode = '', $secret = '') {
        $data = array(
            "keyCodeStaff" => $keyCode,
            "secret" => $secret
        );
        $this->db->select('c.namestore, a.*');
        $this->db->Join('store as c', 'c.idstore = a.idstore', 'left');
        $query = $this->db->get_where('apiauth_staff as a', $data)->result();
        return $query;
    }

    private function skuRack($sku = '', $rack = '', $type = '') {
        $checkSku = $this->db->get_where('product_skurack', array('rack' => $rack, 'sku' => $sku))->result();
        if ($type == 1) {
            if (!empty($checkSku)) {
                $this->db->set('stock', 'stock+1', FALSE);
                $this->db->where('rack', $rack);
                $this->db->where('sku', $sku);
                $this->db->update('product_skurack');
            } else {
                $data = array(
                    'skurack' => $sku . $rack,
                    'rack' => $rack,
                    'sku' => $sku,
                    'stock' => '1'
                );

                $this->db->insert('product_skurack', $data);
            }
        } else {
            if (!empty($checkSku)) {
                $this->db->set('stock', 'stock-1', FALSE);
                $this->db->where('rack', $rack);
                $this->db->where('sku', $sku);
                $this->db->update('product_skurack');
            }
        }
        return $checkSku;
    }

    public function check() {
        $this->db->select('count(*) as ttl, a.trackingCode, b.name, b.phone, b.address');
        $this->db->where('a.trackingCode!=""');
        $this->db->group_by("a.trackingCode");
        $this->db->order_by('ttl', 'DESC');
        $this->db->join('sensus_people as b', 'b.idpeople = a.idpeople', 'left');
        $query = $this->db->get_where('transaction as a', array('a.statusPay' => 1), 15)->result();
        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function updateProcess($data = '') {
        $query = $this->db->get_where('transaction', array('noInvoice' => $data, 'statusPay' => 1, 'status' => 0))->result();
        if (!empty($query)) {
            foreach ($query as $q) {
                
            }
        }
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

    public function stock($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCodeStaff'], $data['secret']);

        if (!empty($dataAccount)) {
            $checkSku = $this->db->get_where('product_sku', array('skuDitails' => $data['sku']))->result();
            if (!empty($checkSku)) {
                if ($data['type'] == 1) {
                    $this->db->set('physical', 'physical+1', FALSE);
                    $this->db->where('skuDitails', $data['sku']);
                    $this->db->update('product_sku');
                } elseif ($data['type'] == 2) {
                    $this->db->set('physical', 'physical-1', FALSE);
                    $this->db->where('skuDitails', $data['sku']);
                    $this->db->update('product_sku');
                }
                $this->db->select('idSkuProd, yearSku, productName, sku, skuDitails, collor, size, price, physical as physicalStock');
                $checkSku = $this->db->get_where('product_sku', array('skuDitails' => $data['sku']))->result();
                $data = $checkSku;
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

    public function debitStock($data = '') {
        $this->db->select('a.noInvoice, a.timeOrder, a.noAwb, a.skuBarcode, a.shipping, b.product, a.qty, a.name, a.phone');
        $this->db->join('fulfil_stock as b', 'b.skuBarcode = a.skuBarcode');
        $checkSku = $this->db->get_where('fulfil_trx as a', array('a.noAwb' => $data))->result();
//        print_r($checkSku);
//        exit;
        $status0 = $this->db->get_where('fulfil_trx', array('noAwb' => $data, 'status' => 0))->result();
        if (count($checkSku) == count($status0)) {
//            echo 'xxx';
            foreach ($checkSku as $cS) {
                $status0 = $this->db->get_where('fulfil_stock', array('skuBarcode' => $cS->skuBarcode, 'stock>=' => 1))->result();
                if (empty($status0)) {
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data failed to receive or data empty.';
                    return $response;
                    exit;
                }
            }
            foreach ($checkSku as $cS) {
//                print_r($cS);
//                exit;
                $this->db->set('stock', 'stock-' . $cS->qty, FALSE);
                $this->db->where('skuBarcode', $cS->skuBarcode);
                $this->db->update('fulfil_stock');
            }

            $this->db->set('status', 1);
            $this->db->where('noAwb', $data);
            $this->db->update('fulfil_trx');

            $checkSku = $checkSku;
        } else {
            $checkSku = 0;
        }

        if ($checkSku) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $checkSku;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function importData2($data = '') {
        $checkSku = $this->db->get_where('fulfil_stock', array('skuBarcode' => $data[0]))->result();
        if (!empty($checkSku)) {
            $this->db->set('stock', $data[3]);
            $this->db->set('product', $data[1] . ' ' . $data[2]);
            $this->db->where('skuBarcode', $data[0]);
            $this->db->update('fulfil_stock');
        } else {

            $datax = array(
                'skuBarcode' => $data[0],
                'stock' => $data[3],
                'product' => $data[1] . ' ' . $data[2]
            );

            $this->db->insert('fulfil_stock', $datax);
        }
        $this->db->set('stock', 0);
        $this->db->where('stock', '#N/A');
        $this->db->update('fulfil_stock');
    }

    public function updateEmpty($data = '') {
        $this->db->set('status', 99);
        $this->db->where('idfulfiltrx', $data);
        $this->db->update('fulfil_trx');
    }

    public function importData3($data = '', $timeOrder = '') {
        $checkSku = $this->db->get_where('fulfil_trx', array('noInvoice' => $data[0]))->result();

//        if (!empty($checkSku)) {
//            $this->db->set('noAwb', $data[1]);
//            $this->db->where('noInvoice', $data[0]);
//            $this->db->update('fulfil_trx');
//        } else {
        $datax = array(
            'noInvoice' => $data[0],
            'timeOrder' => $timeOrder,
            'noAwb' => $data[1],
            'skuBarcode' => $data[4],
            'qty' => $data[5],
            'mp' => $data[3],
            'shipping' => $data[2],
            'name' => $data[6],
            'phone' => $data[7],
            'address' => $data[8]
        );

        $this->db->insert('fulfil_trx', $datax);
//        }
    }

    public function sync() {
        $this->db->select('noInvoice, noAwb, shipping, name, phone, address');
        $this->db->group_by("noInvoice");
        $this->db->order_by('rand()');
        $this->db->where('noAwb=""');
        $sync = $this->db->get('fulfil_trx', 500)->result();
        print_r($sync);
//        exit;
        if (!empty($sync)) {
            foreach ($sync as $s) {
                $this->db->select('noInvoice, noAwb, shipping, name, phone, address');
                $this->db->group_by("noInvoice");
                $update = $this->db->get_where('fulfil_trx', array('noInvoice' => $s->noInvoice))->result();
//                print_r($update);
//                exit;
                $this->db->set('noAwb', $update[0]->noAwb);
                $this->db->set('shipping', $update[0]->shipping);
                $this->db->set('name', $update[0]->name);
                $this->db->set('phone', $update[0]->phone);
                $this->db->set('address', $update[0]->address);
                $this->db->where('noInvoice', $update[0]->noInvoice);
                $this->db->update('fulfil_trx');
            }
        }
    }

    public function handOverDownload() {
        $this->load->dbutil();
        $this->load->helper('file');
        $this->load->helper('download');
        $query = $this->db->get_where('fulfil_trx', array('status' => 0, 'out' => 0));
        $delimiter = ",";
        $newline = "\r\n";
        $data = $this->dbutil->csv_from_result($query, $delimiter, $newline);

        $updateStatus = $this->db->get_where('fulfil_trx', array('status' => 0, 'out' => 0))->result();
        if (!empty($updateStatus)) {
            foreach ($updateStatus as $uS) {
                $this->db->set('out', 2);
                $this->db->where('noAwb', $uS->noAwb);
                $this->db->update('fulfil_trx');
            }
        }

        force_download('CSV_Report.csv', $data);
    }

    public function handOver($data = '') {
        $handOver = $this->db->get_where('fulfil_trx', array('noAwb' => $data, 'status' => 1))->result();
        $status0 = $this->db->get_where('fulfil_trx', array('noAwb' => $data, 'out' => 0))->result();
        if (count($handOver) == count($status0)) {
            $this->db->set('out', 1);
            $this->db->where('noAwb', $data);
            $this->db->update('fulfil_trx');
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
            exit;
        }

        if ($status0) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $status0;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function sendTransaction($awb = '') {
        $this->db->join('fulfil_trx as b', 'b.noAwb = a.awb', 'left');
        $data = $this->db->get_where('fulfil_send as a', array('a.awb' => $awb))->result();
        $datax = $data;
        if (empty($data)) {
            $data = array(
                'awb' => $awb,
                'timeSend' => date('Y-m-d H:i:s')
            );

            $this->db->insert('fulfil_send', $data);

            $this->db->join('fulfil_trx as b', 'b.noAwb = a.awb', 'left');
            $datax = $this->db->get_where('fulfil_send as a', array('a.awb' => $awb))->result();
            $data = 1;
        }

        if ($data) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function trx($data = '') {
        $trx = $this->db->get_where('fulfil_trx', array('noAwb' => $data))->result();
        if (!empty($trx)) {
            $this->db->set('status', 1);
            $this->db->set('out', 2);
            $this->db->where('noAwb', $data);
            $this->db->update('fulfil_trx');
        }

        if ($trx) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $trx;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function restore($data = '') {
        $restore = $this->db->get_where('fulfil_trx', array('noAwb' => $data, 'status' => 1))->result();
//        print_r($restore);
//        exit;
        if (!empty($restore)) {
            foreach ($restore as $r) {
                $this->db->set('status', 0);
                $this->db->set('out', 0);
                $this->db->where('noAwb', $r->noAwb);
                $this->db->update('fulfil_trx');

                $this->db->set('stock', 'stock+' . $r->qty, FALSE);
                $this->db->where('skuBarcode', $r->skuBarcode);
                $this->db->update('fulfil_stock');
            }
        }

        if ($restore) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $restore;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function notifDownload($data = '') {
        $dataStatistic = 0;
        if ($data == 1) {
            $this->db->select('COUNT(*) AS ttlData');
//            $this->db->group_by("noInvoice");
            $dataStatistic = $this->db->get_where('fulfil_trx', array('status' => 0, 'out' => 0))->result();
        } elseif ($data == 2) {
            $this->db->select('COUNT(*) AS ttlData');
//            $this->db->group_by("noInvoice");
            $dataStatistic = $this->db->get_where('fulfil_trx', array('status' => 1, 'out' => 0))->result();
        } elseif ($data == 3) {
            $this->db->select('COUNT(*) AS ttlData');
//            $this->db->group_by("noInvoice");
            $dataStatistic = $this->db->get_where('fulfil_trx', array('status' => 1, 'out' => 1))->result();
        } elseif ($data == 4) {
            $this->db->select('COUNT(*) AS ttlData');
//            $this->db->group_by("noInvoice");
            $dataStatistic = $this->db->get_where('fulfil_trx', array('status' => 1, 'out' => 2))->result();
        }

        if ($dataStatistic) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $dataStatistic;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function checkTransaction($data = '') {
        $this->db->select('a.idfulfiltrx, a.noInvoice, a.timeOrder, a.noAwb, a.skuBarcode, a.shipping, b.product, a.qty, a.name, a.phone, a.status');
        $this->db->join('fulfil_stock as b', 'b.skuBarcode = a.skuBarcode', 'left');
        $this->db->order_by('idfulfiltrx', 'DESC');
        $data = $this->db->get_where('fulfil_trx as a', array('a.noAwb' => $data))->result();

        if ($data) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $data;
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
        } elseif ($type == 2) {
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
//        $data = $this->db->get('store_cashier')->result();
        $data = $this->db->query('SELECT * FROM store_cashier WHERE`idcashier` BETWEEN 561 AND 600')->result();
        return $data;
    }

}
