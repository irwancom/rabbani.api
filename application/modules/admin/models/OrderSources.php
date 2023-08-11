<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Admin\Domain\OrderSource\Contracts\OrderSourceRepositoryContract;

class OrderSources extends CI_Model implements OrderSourceRepositoryContract {

    const TABLE = 'order_sources';

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
        unset($options['perPage']);
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

        $categories = $this->db->get_where(self::TABLE, $condition)->result();
        return count($categories) > 0 ? $categories[0] : null;
    }

    public function detailByFields(array $condition) {
        $condition['deleted_at'] = NULL;

        $orderSource = $this->db->get_where(self::TABLE, $condition)->result();
        return count($orderSource) > 0 ? $orderSource[0] : null;
    }

    public function update($orderSource, array $data) {

        $id = $orderSource->id_order_source;

        unset($data['id_order_source']);
        unset($data['created_at']);

        foreach ($data as $key => $value) {
            if (property_exists($orderSource, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        $this->db->where('id_order_source', $id);

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
