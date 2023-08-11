<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Admin\Domain\Warehouse\Contracts\WarehouseRepositoryContract;

class Warehouses extends CI_Model implements WarehouseRepositoryContract {

    const TABLE = 'warehouses';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function list(array $options) {
        $this->extractQuery($options);
        $this->db->where('deleted_at', NULL);
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

        $warehouses = $this->db->get_where(self::TABLE, $condition)->result();
        return count($warehouses) > 0 ? $warehouses[0] : null;
    }

    public function detailByFields(array $condition) {
        $condition['deleted_at'] = NULL;

        $warehouse = $this->db->get_where(self::TABLE, $condition)->result();
        return count($warehouse) > 0 ? $warehouse[0] : null;
    }

    public function update($warehouse, array $data) {

        $id = $warehouse->id_warehouse;
        $id_auth = $warehouse->id_auth;

        unset($data['id_warehouse']);
        unset($data['id_auth']);
        unset($data['created_at']);

        foreach ($data as $key => $value) {
            if (property_exists($warehouse, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        $this->db->where('id_warehouse', $id);
        $this->db->where('id_auth', $id_auth);

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
