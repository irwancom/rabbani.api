<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Client\Domain\MemberAddress\Contracts\MemberAddressRepositoryContract;

class MemberAddresses extends CI_Model implements MemberAddressRepositoryContract {

    const TABLE = 'user_address';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function handleSelect() {
        return array('user_address.id', 'user_address.user_id', 'user_address.address_name', 'user_address.received_name', 'user_address.phone_number',
            'user_address.province_id', 'provinces.name as province_name', 'user_address.districts_id', 'districts.nama as district_name',
            'user_address.sub_district_id', 'sub_district.nama as sub_district_name', 'user_address.urban_village_id', 'urban_village.nama as village_name',
            'user_address.post_code', 'user_address.address','user_address.city_name','user_address.city_code','user_address.main_address',
            'user_address.created_at','user_address.updated_at');
    }

    public function list(array $options) {
        $this->extractQuery($options);
        $this->db->select($this->handleSelect());
        $this->db->where('user_address.deleted_at', NULL);
        $this->db->join('provinces', 'provinces.id=user_address.province_id', 'left');
        $this->db->join('districts', 'districts.id_kab=user_address.districts_id', 'left');
        $this->db->join('sub_district', 'sub_district.id_kec=user_address.sub_district_id', 'left');
        $this->db->join('urban_village', 'urban_village.id_kel=user_address.urban_village_id', 'left');
        $result = $this->db->get(self::TABLE)->result();
        return $result;
    }

    public function totalItem($options) {
        // unset($options['perPage']);
        $this->extractQuery($options);

        $this->db->from(self::TABLE);
        $result = $this->db->count_all_results();
        return $result;
    }

    private function extractQuery($options) {
        $default = ['q', 'sorted', 'perPage', 'page'];
        foreach ($options as $key => $value) {
            if (!in_array($key, $default)) {
                $this->db->where($key, $value);
            }
        }

        if (isset($options['perPage']))
            $this->db->limit($options['perPage']);

        if (isset($options['page']))
            $this->db->offset($options['page']);

        if (isset($options['sorted'])) {
            $sorted = explode('.', $options['sorted']);
            $this->db->order_by(self::TABLE . '.' . $sorted[0], $sorted[1]);
        }

        if (isset($options['q'])) {
            $this->db->like(self::TABLE . '.name', $options['q']);
        }
    }

    // public function list(array $options) {
    //     $options['deleted_at'] = null;
    //     $query = $this->db->get_where(self::TABLE, $options)->result();
    //     if (!empty($query))
    //         return $query;
    //     return [];
    // }

    public function detailBy($field, $value) {
        $condition = [
            'deleted_at' => NULL,
            $field => $value
        ];

        $memberAddresses = $this->db->get_where(self::TABLE, $condition)->result();
        return count($memberAddresses) > 0 ? $memberAddresses[0] : null;
    }

    public function detailByFields(array $condition) {
        if(isset($condition['id'])){
            $condition['user_address.id'] = $condition['id'];
            unset($condition['id']);
        }
        if(isset($condition['main_address'])){
            $condition['user_address.main_address'] = $condition['main_address'];
            unset($condition['main_address']);
        }

        $condition['user_address.user_id'] = $condition['user_id'];
        $condition['user_address.deleted_at'] = NULL;
        unset($condition['deleted_at']);

        $this->db->select($this->handleSelect());
        $this->db->join('provinces', 'provinces.id=user_address.province_id', 'left');
        $this->db->join('districts', 'districts.id_kab=user_address.districts_id', 'left');
        $this->db->join('sub_district', 'sub_district.id_kec=user_address.sub_district_id', 'left');
        $this->db->join('urban_village', 'urban_village.id_kel=user_address.urban_village_id', 'left');

        $memberAddress = $this->db->get_where(self::TABLE, $condition)->result();
        return count($memberAddress) > 0 ? $memberAddress[0] : null;
    }

    public function update($memberAddress, array $data) {

        $id = $memberAddress->id;
        // $id_auth = $memberAddress->id_auth;

        unset($data['id_member_address']);
        unset($data['id_member']);
        unset($data['id_auth']);
        unset($data['created_at']);

        foreach ($data as $key => $value) {
            if (property_exists($memberAddress, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        $this->db->where('id', $id);
        // $this->db->where('id_auth', $id_auth);

        $result = $this->db->update(self::TABLE, $data);
        return $result;
    }

    public function store(array $data) {
        $date = date('Y-m-d h:i:s');

        $data['created_at'] = $date;
        $data['updated_at'] = $date;

        $result = $this->db->insert(self::TABLE, $data);
        return $result ? $this->db->insert_id() : false;
    }

}
