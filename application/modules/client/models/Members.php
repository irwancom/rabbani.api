<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Client\Domain\Member\Contracts\MemberRepositoryContract;

class Members extends CI_Model implements MemberRepositoryContract {
    
    const TABLE = 'members';
    const MEMBER = 2;

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    
    public function detailByFields(array $options) {
        $isOwner = false;
        if (isset($options['owner'])) {
            $isOwner = true;
            unset($options['owner']);
        }
        $result = $this->db->get_where(self::TABLE, $options)->result_array();
        if ($result) {
            $user = $result[0];

            if (!$isOwner) {
                unset($user['email']);
                unset($user['phone']);
            }

            unset($user['password']);
            unset($user['secret']);
            unset($user['last_login']);
            unset($user['deleted_at']);
            unset($user['updated_at']);
            
            return $user;
        }

        return null;
    }

}
