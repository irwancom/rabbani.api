<?php

class PpobModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function getSetting($secret) {
        $this->db->select('a.id, a.id_auth, a.secret, a.created_on, b.ppob, b.mlm');
        $this->db->join('auth_api as b', 'b.id_auth = a.id_auth', 'left');
        $query = $this->db->get_where('users as a', array('a.secret' => $secret))->result();
//        print_r($query[0]->mlm);
//        exit;
        if (!empty($query)) {
            //setup mlm setting
            $this->db->select('a.typemlm, sum(a.fee+b.comition+b.comitionL1+b.comitionL2+b.comitionL3+b.comitionL4+b.comitionL5+b.comitionL6+b.comitionL7+b.comitionL8+b.comitionL9+b.comitionL10) as total');
            $this->db->group_by("a.typemlm");
            $this->db->order_by('typemlm', 'ASC');
            $this->db->join('leveling_setup as b', 'b.id_auth = a.id_auth AND b.typeComition = 2', 'left');
            $query2 = $this->db->get_where('setup_mlm as a', array('a.id_auth' => $query[0]->id_auth))->result();
//            print_r($query2);
            if (empty($query2)) {
                $query2 = '';
            }
            //end mlm setting
            //setup ppob setting
            $this->db->select('a.typeppob, sum(a.fee+b.comition+b.comitionL1+b.comitionL2+b.comitionL3+b.comitionL4+b.comitionL5+b.comitionL6+b.comitionL7+b.comitionL8+b.comitionL9+b.comitionL10) as total');
            $this->db->group_by("a.typeppob");
            $this->db->order_by('typeppob', 'ASC');
            $this->db->join('leveling_setup as b', 'b.id_auth = a.id_auth AND b.typeComition = 3', 'left');
            $query3 = $this->db->get_where('setup_ppob as a', array('a.id_auth' => $query[0]->id_auth))->result();
            if (empty($query3)) {
                $query3 = '';
            }
            //end ppob setting
            $response['status'] = 200;
//            $response['dataSecret'] = $query;
            if (!empty($query[0]->mlm)) {
                $response['dataSecret'] = $query;
                $response['dataSetupMlm'] = $query2;
            }
            if (!empty($query[0]->ppob)) {
                $response['dataSecret'] = $query;
                $response['dataSetupPpob'] = $query3;
            }
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
        }
        return $response;
    }

    public function addCat($data = '') {
        $query = $this->db->get_where('category_ppob', $data)->result();
        if (empty($query)) {
            $this->db->insert('category_ppob', $data);
        }
    }

    public function getCat($type = '') {
        $this->db->select('idApi, groupId, productName, typeProduct, urlCall, icon');
        if (!empty($type)) {
            $query = $this->db->get_where('category_ppob', array('status' => 1, 'typeProduct' => 'PAYMENT'))->result();
        } else {
            $query = $this->db->get_where('category_ppob', array('status' => 1))->result();
        }
        return $query;
    }

    public function addProPrx($data = '') {
        $query = $this->db->get_where('product_ppob_prefix', $data)->result();
        if (empty($query)) {
            $this->db->insert('product_ppob_prefix', $data);
        }
    }

    public function getPrx($id = '') {
        if (!empty($id)) {
            $this->db->select('idOperator, product_code, product_name, prefix');
            $query = $this->db->get_where('product_ppob_prefix', array('idCatApi' => $id, 'status' => 1))->result();
        } else {
            $query = $this->db->get('product_ppob_prefix')->result();
        }
        return $query;
    }

    public function addProPpob($data = '') {
        $query = $this->db->get_where('product_ppob', array('code' => $data['code']))->result();
        if (empty($query)) {
            $this->db->insert('product_ppob', $data);
        } else {
            $this->db->set($data);
            $this->db->where('code', $data['code']);
            $this->db->update('product_ppob');
        }
    }

    public function getPpob($id = '') {
        if (!empty($id)) {
            $this->db->select('a.idCatApi, a.code, a.product_name, b.productName as category_name, a.price, a.desc, a.status');
            $this->db->join('category_ppob as b', 'b.idApi = a.idCatApi', 'left');
            $query = $this->db->get_where('product_ppob as a', array('a.idOperator' => $id))->result();
        } else {
            $query = $this->db->get('product_ppob')->result();
        }
        return $query;
    }

    public function getPpobDetail($code = '') {
        if (!empty($code)) {
            $this->db->select('idApi, code, product_name, price, desc, status');
            $query = $this->db->get_where('product_ppob', array('code' => $code))->result();
        } else {
            $query = $this->db->get('product_ppob')->result();
        }
        return $query;
    }

    public function addProPay($data = '') {
        $query = $this->db->get_where('product_payment', $data)->result();
        if (empty($query)) {
            $this->db->insert('product_payment', $data);
        }
    }

    public function getPayment($id = '') {
        if (!empty($id)) {
            $this->db->select('idAPi, code, product_name, status');
            $query = $this->db->get_where('product_payment', array('idCatApi' => $id))->result();
        } else {
            $query = $this->db->get('product_payment')->result();
        }
        return $query;
    }

    public function getPaymentDetail($code = '') {
        if (!empty($code)) {
            $this->db->select('idAPi, code, product_name, status');
            $query = $this->db->get_where('product_payment', array('code' => $code))->result();
        } else {
            $query = $this->db->get('product_ppob')->result();
        }
        return $query;
    }

    public function addOrder($idUser = '', $typeOrder = '', $code = '', $phone = '', $cosNumber = '', $pin = '', $addPrice = '') {
        $query = $this->db->get_where('users', array('id' => $idUser, 'pin' => $pin))->result();
        if (!empty($query)) {
            if ($typeOrder != 2) {
                $priceCheck = $this->db->get_where('product_ppob', array('code' => $code))->result();
                if ($priceCheck[0]->price < $query[0]->balance) {
                    $debitBalace = $priceCheck[0]->price + $addPrice;
                    if ($typeOrder == 0) {
                        $inquiry = 'I';
                        $data = array(
                            'idUser' => $idUser,
                            'dateTime' => date('Y-m-d H:i:s'),
                            'typeOrder' => $typeOrder,
                            'dataJason' => '[]',
                            'priceBuy' => $debitBalace
                        );
                    } elseif ($typeOrder == 1) {
                        $inquiry = 'PLN';
                        $data = array(
                            'idUser' => $idUser,
                            'dateTime' => date('Y-m-d H:i:s'),
                            'typeOrder' => $typeOrder,
                            'dataJason' => '[]',
                            'priceBuy' => $debitBalace
                        );
                    } else {
                        $inquiry = 'PAYMENT';
                        $data = array(
                            'idUser' => $idUser,
                            'dateTime' => date('Y-m-d H:i:s'),
                            'typeOrder' => $typeOrder,
                            'dataJason' => '[]',
                            'priceBuy' => $debitBalace
                        );
                    }

                    $this->db->insert('transaction_ppob', $data);
                    $notransaction = $this->db->insert_id();
//                    if ($inquiry != 'PAYMENT') {

                    $bodyReq = [
                        'inquiry' => $inquiry,
                        'code' => $code,
                        'phone' => $phone,
                        'no_meter_pln' => $cosNumber,
                        'api_trxid' => $notransaction,
                        'pin' => '7909'
                    ];
//                    } else {
//                        $codex = '';
//                    }
//                    print_r($codex);

                    $res = $this->tripay->ordersPurchase($bodyReq);

//                    print_r($res);
//                    exit;
                    if ($res->success == false) {
                        $this->db->set('dataJason', json_encode($res));
                        $this->db->set('status', 2);
                        $this->db->where('idtrxppob', $notransaction);
                        $this->db->update('transaction_ppob');
                    }

                    $this->debitCreditBalance($idUser, $pin, $debitBalace);

//                    $this->MainModel->transactionBonus($idUser, 2);

                    $data = array(
                        'idUser' => $idUser,
                        'timeCreate' => date('Y-m-d H:i:s')
                    );

                    $this->db->insert('log_user', $data);

                    $response['status'] = 200;
                    $response['error'] = FALSE;
                    $response['transactionID'] = $notransaction;
                    $response['dataRes'] = $res;
                    $response['dataOrder'] = array('inquiry' => $inquiry,
                        'code' => $code,
                        'phone' => $phone,
                        'no_meter_pln' => $cosNumber,
                        'api_trxid' => $notransaction,);
                } else {
                    $response['status'] = 200;
                    $response['error'] = TRUE;
                    $response['message'] = 'Saldo tidak cukup';
                }
            } else {
                $data = array(
                    'idUser' => $idUser,
                    'dateTime' => date('Y-m-d H:i:s'),
                    'dataJason' => '[]',
                    'typeOrder' => $typeOrder
                );

                $this->db->insert('transaction_ppob', $data);
                $notransaction = $this->db->insert_id();

                $bodyReq = [
                    'inquiry' => $inquiry,
                    'code' => $code,
                    'phone' => $phone,
                    'no_meter_pln' => $cosNumber,
                    'api_trxid' => $notransaction,
                    'pin' => '7909'
                ];
                $res = $this->tripay->ordersPurchase($bodyReq);

                if ($res->success == false) {
                    $this->db->set('dataJason', json_encode($res));
                    $this->db->set('status', 2);
                    $this->db->where('idtrxppob', $notransaction);
                    $this->db->update('transaction_ppob');
                }

                $data = array(
                    'idUser' => $idUser,
                    'timeCreate' => date('Y-m-d H:i:s'),
                    'jsonData' => json_encode(array('idBill' => 'wailting...', 'code' => $code, 'phone' => $phone, 'msg' => 'Pembayaran ' . $code))
                );

                $this->db->insert('log_user', $data);

                $response['status'] = 200;
                $response['error'] = FALSE;
                $response['transactionID'] = $notransaction;
                $response['dataRes'] = $res;
                $response['data'] = array('product' => $code,
                    'phone' => $phone,
                    'no_pelanggan' => $cosNumber,
                    'api_trxid' => $notransaction);
            }
        } else {
            $response['status'] = 502;
            $response['error'] = TRUE;
            $response['message'] = 'Data failed to receive or data empty.';
        }
        return $response;
    }

    public function transaction($idUser = '', $typeOrder = '') {
        $this->db->order_by('idtrxppob', 'DESC');
        if (!empty($typeOrder)) {
            $query = $this->db->get_where('transaction_ppob', array('idUser' => $idUser, 'typeOrder' => 2))->result();
        } else {
            $this->db->where('idUser', $idUser);
            $this->db->where('typeOrder', 0);
            $this->db->or_where('typeOrder', 1);
            $query = $this->db->get('transaction_ppob')->result();
        }

        if (!empty($query)) {
            $response['status'] = 200;
            $response['error'] = FALSE;
            $response['data'] = $query;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
            $response['message'] = 'Data empty';
        }
        return $response;
    }

    public function addMembers($id_auth = '', $idUser = '', $ip_address = '', $nameNewMembers = '', $phone = '', $codeActivation = '', $pin = '') {
        if ((!empty($id_auth)) && (!empty($ip_address)) && (!empty($nameNewMembers)) && (!empty($phone))) {
            $query = $this->db->get_where('users', array('id_auth' => $id_auth, 'phone' => $phone, 'pin' => $pin))->result();
            if (empty($query)) {
                $validCode = $this->db->get_where('mlm_codeActivation', array('code' => $codeActivation, 'status' => 0))->result();
                if (!empty($validCode)) {
                    $this->db->set('status', 1);
                    $this->db->set('timeActivate', time());
                    $this->db->where('code', $codeActivation);
                    $this->db->update('mlm_codeActivation');

                    $data = array(
                        'id_auth' => $id_auth,
                        'ip_address' => $ip_address,
                        'email' => $nameNewMembers . '@' . $phone,
                        'created_on' => time(),
                        'first_name' => $nameNewMembers,
                        'phone' => $phone
                    );
                    $this->db->insert('users', $data);

                    $data = array(
                        'timeCreate' => time(),
                        'idUserSponsor' => $idUser,
                        'idUserDownlen' => $this->db->insert_id()
                    );
                    $this->db->insert('leveling_sponsor', $data);

                    $message = 'Halo ' . $nameNewMembers . ', no hp telah berhasil di aktivasi, silahkan login dengan no hp terdaftar.';
                    $this->sms->send($phone, $message);
                    $response['status'] = 200;
                    $response['error'] = FALSE;
                    $response['message'] = 'added success';
                } else {
                    $response['status'] = 200;
                    $response['error'] = TRUE;
                    $response['message'] = 'code invalid';
                }
            } else {
                $response['status'] = 200;
                $response['error'] = TRUE;
                $response['message'] = 'phone number alert';
            }
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
            $response['message'] = 'incomplete data';
        }
        return $response;
    }

    public function getMembers($idUser = '') {
        $this->db->select('b.id, b.picImage, b.first_name, b.phone, b.created_on as timeJoin, b.last_login');
        $this->db->join('users as b', 'b.id = a.idUserDownlen', 'left');
        $query = $this->db->get_where('leveling_sponsor as a', array('a.idUserSponsor' => $idUser))->result();
        if ($query) {
            $response['status'] = 200;
            $response['error'] = FALSE;
            $response['message'] = 'get data success';
            $response['data'] = $query;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
            $response['message'] = 'code invalid';
        }
        return $response;
    }

    public function dataBonus($idUser = '') {
        $this->db->select('sum(nominal) as bonus, typeBonus');
        $this->db->group_by("typeBonus");
        $query = $this->db->get_where('leveling_bonus as a', array('a.iduser' => $idUser))->result();
        if ($query) {
            $response['status'] = 200;
            $response['error'] = FALSE;
            $response['message'] = 'get data success';
            $response['data'] = $query;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
            $response['message'] = 'code invalid';
        }
        return $response;
    }

    public function redemptionBonus($idUser = '', $pin = '') {
        $this->db->select('sum(nominal) as bonus');
        $this->db->group_by("iduser");
        $query = $this->db->get_where('leveling_bonus as a', array('a.iduser' => $idUser))->result();
        $checkMembers = $this->db->get_where('users', array('id' => $idUser, 'pin' => $pin))->result();
        if (!empty($checkMembers)) {
            if ($query[0]->bonus > 0) {
                $this->debitCreditBalance($idUser, $pin, $query[0]->bonus, 1);

                $data = array(
                    'iduser' => $idUser,
                    'typeBonus' => 4,
                    'nameBonus' => 'DEBIT',
                    'timeAdd' => date('Y-m-d H:i:s'),
                    'nominal' => '-' . $query[0]->bonus,
                    'note' => 'Pengambilan bonus'
                );
                $this->db->insert('leveling_bonus', $data);
                $query = $query[0]->bonus;
            } else {
                $query = 'bonus is not enough';
            }
        }
        if ($query) {
            $response['status'] = 200;
            $response['error'] = FALSE;
            $response['message'] = 'action debiting bonus success';
            $response['data'] = array('bonus' => $query);
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
            $response['message'] = 'code invalid';
        }
        return $response;
    }

    public function transferBalance($idUser = '', $nominal = '', $sendTo = '', $pin = '') {
        $checkMembers = $this->db->get_where('users', array('id' => $idUser, 'pin' => $pin))->result();
        if (!empty($checkMembers)) {
            if ($checkMembers[0]->balance > 0) {
                $this->debitCreditBalance($idUser, $pin, $nominal);

                $this->db->set('balance', 'balance+' . $nominal, FALSE);
                $this->db->where('id_auth', $checkMembers[0]->id_auth);
                $this->db->where('phone', $sendTo);
                $this->db->update('users');
            } else {
                $checkMembers = 'balance is not enough';
            }
        }
        if ($checkMembers) {
            $response['status'] = 200;
            $response['error'] = FALSE;
            $response['message'] = 'action transfering bonus success';
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
            $response['message'] = 'code invalid';
        }
        return $response;
    }

    public function payBill($idUser = '', $idtrxppob = '', $pin = '') {
        $trxPPOB = $this->db->get_where('transaction_ppob', array('idUser' => $idUser, 'idtrxppob' => $idtrxppob))->result();
        $djas = json_decode($trxPPOB[0]->dataJason);
        $checkMembers = $this->db->get_where('users', array('id' => $idUser, 'pin' => $pin))->result();
        if ($checkMembers[0]->balance > $djas->totalPay) {
            $this->debitCreditBalance($idUser, $pin, $djas->totalPay);
            $codex = array(
                'order_id' => $djas->idAPi, // Masukkan ID yang didapat setelah melakukan pengecekan pembayaran
                'api_trxid' => $idtrxppob, // Atau Anda bisa menggunakan ID transaksi dari server Anda (pilih salah satu)
                'pin' => '7909', // Masukkan PIN user (anda)
            );
//            $this->tripay->payBill($codex);

            $response['status'] = 200;
            $response['error'] = FALSE;
            $response['message'] = 'payment processing';
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
            $response['message'] = 'insufficient balance';
        }
        return $response;
    }

    private function debitCreditBalance($idUser = '', $pin = '', $balance = '', $typeFunction = '') {
        $checkMembersdcb = $this->db->get_where('users', array('id' => $idUser, 'pin' => $pin))->result();
        if ($checkMembersdcb) {
            if ($typeFunction == 1) {
                $this->db->set('balance', 'balance+' . $balance, FALSE);
                $this->db->where('id', $idUser);
                $this->db->update('users');
            } else {
                $this->db->set('balance', 'balance-' . $balance, FALSE);
                $this->db->where('id', $idUser);
                $this->db->update('users');
            }

            $response['status'] = 200;
            $response['error'] = FALSE;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
        }
        return $response;
    }

    private function debitCreditBalanceCorp($id_auth = '', $balance = '', $typeFunction = '') {
        $checkMembersdcb = $this->db->get_where('auth_api', array('id_auth' => $id_auth))->result();
        if ($checkMembersdcb) {
            if ($typeFunction == 1) {
                $this->db->set('balance', 'balance+' . $balance, FALSE);
                $this->db->where('auth_api', $id_auth);
                $this->db->update('auth_api');
            } else {
                $this->db->set('balance', 'balance-' . $balance, FALSE);
                $this->db->where('auth_api', $id_auth);
                $this->db->update('auth_api');
            }

            $response['status'] = 200;
            $response['error'] = FALSE;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
        }
        return $response;
    }

    public function comitionUpdate($idUser = '', $typeFunction = '') {
        $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
        $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
        $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
        $checkMembers_0 = $this->db->get_where('users as a', array('a.id' => $idUser, 'c.typeComition' => $typeFunction))->result();
//        print_r($checkMembers_0);
//        exit;
        if (!empty($checkMembers_0)) {
            $this->debitCreditBalance($checkMembers_0[0]->id, $checkMembers_0[0]->pin, $checkMembers_0[0]->comition, 1);

            //level 1
            $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
            $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
            $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
            $checkMembers_1 = $this->db->get_where('users as a', array('a.id' => $checkMembers_0[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();
//            print_r($checkMembers_1);
            if (!empty($checkMembers_1)) {
                $this->debitCreditBalance($checkMembers_1[0]->id, $checkMembers_1[0]->pin, $checkMembers_1[0]->comition, 1);

                //level 2
                $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                $checkMembers_2 = $this->db->get_where('users as a', array('a.id' => $checkMembers_1[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                if (!empty($checkMembers_2)) {
                    $this->debitCreditBalance($checkMembers_2[0]->id, $checkMembers_2[0]->pin, $checkMembers_2[0]->comition, 1);

                    //level 3
                    $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                    $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                    $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                    $checkMembers_3 = $this->db->get_where('users as a', array('a.id' => $checkMembers_2[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                    if (!empty($checkMembers_3)) {
                        $this->debitCreditBalance($checkMembers_3[0]->id, $checkMembers_3[0]->pin, $checkMembers_3[0]->comition, 1);

                        //level 4
                        $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                        $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                        $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                        $checkMembers_4 = $this->db->get_where('users as a', array('a.id' => $checkMembers_3[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                        if (!empty($checkMembers_4)) {
                            $this->debitCreditBalance($checkMembers_4[0]->id, $checkMembers_4[0]->pin, $checkMembers_4[0]->comition, 1);

                            //level 5
                            $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                            $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                            $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                            $checkMembers_5 = $this->db->get_where('users as a', array('a.id' => $checkMembers_4[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                            if (!empty($checkMembers_5)) {
                                $this->debitCreditBalance($checkMembers_5[0]->id, $checkMembers_5[0]->pin, $checkMembers_5[0]->comition, 1);

                                //level 6
                                $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                $checkMembers_6 = $this->db->get_where('users as a', array('a.id' => $checkMembers_5[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                if (!empty($checkMembers_6)) {
                                    $this->debitCreditBalance($checkMembers_6[0]->id, $checkMembers_6[0]->pin, $checkMembers_6[0]->comition, 1);

                                    //level 7
                                    $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                    $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                    $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                    $checkMembers_7 = $this->db->get_where('users as a', array('a.id' => $checkMembers_6[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                    if (!empty($checkMembers_7)) {
                                        $this->debitCreditBalance($checkMembers_7[0]->id, $checkMembers_7[0]->pin, $checkMembers_7[0]->comition, 1);

                                        //level 8
                                        $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                        $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                        $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                        $checkMembers_8 = $this->db->get_where('users as a', array('a.id' => $checkMembers_7[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                        if (!empty($checkMembers_8)) {
                                            $this->debitCreditBalance($checkMembers_8[0]->id, $checkMembers_8[0]->pin, $checkMembers_8[0]->comition, 1);

                                            //level 9
                                            $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                            $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                            $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                            $checkMembers_9 = $this->db->get_where('users as a', array('a.id' => $checkMembers_8[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                            if (!empty($checkMembers_9)) {
                                                $this->debitCreditBalance($checkMembers_9[0]->id, $checkMembers_9[0]->pin, $checkMembers_9[0]->comition, 1);

                                                //level 10
                                                $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                                $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                                $checkMembers_10 = $this->db->get_where('users as a', array('a.id' => $checkMembers_9[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                                if (!empty($checkMembers_10)) {
                                                    $this->debitCreditBalance($checkMembers_10[0]->id, $checkMembers_10[0]->pin, $checkMembers_10[0]->comition, 1);
                                                } else {
                                                    $this->debitCreditBalanceCorp($checkMembers_10[0]->id_auth, $checkMembers_10[0]->comition, 1);
                                                }
                                            } else {
                                                $this->debitCreditBalanceCorp($checkMembers_9[0]->id_auth, $checkMembers_9[0]->comition, 1);
                                            }
                                        } else {
                                            $this->debitCreditBalanceCorp($checkMembers_8[0]->id_auth, $checkMembers_8[0]->comition, 1);
                                        }
                                    } else {
                                        $this->debitCreditBalanceCorp($checkMembers_7[0]->id_auth, $checkMembers_7[0]->comition, 1);
                                    }
                                } else {
                                    $this->debitCreditBalanceCorp($checkMembers_6[0]->id_auth, $checkMembers_6[0]->comition, 1);
                                }
                            } else {
                                $this->debitCreditBalanceCorp($checkMembers_5[0]->id_auth, $checkMembers_5[0]->comition, 1);
                            }
                        } else {
                            $this->debitCreditBalanceCorp($checkMembers_4[0]->id_auth, $checkMembers_4[0]->comition, 1);
                        }
                    } else {
                        $this->debitCreditBalanceCorp($checkMembers_3[0]->id_auth, $checkMembers_3[0]->comition, 1);
                    }
                } else {
                    $this->debitCreditBalanceCorp($checkMembers_2[0]->id_auth, $checkMembers_2[0]->comition, 1);
                }
            } else {
                $this->debitCreditBalanceCorp($checkMembers_1[0]->id_auth, $checkMembers_1[0]->comition, 1);
            }

            $response['status'] = 200;
            $response['error'] = FALSE;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
        }
        return $response;
    }

    public function callBack($code = '') {
//        $query = $this->db->get_where('transaction_ppob', array('idtrxppob' => $id))->result();
//
//        $this->db->set('dataJason', $code['dataJason']);
//        $this->db->set('status', $code['status']);
//        $this->db->where('idtrxppob', $code['idtrxppob']);
//        $this->db->where('idUser', $code['idUser']);
//        $this->db->update('transaction_ppob');
//        if ($code) {
//            $response['status'] = 200;
//            $response['error'] = FALSE;
//        } else {
//            $response['status'] = 200;
//            $response['error'] = TRUE;
//        }
//        return $response;
    }

    public function addCode($code = '') {
        $data = array(
            'timeCreate' => time(),
            'code' => $code
        );

        $this->db->insert('mlm_codeActivation', $data);
    }

}
