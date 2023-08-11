<?php defined('BASEPATH') OR exit('No direct script access allowed');


class Sliders extends CI_Model  {

    const TABLE = 'sliders';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    
    public function list(array $options) {
        $this->extractQuery($options);
        $query = $this->db->get(self::TABLE)->result();
        
        return $query;
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
        foreach($options as $key => $value) {
            if (!in_array($key, $default)) {
                $this->db->where(self::TABLE .'.'. $key, $value);
            }
        }

        if (isset($options['perPage']))
            $this->db->limit($options['perPage']);

        if (isset($options['page']))
            $this->db->offset($options['page']);
        
        if (isset($options['sorted'])) {
            $sorted = explode('.', $options['sorted']);
            $this->db->order_by(self::TABLE.'.'.$sorted[0], $sorted[1]);
        }

    }

    public function detailByFields(array $condition) {
        $category = $this->db->get_where(self::TABLE, $condition)->result();
        return count($category) > 0 ? $category[0] : null;
    }

   
    public function store(array $data) {
        $date = date('Y-m-d h:i:s');

        $data['created_at'] = $date;
        $data['updated_at'] = $date;

        $result = $this->db->insert(self::TABLE, $data);
        return $result ? $this->db->insert_id() : false;
    }

    public function delete($id) {
        return $this->db->delete(self::TABLE, ['id_slider' => $id]);
    }

}
