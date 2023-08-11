<?php

class Auth extends CI_Model {

    const TABLE = 'auth_user';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Auth
     * 
     * @param string $username
     * @param string $password
     */
    public function auth($username = '', $password = '') {
        if (empty($username) || empty($password)) {
            return null;
        }

        $user = $this->get_user($username, $password);
        if (!$user)
            return null;

        if (!password_verify($password, $user->paswd))
            return null;
        $this->update_last_login_and_secret($user, $username, $password);
        $data = [];

        foreach (['uname', 'phone', 'email', 'secret', 'name', 'born'] as $key) {
            $data[$key] = $user->{$key};
        }
        return $data;
    }

    /**
     * Check User is available
     * 
     * @param string $username
     * @param string $password
     */
    private function get_user($username, $password) {

        $whereOr = [
            'uname' => $username,
            'phone' => $username,
            'email' => $username
        ];
        $this->db->or_where($whereOr);

        $result = $this->db->get(self::TABLE)->result();
        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * Update secret and last Login Log
     * 
     * @param object $user
     * @param string $username
     * @param string $password
     */
    private function update_last_login_and_secret($user, $username, $password) {
        $data = [
            'secret' => md5($username . time()) . md5(time() . $password),
            'last_login' => date('Y-m-d H:i:s')
        ];

        $this->db->set($data);
        $this->db->where('id_auth_user', $user->id_auth_user);
        $this->db->update(self::TABLE);

        $user->secret = $data['secret'];
        $user->last_login = $data['last_login'];
    }

    public function getProfileUser($secret = '', $idDevice = '', $typeIdDevice = '') {
        $this->db->select('id, username, balance, created_on, picImage, first_name, last_name, company, phone');
        $query = $this->db->get_where('users', array('secret' => $secret))->result();

        $checkDevice = $this->db->get_where('users_device', array('idUser' => $query[0]->id, 'idDeviceAndroid' => $idDevice))->result();
        if (empty($checkDevice)) {
            //insert
            $data = array(
                'idUser' => $query[0]->id,
                'idDeviceAndroid' => $idDevice
            );

            $this->db->insert('users_device', $data);
        }
        
        if ($query) {
            return $query;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function updateProfileUser($idUser = '', $first_name = '', $last_name = '', $email = '', $phone = '', $company = '', $pin = '') {
        $this->db->set('first_name', $first_name);
        $this->db->set('last_name', $last_name);
        $this->db->set('email', $email);
        $this->db->set('phone', $phone);
        $this->db->set('company', $company);
        $this->db->set('pin', $pin);
        $this->db->where('id', $idUser);
        $data = $this->db->update('users');
        if ($data) {
            $response['status'] = 200;
            $response['error'] = FALSE;
        } else {
            $response['status'] = 200;
            $response['error'] = TRUE;
        }
        return $response;
    }

}
