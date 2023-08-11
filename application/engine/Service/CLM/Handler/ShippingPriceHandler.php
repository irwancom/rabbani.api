<?php
namespace Service\CLM\Handler;

use Service\Entity;
use Service\Delivery;

class ShippingPriceHandler {

	private $delivery;
	private $repository;

	private $user;
	private $admin;
	
	public function __construct ($repository) {
		$this->CI =& get_instance();
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}

    public function getShippingRabbani() {
        $shippingRabbani = array();
        $shippingRabbani[0]['name'] = 'FLAT';
        $shippingRabbani[0]['code'] = 'flat';
        $shippingRabbani[0]['type'] = 'paket';
        $shippingRabbani[0]['price'] = 10000;
        $shippingRabbani[0]['currency'] = 'IDR';
        $shippingRabbani[0]['etd_from'] = '-';
        $shippingRabbani[0]['etd_thru'] = '-';
        $shippingRabbani[0]['times'] = 'D';
        return $shippingRabbani;
    }

	public function getOngkirRabbani($destination = [], $origin = [], $weight = 1) {
        $shippingRabbani = $this->getShippingRabbani();

        $serviceOptions = array();
        foreach($shippingRabbani as $k_ship=>$shipping){
            $setPrice = $shipping['price'];
            if($weight>1){
                $setPrice = $weight*$setPrice;
            }
            $serviceOptions[$k_ship]['origin_name'] = $origin['city_name'];
            $serviceOptions[$k_ship]['destination_name'] = $destination['city_name'];
            $serviceOptions[$k_ship]['service_display'] = $shipping['name'];
            $serviceOptions[$k_ship]['service_code'] = $shipping['code'];
            $serviceOptions[$k_ship]['goods_type'] = $shipping['type'];
            $serviceOptions[$k_ship]['currency'] = $shipping['currency'];
            $serviceOptions[$k_ship]['price'] = $setPrice;
            $serviceOptions[$k_ship]['etd_from'] = $shipping['etd_from'];
            $serviceOptions[$k_ship]['etd_thru'] = $shipping['etd_thru'];
            $serviceOptions[$k_ship]['times'] = $shipping['times'];
        }
        return $serviceOptions;
	}


}