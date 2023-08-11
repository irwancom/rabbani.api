<?php defined('BASEPATH') OR exit('No direct script access allowed');



class Carts extends CI_Model {

    const TABLE = 'carts';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    
    public function detail($field, $value) {
        $result = $this->db
                        ->get_where(self::TABLE, [$field => $value])
                        ->result();

        return $result ? $result[0] : null;
    }
    
}
