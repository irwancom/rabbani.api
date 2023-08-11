<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Andri\Engine\Client\Domain\Order\Contracts\FlashSaleRepositoryContract;

class Flashsales extends CI_Model implements FlashSaleRepositoryContract {

    const TABLE = 'flash_sales';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }


    public function detail(array $condition) {
        $discount = $this->db->get_where(self::TABLE, $condition)->result();
        return count($discount) > 0 ? $discount[0] : null;
    }
    
}
