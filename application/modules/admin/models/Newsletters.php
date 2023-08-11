<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Admin\Domain\NewsLetter\Contracts\NewsLetterRepositoryContract;

class Newsletters extends CI_Model implements NewsLetterRepositoryContract {

    const TABLE = 'news_letter';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function list(array $options) {
        $query = $this->db->get_where(self::TABLE, $options)->result();
        if (!empty($query))
            return $query;
        
        return [];
    }

    public function detailBy($field, $value) {
        $letter = $this->db->get_where(self::TABLE, [$field => $value])->result();
        return count($letter) > 0 ? $letter[0] : null;
    }

    public function detailByFields(array $condition) {
        $letter = $this->db->get_where(self::TABLE, $condition)->result();
        return count($letter) > 0 ? $letter[0] : null;
    }

    public function update($letter, array $data) {
        
        $id      = $letter->id_letter;
        $id_auth = $letter->id_auth;

        unset($data['id_letter']);
        unset($data['id_auth']);
        unset($data['created_at']);

        foreach($data as $key => $value) {
            if (property_exists($letter, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        $this->db->where('id_letter', $id);
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

    
    public function delete(int $id) {
        $result = $this->db->delete(self::TABLE, ['id_letter' => $id]);
        return $result;
    }
    
}
