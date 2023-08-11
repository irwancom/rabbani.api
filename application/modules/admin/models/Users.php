<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Admin\Domain\User\Contracts\UserRepositoryContract;

class Users extends CI_Model implements UserRepositoryContract {

    const TABLE = 'auth_user';

    CONST ADMIN  = 0;
    CONST STAFF  = 1;
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function list(array $options) {
        if (!key_exists('deleted_at', $options)) {
            $options['deleted_at'] = NULL;
        }

        $query = $this->db->get_where(self::TABLE, $options)->result();
        if (!empty($query))
            return $query;
        
        return [];
    }

    public function detailBy($field, $value) {

        $condition = [
            'deleted_at' => NULL,
            $field => $value
        ];

        $user = $this->db->get_where(self::TABLE, $condition)->result();
        return count($user) > 0 ? $user[0] : null;
    }

    public function detailByFields(array $condition) {
        if (!isset($condition['deleted_at'])) {
            $condition['deleted_at'] = null;
        }

        $user = $this->db->get_where(self::TABLE, $condition)->result();
        return count($user) > 0 ? $user[0] : null;
    }

    public function update($user, array $data) {
        
        $id      = $user->id_auth_user;
        $id_auth = $user->id_auth;

        unset($data['id_auth_user']);
        unset($data['id_auth']);
        unset($data['created_at']);

        foreach($data as $key => $value) {
            if (property_exists($user, $key)) {
                $this->db->set($key, $value);
            }
        }

        $this->db->set('updated_at', date('Y-m-d h:i:s'));
        $this->db->where('id_auth_user', $id);
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
