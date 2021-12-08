<?php

class Artificial_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
        $this->load->helper(array('form', 'url'));
    }

    public function verfyAccount($keyCode = '') {
        $data = array(
            "keyCode" => $keyCode
        );
        $query = $this->db->get_where('apiauth_user', $data)->result();
        return $query[0];
    }

    public function getCode($idCity = '') {
		
        $data = array(
            "CITY_CODE" => $idCity
        );
		
        $query = $this->db->get_where('sensus', $data)->result();
		 //print_r($query);exit
        if (!empty($query)) {
            $query = $query[0];
        } else {
            $query = 0;
        }
        return $query;
    }

    public function getPrintData($type = '') {
        if ($type == 1) {
            $data = array(
                "status" => 0,
                "statusPay" => 1,
                
            );
            $query = $this->db->get_where('transaction', $data, 100)->result();
			//print_r($query);
			//exit;
            return $query;
        } else {
            $data = array(
                "a.status" => 0,
                "a.statusPay" => 1,
            );
            $this->db->select(
                    'a.idtransaction, a.noInvoice, a.status, a.statusPay, a.trackingCode, a.dateCreate,
                b.idpeople, b.id_city, b.address, b.pos, b.name, b.phone,
                c.PROVINCE_NAME as province_name, c.CITY_NAME as nameCity, c.ZIP_CODE as postcode, c.CITY_CODE as JNEcode'
            );
            $this->db->where('a.trackingCode!=""');
            $this->db->where('a.dateCreate >=','2021-12-01');
            $this->db->where('a.status', 0);
            $this->db->where('a.statusPay = 1 OR a.statusPay = 4');
            
            $this->db->join('sensus_people as b', 'b.idpeople = a.idpeople', 'left');
            $this->db->join('sensus as c', 'c.CITY_ID = b.id_city', 'left');
            $this->db->group_by('a.idtransaction');
            $this->db->order_by('a.idtransaction','ASC');
            
            // $this->db->or_where('a.statusPay', 4);
            $query = $this->db->get_where('transaction as a')->result();
             //print_r($query);
             //exit;
            $dataTrx = 0; 
            if (!empty($query)) {
                $dataTrx = array();
                foreach ($query as $qr) {
                    // print_r($qr);
                    $datax = array(
                        "idtransaction" => $qr->idtransaction
                    );
                    $queryx = $this->db->get_where('transaction_details', $datax)->result();
                    // print_r($queryx);
                    if (!empty($queryx)) {
                        $dataTrx[] = array('dataTransaction' => $qr, 'ditailsTransaction' => $queryx);
                    }
                }
            }
            // print_r($dataTrx);
             //exit;

            return $dataTrx;
        }
    }

    public function getInfoTracking($keyCode = '', $awb = '') {
        $data = array(
            "a.keyCode" => $keyCode,
            'b.trackingCode' => $awb
        );
        $this->db->join('transaction as b', 'b.idauthuser = a.idauthuser');
        $query = $this->db->get_where('apiauth_user as a', $data)->result();
        //print_r($query);
        if (!empty($query)) {
            $query = $query[0];
        } else {
            $query = 0;
        }
        return $query;
    }

    public function getInfoAwb($keyCodeStaff = '', $secret = '', $noInvoice = '') {
        $data = array(
            "keyCodeStaff" => $keyCodeStaff,
            'secret' => $secret
        );
        $query = $this->db->get_where('apiauth_staff', $data)->result();
//print_r ($query);exit;
        $datax = array(
            "a.noInvoice" => $noInvoice,
            "a.status" => 0,
            "a.statusPay" => 1,
//            "c.IDSENSUS" => "b.id_dis"
        );
        $this->db->select(
                'a.idtransaction, a.noInvoice, a.status, a.statusPay, a.shipping,
            b.idpeople, b.id_dis, b.id_city, b.address, b.pos, b.name, b.phone,
            c.PROVINCE_NAME as province_name, c.CITY_NAME as nameCity, c.ZIP_CODE as postcode, c.CITY_CODE as JNEcode'
        );
        $this->db->join('sensus_people as b', 'b.idpeople = a.idpeople', 'left');
        $this->db->join('sensus as c', 'c.CITY_ID = b.id_city AND c.IDSENSUS = b.id_dis', 'left');
        // $this->db->join('transaction_details as d', 'd.idtransaction = a.idtransaction', 'left');
        // $this->db->group_by("a.idtransaction");
//        $this->db->or_where();
        $queryx = $this->db->get_where('transaction as a', $datax)->result();

        if (!empty($queryx)) {
            $dataz = array(
                "idtransaction" => $queryx[0]->idtransaction
            );
            $this->db->select('sum(weight) as weight, sum(qty) as qty');
            $this->db->group_by("idtransaction");
            $queryz = $this->db->get_where('transaction_details', $dataz)->result();

            $query = array('dataOrder' => $queryx[0], 'dataProduct' => $queryz[0]);

            return $query;
        } else {
            return array();
        }
    }

    public function updateAwbToInvoice($awb = '', $noInvoice = '') {
        // echo $awb.'-'.$noInvoice;
        // exit;
        $this->db->set('trackingCode', $awb);
        // $this->db->set('status', 1);
        $this->db->where('noInvoice', $noInvoice);
        $this->db->update('transaction');
    }

    public function artorder($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCode']);
        if (!empty($dataAccount)) {
            $dataSku = json_decode($data['sku']);
            if (!empty($dataSku)) {
                foreach ($dataSku as $dS) {
                    $this->db->join('store as b', 'b.idquantum = a.idStore', 'left');
                    $this->db->where('a.skuDitails', $dS);
//                    $this->db->where('a.idStore', 'M001-O0042');
//                    $this->db->where('b.id_city', $data['idCity']);
//                    $this->db->where('b.id_prov', $data['idProv']);
                    $query = $this->db->get('product_store as a')->result();
                    print_r($query);
//                    exit;
//                    $aut2 = $this->quantum->callAPi($dS);
//                    print_r($aut2);
                    if (!empty($aut2)) {
                        foreach ($aut2 as $a2) {
                            $data = $this->db->get_where('product_store', array('skuDitails' => $a2->b, 'idStore' => $a2->w))->result();
                            $dataO = array(
                                'skuDitails' => $a2->b,
                                'idStore' => $a2->w,
                                'stock' => $a2->s
                            );
                            if (empty($data)) {
                                $this->db->insert('product_store', $dataO);
                            } else {
                                $this->db->set($dataO);
                                $this->db->where('skuDitails', $a2->b);
                                $this->db->where('idStore', $a2->w);
                                $this->db->update('product_store');
                            }
                        }
                    }
//                    exit;
                }
            }
//            print_r($dataSku);
            exit;
            $aut2 = $this->quantum->callAPi($dS);
            $dataStoreByCity = $this->db->get_where('store', array('id_city' => $data['idCity']))->result();
            if (!empty($dataStoreByCity)) {
                print_r($dataStoreByCity);
            } else {
                $dataStoreByProv = $this->db->get_where('store', array('id_prov' => $data['idProv']))->result();
                print_r($dataStoreByProv);
            }
            exit;
            print_r($dataSku);
            if (!empty($dataSku)) {
                foreach ($dataSku as $dS) {
//                    echo $dS;
                    $aut2 = $this->quantum->callAPi($dS);
//                    print_r($aut2);
//                    exit;
                    if (!empty($aut2)) {
                        foreach ($aut2 as $a2) {
                            $artOrder = $this->db->get_where('art_order', array('idauthuser' => $dataAccount->idauthuser, 'idStore' => $a2->w))->result();
                            $dataO = array(
                                'idauthuser' => $dataAccount->idauthuser,
                                'idCity' => $data['idCity'],
                                'sku' => $dS,
                                'qty' => $a2->s,
                                'idStore' => $a2->w
                            );
                            if (empty($artOrder)) {
                                $this->db->insert('art_order', $dataO);
                            }

                            $artStoreShipping = $this->db->get_where('art_store_shipping', array('idauthuser' => $dataAccount->idauthuser, 'idstore' => $a2->w))->result();
                            $dataO1 = array(
                                'idauthuser' => $dataAccount->idauthuser,
                                'idstore' => $a2->w
                            );
                            if (empty($artStoreShipping)) {
                                $this->db->insert('art_store_shipping', $dataO1);
                            }
                        }
                    }
                }
            }
            exit();
        }
        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['msg'] = 'Produk akan di kirim dari cabang.';
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function expedition($data = '') {
        $dataAccount = $this->verfyAccount($data['keyCode']);
        if (!empty($dataAccount)) {
            $artOrder = array(
                'idauthuser' => $dataAccount->idauthuser,
                'idCity' => $data['idCity'],
                'sku' => $data['sku'],
                'qty' => $data['qty']
            );
            $artOrder = $this->db->get_where('art_order', $artOrder)->result();

            if (!empty($artOrder)) {
                $insert_id = $artOrder[0]->idorderart;
            } else {
                $dataO = array(
                    'idauthuser' => $dataAccount->idauthuser,
                    'idCity' => $data['idCity'],
                    'sku' => $data['sku'],
                    'qty' => $data['qty']
                );

                $this->db->insert('art_order', $dataO);
                $insert_id = $this->db->insert_id();
            }

            $this->db->join('store as b', 'b.idquantum = a.idstorequantum', 'left');
            $this->db->where_in('a.skuProduct', json_decode($data['sku']));
            $dataP = $this->db->get('product_stockStore as a')->result();
//            print_r($dataP);
//            exit;
            if (!empty($dataP)) {
                foreach ($dataP as $d) {
//                    print_r($data['idCity']);
//                    exit;
                    if (empty($data['idCity'])) {
                        $idCityUser = 0;
                    } else {
                        $idCityUser = $data['idCity'];
                    }
                    $artStore = array(
                        'idorderart' => $insert_id,
                        'idCityUser' => $idCityUser,
                        'idCityFrom' => $d->id_city
                    );
//                    print_r($artStore);
                    $artStore = $this->db->get_where('art_store', $artStore)->result();
                    if (!empty($artStore)) {
                        $data = '';
                    } else {
                        $dataCourir = $this->courir->getCostExpedition('fdc5017ffe12f8a6f91a4ab338913d63', $d->id_city, $idCityUser, '1000', 'jne');
                        $courirData = json_decode($dataCourir);
//                        print_r($courirData);
//                        exit;
                        if (!empty($courirData->rajaongkir->results[0]->costs[0]->cost[0]->value)) {
                            $courirDataInsert = $courirData->rajaongkir->results[0]->costs[0]->cost[0]->value;
                            $etdDataInsert = $courirData->rajaongkir->results[0]->costs[0]->cost[0]->etd;
                            $originDataInsert = $courirData->rajaongkir->origin_details;
                        } else {
                            $courirDataInsert = 0;
                            $etdDataInsert = 0;
                            $originDataInsert = 0;
                        }
                        $dataInStore = array(
                            'idorderart' => $insert_id,
                            'idCityUser' => $idCityUser,
                            'idCityFrom' => $d->id_city,
                            'weight' => 1,
                            'costCourir' => $courirDataInsert,
                            'etd' => $etdDataInsert
                        );
                        if (!empty($courirDataInsert)) {
                            $this->db->insert('art_store', $dataInStore);
                        }

                        $data = array(
                            'origin_details' => $originDataInsert,
                            'dataShiping' => $dataInStore
                        );
                    }
                }
            }
            $artStoreShipping = array('a.idCityUser', $d->id_city);
            $this->db->join('art_order as b', 'b.idorderart = a.idorderart', 'left');
//            $this->db->where('a.idCityUser', $d->id_city);
            $dataShipping = $this->db->get_where('art_store as a', $artStoreShipping)->result();

            print_r($dataShipping);
            exit;
        }

        if ($dataAccount) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['msg'] = 'Produk akan di kirim dari cabang.';
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
