<?php

class MainModel extends CI_Model {

    public function __construct() {

        parent::__construct();

        $this->load->database();
    }

    public function insert($table, $data) {
        $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    public function update($table, $data, $filter = null, $filterOrWhere = null) {
        foreach ($data as $key => $value) {
            $this->db->set($key, $value);
        }

        if (!empty($filter)) {
            foreach ($filter as $key => $value) {
                if (is_array($value)) {
                    if ($value['condition'] == 'like') {
                        $this->db->like($key, $value['value']);
                    } else if ($value['condition'] == 'not_null') {
                        $this->db->where(sprintf('%s IS NOT NULL', $key));
                    } else if ($value['condition'] == 'is_null') {
                        $this->db->where(sprintf('%s IS NULL', $key));
                    } else if ($value['condition'] == 'where_in') {
                        $this->db->where_in($key, $value['value']);
                    }
                } else {
                    $this->db->where($key, $value);
                }
            }
        }

        if (!empty($filterOrWhere)) {
            $this->db->group_start();
            foreach ($filterOrWhere as $key => $value) {
                if (!is_int($key)) {
                    $this->db->or_where($key, $value);
                } else {
                    $this->db->or_where($value);   
                }
            }
            $this->db->group_end();
        }

        $result = $this->db->update($table, $data);
        return $result;
    }

    /**
     * @param String table
     * @param Array filterWhere
     * @param Array filterOrWhere
     *
     *
     */
    public function findOne($table, $filterWhere = null, $filterOrWhere = null, $join = null, $select = null, $orderKey = '', $orderValue = 'DESC', $groupBy = null, $del = '') {
        if (!empty($select)) {
            $this->db->select($select);
        }

        if (!empty($join)) {
            foreach ($join as $key => $value) {
                if (is_array($value)) {
                    $this->db->join($key, $value['value'], $value['type']);
                } else {
                    $this->db->join($key, $value);   
                }
            }
        }

        if (!empty($filterWhere)) {
            foreach ($filterWhere as $key => $value) {
                if (is_array($value)) {
                    if ($value['condition'] == 'like') {
                        $this->db->like($key, $value['value']);
                    } else if ($value['condition'] == 'not_null') {
                        $this->db->where(sprintf('%s IS NOT NULL', $key));
                    } else if ($value['condition'] == 'where_in') {
                        $this->db->where_in($key, $value['value']);
                    } else if ($value['condition'] == 'custom') {
                        $this->db->where($value['value']);
                    }
                } else if (is_int($key)) {
                    $this->db->where($value);
                } else {
                    $this->db->where($key, $value);
                }
            }
        }
        if (!empty($filterOrWhere)) {
            $this->db->group_start();
            foreach ($filterOrWhere as $key => $value) {
                if (!is_int($key)) {
                    if (is_array($value)) {
                        if ($value['condition'] == 'like') {
                            $this->db->or_like($key, $value['value']);
                        }
                    } else {
                        $this->db->or_where($key, $value);   
                    }
                } else {
                    $this->db->or_where($value);   
                }
            }

            //foreach ($filterOrWhere as $key => $value) {
                //if (!is_int($key)) {
                    //$this->db->or_where($key, $value);
                //} else {
                    //$this->db->or_where($value);   
                //}
            //}
            $this->db->group_end();
        }

        if(empty($del)){
            $this->db->where($table.'.deleted_at IS NULL', NULL, FALSE);
            if (!empty($orderKey)) {
                $this->db->order_by($orderKey, $orderValue);
            }
        }

        if (!empty($groupBy)) {
            $this->db->group_by($groupBy);
        }
        $query = $this->db->get($table)->result();
        return count($query) > 0 ? $query[0] : null;
    }

    public function find($table, $filterWhere = null, $filterOrWhere = null, $join = null, $select = null, $groupBy = null, $havingBy = null, $orderKey = '', $orderValue = 'DESC') {
        if (!empty($select)) {
            $this->db->select($select);
        }

        if (!empty($join)) {
            foreach ($join as $key => $value) {
                if (is_array($value)) {
                    $this->db->join($key, $value['value'], $value['type']);
                } else {
                    $this->db->join($key, $value);   
                }
            }
        }

        if (!empty($filterWhere)) {
            foreach ($filterWhere as $key => $value) {
                if (is_array($value)) {
                    if ($value['condition'] == 'like') {
                        $this->db->like($key, $value['value']);
                    } else if ($value['condition'] == 'not_null') {
                        $this->db->where(sprintf('%s IS NOT NULL', $key));
                    } else if ($value['condition'] == 'where_in') {
                        $this->db->where_in($key, $value['value']);
                    } else if ($value['condition'] == 'custom') {
                        $this->db->where($value['value']);
                    }
                } else {
                    $this->db->where($key, $value);
                }
            }
        }
        if (!empty($filterOrWhere)) {
            $this->db->group_start();
            foreach ($filterOrWhere as $key => $value) {
                if (!is_int($key)) {
                    if (is_array($value)) {
                        if ($value['condition'] == 'like') {
                            $this->db->or_like($key, $value['value']);
                        }
                    } else {
                        $this->db->or_where($key, $value);   
                    }
                } else {
                    $this->db->or_where($value);   
                }
            }
            $this->db->group_end();
        }

        if (!empty($groupBy)) {
            $this->db->group_by($groupBy);
        }

        if (!empty($havingBy)) {
            if (is_array($havingBy)) {
                foreach ($havingBy as $having) {
                    $this->db->having($having);
                }
            } else {
                $this->db->having($havingBy);
            }
        }
        if (!empty($orderKey)) {
            $this->db->order_by($orderKey, $orderValue);
        }
        $this->db->where($table.'.deleted_at IS NULL', NULL, FALSE);
        $query = $this->db->get($table)->result();
        return $query;
    }

    public function findPaginated($table, $filterWhere = null, $filterOrWhere = null, $join = null, $select = null, $offset = 0, $limit = 5, $orderKey, $orderValue = 'DESC', $groupBy = null, $altJoin = null, $altGroupBy = null, $altSelect = 'COUNT(*) as total', $havingBy = null, $multiSort = null) {
        
        // count
        $this->db->from($table);
        if (empty($altSelect)) {
            $altSelect = 'COUNT(*) as total';
        }
        $this->addFilter($table, $filterWhere, $filterOrWhere, $altJoin, $altSelect, $offset, $limit, $orderKey, $orderValue, $altGroupBy);
        $totalData = $this->db->get()->result();
        if (!empty($totalData)) {
            $totalData = $totalData[0]->total;
        } else {
            $totalData = 0;
        }
        $this->db->from($table);
        $this->addFilter($table, $filterWhere, $filterOrWhere, $join, $select, $offset, $limit, $orderKey, $orderValue, $groupBy);
        if ($havingBy && !empty($havingBy) && !is_null($havingBy)) {
            if (is_array($havingBy)) {
                foreach ($havingBy as $having) {
                    $this->db->having($having);
                }
            } else {
                $this->db->having($havingBy);
            }
        }
        $this->db->limit($limit, $offset);

        if ($multiSort && !empty($multiSort) && !is_null($multiSort)) {
            foreach ($multiSort as $key_sort => $sorting) {
                $this->db->order_by($key_sort, $sorting);
            }
        }else{
            $this->db->order_by($orderKey, $orderValue);
        }
        
        $data = $this->db->get()->result();
        $page = 1;
        if ($offset > 0) {
            $page = ceil($offset/$limit);
            $page = $page+1;
        }
        $maxPage = ceil($totalData/$limit);
        $result = [
            'result' => $data,
            'limit' => $limit,
            'total_data' => $totalData,
            'current_page' => $page,
            'max_page' => $maxPage
        ];
        return $result;
    }

    public function findCountData($table, $filterWhere = null, $filterOrWhere = null, $join = null, $select = null, $groupBy = null, $havingBy = null, $orderKey = '', $orderValue = 'DESC') {
        if (!empty($select)) {
            $this->db->select($select);
        }

        if (!empty($join)) {
            foreach ($join as $key => $value) {
                if (is_array($value)) {
                    $this->db->join($key, $value['value'], $value['type']);
                } else {
                    $this->db->join($key, $value);   
                }
            }
        }

        if (!empty($filterWhere)) {
            foreach ($filterWhere as $key => $value) {
                if (is_array($value)) {
                    if ($value['condition'] == 'like') {
                        $this->db->like($key, $value['value']);
                    } else if ($value['condition'] == 'not_null') {
                        $this->db->where(sprintf('%s IS NOT NULL', $key));
                    } else if ($value['condition'] == 'where_in') {
                        $this->db->where_in($key, $value['value']);
                    } else if ($value['condition'] == 'custom') {
                        $this->db->where($value['value']);
                    }
                } else {
                    $this->db->where($key, $value);
                }
            }
        }
        if (!empty($filterOrWhere)) {
            $this->db->group_start();
            foreach ($filterOrWhere as $key => $value) {
                if (!is_int($key)) {
                    if (is_array($value)) {
                        if ($value['condition'] == 'like') {
                            $this->db->or_like($key, $value['value']);
                        }
                    } else {
                        $this->db->or_where($key, $value);   
                    }
                } else {
                    $this->db->or_where($value);   
                }
            }
            $this->db->group_end();
        }

        if (!empty($groupBy)) {
            $this->db->group_by($groupBy);
        }

        if (!empty($havingBy)) {
            if (is_array($havingBy)) {
                foreach ($havingBy as $having) {
                    $this->db->having($having);
                }
            } else {
                $this->db->having($havingBy);
            }
        }
        if (!empty($orderKey)) {
            $this->db->order_by($orderKey, $orderValue);
        }
        $this->db->where($table.'.deleted_at IS NULL', NULL, FALSE);
        $query = $this->db->count_all_results($table);
        return $query;
    }

    public function startTransaction () {
        $this->db->trans_start();        
    }

    /**
     * Inno DB Use this
     */
    public function beginTransaction () {
        $this->db->trans_begin();
    }

    public function completeTransaction () {
        $this->db->trans_complete();
    }

    public function statusTransaction () {
        return $this->db->trans_status();
    }

    public function rollbackTransaction () {
        $this->db->trans_rollback();
    }

    public function commitTransaction () {
        $this->db->trans_commit();
    }

    public function getLastQuery () {
        return $this->db->last_query();
    }

    public function executeRawQuery ($query, $params = []) {
        return $this->db->query($query, $params);
    }

    private function addFilter ($table, $filterWhere = null, $filterOrWhere = null, $join = null, $select = null, $offset = 0, $limit = 5, $orderKey, $orderValue = 'DESC', $groupBy = null) {
        if (!empty($select)) {
            $this->db->select($select);
        }

        $showDeleted = false;
        if (isset($filterWhere['show_deleted']) && (bool)$filterWhere['show_deleted']) {
            $showDeleted = true;
            unset($filterWhere['show_deleted']);
        }

        if (!empty($join)) {
            foreach ($join as $key => $value) {
                if (is_array($value)) {
                    $this->db->join($key, $value['value'], $value['type']);
                } else {
                    $this->db->join($key, $value);   
                }
            }
        }

        if (!empty($filterWhere)) {
            foreach ($filterWhere as $key => $value) {
                if (is_array($value)) {
                    if ($value['condition'] == 'like') {
                        $this->db->like($key, $value['value']);
                    } else if ($value['condition'] == 'not_null') {
                        $this->db->where(sprintf('%s IS NOT NULL', $key));
                    } else if ($value['condition'] == 'where_in') {
                        $this->db->where_in($key, $value['value']);
                    } else if ($value['condition'] == 'custom') {
                        $this->db->where($value['value']);
                    }
                } else {
                    $this->db->where($key, $value);
                }
            }
        }
        if (!empty($filterOrWhere)) {
            $this->db->group_start();
            foreach ($filterOrWhere as $key => $value) {
                if (!is_int($key)) {
                    if (is_array($value)) {
                        if ($value['condition'] == 'like') {
                            $this->db->or_like($key, $value['value']);
                        }
                    } else {
                        $this->db->or_where($key, $value);   
                    }
                } else {
                    $this->db->or_where($value);   
                }
            }
            $this->db->group_end();
        }

        if (!empty($groupBy)) {
            $this->db->group_by($groupBy);
        }

        if ($showDeleted) {
            $this->db->where($table.'.deleted_at IS NOT NULL', NULL, FALSE);
        } else {
            $this->db->where($table.'.deleted_at IS NULL', NULL, FALSE);
        }
    }

    public function findLastOtpByAuthUser($userId, $otp) {
        $this->db->where('id_user', $userId);
        $this->db->where('type', 'auth_user');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get('otp')->result();
        return count($query) > 0 ? $query[0] : null;
    }

    public function findLastOtpByUser($userId, $otp) {
        $this->db->where('id_user', $userId);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get('otp')->result();
        return count($query) > 0 ? $query[0] : null;
    }

    //validation
    public function verfyUser($secret) {
        $this->db->select('a.id, a.id_auth, a.secret, a.created_on');
        $this->db->join('auth_api as b', 'b.id_auth = a.id_auth', 'left');
        $query = $this->db->get_where('users as a', array('a.secret' => $secret))->result();

        if ($query) {
            $response['status'] = 200;
            $response['dataSecret'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    //end validation
    public function addTransaction($idUser = '', $nominal = '', $note = '') {
        $data = array(
            'iduser' => $idUser,
            'timeAdd' => date('Y-m-d H:i:s'),
            'nominal' => $nominal,
            'note' => $note
        );

        $this->db->insert('leveling_bonus', $data);
    }

    public function transactionBonus($idUser = '', $type = '') {
        //type = 1 = commition join, 2 = commition pulsa, 3 = commition payment
        $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
        $getProfile_0 = $this->db->get_where('users as a', array('a.id' => $idUser))->result(); //level 0
        $feeLeveling = $this->db->get_where('leveling_setup', array('id_auth' => $getProfile_0[0]->id_auth, 'typeComition' => $type))->result();

        if (!empty($getProfile_0)) {
//            $this->db->set('balance', 'balance+' . $feeLeveling[0]->comition, FALSE);
//            $this->db->where('id', $getProfile_0[0]->id);
//            $this->db->update('users');
            if ($feeLeveling[0]->comition > 0) {
                $this->addTransaction($idUser, $feeLeveling[0]->comition, 'Komisi bonus transaksi');
            }

            $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
            $getProfile_1 = $this->db->get_where('users as a', array('a.id' => $getProfile_0[0]->idUserSponsor))->result();
            if (!empty($getProfile_1)) {
//                $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL1, FALSE);
//                $this->db->where('id', $getProfile_1[0]->id);
//                $this->db->update('users');
                if ($feeLeveling[0]->comitionL1 > 0) {
                    $this->addTransaction($idUser, $feeLeveling[0]->comitionL1, 'Komisi bonus LEVEL 1');
                }

                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                $getProfile_2 = $this->db->get_where('users as a', array('a.id' => $getProfile_1[0]->idUserSponsor))->result();
                if (!empty($getProfile_2)) {
//                    $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL2, FALSE);
//                    $this->db->where('id', $getProfile_2[0]->id);
//                    $this->db->update('users');
                    if ($feeLeveling[0]->comitionL2 > 0) {
                        $this->addTransaction($idUser, $feeLeveling[0]->comitionL2, 'Komisi bonus LEVEL 2');
                    }

                    $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                    $getProfile_3 = $this->db->get_where('users as a', array('a.id' => $getProfile_2[0]->idUserSponsor))->result();
                    if (!empty($getProfile_3)) {
//                        $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL3, FALSE);
//                        $this->db->where('id', $getProfile_3[0]->id);
//                        $this->db->update('users');
                        if ($feeLeveling[0]->comitionL3 > 0) {
                            $this->addTransaction($idUser, $feeLeveling[0]->comitionL3, 'Komisi bonus LEVEL 3');
                        }

                        $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                        $getProfile_4 = $this->db->get_where('users as a', array('a.id' => $getProfile_3[0]->idUserSponsor))->result();
                        if (!empty($getProfile_4)) {
//                            $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL4, FALSE);
//                            $this->db->where('id', $getProfile_4[0]->id);
//                            $this->db->update('users');
                            if ($feeLeveling[0]->comitionL4 > 0) {
                                $this->addTransaction($idUser, $feeLeveling[0]->comitionL4, 'Komisi bonus LEVEL 4');
                            }

                            $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                            $getProfile_5 = $this->db->get_where('users as a', array('a.id' => $getProfile_4[0]->idUserSponsor))->result();
                            if (!empty($getProfile_5)) {
//                                $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL5, FALSE);
//                                $this->db->where('id', $getProfile_5[0]->id);
//                                $this->db->update('users');
                                if ($feeLeveling[0]->comitionL5 > 0) {
                                    $this->addTransaction($idUser, $feeLeveling[0]->comitionL5, 'Komisi bonus LEVEL 5');
                                }

                                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                $getProfile_6 = $this->db->get_where('users as a', array('a.id' => $getProfile_5[0]->idUserSponsor))->result();
                                if (!empty($getProfile_6)) {
//                                    $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL6, FALSE);
//                                    $this->db->where('id', $getProfile_6[0]->id);
//                                    $this->db->update('users');
                                    if ($feeLeveling[0]->comitionL6 > 0) {
                                        $this->addTransaction($idUser, $feeLeveling[0]->comitionL6, 'Komisi bonus LEVEL 6');
                                    }

                                    $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                    $getProfile_7 = $this->db->get_where('users as a', array('a.id' => $getProfile_6[0]->idUserSponsor))->result();
                                    if (!empty($getProfile_7)) {
//                                        $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL7, FALSE);
//                                        $this->db->where('id', $getProfile_7[0]->id);
//                                        $this->db->update('users');
                                        if ($feeLeveling[0]->comitionL7 > 0) {
                                            $this->addTransaction($idUser, $feeLeveling[0]->comitionL7, 'Komisi bonus LEVEL 7');
                                        }

                                        $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                        $getProfile_8 = $this->db->get_where('users as a', array('a.id' => $getProfile_7[0]->idUserSponsor))->result();
                                        if (!empty($getProfile_8)) {
//                                            $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL8, FALSE);
//                                            $this->db->where('id', $getProfile_8[0]->id);
//                                            $this->db->update('users');
                                            if ($feeLeveling[0]->comitionL8 > 0) {
                                                $this->addTransaction($idUser, $feeLeveling[0]->comitionL8, 'Komisi bonus LEVEL 8');
                                            }

                                            $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                            $getProfile_9 = $this->db->get_where('users as a', array('a.id' => $getProfile_8[0]->idUserSponsor))->result();
                                            if (!empty($getProfile_9)) {
//                                                $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL9, FALSE);
//                                                $this->db->where('id', $getProfile_9[0]->id);
//                                                $this->db->update('users');
                                                if ($feeLeveling[0]->comitionL9 > 0) {
                                                    $this->addTransaction($idUser, $feeLeveling[0]->comitionL9, 'Komisi bonus LEVEL 9');
                                                }

                                                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                                $getProfile_10 = $this->db->get_where('users as a', array('a.id' => $getProfile_9[0]->idUserSponsor))->result();
                                                if (!empty($getProfile_10)) {
//                                                    $this->db->set('balance', 'balance+' . $feeLeveling[0]->comitionL10, FALSE);
//                                                    $this->db->where('id', $getProfile_10[0]->id);
//                                                    $this->db->update('users');
                                                    if ($feeLeveling[0]->comitionL10 > 0) {
                                                        $this->addTransaction($idUser, $feeLeveling[0]->comitionL10, 'Komisi bonus LEVEL 10');
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $data['status'] = 200;
            $data['msg'] = 'success add commiton';
            return $data;
        } else {
            $data['status'] = 403;
            $data['msg'] = 'info to admin';
            return $data;
        }
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
        $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.*, b.idUserSponsor');
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

            if (!empty($checkMembers_1)) {
                $this->debitCreditBalance($checkMembers_1[0]->id, $checkMembers_1[0]->pin, $checkMembers_0[0]->comitionL1, 1);

                //level 2
                $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                $checkMembers_2 = $this->db->get_where('users as a', array('a.id' => $checkMembers_1[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                if (!empty($checkMembers_2)) {
                    $this->debitCreditBalance($checkMembers_2[0]->id, $checkMembers_2[0]->pin, $checkMembers_0[0]->comitionL2, 1);

                    //level 3
                    $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                    $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                    $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                    $checkMembers_3 = $this->db->get_where('users as a', array('a.id' => $checkMembers_2[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                    if (!empty($checkMembers_3)) {
                        $this->debitCreditBalance($checkMembers_3[0]->id, $checkMembers_3[0]->pin, $checkMembers_0[0]->comitionL3, 1);

                        //level 4
                        $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                        $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                        $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                        $checkMembers_4 = $this->db->get_where('users as a', array('a.id' => $checkMembers_3[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                        if (!empty($checkMembers_4)) {
                            $this->debitCreditBalance($checkMembers_4[0]->id, $checkMembers_4[0]->pin, $checkMembers_0[0]->comitionL4, 1);

                            //level 5
                            $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                            $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                            $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                            $checkMembers_5 = $this->db->get_where('users as a', array('a.id' => $checkMembers_4[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                            if (!empty($checkMembers_5)) {
                                $this->debitCreditBalance($checkMembers_5[0]->id, $checkMembers_5[0]->pin, $checkMembers_0[0]->comitionL5, 1);

                                //level 6
                                $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                $checkMembers_6 = $this->db->get_where('users as a', array('a.id' => $checkMembers_5[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                if (!empty($checkMembers_6)) {
                                    $this->debitCreditBalance($checkMembers_6[0]->id, $checkMembers_6[0]->pin, $checkMembers_0[0]->comitionL6, 1);

                                    //level 7
                                    $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                    $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                    $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                    $checkMembers_7 = $this->db->get_where('users as a', array('a.id' => $checkMembers_6[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                    if (!empty($checkMembers_7)) {
                                        $this->debitCreditBalance($checkMembers_7[0]->id, $checkMembers_7[0]->pin, $checkMembers_0[0]->comitionL7, 1);

                                        //level 8
                                        $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                        $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                        $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                        $checkMembers_8 = $this->db->get_where('users as a', array('a.id' => $checkMembers_7[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                        if (!empty($checkMembers_8)) {
                                            $this->debitCreditBalance($checkMembers_8[0]->id, $checkMembers_8[0]->pin, $checkMembers_0[0]->comitionL8, 1);

                                            //level 9
                                            $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                            $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                            $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                            $checkMembers_9 = $this->db->get_where('users as a', array('a.id' => $checkMembers_8[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                            if (!empty($checkMembers_9)) {
                                                $this->debitCreditBalance($checkMembers_9[0]->id, $checkMembers_9[0]->pin, $checkMembers_0[0]->comitionL9, 1);

                                                //level 10
                                                $this->db->select('a.id, a.id_auth, a.username, a.first_name, a.last_name, a.pin, c.comition, b.idUserSponsor');
                                                $this->db->join('leveling_setup as c', 'c.id_auth = a.id_auth');
                                                $this->db->join('leveling_sponsor as b', 'b.idUserDownlen = a.id');
                                                $checkMembers_10 = $this->db->get_where('users as a', array('a.id' => $checkMembers_9[0]->idUserSponsor, 'c.typeComition' => $typeFunction))->result();

                                                if (!empty($checkMembers_10)) {
                                                    $this->debitCreditBalance($checkMembers_10[0]->id, $checkMembers_10[0]->pin, $checkMembers_0[0]->comitionL10, 1);
                                                } else {
//                                                    echo 10;
//                                                    $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL10, 1);
                                                }
                                            } else {
//                                                echo 9;
//                                                $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL9, 1);
                                            }
                                        } else {
//                                            echo 8;
//                                            $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL8, 1);
                                        }
                                    } else {
//                                        echo 7;
//                                        $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL7, 1);
                                    }
                                } else {
//                                    echo 6;
//                                    $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL6, 1);
                                }
                            } else {
//                                echo 5;
//                                $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL5, 1);
                            }
                        } else {
//                            echo 4;
//                            $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL4, 1);
                        }
                    } else {
//                        echo 3;
//                        $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL3, 1);
                    }
                } else {
//                    echo 2;
//                    $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL2, 1);
                }
            } else {
//                echo 1;
//                $this->debitCreditBalanceCorp($checkMembers_0[0]->id_auth, $checkMembers_0[0]->comitionL1, 1);
            }

            $response['status'] = 200;
            $response['error'] = FALSE;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
        }
        return $response;
    }

}
