<?php

class Authapi_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
    }

    public function empty_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field not empty';
        return $response;
    }

    public function apiauth($username = '', $password = '') {
        if (empty($username) || empty($password)) {
            return $this->empty_response();
        } else {
            $data = array(
                "username" => $username,
                "password" => md5($password)
            );
            $checkAuth = $this->db->get_where('apiauth', $data)->result();
            $dataCode = array(
                'keyCode' => md5(time()),
                'secret' => md5($username . time()) . md5(time() . $password)
            );
            $this->db->set($dataCode);
            $this->db->where($data);
            $this->db->update('apiauth');

            $this->db->select('username, keyCode, secret');
            $query = $this->db->get_where('apiauth', $data)->result();
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'fail';
                return $response;
            }
        }
    }

    public function apiauth_admin($username = '', $password = '') {
        if (empty($username) || empty($password)) {
            return $this->empty_response();
        } else {
            $data = array(
                "username" => $username,
                "password" => md5($password),
                "idstore" => 1,
                "status" => 0
            );
            $checkAuth = $this->db->get_where('apiauth_staff', $data)->result();
            $dataCode = array(
                'keyCodeStaff' => md5(time()),
                'secret' => md5($username . time()) . md5(time() . $password)
            );
            $this->db->set($dataCode);
            $this->db->where($data);
            $this->db->update('apiauth_staff');

            $this->db->select('a.name, a.username, a.idstore, a.keyCodeStaff, a.secret, b.urlimage');
            $this->db->join('apiauth_staff_images as b', 'b.idauthstaff = a.idauthstaff', 'left');
            $query = $this->db->get_where('apiauth_staff as a', $data)->result();
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'fail';
                return $response;
            }
        }
    }

    public function apiauth_sellercenter($username = '', $password = '') {
        if (empty($username) || empty($password)) {
            return $this->empty_response();
        } else {
            $data = array(
                "username" => $username,
                "password" => md5($password),
                "status" => 0
            );
            $checkAuth = $this->db->get_where('apiauth_staff', $data)->result();
            $dataCode = array(
                'keyCodeStaff' => md5(time()),
                'secret' => md5($username . time()) . md5(time() . $password)
            );
            $this->db->set($dataCode);
            $this->db->where($data);
            $this->db->update('apiauth_staff');

            $this->db->select('a.name, a.username, a.idstore, a.keyCodeStaff, a.secret, b.urlimage');
            $this->db->join('apiauth_staff_images as b', 'b.idauthstaff = a.idauthstaff', 'left');
            $query = $this->db->get_where('apiauth_staff as a', $data)->result();
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'fail';
                return $response;
            }
        }
    }

    public function apiauth_main($username = '', $password = '') {

        if (empty($username) || empty($password)) {
            return $this->empty_response();
        } else {
            $data = array(
                "username" => $username,
                "password" => md5($password),
                "status" => 0
            );

            $checkAuth = $this->db->get_where('apiauth_user', $data)->result();

            $dataCode = array(
                'keyCode' => md5($username . $password)
            );
            $this->db->set($dataCode);
            $this->db->where($data);
            $this->db->update('apiauth_user');

            $this->db->select('a.*, b.urlimage');
            $this->db->join('apiauth_user_images as b', 'b.idauthuser = a.idauthuser', 'left');
            $query = $this->db->get_where('apiauth_user as a', $data)->result();
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'fail';
                return $response;
            }
        }
    }

    public function apiauth_fulfillment($username = '', $password = '') {
        if (empty($username) || empty($password)) {
            return $this->empty_response();
        } else {
            $data = array(
                "username" => $username,
                "password" => md5($password),
                "status" => 0,
                "level" => 2
            );
            $checkAuth = $this->db->get_where('apiauth_staff', $data)->result();
            $dataCode = array(
                'keyCodeStaff' => md5(time()),
                'secret' => md5($username . time()) . md5(time() . $password)
            );
            $this->db->set($dataCode);
            $this->db->where($data);
            $this->db->update('apiauth_staff');

            $this->db->select('a.name, a.username, a.idstore, a.keyCodeStaff, a.secret, b.urlimage');
            $this->db->join('apiauth_staff_images as b', 'b.idauthstaff = a.idauthstaff', 'left');
            $query = $this->db->get_where('apiauth_staff as a', $data)->result();
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['data'] = $query;
                $response['dataApps'] = array(
                    'iconApps'=>'http://bangun.rmall.id/asset/main/image/pavicon.png',
                    'nameApps'=> 'Fullfilment Online System',
                    'logoApps'=> 'https://i1.wp.com/www.rabbanimallonline.com/wp-content/uploads/2018/11/ic_default_image.png?resize=150%2C150'
                );
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'fail';
                return $response;
            }
        }
    }

}
