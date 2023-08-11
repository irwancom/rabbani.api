<?php defined('BASEPATH') OR exit('No direct script access allowed');

class CoreAuth extends CI_Model {

    public function validate(string $token) {
        $data = [
            'secret' => $token
        ];

        $query = $this->db->get_where('admins', ['secret' => $token])->result();
        return count($query) > 0 ? $query[0] : null;
    }

    public function validateUser(string $token) {
        $data = [
            'secret' => $token
        ];

        $query = $this->db->get_where('users', ['secret' => $token])->result();
        return count($query) > 0 ? $query[0] : null;
    }
}