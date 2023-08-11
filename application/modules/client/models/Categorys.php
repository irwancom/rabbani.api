<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Client\Domain\Category\Contracts\CategoryRepositoryContract;

class Categorys extends CI_Model implements CategoryRepositoryContract {

    const TABLE = 'category';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    
    public function list(array $options) {
        $options['deleted_at'] = null;
        $query = $this->db->get_where(self::TABLE, $options)->result();
        if (!empty($query))
            return $query;
        
        return [];
    }

}
