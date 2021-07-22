<?php

class H2h_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
        $this->load->helper(array('form', 'url'));
    }

    public function verfyAccount($keyCode = '', $secret = '') {
        $data = array(
            "keyCode" => $keyCode,
            "secret" => $secret
        );
        $query = $this->db->get_where('apiauth', $data)->result();
        return $query;
    }

    public function inputJne($data) {
        $dataOrders = $this->db->get_where('sensus', $data)->result();
        if (empty($dataOrders)) {
            echo 'in';
            $this->db->insert('sensus', $data);
        } else {
            echo 'exist';
        }
    }

    public function empty_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field tidak boleh kosong';
        return $response;
    }

    public function field_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Data Tidak Ada';
        return $response;
    }

    public function duplicate_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field Sudah Terdaftar';
        return $response;
    }

    public function token_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Token tidak boleh salah';
        return $response;
    }

    public function store($data = '') {
        //print_r($data);exit;

        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);
        //print_r($dataAccount);exit;


        if (!empty($dataAccount)) {
            $checkDataStore = $this->db->get_where('store', array('idquantum' => $data['idquantum']))->result();
            $data = array(
                'idquantum' => $data['idquantum'],
                'namestore' => $data['namestore'],
                'phonestore' => $data['phonestore'],
                'addrstore' => $data['addrstore'],
                'typeStore' => $data['typeStore']
            );
            if (empty($checkDataStore)) {
                $this->db->insert('store', $data);
                $msg = 'Entry Data Successful';
            } else {
                $this->db->set($data);
                $this->db->where('idquantum', $data['idquantum']);
                $this->db->update('store');
                $msg = 'Update Data Successful';
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['msg'] = $msg;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function product($data = '') {
		
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            $checkDataStore = $this->db->get_where('product_stockStore', array('idstorequantum' => $data['idstorequantum'], 'skuProduct' => $data['skuProduct']))->result();
            //print_r($checkDataStore);exit;
			$data = array(
                'idstorequantum' => $data['idstorequantum'],
                'skuProduct' => $data['skuProduct'],
                'nameProduct' => $data['nameProduct'],
                'collor' => $data['collor'],
                'priceProduct' => $data['priceProduct'],
                'stockStore' => $data['stockStore']
            );
            if (empty($checkDataStore)) {
                $this->db->insert('product_stockStore', $data);
                $msg = 'Entry Data Successful';
            } else {
                $this->db->set($data);
                $this->db->where('idstorequantum', $data['idstorequantum']);
                $this->db->where('skuProduct', $data['skuProduct']);
                $this->db->update('product_stockStore');
                $msg = 'Update Data Successful';
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['msg'] = $msg;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function transaction($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            $this->db->select('idtransaction, timeCreate, dateCreate, orderBy, noInvoice, shipping, shippingprice, trackingCode, subtotal, discount');
            $checkDataStore = $this->db->get_where('transaction', array('readData' => 0, 'statuspay' => 1, 'status' => 1))->result();
            if (!empty($checkDataStore)) {
                foreach ($checkDataStore as $cDS) {
//                    $data[] = $cDS;
                    $this->db->select('skuPditails, collor, size, price, disc, qty, subtotal');
                    $ditailsDataTransaction = $this->db->get_where('transaction_details', array('idtransaction' => $cDS->idtransaction))->result();
                    if (!empty($ditailsDataTransaction)) {
                        foreach ($ditailsDataTransaction as $dDT) {
//                            $dataDitails[] = $dDT;
                            $data[] = array(
                                'transaction' => $cDS,
                                'transactionDetails' => $dDT
                            );
                        }
                    }
                }
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function transactionUpdate($data = '') {
//        print_r($data);
//        exit;
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            $checkDataTransaction = $this->db->get_where('transaction', array('noInvoice' => $data['noInvoice']))->result();
            if (!empty($checkDataTransaction)) {
                $dataUpdate = array(
                    'readData' => 1
                );
                $this->db->set($dataUpdate);
                $this->db->where('noInvoice', $data['noInvoice']);
                $this->db->update('transaction');
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function listData($data = '') {
//        print_r($data);
//        exit;
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);

        if (!empty($dataAccount)) {
            if ($data['page'] == 'store') {
                $this->db->select('idquantum, namestore');
                $data = $this->db->get('store')->result();
            } else {
                $this->db->select('idstorequantum, skuProduct, nameProduct, collor, priceProduct, stockStore, idquantum, namestore');
                $this->db->join('store as b', 'b.idquantum = a.idstorequantum', 'left');
                $data = $this->db->get('product_stockStore as a')->result();
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
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

    public function dataSku($data = '') {
			//print_r($data);exit;
		
        $dataAccount = $this->verfyAccount($data['keyCode'], $data['secret']);
		
        if (!empty($dataAccount)) {
            $checkDataSku = $this->db->get_where('product_sku', array('skuDitails' => $data['skuDitails']))->result();
			//print_r($checkDataSku);exit;
            $dataQuery = array(
                'yearSku' => $data['yearSku'],
                'sku' => $data['sku'],
                'productName' => strtoupper($data['productName']),
                'skuDitails' => $data['skuDitails'],
                'collor' => $data['collor'],
                'size' => $data['size'],
                'price' => $data['price'],
                'weight' => $data['weight']
            );
            if (empty($checkDataSku)) {
                $this->db->insert('product_sku', $dataQuery);
                $msg = 'Sucess full insert data.';
            } else {
                $this->db->set($dataQuery);
                $this->db->where('skuDitails', $data['skuDitails']);
                $this->db->update('product_sku');
                $msg = 'Sucess full update data.';
            }
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = $msg;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function entryDataSensus($param = '') {
        if (!empty($param)) {
            foreach ($param as $pm) {
                $checkData = $this->db->get_where('sensus_province', array('nameProv' => $pm->province))->result();
                $data = array(
                    'nameProv' => $pm->province
                );
                if (empty($checkData)) {
                    $this->db->insert('sensus_province', $data);
                    $msg = 'Entry Data Successful';
                } else {
                    $this->db->set($data);
                    $this->db->where('id_prov', $checkData[0]->id_prov);
                    $this->db->update('sensus_province');
                    $msg = 'Update Data Successful';
                }
            }
        }
    }

    public function dataPrint($param = '') {
        if (!empty($param)) {
            $transactionData = $this->db->get_where('transaction', array('idstore' => $param, 'status' => 1))->result();
//            print_r($transactionData[0]->idtransaction);
//            exit;
            if (!empty($transactionData)) {
                foreach ($transactionData as $tD) {
                    $transactionDataDitails = $this->db->get_where('transaction_details', array('idtransaction' => $tD->idtransaction))->result();
                    $data[] = array(
                        'dataTransaksi' => $tD,
                        'ditailtranskasi' => $transactionDataDitails
                    );
                }
            }
            if ($transactionData) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $data;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function short($url = '') {
        $dataShort = $this->db->get_where('short_url', array('urlname' => $url))->result();
        if ($dataShort) {
            $this->db->set('hitcount', 'hitcount+1', FALSE);
            $this->db->where('idshort', $dataShort[0]->idshort);
            $this->db->update('short_url');

            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $dataShort;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function syncStock($data = '') {

        if (!empty($data)) {
            $pg = $data * 20;
        } else {
            $pg = 20;
        }
        $dataSku = $this->db->get_where('product_ditails', array('delproductditails' => 0), 20, $pg)->result();
        //print_r($dataSku);
        //exit;
        if (!empty($dataSku)) {
            foreach ($dataSku as $dS) {
                //print_r($dS);
                $data = $this->quantum->callAPi($dS->skuPditails, 3);
                //f print_r($data);
                if (!empty($data[0]->s)) {
//                    $du = $this->quantum->callAPi($dS->skuPditails, 3, 'M001-O0001');
//                    if (!empty($du[0]->s)) {
//                        $du = $du[0]->s;
//                    } else {
//                        $du = 0;
//                    }
////                    $kopo = $this->quantum->callAPi($dS->skuPditails, 3,'M001-O0004');
//                    if (!empty($kopo[0]->s)) {
//                        $kopo = $kopo[0]->s;
//                    } else {
//                        $kopo = 0;
//                    }
////                    $nangor = $this->quantum->callAPi($dS->skuPditails, 3,'M001-O0006');
//                    if (!empty($nangor[0]->s)) {
//                        $nangor = $nangor[0]->s;
//                    } else {
//                        $nangor = 0;
//                    }
//                    $cimahi = $this->quantum->callAPi($dS->skuPditails, 3, 'M001-O0029');
//                    if (!empty($cimahi[0]->s)) {
//                        $cimahi = $cimahi[0]->s;
//                    } else {
//                        $cimahi = 0;
//                    }
//                    $bubat = $this->quantum->callAPi($dS->skuPditails, 3, 'M001-O0041');
//                    if (!empty($bubat[0]->s)) {
//                        $bubat = $bubat[0]->s;
//                    } else {
//                        $bubat = 0;
//                    }
//                    $bubat2 = $this->quantum->callAPi($dS->skuPditails, 3, 'M001-O0043');
//                    if (!empty($bubat2[0]->s)) {
//                        $bubat2 = $bubat2[0]->s;
//                    } else {
//                        $bubat2 = 0;
//                    }
//                    $stockAllBandung = $du + $kopo + $nangor + $cimahi + $bubat + $bubat2;
//                    $stockAllBandung = 0;
//                    print_r($stockAllBandung);
//                    exit;
                    $this->db->set('stock', $data[0]->s);
                    $this->db->set('stockRmall', $data[0]->s);
                    $this->db->set('stockAllBandung', 0);
                    $this->db->set('lastUpdate', date('Y-m-d H:i:s'));
                    $this->db->where('skuPditails', $dS->skuPditails);
                    $this->db->update('product_ditails');
                } else {
                    $this->db->set('stock', 0);
                    $this->db->set('delproductditails', 1);
                    $this->db->set('stockAllBandung', 0);
                    $this->db->set('lastUpdate', date('Y-m-d H:i:s'));
                    $this->db->where('skuPditails', $dS->skuPditails);
                    $this->db->update('product_ditails');
                }
                $this->db->set('valuePrice', 'stock*price', FALSE);
                $this->db->set('valuePriceAllBandung', 'stockAllBandung*price', FALSE);
                $this->db->where('skuPditails', $dS->skuPditails);
                $this->db->update('product_ditails');
                $data[] = $data;
            }
        }
//        print_r($data);
        if (!empty($dataSku)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $data;
            $response['message'] = 'Synce stock success.';
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function cron() {

        $this->db->where('stock > 0');
        $this->db->set('delproductditails', 0);
        $product = $this->db->update('product_ditails');
        //exit;
//        $dataOrders = $this->db->get_where('transaction', array('statusPay' => 1, 'trackingCode' => ''), 1)->result();
//        if (!empty($dataOrders)) {
//            foreach ($dataOrders as $dO) {
//                $curl = curl_init();
//
//                curl_setopt_array($curl, array(
//                    CURLOPT_URL => "https://api.rmall.id/artificial/getAwb",
//                    CURLOPT_RETURNTRANSFER => true,
//                    CURLOPT_ENCODING => "",
//                    CURLOPT_MAXREDIRS => 10,
//                    CURLOPT_TIMEOUT => 0,
//                    CURLOPT_FOLLOWLOCATION => true,
//                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                    CURLOPT_CUSTOMREQUEST => "POST",
//                    CURLOPT_POSTFIELDS => array('keyCodeStaff' => 'dd14f7b6bc8c82ef660131c0e0c12c2d', 'secret' => 'da7e2e436c7e761229bad7a7417ef4f255d2905d04da77325f9ecd8bc6acfe50', 'noInvoice' => $dO->noInvoice),
//                ));
//
//                $response = curl_exec($curl);
//
//                curl_close($curl);
//                $data = json_decode($response);
//                print_r($data);
//            }
//        }
//        exit;
//        $this->db->set('realprice', 'price*0.9',FALSE);
//        $this->db->set('priceDiscount','price*0.1',FALSE);
//        $this->db->where('idproduct', '1507');
//        $this->db->where('idproduct', '1505');
//        $this->db->where('idproduct', '130');
//        $this->db->update('product_ditails');
//        
//        $this->db->set('status', 0);
//        $this->db->where('statusPay', 1);
//        $this->db->update('transaction');
//        exit;
        /* CHECK TRANSACTION AUTO CANCLE */
        $dataOrders = $this->db->get_where('transaction', array('statusPay' => 0), 500)->result();
         //print_r ($dataOrders);
       // exit;
        if (!empty($dataOrders)) {
            foreach ($dataOrders as $dO) {
                //print_r ($dO);
                //exit;
                echo $dO->dateCreate . ' ' . $dO->timeCreate . '<br>';
                $awal = date_create($dO->dateCreate . ' ' . $dO->timeCreate);
                $akhir = date_create();
                $diff = date_diff($awal, $akhir);

                //$now = date('Y-m-d');
                //$old = $dO->dateCreate;
                //$diff2 = $now->diff($old);
                ///print_r($diff->d);
                //exit;
                // }  
                if ($diff->d >= 1) {
                    $this->db->set('statusPay', 2);
                    $this->db->where('idtransaction', $dO->idtransaction);
                    $this->db->update('transaction');
                }

                //if ($diff->d = 0 && $diff->h = 3) {
                //$massage = 'rmall.id : Assalamualaikum Rabbaners belanja anda belum di tranfers lho no invoice' . $dataOrders[0]->noInvoice . 'Batas Pembayaran 1x24 Jam, Jazakallah';
                //$this->sms->SendSms($hp, $massage);
                // }  
            }
        }
        /* END CHECK */
        /* TRACKING POSITION */
        $where = array(
            'statusPay' => 1
        );
        $this->db->where('trackingCode!=""');
        $this->db->where('status!=2');
        $this->db->order_by('rand()');
        $dataTracking = $this->db->get_where('transaction', $where, 25)->result();
        if (!empty($dataTracking)) {
            foreach ($dataTracking as $dT) {
                echo $dT->trackingCode . '<br>';
                $data = $this->courir->jne(2, $dT->trackingCode);
                $data = json_decode($data);
                //print_r($data);
				//exit;
                if ((!empty($data->cnote->pod_status)) && ($data->cnote->pod_status == 'DELIVERED')) {
                    $this->db->set('status', 2);
                    $this->db->where('idtransaction', $dT->idtransaction);
                    $this->db->update('transaction');
                } elseif ((!empty($data->cnote->pod_status)) && ($data->cnote->pod_status == 'ON PROCESS')) {
                    $this->db->set('status', 1);
                    $this->db->where('idtransaction', $dT->idtransaction);
                    $this->db->update('transaction');
                }
            }
        }
        /* END TRACKING */
        /* SYNCE DATA STOCK QUANTUM */
		//$skuPditails = 'KAN0HA09141A200' ;
		 //echo $skuPditails;
           //     exit;
	//	echo $dS->skuPditails . '<br>';
		
		//$sync = $this->quantum->callAPi($skuPditails, 2);
		//print_r($sync);
          //      exit;
		
        $last_min = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $this->db->order_by('rand()');
        $dataSync = $this->db->get_where('product_ditails', array('delproductditails' => 0, 'lastUpdate<' => $last_min),50)->result();
			//print_r($dataSync);
              //  exit;
//        if (!empty($dataSync)) {
//            foreach ($dataSync as $dS) {
//                echo $dS->skuPditails . '<br>';
//                $sync = $this->quantum->callAPi($dS->skuPditails, 2);
//                //print_r($sync);
//               // exit;
//                //$this->db->set('stock', $sync->ts);
//                $this->db->set('stockRmall', $sync->ts);
//                $this->db->set('valuePrice', $dS->price * $sync->ts);
//                $this->db->set('lastUpdate', date('Y-m-d H:i:s'));
//                // //if (empty($sync->ts)) {
//                //  //  $this->db->set('delproductditails', 1);
//                //  //}
//                $this->db->set('stockAllBandung', 0);
//                $this->db->set('valuePriceAllBandung', 0);
//                $this->db->set('stockAllJabar', 0);
//                $this->db->set('valuePriceAllJabar', 0);
//                $this->db->where('skuPditails', $dS->skuPditails);
//                $this->db->update('product_ditails');
//				 // print_r($sync);
//                //exit;
//            }
//        }
        /* END SYNCE STOCK */
        /* ADD NEW PRODUCT BY QUANTUM */
       // exit;
		//$newProduct = $this->db->get_where('product_sku', array('ssku' => 0), 25)->result();
        $newProduct = $this->db->get_where('product_sku', array('ssku' => 0), 25)->result();
		//print_r($newProduct);
        if (!empty($newProduct)) {
            foreach ($newProduct as $nP) {
//                print_r($nP);
                $dataQuantum = $this->quantum->callAPi($nP->skuDitails, 3);
              
                if (!empty($dataQuantum)) {
                    $checkProduct = $this->db->get_where('product', array('skuProduct' => $nP->sku))->result();
                    $insert_id = $checkProduct[0]->idproduct;
                    if (empty($checkProduct)) {
                        $data = array(
                            'skuProduct' => $nP->sku,
                            'timeCreate' => date('H:i:s'),
                            'dateCreate' => date('Y-m-d'),
                            'productName' => $nP->productName,
                            'delproduct' => 1
                        );

                        $this->db->insert('product', $data);
                        $insert_id = $this->db->insert_id();
                    }
                    $checkProductDetails = $this->db->get_where('product_ditails', array('skuPditails' => $nP->skuDitails))->result();
                    if (!empty($checkProductDetails)) {
                        $data = array(
//                            'idproduct' => $insert_id,
//                            'skuPditails' => $nP->skuDitails,
//                            'collor' => $nP->collor,
//                            'size' => $nP->size,
//                            'price' => $nP->price,
//                            'realprice' => $nP->price,
                            'stock' => $dataQuantum[0]->s
                        );
                        $this->db->set($data);
                        $this->db->where('skuPditails', $nP->skuDitails);
                        $this->db->update('product_ditails');
                    } else {
                        $data = array(
                            'idproduct' => $insert_id,
                            'skuPditails' => $nP->skuDitails,
                            'collor' => $nP->collor,
                            'size' => $nP->size,
                            'price' => $nP->price,
                            'realprice' => $nP->price,
                            'stock' => $dataQuantum[0]->s
                        );

                        $this->db->insert('product_ditails', $data);
                    }
                }
                $this->db->set('ssku', 1);
                $this->db->where('skuDitails', $nP->skuDitails);
                $this->db->update('product_sku');
            }
        } else {
            $this->db->set('ssku', 0);
            $this->db->update('product_sku');
        }
        /* END NEW PRODUCT BY QUANTUM */
    }

    public function po($data = '') {
        //print_r($data);
// exit;
        $data1 = json_decode($data[0]);

        //print_r($data1);
        //exit;
        $datapo = array(
            'name' => $data1[0]->name,
            'datepo' => date('Y-m-d'),
            'handphone' => $data1[0]->handphone,
            // 'email' => $data1[0]->email,
            'productname' => $data1[0]->product,
                // 'collor' => $data1[0]->collor,
                // 'size' => $data1[0]->size,
                // 'quantity' => $data1[0]->qty
        );
        //print_r($datapo);
        //exit;



        $query = $this->db->insert('order_po', $datapo);


        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            // $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function posub($data = '') {
        //print_r($data);
// exit;
        $data1 = json_decode($data[0]);

        //print_r($data1);
        //exit;
        $datapo = array(
            'name' => $data1[0]->name,
            'handphone' => $data1[0]->handphone,
            'datepo' => date('Y-m-d')
        );




        $query = $this->db->insert('order_po', $datapo);


        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            // $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function cod() {

        $this->db->select('*');
        $this->db->from('sensus_cod');


        $query = $this->db->get()->result();


        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            //$response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function category() {


        $this->db->select('a.idcategory,a.parentidcategory,a.categoryName,b.urlImage');
        $this->db->where('delcat', '0');
        $this->db->join('category_images as b', 'b.idcategory = a.idcategory', 'left');
        $this->db->order_by('categoryName ASC');
        $dataCat = $this->db->get_where('category as a', array('a.parentidcategory' => 0))->result();
        // print_r($dataCat);
        // exit;
        foreach ($dataCat as $dC) {
            // print_r($dC);
            // exit;
            $this->db->order_by('categoryName ASC');
            $dataSubCat = $this->db->get_where('category', array('parentidcategory' => $dC->idcategory, 'delcat' => 0))->result();
            // print_r($dataSubCat);
            // exit;
            $dataCatx[] = array(
                'idcategory' => $dC->idcategory,
                'categoryName' => $dC->categoryName,
                'imagecategory' => $dC->urlImage,
                'dataSubCat' => $dataSubCat
            );
        }
        $supdate = $dataCatx;
        $this->db->select('a.idcategory,a.parentidcategory,a.categoryName,b.urlImage');
        $this->db->join('category_images_icon as b', 'b.idcategory = a.idcategory', 'left');
        $data1 = $this->db->get_where('category as a')->result();

        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCat);
            $response['data'] = $dataCatx;
            $response['icon'] = $data1;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function getDataByCat($data = '') {
        $this->db->select('a.idproduct,a.idcategory,a.skuProduct,a.productName,a.descr,b.idpditails,b.skuPditails,b.collor,b.weight,b.size,b.stock,b.price,b.realprice,c.urlImage');
        $this->db->where('a.delproduct', 0);
        $this->db->where('b.delproductditails', 0);
        $this->db->where('b.stock>0');
        $this->db->where('delproduct', 0);
        $this->db->where('a.idcategory', $data[0]);

        $this->db->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct', 'left');
        //$this->db->join('product_images_ditails as d', 'd.idpditails = b.idproduct','left');
        $this->db->group_by('a.idproduct');
        $this->db->order_by('a.idproduct', 'DESC');
        $datax = $this->db->get_where('product as a')->result();

        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function realproduct($data = '') {

        // print_r($data);
        //exit;
        $this->db->select('a.idproduct,a.idcategory,a.skuProduct,a.productName,a.descr,b.urlImage');
        $this->db->where('a.delproduct', 0);
        // $this->db->where('a.stock>0');

        $this->db->where('a.idproduct', $data[0]);

        $this->db->join('product_images as b', 'b.idproduct = a.idproduct');
        // $this->db->group_by('a.idpditails');
        //$this->db->order_by('a.idproduct', 'DESC');
        $datax = $this->db->get_where('product as a')->result();


        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function productditails($data = '') {

        //print_r($data);
        //exit;
        $this->db->select('a.idpditails,a.skuPditails,a.collor,a.weight,a.size,a.stock,a.price,a.realprice,b.urlImage');
        $this->db->where('a.delproductditails', 0);
        $this->db->where('a.stock>0');

        $this->db->where('a.idproduct', $data[0]);

        $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
        $this->db->group_by('a.idpditails');
        //$this->db->order_by('a.idproduct', 'DESC');
        $datax = $this->db->get_where('product_ditails as a')->result();


        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function productditailssku($data = '') {



        $this->db->select('a.idproduct,a.idcategory,a.skuProduct,a.productName,a.descr,b.idpditails,b.skuPditails,b.collor,b.weight,b.size,b.stock,b.price,b.realprice,d.urlImage');
        //$this->db->where('a.delproduct', 0);
        //$this->db->where('b.delproductditails', 0);
        //$this->db->where('b.stock>0');
        $this->db->where('b.skuPditails', strtoupper($data[0]));

        $this->db->join('product_images_ditails as d', 'd.idproduct = a.idproduct', 'left');
        //$this->db->join('product_images as c', 'c.idproduct = a.idproduct','left');
        $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct', 'left');
        //$this->db->join('product_images_ditails as d', 'd.idpditails = b.idproduct','left');
        $this->db->group_by('a.idproduct');
        //$this->db->order_by('a.idproduct', 'RANDOM');
        $datax = $this->db->get_where('product as a')->result();

        // print_r($data);
        //exit;
        //$this->db->select('a.idpditails,a.skuPditails,a.collor,a.weight,a.size,a.stock,a.price,b.urlImage');
        //$this->db->where('a.delproductditails', 0);
        //$this->db->where('a.stock>0');
        //$this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
        // $this->db->group_by('a.idpditails');
        //$this->db->order_by('a.idproduct', 'DESC');
        // $datax= $this->db->get_where('product_ditails as a')->result();


        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function getmember($data = '') {

        $this->db->select('*');
        $datax = $this->db->get_where('member')->result();



        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count(datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function addmember($data = '') {


        $datax = json_decode($data[0]);
        //print_r($datax);
        //exit;
        $sql = $this->db->query("SELECT wa FROM member where wa ='$datax->wa'");
        $cek_id = $sql->num_rows();

        $this->db->select('RIGHT(member.kodemember,4) as kode', FALSE);
        $this->db->order_by('kodemember', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get('member');
        //print_r($query);
        // exit;		  //cek dulu apakah ada sudah ada kode di tabel.    
        if ($query->num_rows() <> 0) {
            //jika kode ternyata sudah ada.      
            $data = $query->row();
            $kode = intval($data->kode) + 1;
        } else {
            //jika kode belum ada      
            $kode = 1;
        }

        $kodemax = str_pad($kode, 4, "0", STR_PAD_LEFT); // angka 4 menunjukkan jumlah digit angka 0
        $kodejadi = "FM" . $kodemax;    // hasilnya ODJ-9921-0001 dst.


        if ($cek_id > 0) {

            return $this->duplicate_response();
        } else {

            $datay = array(
                'date' => date('Y-m-d'),
                'nama' => $datax->nama,
                'wa' => $datax->wa,
                'email' => $datax->email,
                'kodemember' => $kodejadi,
                'address' => $datax->alamat,
                'province' => $datax->propinsi,
                'city' => $datax->kota,
                'district' => $datax->kecamatan,
                //'village' => $datax->kecamatan,
                'rt' => $datax->rt,
                'rw' => $datax->rw,
                'kodepos' => $datax->kodepos
            );


            $updatey = $this->db->insert('member', $datay);


            $message = 'RABBANI : Tunjukan Kode Aktivasi  ' . $datay[kodemember] . ', di Outlet Rabbani, Dapatkan Member Card & Diskon 10%-15% All Item, Jazakallah Katsiron';
            // $message1 = 'order ' . $people[0]->name . ' ';
            // $this->load->library('wa');

            $this->sms->SendSms($datax->wa, $message);
            //$this->wa->SendWa($datax->wa, $message);
        }


        if (!empty($datay)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count(datay);
            $response['data'] = $datay;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function autoview($data = '') {



        $this->db->where('stock > 0');
        $this->db->set('delproductditails', 0);
        $product = $this->db->update('product_ditails');




        if (!empty($product)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($product);
            $response['data'] = $product;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function autodraft($data = '') {

        $id = $this->db->get_where('product')->result();
        foreach ($id[0]->idproduct as $x) {

            $this->db->where('stock > 0');
            //$this->db->where('a.delproduct', 0);
            $this->db->where('delproductditails', 0);
            $this->db->where('b.urlImage!=""');
            $this->db->where('a.idproduct', $x);
            $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
            //$this->db->join('product_images as b', 'b.idproduct = a.idproduct');
            $this->db->group_by('a.idpditails');
            $product = $this->db->get_where('product_ditails as a')->result();
        }

        if (empty($product)) {

            $response['data'] = $x;
        }





        if (!empty($product)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($product);
            $response['data'] = $product;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            $response['data'] = $id;
            return $response;
        }
    }

    public function insertposview($data = '') {
        //print_r($data[0]);exit;
        $dataAccount = $this->verfyAccount($data[0], $data[1]);

        if (!empty($dataAccount)) {
            $this->db->select('idall_order,no_order,source_order,user_name,HPJ,total_order as HNJ,total_voucher,id_quantum,rekening,kasir,detail_order');
            $this->db->order_by('cek_date', 'ASC');
            //$this->db->where('cek_date', $data[2]);
            //$this->db->where('cek_time <=',$data[3]);
            $this->db->where('status', 1);
            $dataCat = $this->db->get_where('all_order')->result();

            //print_r($dataCat[0]->source_order);
            //exit;
        } else {
            return $this->token_response();
        }
        if ($dataCat) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['count'] = count($dataCat);
            $response['data'] = $dataCat;
            //$response['data1'] = $a;
            return $response;
        } else {//
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function updateposview($data = '') {
        //print_r($data);exit;
        $dataAccount = $this->verfyAccount($data[0], $data[1]);
        $sql = $this->db->query("SELECT id_quantum FROM all_order where id_quantum='$data[2]'");
        $cek_id = $sql->num_rows();
        //print_r($cek_id);
        //exit;
        if (!empty($dataAccount)) {
			//print_r($dataAccount);exit;
            if ($cek_id > 0) {
                $this->db->set('status', 2);
				//$a = '2020-12-22';
                $this->db->where('id_quantum', $data[2]);
                //$this->db->where('cek_date <', '2020-12-22');
                $dataCat = $this->db->update('all_order');
            } else {
                return $this->field_response();
            }
		   //print_r($dataCat);exit;
        } else {
            return $this->token_response();
        }
        if (!empty($dataCat)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['msg'] = 'Berhasil Update';
            $response['data'] = $dataCat;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function getDataProduct() {
        $this->db->select('a.idproduct, b.idpditails, a.skuProduct, a.productName, a.descrDitails, b.skuPditails, b.collor, b.size, b.price');
        $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct');
        $query = $this->db->get_where('product as a', array('b.statusSend' => 0),100)->result();
        if (!empty($query)) {
            foreach ($query as $q) {
//                print_r($q);
                $data = array(
                    'sku' => $q->skuProduct,
                    'product_name' => $q->productName,
                    'desc' => $q->descrDitails,
                    'sku_code' => $q->skuPditails,
                    'variable' => '{"COLOR":"' . $q->collor . '","SIZE":"' . $q->size . '"}',
                    'price' => $q->price
                );
                $respound = $this->sim->addUpdate($data);
                
                $this->db->set('statusSend', 1);
                $this->db->where('idpditails', $q->idpditails);
                $this->db->update('product_ditails');
                
                $return[] = array(
                    $q, 'respound' => $respound
                );
            }
        }
        return $return;
    }
	
	
	 public function productact($data = '') {
		 $dataAccount = $this->verfyAccount($data[0], $data[1]);
		 
		 if (!empty($dataAccount)){
        $this->db->select('a.idproduct,a.idcategory,a.skuProduct,a.productName,a.descr,b.idpditails,b.skuPditails,b.collor,b.weight,b.size,b.stock,b.price,c.urlImage');
        $this->db->where('a.delproduct', 0);
        $this->db->where('b.delproductditails', 0);
        $this->db->where('b.stock>5');
        //$this->db->where('delproduct', 0);
       // $this->db->where('a.idcategory', $data[0]);

        $this->db->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct', 'left');
        //$this->db->join('product_images_ditails as d', 'd.idpditails = b.idproduct','left');
        $this->db->group_by('a.idproduct');
        $this->db->order_by('a.idproduct', 'DESC');
        $datax = $this->db->get_where('product as a')->result();
		 } else {
			 return $this->token_response();
		 }

        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	 public function productcatact($data = '') {
		 $dataAccount = $this->verfyAccount($data[0], $data[1]);
		 
		 if (!empty($dataAccount)){
        $this->db->select('a.idproduct,a.idcategory,a.skuProduct,a.productName,a.descr,b.idpditails,b.skuPditails,b.collor,b.weight,b.size,b.stock,b.price,c.urlImage');
        $this->db->where('a.delproduct', 0);
        $this->db->where('b.delproductditails', 0);
        $this->db->where('b.stock>5');
        //$this->db->where('delproduct', 0);
        $this->db->where('a.idcategory', $data[2]);

        $this->db->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct', 'left');
        //$this->db->join('product_images_ditails as d', 'd.idpditails = b.idproduct','left');
        $this->db->group_by('a.idproduct');
        $this->db->order_by('a.idproduct', 'DESC');
        $datax = $this->db->get_where('product as a')->result();
		 } else {
			 return $this->token_response();
		 }
		 $this->db->select('idcategory,categoryName');
		 $cat = $this->db->get_where('category')->result();

        if (!empty($cat)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
			$response['idcat'] = $cat;
            $response['databycat'] = $datax;
			
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	public function productditailsact($data = '') {


        //print_r($data);
        //exit;
        $this->db->select('a.idpditails,a.skuPditails,a.collor,a.weight,a.size,a.stock,a.price,a.realprice,b.urlImage');
        $this->db->where('a.delproductditails', 0);
        $this->db->where('a.stock>5');

        $this->db->where('a.idproduct', $data[2]);

        $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
        $this->db->group_by('a.idpditails');
        //$this->db->order_by('a.idproduct', 'DESC');
        $datax = $this->db->get_where('product_ditails as a')->result();


        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	public function tailor($data = '') {
	//print_r($data[0]);exit;	  
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $datax = json_decode($data[0]);
			$cek = $this->db->get_where('tailor', array('email' => $datax->email))->result();
			//print_r($cek);exit;	
			if (!empty($cek)) {
				return $this->duplicate_response();
				} else {
					$datay = array(
						'date' =>  date('Y-m-d'),
						'time' => date('H:i:s'),
                        'name' => $datax->nama,
						'email' => $datax->email
                    );
					$supdate = $this->db->insert('tailor', $datay);
				}
			

            if ($datay) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($datay);
                $response['data'] = $datay;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function viewtailor($data = '') {
    //print_r($data[0]);exit;     
       
			$this->db->order_by('idtailor', 'DESC');
            $datay = $this->db->get_where('tailor')->result();
            if ($datay) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($datay);
                $response['data'] = $datay;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        
    }
	
	public function rumahjahit($data = '') {
	//print_r($data[0]);exit;	   
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $datax = json_decode($data[0]);
			$cek = $this->db->get_where('rumahjahit', array('wa' => $datax->wa))->result();
			//print_r($cek);exit;	
			if (!empty($cek)) {
				return $this->duplicate_response();
				} else {
					$datay = array(
						'date' =>  date('Y-m-d'),
						'time' => date('H:i:s'),
                        'name' => $datax->nama,
						'email' => $datax->email,
						'wa' => $datax->wa
                    );
					$supdate = $this->db->insert('rumahjahit', $datay);
				}
			

            if ($datay) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($datay);
                $response['data'] = $datay;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	
     public function inputstore($data = '') {
    // print_r($data[0]);exit;      
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $datax = json_decode($data[0]);
           $this->db->limit('1');
            $this->db->like('nameProv',$datax->id_prov);
            $cekprov = $this->db->get_where('sensus_province')->result();
            print_r($cekprov);exit;
            if (empty($cekprov)) {
                $cekprov[0]->id_prov = 0;
            }
            $this->db->limit('1');
            $this->db->like('nameCity',$datax->id_city);
            $cekkota = $this->db->get_where('sensus_city')->result();
            // print_r($cekkota);exit;
            if (empty($cekkota)) {
                $cekkota[0]->id_city = 0;
            }

             $cek = $this->db->get_where('store', array('namestore' => $datax->nama))->result(); 
            if (!empty($cek)) {
                
                $dataz = array(
                        'namestore' => $datax->nama,
                        'addrstore' => $datax->alamat,
                        'phonestore' => $datax->tlp,
                        'wa' => $datax->wa,
                        'id_prov' => $datax->id_prov,
                        'id_city' => $datax->id_city
                    );
                               $this->db->where('namestore', $datax->nama);
                    $supdate = $this->db->update('store', $dataz);
                } else {
                    $datay = array(
                        'namestore' => $datax->nama,
                        'addrstore' => $datax->alamat,
                        'phonestore' => $datax->tlp,
                        'wa' => $datax->wa,
                        'id_prov' => $datax->id_prov,
                        'id_city' => $datax->id_city
                    );
                    $supdate = $this->db->insert('store', $datay);
                }
            

            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($datax);
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

	
	
	


}
