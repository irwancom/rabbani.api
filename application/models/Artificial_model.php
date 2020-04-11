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
