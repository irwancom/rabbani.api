<?php 

Class Device{

   static function Platform() {
        $is_mobile = false;
		$iphone = strpos($_SERVER['HTTP_USER_AGENT'],"iPhone");
		$android = strpos($_SERVER['HTTP_USER_AGENT'],"Android");
		$palmpre = strpos($_SERVER['HTTP_USER_AGENT'],"webOS");
		$berry = strpos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
		$ipod = strpos($_SERVER['HTTP_USER_AGENT'],"iPod");
		 
		if ($iphone || $ipod == true){
			$type_device = 1;
		}else if ($android == true){
			$type_device = 0;
		}else{
			$type_device = '';
		}
		$no_acak = rand(9,1000000000000000);
		$id_device =  sprintf("%016s", $no_acak);
		return ['type_device' => $type_device, 'id_device' => $id_device];
    }

    static function Network() {
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
			$ip=$_SERVER['REMOTE_ADDR'];
		}

		$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		return $host;
    }


 
}
?>
