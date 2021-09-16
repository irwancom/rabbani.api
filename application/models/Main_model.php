<?php

class Main_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
        $this->load->helper(array('form', 'url'));
    }

    public function empty_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field tidak boleh kosong';
        return $response;
    }
	
	 public function cart_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Keranjang Anda kosong';
        return $response;
    }
	
    public function voucher_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Voucher Tidak Berlaku';
        return $response;
    }
	 public function voucher2_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Voucher Sudah Kadaluarsa';
        return $response;
    }
	
	public function otp_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Kode OTP Salah';
        return $response;
    }

    public function duplicate_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field Sudah Terdaftar';
        return $response;
    }
	
	public function affiliate_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Kode Affiliate Sudah Ada';
        return $response;
    }
	
	  public function pass_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Data Belum Terdaftar';
        return $response;
    }

    public function token_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Token tidak boleh salah';
        return $response;
    }

    public function verfyAccount($keyCode = '') {
        $data = array(
            "keyCode" => $keyCode
                //"secret" => $secret
        );
        //$this->db->select('c.namestore, a.*');
        //$this->db->Join('store as c', 'c.idstore = a.idstore', 'left' );
        $query = $this->db->get_where('apiauth_user', $data)->result();
        return $query;
    }

    public function debitvoucher($idvoucher_new = '', $debit = '') {
        $this->db->set('voucher_amount', 'voucher_amount-' . $debit, FALSE);
        // $this->db->set('physical', 'physical-' . $debit, FALSE);
        $this->db->where('idvoucher_new', $idvoucher_new);
        // $this->db->where('skuPditails', $sku);
        $this->db->update('voucher_new');
    }

    // public function seasson_profile(){
    // }

    public function logIp($data = '') {
        $data = array(
            'ipaddress' => $data
        );
        $query = $this->db->get_where('logIp', $data)->result();
        if (empty($query)) {
            $data = array(
                'ipaddress' => $data['ipaddress'],
                'ttlHit' => 1,
                'timeAccessStart' => date('Y-m-d H:i:s')
            );

//            $this->db->insert('logIp', $data);
        } else {
            $this->db->set('ttlHit', 'ttlHit+1', FALSE);
            $this->db->set('timeAccessUpdate', date('Y-m-d H:i:s'));
            $this->db->where('ipaddress', $data['ipaddress']);
//            $this->db->update('logIp');
        }

        $datax = array(
            'ipaddress' => $data['ipaddress'],
            'status' => 0
        );
        $queryx = $this->db->get_where('logIp', $datax)->result();
        if (!empty($queryx)) {
            $awal = date_create($query[0]->timeAccessStart);
            $akhir = date_create(); // waktu sekarang
            $diff = date_diff($awal, $akhir);
            if ($diff->i < 1) {
                if ($query[0]->ttlHit > 100) {
                    $this->db->set('status', 1);
                    $this->db->where('ipaddress', $data['ipaddress']);
                    $this->db->update('logIp');
                }
            }
            /* elseif($diff->i<2){
              if($query[0]->ttlHit>35){
              $this->db->set('status', 1);
              $this->db->where('ipaddress', $data['ipaddress']);
              $this->db->update('logIp');
              }
              }
              elseif($diff->i<3){
              if($query[0]->ttlHit>45){
              $this->db->set('status', 1);
              $this->db->where('ipaddress', $data['ipaddress']);
              $this->db->update('logIp');
              }
              }
              elseif($diff->i<5){
              if($query[0]->ttlHit>55){
              $this->db->set('status', 1);
              $this->db->where('ipaddress', $data['ipaddress']);
              $this->db->update('logIp');
              }
              }
              /*elseif($diff->i<10){
              if($query[0]->ttlHit>100){
              $this->db->set('status', 1);
              $this->db->where('ipaddress', $data['ipaddress']);
              $this->db->update('logIp');
              }
              } */
        }

        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function logIpDev($data = '') {
        $data = array(
            'ipaddress' => $data
        );
        $query = $this->db->get_where('logIp', $data)->result();
        if (empty($query)) {
            $data = array(
                'ipaddress' => $data['ipaddress'],
                'ttlHit' => 1,
                'timeAccessStart' => date('Y-m-d H:i:s')
            );

            $this->db->insert('logIp', $data);
        } else {
            $this->db->set('ttlHit', 'ttlHit+1', FALSE);
            $this->db->set('timeAccessUpdate', date('Y-m-d H:i:s'));
            $this->db->where('ipaddress', $data['ipaddress']);
            $this->db->update('logIp');
        }

        $queryx = $this->db->get_where('logIp', $datax)->result();
        if (!empty($queryx)) {
            $awal = date_create($query[0]->timeAccessStart);
            $akhir = date_create(); // waktu sekarang
            $diff = date_diff($awal, $akhir);
            if ($diff->i < 1) {
                if ($query[0]->ttlHit > 10) {
                    $this->db->set('status', 1);
                    $this->db->where('ipaddress', $data['ipaddress']);
                    $this->db->update('logIp');
                }
            } elseif ($diff->i < 2) {
                if ($query[0]->ttlHit > 15) {
                    $this->db->set('status', 1);
                    $this->db->where('ipaddress', $data['ipaddress']);
                    $this->db->update('logIp');
                }
            } elseif ($diff->i < 3) {
                if ($query[0]->ttlHit > 25) {
                    $this->db->set('status', 1);
                    $this->db->where('ipaddress', $data['ipaddress']);
                    $this->db->update('logIp');
                }
            } elseif ($diff->i < 5) {
                if ($query[0]->ttlHit > 35) {
                    $this->db->set('status', 1);
                    $this->db->where('ipaddress', $data['ipaddress']);
                    $this->db->update('logIp');
                }
            } elseif ($diff->i < 10) {
                if ($query[0]->ttlHit > 200) {
                    $this->db->set('status', 1);
                    $this->db->where('ipaddress', $data['ipaddress']);
                    $this->db->update('logIp');
                }
            }
        }

        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function dataProduct($idproduct = '', $sku = '') {
        $this->db->cache_on();
        $this->db->from('product as a');
        $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct', 'left');
        $this->db->where('a.idproduct', $idproduct);
        $this->db->where('b.sku', $sku);
        $query = $this->db->get()->result();
        if (!empty($query)) {
            $query = $query[0];
        } else {
            $query = null;
        }
        return $query;
    }

    public function Category() {
        $this->db->cache_on();
        $this->db->select('a.*,b.urlImage');
        $this->db->where('delcat', '0');
        $this->db->join('category_images as b', 'b.idcategory = a.idcategory', 'left');
        $this->db->order_by('categoryName ASC');
        $dataCat = $this->db->get_where('category as a', array('a.parentidcategory' => 0))->result();
         // print_r($dataCat);
         // exit;
        foreach ($dataCat as $dC) {
            // print_r($dC);
            // exit;
            $this->db->order_by('categoryName ASC');
            $dataSubCat = $this->db->get_where('category', array('parentidcategory' => $dC->idcategory, 'delcat' => 0))->result();
            // print_r($dataSubCat);
            // exit;
            $dataCatx[] = array(
                'idcategory' => $dC->idcategory,
                'categoryName' => $dC->categoryName,
                'imagecategory' => $dC->urlImage,
                'dataSubCat' => $dataSubCat
            );
        }
        $supdate = $dataCatx;
        $this->db->select('a.*,b.urlImage');
        $this->db->join('category_images_icon as b', 'b.idcategory = a.idcategory', 'left');
        $data1 = $this->db->get_where('category as a')->result();

        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCat);
            $response['data'] = $dataCatx;
            $response['icon'] = $data1;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function banner() {
        $this->db->cache_on();
        $this->db->select('*');
        // $this->db->where('delcat', '0');
        //$this->db->join('category_images as b', 'b.idcategory = a.idcategory', 'left');
         $this->db->order_by('idbanner', 'DESC');
        $dataCat = $this->db->get_where('banner')->result();


        if ($dataCat) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCat);
            $response['data'] = $dataCat;
            $response['data'] = $dataCat;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
	
	 public function comment($data = ''){
		 //print_r($data);
		 //exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			// print_r($verify);
			//exit;
            if (!empty($verify)) {
                $db2 = $this->load->database('db2', TRUE);
                $db2->select('*');
                //$this->db->where('idstore', $data[1]);
                $dataCat = $db2->get_where('comment')->result();
            } else {
                return $this->token_response();
            }
			   if ($dataCat) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCat);
            $response['data'] = $dataCat;
            $response['data'] = $dataCat;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

  }
		

    public function getData($page = '') {
		//print_r($data);
		//exit;
        $this->db->cache_on();
        $db2 = $this->load->database('db2', TRUE);
        $db2->select('a.*,c.urlImage');
        $db2->from('product as a');
        //$db2->join('category as b', 'b.idcategory = a.idcategory', 'left');
        $db2->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $db2->where('delproduct', 0);
        $db2->where('status', 0);
        $db2->limit(10, $page);
        $db2->group_by('idproduct');
        $db2->order_by('idproduct', 'DESC');
        $query = $db2->get()->result();
		
        foreach ($query as $q) {
		   
            $db2->select('a.*,b.urlImage as imagesVariable');
            $db2->from('product_ditails as a');
            $db2->where('a.idproduct', $q->idproduct);
            $db2->where('stock>0');
			$db2->order_by('idproduct', 'DESC');
			$db2->group_by('idpditails');
			$db2->where('delproductditails', 0);
            $db2->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
			
            $query1 = $db2->get()->result();

            $dataq = array(
                'idproduct' => $q->idproduct
            );
            $db2->select('*');
            $queryq = $db2->get_where('product_images', $dataq)->result();
		$datax[] = array(
                'product' => $q,
                'totalsku' => count($query),
                'variableProduct' => $query1,
                'imageProduct' => $queryq
            );
          
        }
		
		 
        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	    public function getDataproduct($page = '') {
		//exit;
		 
		 $this->db->where('a.delproduct', 0);
         $this->db->where('a.status', 0);
		 $this->db->where('b.delproductditails', 0);
		 $this->db->where('b.stock>2');
		
		 $this->db->limit(10, $page);
         
         $this->db->join('product_images as c', 'c.idproduct = a.idproduct');
		 $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct');
		 //$this->db->join('product_images_ditails as d', 'd.idpditails = b.idproduct','left');
		 $this->db->group_by('a.idproduct');
		 $this->db->order_by('a.dateCreate', 'DESC');
		 $this->db->order_by('a.timeCreate', 'DESC');
		 $datax= $this->db->get_where('product as a')->result();
		
		
		 
		 
        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	public function getDataproductrandom($page = '') {
        
		 //$this->db->select('a.*,b.*,c.*,d.urlImage as img');
		 $this->db->where('a.delproduct', 0);
         $this->db->where('a.status', 0);
		 $this->db->where('b.delproductditails', 0);
		 $this->db->where('b.stock>2');
		 //$this->db->where('d.urlImage !=', '');
		
        
         $this->db->join('product_images as c', 'c.idproduct = a.idproduct');
		 $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct');
		 //$this->db->join('product_images_ditails as d', 'd.idproduct = a.idproduct','left');
		 $this->db->group_by('a.idproduct');
		 //$this->db->group_by('d.idpditails');
		 $this->db->order_by('a.idproduct', 'RANDOM');
		 $this->db->limit(10, $page);
		 $datax= $this->db->get_where('product as a')->result();

        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	public function getDataproduct200($page = '') {
        
		 
		 $this->db->where('a.delproduct', 0);
		 $this->db->where('b.delproductditails', 0);
		 $this->db->where('b.price<=200000');
		 $this->db->limit(10, $page);
         
         $this->db->join('product_images as c', 'c.idproduct = a.idproduct','left');
		 $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct','left');
		 //$this->db->join('product_images_ditails as d', 'd.idpditails = b.idproduct','left');
		 $this->db->group_by('a.idproduct');
		 $this->db->order_by('a.dateCreate', 'DESC');
		 $this->db->order_by('a.timeCreate', 'DESC');
		 $datax= $this->db->get_where('product as a')->result();

        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function getDatarandom($page = '') {
       $this->db->cache_on();
        $db2 = $this->load->database('db2', TRUE);
        $db2->select('a.*,c.urlImage');
        $db2->from('product as a');



        $db2->join('category as b', 'b.idcategory = a.idcategory', 'left');
        $db2->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $db2->where('delproduct', 0);
        $db2->limit(10, $page);
        //$db2->group_by('idproduct');
        $db2->order_by('idproduct', 'random');
        //$db2->order_by('timeCreate', 'DESC');

        $query = $db2->get()->result();
		//print_r($query);
		//exit;
        foreach ($query as $q) {
            $db2->select('a.*,b.urlImage as imagesVariable');
            $db2->from('product_ditails as a');
            $db2->where('a.idproduct', $q->idproduct);
            $db2->where('stock>0');
			$db2->where('delproductditails', 0);
			$db2->group_by('idpditails');
            $db2->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
            $query1 = $db2->get()->result();

            $dataq = array(
                'idproduct' => $q->idproduct
            );
            $db2->select('*');
            $queryq = $db2->get_where('product_images', $dataq)->result();

            $datax[] = array(
                'product' => $q,
                'totalsku' => count($query),
                'variableProduct' => $query1,
                'imageProduct' => $queryq
            );
        }
        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function getDatasimiliar($page = '') {
        $this->db->cache_on();
        $db2 = $this->load->database('db2', TRUE);
        $db2->select('a.*, c.urlImage');
        $db2->from('product as a');

        $db2->where('delproduct', 0);
        //$db2->where('stock>0');
        $db2->join('category as b', 'b.idcategory = a.idcategory', 'left');
        $db2->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $db2->limit(10, $page);
        $db2->group_by('idproduct');
        $db2->order_by('idproduct', 'RANDOM');
        $query = $db2->get()->result();
        //print_r($query);
        //exit;

        foreach ($query as $q) {
            //   $this->db->select('a.*,b.urlImage as imagesVariable');
            // $this->db->from('product_ditails as a');
            //$this->db->where('a.idproduct', $q->idproduct);
            //$this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
            //$query = $this->db->get()->result();

            $dataq = array(
                'idproduct' => $q->idproduct
            );
            $db2->select('*');
            $queryq = $db2->get_where('product_images', $dataq)->result();

            $datax[] = array(
                'total' => $q,
                'product' => $query,
                //'variableProduct' => $query,
                'imageProduct' => $queryq
            );
        }
        if (!empty($datax)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            //$response['totalData'] = count($datax);
            $response['data'] = $datax;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	// product similiar terbaru
	 public function similiar($data = ''){
		
		// print_r($data);exit;
        
		 $name = $this->db->get_where('product', array('idproduct' => $data[0]))->result();
          // print_r($name[0]->idcategory);exit;
		 
		 $this->db->select('a.idproduct');
		 $this->db->where('e.delproduct', 0);
		 $this->db->where('a.delproductditails', 0);
		 $this->db->where('a.stock>2');
         $this->db->where('e.status',0);
		 $this->db->where('e.idcategory', $name[0]->idcategory);
		 $this->db->limit(5,1);
	     $this->db->group_by('a.idproduct');
		 $this->db->order_by('a.idproduct', 'RANDOM');
		 $this->db->join('product as e', 'e.idproduct = a.idproduct');
		 $this->db->join('product_images_ditails as b', 'b.idpditails = b.idpditails');
		 $datax= $this->db->get_where('product_ditails as a')->result();
		 // print_r($datax);exit;
		 
		 foreach ($datax as $q){
			 $this->db->select('b.idproduct,b.delproduct,e.idpditails,b.productName,e.price,e.realprice');
			 $this->db->group_by('b.idproduct');
			 $this->db->where('e.stock>2');
			 $this->db->where('e.delproductditails', 0);
			 $this->db->where('b.delproduct', 0);
			 //$this->db->join('product_images as c', 'c.idproduct = b.idproduct');
			 $this->db->join('product_ditails as e', 'e.idproduct = b.idproduct');
			 $user = $this->db->get_where('product as b', array('b.idproduct' => $q->idproduct))->result();
			 
			 foreach ($user as $y){
				 //print_r($y);exit;
			 $this->db->select('urlImage');
			 $image = $this->db->get_where('product_images', array('idproduct' => $y->idproduct))->result();

			 //print_r($user);
			 
			 
		 }
			 $dataCatx[] = array(
                        'Product' => $user,
						'Image' => $image
                    );
			
		 }
		 
		  
			 

        if (!empty($dataCatx)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($dataCatx);
            $response['data'] = $dataCatx;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	public function getproductrandom($page = '') {
        // print_r($data);exit;
		 
		 $this->db->select('a.idproduct');
		 $this->db->where('e.delproduct', 0);
                 $this->db->where('e.status', 0);
		 $this->db->where('a.delproductditails', 0);
		 $this->db->where('a.stock>2');
		 $this->db->where('d.urlImage !=',"");
		 $this->db->limit(10, $page);
	     $this->db->group_by('e.idproduct');
		 $this->db->order_by('a.idproduct', 'RANDOM');
		 // $this->db->group_by('d.idpditails');
		//$this->db->where('idproduct',0);
	   //$datax = $this->db->delete('product_images_ditails');
         //$this->db->join('product_images as c', 'c.idproduct = a.idproduct');
		 $this->db->join('product as e', 'e.idproduct = a.idproduct');
		 $this->db->join('product_images_ditails as d', 'd.idpditails = a.idpditails');
		 $datax= $this->db->get_where('product_ditails as a')->result();
		 //print_r($datax);exit;
		 
		 foreach ($datax as $q){
			 $this->db->select('b.idproduct,b.delproduct,e.*,b.productName,e.price,e.realprice');
			 $this->db->group_by('b.idproduct');
			 $this->db->where('e.stock>2');
			 $this->db->where('e.delproductditails', 0);
			 $this->db->where('b.delproduct', 0);
			 //$this->db->join('product_images as c', 'c.idproduct = b.idproduct');
			 $this->db->join('product_ditails as e', 'e.idproduct = b.idproduct');
			 $user = $this->db->get_where('product as b', array('b.idproduct' => $q->idproduct))->result();
			 
			 foreach ($user as $y){
				 //print_r($y);exit;
			 $this->db->select('urlImage');
			 $image = $this->db->get_where('product_images', array('idproduct' => $y->idproduct))->result();

			 //print_r($user);
			 
			 
		 }
			 $dataCatx[] = array(
                        'Product' => $user,
						'Image' => $image
                    );
			
		 }
		 
		  
			 

        if (!empty($dataCatx)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($dataCatx);
            $response['data'] = $dataCatx;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	public function getproductcat($data = '') {
        
		// print_r($data);exit;
		 $this->db->select('a.idproduct');
		 $this->db->where('e.delproduct', 0);
		 $this->db->where('a.delproductditails', 0);
		 $this->db->where('a.stock>2');
		 $this->db->where('e.idcategory',$data[0]);
		 $this->db->limit(10, $data[1]);
	     $this->db->group_by('a.idproduct');
		 $this->db->order_by('a.idproduct', 'RANDOM');
		 // $this->db->group_by('d.idpditails');
		//$this->db->where('idproduct',0);
	   //$datax = $this->db->delete('product_images_ditails');
         //$this->db->join('product_images as c', 'c.idproduct = a.idproduct');
		 $this->db->join('product as e', 'e.idproduct = a.idproduct');
		 $this->db->join('product_images_ditails as d', 'd.idproduct = a.idproduct');
		 $datax= $this->db->get_where('product_ditails as a')->result();
		 //print_r($datax);exit;
		 
		 foreach ($datax as $q){
			 $this->db->select('b.idproduct,b.idcategory,e.idpditails,b.productName,e.price,e.realprice');
			 $this->db->group_by('b.idproduct');
			 $this->db->where('e.stock>2');
			 $this->db->where('e.delproductditails', 0);
			 $this->db->where('b.delproduct', 0);
			 //$this->db->join('product_images as c', 'c.idproduct = b.idproduct');
			 $this->db->join('product_ditails as e', 'e.idproduct = b.idproduct');
			 $user = $this->db->get_where('product as b', array('b.idproduct' => $q->idproduct))->result();
			 
			 foreach ($user as $y){
				 //print_r($y);exit;
			 $this->db->select('urlImage');
			 $image = $this->db->get_where('product_images', array('idproduct' => $y->idproduct))->result();

			 //print_r($user);
			 
			 
		 }
			 $dataCatx[] = array(
                        'Product' => $user,
						'Image' => $image
                    );
			
		 }
		 
		  
			 

        if (!empty($dataCatx)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($dataCatx);
            $response['data'] = $dataCatx;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function getDataByCat($data = '') {
         $this->db->where('a.delproduct', 0);
		 $this->db->where('b.delproductditails', 0);
		 $this->db->where('b.stock>2');
		 $this->db->where('a.idcategory', $data[0]);
         
         $this->db->join('product_images as c', 'c.idproduct = a.idproduct','left');
		 $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct','left');
		 //$this->db->join('product_images_ditails as d', 'd.idpditails = b.idproduct','left');
		 $this->db->group_by('a.idproduct');
		 $this->db->order_by('a.idproduct', 'DESC');
		 $datax= $this->db->get_where('product as a')->result();
       
            if (!empty($datax)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['totalData'] = count($datax);
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        
    }

    public function productDetailsnewold($data = '') {
			$this->db->select('a.*,d.urlImage');
			$this->db->where('e.delproduct', 0);
			$this->db->where('a.delproductditails', 0);
			$this->db->where('a.stock>2');
		    $this->db->where('a.idproduct',$data[0]);
			//$this->db->limit(10, $page);
	    // $this->db->group_by('a.idproduct');
			//$this->db->order_by('a.idproduct', 'RANDOM');
		   $this->db->group_by('a.idpditails');
		//$this->db->where('idproduct',0);
	   //$datax = $this->db->delete('product_images_ditails');
         //$this->db->join('product_images as c', 'c.idproduct = a.idproduct');
			$this->db->join('product as e', 'e.idproduct = a.idproduct');
			$this->db->join('product_images_ditails as d', 'd.idproduct = a.idproduct');
			$datax= $this->db->get_where('product_ditails as a')->result();
        

            if (!empty($datax)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['totalData'] = count($datax);
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    
	
	public function ditailsGetData($data = '') {
        $this->db->cache_on();
		$datax = array(
                   
                    'dateview' => date('Y-m-d'),
                    'ip' => ($data[1]),
                    'idauthuser' => ($data[2]),
					'idproduct' => ($data[0])
					
                );
                // $this->db->where()('idcart');
        $this->db->insert('log_view', $datax);
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $db2 = $this->load->database('db2', TRUE);
            $db2->select('a.*,b.*');
            $db2->from('product as a');
            $db2->join('category as b', 'b.idcategory = a.idcategory');

            $db2->where('a.idproduct', $data[0]);

            $query = $db2->get()->result();
			//print_r($query);
			//exit;

            foreach ($query as $x) {
                $db2->select('size');
                //$db2->from('product_ditails');
                $db2->where('idproduct', $x->idproduct);
                $db2->where('delproductditails', 0);
                $db2->where('stock>2');
                $db2->group_by('size');
                $query1 = $db2->get_where(product_ditails)->result();
				
				
            }



            foreach ($query as $x) {
                $db2->select('a.collor');
                $db2->from('product_images_ditails as a');
				$db2->join('product_ditails as b', 'b.idpditails = a.idpditails');
                $db2->where('a.idproduct', $x->idproduct);
                $db2->where('b.delproductditails', 0);
                $db2->where('b.stock>2');
                $db2->group_by('collor');
                $query2 = $db2->get()->result();
				
			
            }

            foreach ($query as $x) {
                $db2->select('a.idpditails,a.size,a.collor,a.realprice,a.priceDiscount,a.price,a.stock');
                $db2->from('product_ditails as a');
				$db2->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
                $db2->where('a.delproductditails', 0);
                $db2->where('a.stock>2');
                $db2->where('b.idproduct', $x->idproduct);
				$db2->group_by('a.idpditails');
                $query3 = $db2->get()->result();
					//print_r($query3);
				//exit;
            }

            foreach ($query as $q) {
                $db2->select('a.*,b.urlImage as imagesVariable ,c.productName');
                $db2->from('product_ditails as a');
                //$this->db->group_by('a.size');
                $db2->where('a.idproduct', $q->idproduct);
                $db2->where('delproductditails', 0);
				
				$db2->where('a.stock>2');
                $db2->group_by('a.collor');

				$db2->join('product as c', 'c.idproduct = a.idproduct');	
                $db2->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
                $db2->where('b.urlImage!=""');
                $query = $db2->get()->result();

                $dataq = array(
                    'idproduct' => $q->idproduct
                );
                $db2->select('urlImage, imageFile');
				//$db2->group_by('idProducts');
                $queryq = $db2->get_where('product_images', $dataq)->result();
                //print_r($queryq);
				//exit;
                $datax[] = array(
                    'product' => $q,
                    'totalsku' => count($query),
                    'variableProduct' => $query,
                    'size' => $query1,
                    'collor' => $query2,
                    'varian' => $query3,
                    'imageProduct' => $queryq
                );
            }

            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['totalData'] = count($datax);
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }
	
	public function productDetailsnew($data = '') {
        $this->db->cache_on();
		$datax = array(
                   
                    'dateview' => date('Y-m-d'),
                    'ip' => ($data[1]),
                    'idauthuser' => ($data[2]),
					'idproduct' => ($data[0])
					
                );
                // $this->db->where()('idcart');
        $this->db->insert('log_view', $datax);
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $db2 = $this->load->database('db2', TRUE);
            $db2->select('a.*,b.*');
            $db2->from('product as a');
            $db2->join('category as b', 'b.idcategory = a.idcategory');

            $db2->where('a.idproduct', $data[0]);

            $query = $db2->get()->result();
			//print_r($query);
			//exit;

            foreach ($query as $x) {
                $db2->select('size');
                //$db2->from('product_ditails');
                $db2->where('idproduct', $x->idproduct);
                $db2->where('delproductditails', 0);
                $db2->where('stock>2');
                $db2->group_by('size');
                $query1 = $db2->get_where(product_ditails)->result();
				
				
            }



            foreach ($query as $x) {
                $db2->select('a.collor');
                $db2->from('product_images_ditails as a');
				$db2->join('product_ditails as b', 'b.idpditails = a.idpditails');
                $db2->where('a.idproduct', $x->idproduct);
                $db2->where('b.delproductditails', 0);
                $db2->where('b.stock>2');
                $db2->group_by('collor');
                $query2 = $db2->get()->result();
				
			
            }

            foreach ($query as $x) {
                $db2->select('a.idpditails,a.size,a.collor,a.realprice,a.priceDiscount,a.price,a.stock');
                $db2->from('product_ditails as a');
				$db2->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
                $db2->where('a.delproductditails', 0);
                $db2->where('a.stock>2');
                $db2->where('b.idproduct', $x->idproduct);
				$db2->group_by('a.idpditails');
                $query3 = $db2->get()->result();
					//print_r($query3);
				//exit;
            }

            foreach ($query as $q) {
                $db2->select('a.*,b.urlImage,c.productName');
                $db2->from('product_ditails as a');
                //$this->db->group_by('a.size');
                $db2->where('a.idproduct', $q->idproduct);
                $db2->where('delproductditails', 0);
				
				$db2->where('a.stock>2');
                $db2->group_by('a.collor');

				$db2->join('product as c', 'c.idproduct = a.idproduct');	
                $db2->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
                $db2->where('b.urlImage!=""');
                $query = $db2->get()->result();

                $dataq = array(
                    'idproduct' => $q->idproduct
                );
                $db2->select('urlImage, imageFile');
				//$db2->group_by('idProducts');
                $queryq = $db2->get_where('product_images', $dataq)->result();
                //print_r($queryq);
				//exit;
                $datax[] = array(
                    'product' => $q,
                    'totalsku' => count($query),
                    'variableProduct' => $query,
                    'size' => $query1,
                    'collor' => $query2,
                    'varian' => $query3,
                    'imageProduct' => $queryq
                );
            }

            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['totalData'] = count($datax);
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }


    public function ditailsSize($data = '') {
        $this->db->cache_on();
        if (empty($data[0])) {
            return $this->empty_response();
        } else {

            $this->db->select('a.*,b.*');
            $this->db->from('product as a');
            $this->db->join('category as b', 'b.idcategory = a.idcategory', 'left');

            $this->db->where('a.idproduct', $data[0]);

            $query = $this->db->get()->result();

            foreach ($query as $q) {
                $this->db->select('size');
                $this->db->from('product_ditails');
                $this->db->where('idproduct', $q->idproduct);
                $this->db->group_by('size');


                //$this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                $query = $this->db->get()->result();

                $dataq = array(
                    'idproduct' => $q->idproduct
                );
                $this->db->select('urlImage, imageFile');
                $queryq = $this->db->get_where('product_images', $dataq)->result();
                //$this->db->where('a.idproduct', $q->idproduct);
                $datax[] = array(
                    'product' => $q,
                    'totalsku' => count($query),
                    'variableProduct' => $query,
                    'imageProduct' => $queryq
                );
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['totalData'] = count($datax);
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }
	
	//  public function addOrders($data = '') {


    //     if (empty($data[0])) {
    //         return $this->empty_response();
    //     } else {
    //         $verify = $this->verfyAccount($data[0]);

    //         if (!empty($verify)) {


    //             $data = json_decode($data[2]);

    //             $dataTrx = array(
    //                 'timeCreate' => date('H:i:s'),
    //                 'dateCreate' => date('Y-m-d'),
    //                 'noInvoice' => $verify[0]->idauthuser . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
    //                 'shipping' => ($data->shipping),
    //                 'shippingprice' => ($data->shippingprice),
    //                 'idauthuser' => $verify[0]->idauthuser,
    //                 'idpeople' => ($data->idpeople),
    //                 'payment' => ($data->payment)
    //             );

    //             $supdate = $this->db->insert('transaction', $dataTrx);
    //             $insert_id = $this->db->insert_id();


    //             if (!empty($data)) {
    //                 foreach ($data->dataOrders as $dO) {
    //                     $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
    //                     $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');

    //                     $dataProduct = $this->db->get_where('shop_cart as a', array('a.idcart' => $dO->idcart))->result();
    //                     //print_r($dataProduct);
    //                     //exit;
    //                     $voucher = $this->db->get_where('voucher', array('vouchercode' => $data->voucher))->result();
	// 					//print_r($voucher);
	// 					//exit;
    //                     if (!empty($dataProduct)) {
    //                         $dataOrdersx = array(
    //                             'idtransaction' => $insert_id,
    //                             'idproduct' => $dataProduct[0]->idproduct,
    //                             'idpditails' => $dataProduct[0]->idpditails,
    //                             'productName' => $dataProduct[0]->productName,
    //                             'skuPditails' => $dataProduct[0]->skuPditails,
    //                             'voucher' => $voucher[0]->voucherdisc,
    //                             'collor' => $dataProduct[0]->collor,
    //                             'size' => $dataProduct[0]->size,
    //                             'price' => $dataProduct[0]->price,
    //                             'disc' => $dataProduct[0]->priceDiscount * $dataProduct[0]->qty,
    //                             'qty' => $dataProduct[0]->qty,
    //                             'weight' => ($dataProduct[0]->weight) * $dataProduct[0]->qty,
    //                             'subtotal' => ($dataProduct[0]->price) * $dataProduct[0]->qty
    //                         );
                            
    //                         $subtotal[] = $dataOrdersx['subtotal'];
    //                         $subdisc[] = $dataOrdersx['disc'];
    //                         $totalweight[] = ($dataOrdersx['weight']);


    //                         $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataProduct[0]->qty);
    //                         $this->db->insert('transaction_details', $dataOrdersx);
    //                         $this->db->where('idcart', $dO->idcart);
    //                         $this->db->delete('shop_cart');
    //                     }
    //                 }

    //                 $cost = $data->shippingprice * ceil(array_sum($totalweight) / 1000);

    //                 $this->db->set('cost', ($cost), true);
    //                 $this->db->set('subtotal', array_sum($subtotal), true);
    //                 $this->db->set('discount', array_sum($subdisc), true);
    //                 $sql = $this->db->query("SELECT vouchercode FROM voucher where vouchercode ='$data->voucher'");
    //                 $cek_id = $sql->num_rows();
										
    //                 if ($cek_id > 0) {
    //                    $voucher = $this->db->get_where('voucher', array('vouchercode' => $data->voucher))->result();
    //                     $voucher1 = 0; 
    //                 } else {
    //                     $voucher1 = 0;
    //                 }
	// 				//$this->db->insert('transaction_details', array('discvoucher' => $voucher1));
    //                 $total = (array_sum($subtotal) + ($cost) - array_sum($subdisc) - ($voucher1) + $data->kodeunik);
    //                 $this->db->set('discvoucher',$voucher1);
    //                 $this->db->set('totalpay', array_sum($subtotal) + ($cost) - array_sum($subdisc) - ($voucher1)+ $data->kodeunik, true);
    //                 $this->db->where('idtransaction', $insert_id);
    //                 $this->db->update('transaction');

	// 				$people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();

    //                 //$message = 'rmall.id : Pesanan Berhasil, Total Transfers Rp ' . $total . ', Rekening : BCA 7771503334, MANDIRI 1310012668739, BNI 308050850 AN Rabbani Asysa, Jazakallah';
    //                 //$message1 = 'order ' .$people[0]->name.' ';
	// 				#$this->load->library('sms');
	// 				//$notif = '081386118382';
    //                // $this->sms->SendSms($verify[0]->hp, $message);
	// 				//$this->sms->SendSms($people[0]->phone, $message);
	// 				//$this->sms->SendSms($notif, $message1);
				
    //             }
    //         } else {
    //             return $this->token_response();
    //         }




    //         if (!empty($dataProduct)) {
    //             $response['status'] = 200;
    //             $response['error'] = false;
    //             $response['message'] = 'Data successfully processed.';
    //             $response['dataTransaction'] = array(
    //                 'ordersDay' => $dataTrx['dateCreate'],
    //                 //'corp' => $dataTrx['orderBy'],
    //                 'noInvoice' => $dataTrx['noInvoice'],
    //                 'shipping' => $dataTrx['shipping'],
    //                 'VocherDiscount' => $voucher1,
    //                     // 'addressSender' => $dataTrx['addressSender'],
    //                     // 'addressRecipient' => $dataTrx['addressRecipient'],
    //             );
    //             return $response;
    //         } else {
    //             $response['status'] = 502;
    //             $response['error'] = true;
    //             $response['message'] = 'Data failed to receive.';
    //             return $response;
    //         }
    //     }
    // }
	
	public function addOrders1	($data = '') {
//print_r($data);exit;

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {


                $data = json_decode($data[2]);
				//print_r($data->dataOrders);exit;

                $dataTrx = array(
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'noInvoice' => $verify[0]->idauthuser . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
                    'shipping' => ($data->shipping),
                    'shippingprice' => ($data->shippingprice),
                    'idauthuser' => $verify[0]->idauthuser,
                    'idpeople' => ($data->idpeople),
                    'payment' => ($data->payment),
					'voucher' => ($data->voucher)
					
                );

                $supdate = $this->db->insert('transaction', $dataTrx);
                $insert_id = $this->db->insert_id();


                if (!empty($data)) {
                    foreach ($data->dataOrders as $dO) {
						//print_r($dO);exit;
                        $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
                        $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');

                        $dataProduct = $this->db->get_where('shop_cart as a', array('a.idcart' => $dO->idcart))->result();
                        //print_r($dataProduct);
                        //exit;
                        $voucher = $this->db->get_where('voucher', array('vouchercode' => $data->voucher))->result();
						//print_r($voucher);
						//exit;
                        if (!empty($dataProduct)) {
                            $dataOrdersx = array(
                                'idtransaction' => $insert_id,
                                'idproduct' => $dataProduct[0]->idproduct,
                                'idpditails' => $dataProduct[0]->idpditails,
                                'productName' => $dataProduct[0]->productName,
                                'skuPditails' => $dataProduct[0]->skuPditails,
                                'voucher' => $voucher[0]->voucherdisc,
                                'collor' => $dataProduct[0]->collor,
                                'size' => $dataProduct[0]->size,
                                'price' => $dataProduct[0]->price,
                                'disc' => $dataProduct[0]->priceDiscount * $dataProduct[0]->qty,
                                'qty' => $dataProduct[0]->qty,
                                'weight' => ($dataProduct[0]->weight) * $dataProduct[0]->qty,
                                'subtotal' => ($dataProduct[0]->realprice) * $dataProduct[0]->qty
                            );
                            
                            $subtotal[] = $dataOrdersx['subtotal'];
							 
                            $subdisc[] = $dataOrdersx['disc'];
                            $totalweight[] = ($dataOrdersx['weight']);


                            $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataProduct[0]->qty);
                            $this->db->insert('transaction_details', $dataOrdersx);
							
                            
                        }
                    }
					$this->db->where('idauthuser', $verify[0]->idauthuser);
                    $this->db->delete('shop_cart');

                    $cost = $data->shippingprice ;
                    $this->db->set('cost', ($cost), true);
                    $this->db->set('subtotal', array_sum($subtotal), true);
                    $this->db->set('discount', array_sum($subdisc), true);
                    $sql = $this->db->query("SELECT vouchercode FROM voucher where vouchercode ='$data->voucher'");
                    $cek_id = $sql->num_rows();
					//print_r($sql);
					//exit;
					   //print_r(array_sum($subtotal));
					  // exit;
                    if ($cek_id > 0) {
						$voucher = $this->db->get_where('voucher', array('vouchercode' => $data->voucher))->result();
							$cek_voucher_discount = substr($voucher[0]->voucherdisc, -1);
							if($cek_voucher_discount=='%'){
							$crack_voucher_disc = explode('%', $voucher[0]->voucherdisc);
							$this_nominal_disc = (array_sum($subtotal))*($crack_voucher_disc[0]/100);
							$voucher1 = $this_nominal_disc;
						}else{
							$voucher1 = $voucher[0]->voucherdisc;
						}
						   //print_r($this_nominal_disc);
					   ///exit;
					   
                        //$voucher1 = $voucher[0]->voucherdisc ;
						 
						 
						 
                    } else {
                        $voucher1 = 0;
                    }
					//$this->db->insert('transaction_details', array('discvoucher' => $voucher1));
                    $total = (array_sum($subtotal) - ($voucher1) + $data->kodeunik + ($cost));
                    $this->db->set('discvoucher',$voucher1);
                    $this->db->set('totalpay', array_sum($subtotal)  - ($voucher1)+ $data->kodeunik + ($cost), true);
                    $this->db->where('idtransaction', $insert_id);
                    $this->db->update('transaction');

					$people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();

                    //$message = 'rmall.id : Pesanan Berhasil, Total Transfers Rp ' . $total . ', Rekening : BCA 7771503334, MANDIRI 1310012668739, BNI 308050850 AN Rabbani Asysa, Jazakallah';
                    //$message1 = 'order ' .$people[0]->name.' ';
					#$this->load->library('sms');
					//$notif = '081386118382';
                   // $this->sms->SendSms($verify[0]->hp, $message);
					//$this->sms->SendSms($people[0]->phone, $message);
					//$this->sms->SendSms($notif, $message1);
				
                }
            } else {
                return $this->token_response();
            }

            if (!empty($dataProduct)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['dataTransaction'] = array(
                    'ordersDay' => $dataTrx['dateCreate'],
                    //'corp' => $dataTrx['orderBy'],
                    'noInvoice' => $dataTrx['noInvoice'],
                    'shipping' => $dataTrx['shipping'],
                    'VocherDiscount' => $voucher1,
					'Ditailsproduct' => $dataOrdersx,
                        // 'addressSender' => $dataTrx['addressSender'],
                        // 'addressRecipient' => $dataTrx['addressRecipient'],
                );
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function addOrders2	($data = '') {
        // print_r($data);exit;
        
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {


                $data = json_decode($data[2]);
				//print_r($data->dataOrders);exit;

                $dataTrx = array(
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'noInvoice' => $verify[0]->idauthuser . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
                    'shipping' => ($data->shipping),
                    'shippingprice' => ($data->shippingprice),
                    'idauthuser' => $verify[0]->idauthuser,
                    'idpeople' => ($data->idpeople),
                    'payment' => ($data->payment),
					'voucher' => ($data->voucher),
                    'uniquecode' => ($data->kodeunik)
					
                );

                $supdate = $this->db->insert('transaction', $dataTrx);
                $insert_id = $this->db->insert_id();


                if (!empty($data)) {
                    foreach ($data->dataOrders as $dO) {
						//print_r($dO);exit;
                        $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
                        $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');

                        $dataProduct = $this->db->get_where('shop_cart as a', array('a.idcart' => $dO->idcart))->result();
                         // print_r($dataProduct);exit;
                        
                        if (!empty($dataProduct)) {
                            $dataOrdersx = array(
                                'idtransaction' => $insert_id,
                                'idproduct' => $dataProduct[0]->idproduct,
                                'idpditails' => $dataProduct[0]->idpditails,
                                'productName' => $dataProduct[0]->productName,
                                'skuPditails' => $dataProduct[0]->skuPditails,
                               // 'voucher' => $data->voucher,
                                'collor' => $dataProduct[0]->collor,
                                'size' => $dataProduct[0]->size,
                                'price' => $dataProduct[0]->price,
                                'disc' => $dataProduct[0]->priceDiscount * $dataProduct[0]->qty,
                                'qty' => $dataProduct[0]->qty,
                                'weight' => ($dataProduct[0]->weight) * $dataProduct[0]->qty,
                                'subtotal' => ($dataProduct[0]->realprice) * $dataProduct[0]->qty
                            );
                            
                            $subtotal[] = $dataOrdersx['subtotal'];
							 
                            $subdisc[] = $dataOrdersx['disc'];
                            $totalweight[] = ($dataOrdersx['weight']);


                            // $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataProduct[0]->qty);
                             $this->db->insert('transaction_details', $dataOrdersx);
							
                            
                        }
                    }
					// $this->db->where('idauthuser', $verify[0]->idauthuser);
                    // $this->db->delete('shop_cart');

                 
                    $voucher = $this->db->get_where('voucher_new', array('voucher_code' => $data->voucher))->result();
                      // print_r($voucher);exit;         

                    if (!empty($voucher)) {

                        // if ($voucher[0]->minimal_order <= array_sum($subtotal) ) {

                            if ($voucher[0]->voucher_type == 1) {
                              if ($voucher[0]->voucher_value == 1) {
                                 
                                $this_nominal_disc = (array_sum($subtotal))*($voucher[0]->discount/100);

                                 // print_r($this_nominal_disc);exit;
                                   if ($this_nominal_disc > $voucher[0]->max_discount) {
                                    $voucher1 = $voucher[0]->max_discount;
                                    $discount = $voucher1;
                                    $ongkir = $data->shippingprice ;
                                    $this->debitvoucher($voucher[0]->idvoucher_new,1);
                                    // print_r($voucher1);exit;
                                     } else {
                                    $voucher1 = ceil($this_nominal_disc);
                                    $discount = $voucher1;
                                    $ongkir = $data->shippingprice ;
                                    $this->debitvoucher($voucher[0]->idvoucher_new,1);
                                    // print_r($voucher1);exit;
                                     }
                                 // $voucher1 = $this_nominal_disc;
                                 //  $this->debitvoucher($voucher[0]->idvoucher_new,1);
                                 // print_r($voucher1);exit;
                              } else if ($voucher[0]->voucher_value == 2) {
                                 $voucher1 = $voucher[0]->discount;
                                 $discount = $voucher1;
                                 $ongkir = $data->shippingprice ;
                                 $this->debitvoucher($voucher[0]->idvoucher_new,1);
                                  // print_r($voucher1);exit;
                              } 
                        } else if ($voucher[0]->voucher_type == 2) {

                             if ($voucher[0]->voucher_value == 1) {

                                $this_nominal_disc = ($data->shippingprice)*($voucher[0]->discount/100);
                                  // print_r($this_nominal_disc);exit;

                                  if ($this_nominal_disc <= $voucher[0]->max_discount) {
                                    $ongkir = $data->shippingprice - $data->shippingprice;
                                    $discount = $ongkir;
                                    $voucher1 = 0 ;
                                    $this->debitvoucher($voucher[0]->idvoucher_new,1);
                                       // print_r(     $ongkir);exit;
                                     } else {
                                    $ongkir = $data->shippingprice-$voucher[0]->max_discount;
                                    $discount = $ongkir;
                                    $voucher1 = 0 ;
                                    $this->debitvoucher($voucher[0]->idvoucher_new,1);
                                       // print_r($voucher[0]->max_discount);exit;
                                     }


                             } else if ($voucher[0]->voucher_value == 2) {
                                 if ($voucher[0]->discount >= $data->shippingprice ) {
                                    $ongkir = $data->shippingprice - $data->shippingprice;
                                    $discount = $ongkir;
                                    $voucher1 = 0 ;
                                    $this->debitvoucher($voucher[0]->idvoucher_new,1);

                                 } else {
                                    $ongkir = $data->shippingprice - $voucher[0]->discount;
                                    $discount = $ongkir;
                                    $voucher1 = 0 ;
                                    $this->debitvoucher($voucher[0]->idvoucher_new,1);
                                }
                               // print_r($ongkir);exit;
                             }
                          
                                } 
                    //     } else {
                    // $voucher1 = 0;
                    // $ongkir = $data->shippingprice ;
                    //  // return $this->voucher_response();
                    //     }

                 } else {
                    $voucher1 = 0;
                    $ongkir = $data->shippingprice ;
                    $discount = $voucher1;
                     // return $this->voucher_response();
                }

                    $cost = $ongkir ;
                    $this->db->set('cost', ($cost), true);
                    $this->db->set('subtotal', array_sum($subtotal), true);
                    $this->db->set('discount', array_sum($subdisc), true);




                    $total = (array_sum($subtotal) - ($voucher1) + $data->kodeunik + ($cost));
                    $this->db->set('discvoucher',$discount);
                    $this->db->set('totalpay', array_sum($subtotal)  - ($voucher1)+ $data->kodeunik + ($cost), true);
                    $this->db->where('idtransaction', $insert_id);
                    $this->db->update('transaction');

					$people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();

                    //$message = 'rmall.id : Pesanan Berhasil, Total Transfers Rp ' . $total . ', Rekening : BCA 7771503334, MANDIRI 1310012668739, BNI 308050850 AN Rabbani Asysa, Jazakallah';
                    //$message1 = 'order ' .$people[0]->name.' ';
					#$this->load->library('sms');
					//$notif = '081386118382';
                   // $this->sms->SendSms($verify[0]->hp, $message);
					//$this->sms->SendSms($people[0]->phone, $message);
					//$this->sms->SendSms($notif, $message1);
				
                }
            } else {
                return $this->token_response();
            }

            if (!empty($dataProduct)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['dataTransaction'] = array(
                    'ordersDay' => $dataTrx['dateCreate'],
                    //'corp' => $dataTrx['orderBy'],
                    'noInvoice' => $dataTrx['noInvoice'],
                    'shipping' => $dataTrx['shipping'],
                    'VocherDiscount' => $voucher1,
					'Ditailsproduct' => $dataOrdersx,
                        // 'addressSender' => $dataTrx['addressSender'],
                        // 'addressRecipient' => $dataTrx['addressRecipient'],
                );
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }
// 	public function addOrdersnew($data = '') {
// //print_r($data);exit;

//         if (empty($data[0])) {
//             return $this->empty_response();
//         } else {
//             $verify = $this->verfyAccount($data[0]);

//             if (!empty($verify)) {
// 				//

//                 $datax = json_decode($data[2]);
// 				//print_r($datax);exit;
//                 $dataTrx = array(
//                     'timeCreate' => date('H:i:s'),
//                     'dateCreate' => date('Y-m-d'),
//                     'noInvoice' => $verify[0]->idauthuser . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
//                     'shipping' => ($datax->shipping),
//                     'shippingprice' => ($datax->shippingprice),
//                     'idauthuser' => $verify[0]->idauthuser,
//                     'idpeople' => ($datax->idpeople),
//                     'payment' => ($datax->payment)
//                 );

//                 $supdate = $this->db->insert('transaction', $dataTrx);
//                 $insert_id = $this->db->insert_id();
// 				//print_r($insert_id);exit;

//                 if (!empty($datax)) {
// 					//print_r($datax);exit;
//                     foreach ($datax->dataOrders as $dO) {
// 						//print_r($dO);exit;
//                         $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
//                         $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');

//                         $dataProduct = $this->db->get_where('shop_cart as a', array('a.idcart' => $dO->idcart))->result();
                       
//                         $voucher = $this->db->get_where('voucher', array('vouchercode' => $datax->voucher))->result();
// 						 if (empty($voucher)) {
// 							 $voucher[0]->voucherdisc = 0;
// 						 } else {
// 							 $voucher = $this->db->get_where('voucher', array('vouchercode' => $datax->voucher))->result();
// 						 }
// 						 //print_r($voucher);exit;
//                         if (!empty($dataProduct)) {
//                             $dataOrdersx = array(
//                                 'idtransaction' => $insert_id,
//                                 'idproduct' => $dataProduct[0]->idproduct,
//                                 'idpditails' => $dataProduct[0]->idpditails,
//                                 'productName' => $dataProduct[0]->productName,
//                                 'skuPditails' => $dataProduct[0]->skuPditails,
//                                 'voucher' => $voucher[0]->voucherdisc,
//                                 'collor' => $dataProduct[0]->collor,
//                                 'size' => $dataProduct[0]->size,
//                                 'price' => $dataProduct[0]->price,
//                                 'disc' => $dataProduct[0]->priceDiscount * $dataProduct[0]->qty,
//                                 'qty' => $dataProduct[0]->qty,
//                                 'weight' => ($dataProduct[0]->weight) * $dataProduct[0]->qty,
//                                 'subtotal' => ($dataProduct[0]->realprice) * $dataProduct[0]->qty
//                             );
//                             //print_r($dataOrdersx);exit;
//                             $subtotal[] = $dataOrdersx['subtotal'];
							 
//                             $subdisc[] = $dataOrdersx['disc'];
//                             $totalweight[] = ($dataOrdersx['weight']);
//                             $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataProduct[0]->qty);
//                             $this->db->insert('transaction_details', $dataOrdersx);
							
                            
//                         }
//                     }
					
// 					$this->db->where('idauthuser', $verify[0]->idauthuser);
// 					$this->db->delete('shop_cart'); 
//                     $cost = $data->shippingprice ;
//                     $this->db->set('cost', ($cost), true);
//                     $this->db->set('subtotal', array_sum($subtotal), true);
//                     $this->db->set('discount', array_sum($subdisc), true);
//                     $sql = $this->db->query("SELECT vouchercode FROM voucher where vouchercode ='$data->voucher'");
//                     $cek_id = $sql->num_rows();
// 					//print_r($sql);
// 					//exit;
// 					   //print_r(array_sum($subtotal));
// 					  // exit;
//                     if ($cek_id > 0) {
// 						$voucher = $this->db->get_where('voucher', array('vouchercode' => $data->voucher))->result();
// 							$cek_voucher_discount = substr($voucher[0]->voucherdisc, -1);
// 							if($cek_voucher_discount=='%'){
// 							$crack_voucher_disc = explode('%', $voucher[0]->voucherdisc);
// 							$this_nominal_disc = (array_sum($subtotal))*($crack_voucher_disc[0]/100);
// 							$voucher1 = $this_nominal_disc;
// 						}else{
// 							$voucher1 = $voucher[0]->voucherdisc;
// 						}
// 						   //print_r($this_nominal_disc);
// 					   ///exit;
					   
//                         //$voucher1 = $voucher[0]->voucherdisc ;
						 
						 
						 
//                     } else {
//                         $voucher1 = 0;
//                     }
// 					//$this->db->insert('transaction_details', array('discvoucher' => $voucher1));
//                     $total = (array_sum($subtotal) - ($voucher1) + $data->kodeunik + ($cost));
//                     $this->db->set('discvoucher',$voucher1);
//                     $this->db->set('totalpay', array_sum($subtotal)  - ($voucher1)+ $data->kodeunik + ($cost), true);
//                     $this->db->where('idtransaction', $insert_id);
//                     $this->db->update('transaction');

// 					$people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();

//                     //$message = 'rmall.id : Pesanan Berhasil, Total Transfers Rp ' . $total . ', Rekening : BCA 7771503334, MANDIRI 1310012668739, BNI 308050850 AN Rabbani Asysa, Jazakallah';
//                     //$message1 = 'order ' .$people[0]->name.' ';
// 					#$this->load->library('sms');
// 					//$notif = '081386118382';
//                    // $this->sms->SendSms($verify[0]->hp, $message);
// 					//$this->sms->SendSms($people[0]->phone, $message);
// 					//$this->sms->SendSms($notif, $message1);
				
//                 }
//             } else {
//                 return $this->token_response();
//             }

//             if (!empty($dataProduct)) {
//                 $response['status'] = 200;
//                 $response['error'] = false;
//                 $response['message'] = 'Data successfully processed.';
//                 $response['dataTransaction'] = array(
//                     'ordersDay' => $dataTrx['dateCreate'],
//                     //'corp' => $dataTrx['orderBy'],
//                     'noInvoice' => $dataTrx['noInvoice'],
//                     'shipping' => $dataTrx['shipping'],
//                     'VocherDiscount' => $voucher1,
// 					'Ditailsproduct' => $dataOrdersx,
//                         // 'addressSender' => $dataTrx['addressSender'],
//                         // 'addressRecipient' => $dataTrx['addressRecipient'],
//                 );
//                 return $response;
//             } else {
//                 $response['status'] = 502;
//                 $response['error'] = true;
//                 $response['message'] = 'Data failed to receive.';
//                 return $response;
//             }
//         }
//     }
	
// 	public function addOrders2($data = '') {
// //print_r($data);exit;
//  if (empty($data[0])) {
//             return $this->empty_response();
//         } else {
//             $verify = $this->verfyAccount($data[0]);
//             if (!empty($verify)) {
//                 $data = json_decode($data[2]);
//                 $dataTrx = array(
//                     'timeCreate' => date('H:i:s'),
//                     'dateCreate' => date('Y-m-d'),
//                     'noInvoice' => $verify[0]->idauthuser . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
//                     'shipping' => ($data->shipping),
//                     'shippingprice' => ($data->shippingprice),
//                     'idauthuser' => $verify[0]->idauthuser,
//                     'idpeople' => ($data->idpeople),
//                     'payment' => ($data->payment)
//                 );

//                 $supdate = $this->db->insert('transaction', $dataTrx);
//                 $insert_id = $this->db->insert_id();

//                 if (!empty($data)) {
//                     //CALL ARRAY DISKON HARGA //
//                     $discount_price = $data->discountprice;
//                     //CALL ARRAY DISKON HARGA //
//                     foreach ($data->dataOrders as $dO) {
//                         $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
//                         $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');

//                         $dataProduct = $this->db->get_where('shop_cart as a', array('a.idcart' => $dO->idcart))->result();
//                         //print_r($dataProduct);
//                         //exit;
//                         $voucher = $this->db->get_where('voucher', array('vouchercode' => $data->voucher))->result();
//                         //print_r($voucher);
//                         //exit;
//                         if (!empty($dataProduct)) {

//                               //KALKULASI DISKON HARGA ALL PRODUK//  
//                                 if($discount_price['status']==1){
//                                     $type_discount = $discount_price['type'];
//                                     if($type_discount=='percent'){
//                                         $set_disc_price = ($dataProduct[0]->price*$discount_price['value'])/100;
//                                     }else{
//                                         $set_disc_price = $discount_price['value'];
//                                     }
//                                     $set_realprice = $dataProduct[0]->price - $set_disc_price;
//                                 }else{
//                                     $set_disc_price = $dataProduct[0]->priceDiscount;
//                                     $set_realprice = $dataProduct[0]->realprice;
//                                 }
//                             //KALKULASI DISKON HARGA ALL PRODUK//

//                             $dataOrdersx = array(
//                                 'idtransaction' => $insert_id,
//                                 'idproduct' => $dataProduct[0]->idproduct,
//                                 'idpditails' => $dataProduct[0]->idpditails,
//                                 'productName' => $dataProduct[0]->productName,
//                                 'skuPditails' => $dataProduct[0]->skuPditails,
//                                 'voucher' => $voucher[0]->voucherdisc,
//                                 'collor' => $dataProduct[0]->collor,
//                                 'size' => $dataProduct[0]->size,
//                                 'price' => $dataProduct[0]->price,
//                                 'disc' => $set_disc_price * $dataProduct[0]->qty,
//                                 'qty' => $dataProduct[0]->qty,
//                                 'weight' => ($dataProduct[0]->weight) * $dataProduct[0]->qty,
//                                 'subtotal' => $set_realprice * $dataProduct[0]->qty
//                             );
                            
//                             $subtotal[] = $dataOrdersx['subtotal'];
//                             $subdisc[] = $dataOrdersx['disc'];
//                             $totalweight[] = ($dataOrdersx['weight']);

//                             $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataProduct[0]->qty);
//                             $this->db->insert('transaction_details', $dataOrdersx);
//                             $this->db->where('idcart', $dO->idcart);
//                             $this->db->delete('shop_cart');
//                         }
//                     }

//                     $cost = $data->shippingprice ;
//                     $this->db->set('cost', ($cost), true);
//                     $this->db->set('subtotal', array_sum($subtotal), true);
//                     $this->db->set('discount', array_sum($subdisc), true);
//                     $sql = $this->db->query("SELECT vouchercode FROM voucher where vouchercode ='$data->voucher'");
//                     $cek_id = $sql->num_rows();
//                     //print_r($sql);
//                     //exit;
                    
//                     if ($cek_id > 0) {
//                        $voucher = $this->db->get_where('voucher', array('vouchercode' => $data->voucher))->result();
//                       // print_r($voucher[0]->voucherdisc);
//                        //exit;
//                         $voucher1 = $voucher[0]->voucherdisc ;
//                     } else {
//                         $voucher1 = 0;
//                     }
//                     //$this->db->insert('transaction_details', array('discvoucher' => $voucher1));
//                     $total = (array_sum($subtotal) - ($voucher1) + $data->kodeunik + ($cost));
//                     $this->db->set('discvoucher',$voucher1);
//                     $this->db->set('totalpay', array_sum($subtotal)  - ($voucher1)+ $data->kodeunik + ($cost), true);
//                     $this->db->where('idtransaction', $insert_id);
//                     $this->db->update('transaction');

//                     $people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();

//                     //$message = 'rmall.id : Pesanan Berhasil, Total Transfers Rp ' . $total . ', Rekening : BCA 7771503334, MANDIRI 1310012668739, BNI 308050850 AN Rabbani Asysa, Jazakallah';
//                     //$message1 = 'order ' .$people[0]->name.' ';
//                     #$this->load->library('sms');
//                     //$notif = '081386118382';
//                    // $this->sms->SendSms($verify[0]->hp, $message);
//                     //$this->sms->SendSms($people[0]->phone, $message);
//                     //$this->sms->SendSms($notif, $message1);
                
//                 }
//             } else {
//                 return $this->token_response();
//             }




//             if (!empty($dataProduct)) {
//                 $response['status'] = 200;
//                 $response['error'] = false;
//                 $response['message'] = 'Data successfully processed.';
//                 $response['dataTransaction'] = array(
//                     'ordersDay' => $dataTrx['dateCreate'],
//                     //'corp' => $dataTrx['orderBy'],
//                     'noInvoice' => $dataTrx['noInvoice'],
//                     'shipping' => $dataTrx['shipping'],
//                     'VocherDiscount' => $voucher1,
//                         // 'addressSender' => $dataTrx['addressSender'],
//                         // 'addressRecipient' => $dataTrx['addressRecipient'],
//                 );
//                 return $response;
//             } else {
//                 $response['status'] = 502;
//                 $response['error'] = true;
//                 $response['message'] = 'Data failed to receive.';
//                 return $response;
//             }
//         }
//     }

    public function addOrdersByMp($data = '') {
        //print_r($dataOrders);
        //exit;
// if (empty($data[0]) || empty($data[1])) {
//     return $this->empty_response();
// } else {
//     $verify = $this->verfyAccount($data[0], $data[1]);
//     if (!empty($verify)) {
//      $data = json_decode($data[0]);
//      $dataTrx = array(
//          //'idauth' => $verify[0]->idauth,
//          'timeCreate' => date('Y-m-d H:i:s'),
//         // 'orderBy' => strtoupper($verify[0]->corp),
//          'noInvoice' => $data->noInvoice,
//          'shipping' => ($data->shipping),
//          'trackingCode' => $data->trackingCode,
//          'subtotal' => $data->subtotal,
//          'discount' => $data->discount,
//          'totalpay' => $data->totalpay,
//         // 'addressSender' => $verify[0]->addressSender,
//          'addressRecipient' => json_encode($data->shippingSend->recipient),
//          'longlang' => $data->longlang,
//          'statusPay' => $data->statusPay
//      );
//      $supdate = $this->db->insert('transaction', $dataTrx);
//      $insert_id = $this->db->insert_id();
//      foreach ($data->dataOrders as $dataOrders) {
//          $dataProduct = $this->dataProduct($dataOrders->idProducts, $dataOrders->sku);
//          if (!empty($dataProduct)) {
//              $dataOrdersx = array(
//                  'idtransaction' => $insert_id,
//                  'idproduct' => $dataProduct->idproduct,
//                  'idpditails' => $dataProduct->idpditails,
//                  'productName' => $dataProduct->productName,
//                  'sku' => $dataProduct->sku,
//                  'variable' => $dataProduct->variable,
//                  'collor' => $dataProduct->collor,
//                  'size' => $dataProduct->size,
//                  'price' => $dataProduct->price,
//                  'disc' => $dataProduct->priceDiscount,
//                  'qty' => $dataOrders->qty,
//                  'subtotal' => ($dataProduct->price - $dataProduct->priceDiscount) * $dataOrders->qty
//              );
//              $this->debitStock($dataProduct->idpditails, $dataProduct->sku, $dataOrders->qty);
//              $this->db->insert('transaction_details', $dataOrdersx);
//              $subtotal[] = $dataOrdersx['subtotal'];
//          } else {
//              $subtotal[] = 0;
//          }
//      }
//      $stUpdate = 1;
// // } else {
//     // $stUpdate = 0;
// // }
//  if (!empty($stUpdate)) {
//      $response['status'] = 200;
//      $response['error'] = false;
//      $response['message'] = 'Data successfully processed.';
//      $response['dataTransaction'] = array(
//          'ordersTime' => $dataTrx['timeCreate'],
//          'corp' => $dataTrx['orderBy'],
//          'noInvoice' => $dataTrx['noInvoice'],
//          'shipping' => $dataTrx['shipping'],
//          'addressSender' => $dataTrx['addressSender'],
//          'addressRecipient' => $dataTrx['addressRecipient'],
//      );
//      return $response;
//  } else {
//      $response['status'] = 502;
//      $response['error'] = true;
//      $response['message'] = 'Data failed to receive.';
//      return $response;
//  }
    }

// }

    public function dataUser($data = '') {
        $this->db->cache_on();
 //print_r($data); exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
			//print_r($verify); exit;
            if (!empty($verify)) {
                $db2 = $this->load->database('db2', TRUE);
                $db2->select('*');
                $db2->where('idauthuser', $verify[0]->idauthuser);
                $dataCat = $db->get_where('apiauth_user')->result();
            } else {
                $supdate = $verify;
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function UseraddData($data = '') {
        $this->db->cache_on();
// print_r($data);
// exit;
// if (empty($data[0]) || empty($data[1]) || empty($data)) {
//     return $this->empty_response();
// } else {
//     $verify = $this->verfyAccount($data[0], $data[1]);
//     //    print_r($verify);
//     // exit;
//     if (!empty($verify)) {

        $data = array(
            'name' => ($data[0]),
            'email' => ($data[1]),
            'password' => md5($data[3]),
            'hp' => ($data[3])
                //'foto' => strtoupper($data[4]),
        );

        $dataCat = $this->db->get_where('apiauth_user', $data)->result();
        if (empty($dataCat)) {
            $supdate = $this->db->insert('apiauth_user', $data);
        } else {
            $supdate = '';
        }
        $dataCat = $this->db->get_where('apiauth_user', $data)->result();
        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $dataCat;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data already exists.';
            $response['data'] = $dataCat;
            return $response;
        }
//}
// }
    }

    public function UserupdateData($data = '') {
// print_r($data);
// exit;
        if (empty($data[2]) || empty($data[3]) || empty($data)) {
            return $this->empty_response();
        } else {
// $verify = $this->verfyAccount($data[0], $data[1]);
// if (!empty($verify)) {
// print_r($data);
// exit;
            $datac = array(
                'firstname' => ($data[0]),
                'lastname' => ($data[1]),
                'username' => ($data[2]),
                'password' => ($data[3]),
                'email' => ($data[4]),
                'hp' => ($data[5]),
                'idauthuser' => ($data[6])
            );
// print_r($datac);
//exit;  
            $dataCat = $this->db->get_where('apiauth_user', $data)->result();
// print_r($dataCat);
// exit;       
            if (empty($dataCat)) {


                $this->db->set('firstname', ($data[0]));
                $this->db->set('lastname', ($data[1]));
                $this->db->set('username', ($data[2]));
                $this->db->set('password', md5($data[3]));
                $this->db->set('email', ($data[4]));
                $this->db->set('hp', ($data[5]));

                $this->db->where('idauthuser', $data[6]);
                $this->db->update('apiauth_user');

                $supdate = 1;
                // print_r($dataCat);
                // exit;
            } else {
                $supdate = '';
            }

            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $supdate;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function userimage($data = '') {
        print_r($data);
        exit;
//  if (empty($data[0]) ) {
//     return $this->empty_response();
// } else {
//     $verify = $this->verfyAccount($data[0], $data[1]);
//     if (!empty($verify)) {
//             $data = array(
//                // 'idproduct' => $data[2],
//                 'urlImage' => 'http://sandbox.rmall.id/file/img/' . $data[1]['upload_data']['file_name'],
//                 'dir' => $data[2],
//                 'imageFile' => $data[1]['upload_data']['file_name'],
//                 'size' => $data[1]['upload_data']['file_size'],
//                 'type' => $data[1]['upload_data']['image_type']
//             );
//             print_r($data);
//             exit;
//             //$this->db->where('idauthuser', $data[0]);
//             $this->db->update('apiauth_user_images', $data);
//             $supdate = $data;
//              } else {
//         $supdate = $verify;
//     }
//     if ($supdate) {
//         $response['status'] = 200;
//         $response['error'] = false;
//         $response['message'] = 'Data received successfully.';
//         return $response;
//     } else {
//         unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
//         $response['status'] = 502;
//         $response['error'] = true;
//         $response['message'] = 'Data failed to receive.';
//         return $response;
//     }
// }
    }

    public function dataBanner($param = '') {
        $data = array(
            'position' => $param,
//            'timeStart' => '',
//            'timeFinish' => ''
        );
        $this->db->order_by('dateCreate', 'ASC');
        $dataCat = $this->db->get_where('banner', $data)->result();
        if (!empty($dataCat)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $dataCat;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

   

	
	
    public function login($data = '') {
         //print_r($data[0]);
        //exit;
		 $sql = $this->db->query("SELECT hp FROM apiauth_user where hp ='$data[0]'");
         $cek_id = $sql->num_rows();
		 
			
	       if ($cek_id > 0 ) {
					

            $dataCode = md5($data[0]);	
			$otp = rand(pow(10, 5 - 1), pow(10, 5) - 1);
            $this->db->set('keyCode',$dataCode);
			$this->db->set('otp',$otp);
            $this->db->where('hp',$data[0]);
            $this->db->update('apiauth_user');
			
			
			$massage = ' Kode OTP dari https://rabbani.id adalah ' . $otp . ' Jangan Memberikan Kode INI Selain Untuk LOGIN Anda';
            //$this->sms->SendSms($data[0], $massage);
			$this->otp->SendOtp($data[0], $massage);
			//$cek_otp = $this->db->get_where('apiauth_user', array('hp' => $data[0]))->result();
			//print_r($cek_otp);
			//exit;
			//$data1 = array(
               //     'otp' => $otp,
                  
             //   );
			//if ($cek_otp[0]->otp != '') {
			//$this->db->set('otp',$otp);
			//$this->db->where('idauthuser', $cek_otp[0]->idauthuser);
			//$supdate = $this->db->update('apiauth_user');
			//} else {
			//$this->db->where('idauthuser', $cek_otp[0]->idauthuser);
			//$supdate = $this->db->insert('apiauth_user', $data1);
			//}

            $this->db->select('a.idauthuser, a.firstname,a.lastname,a.username,a.email,a.hp,a.keyCode,a.otp, b.urlimage');
            $this->db->join('apiauth_user_images as b', 'b.idauthuser = a.idauthuser', 'left');
			$this->db->where('hp',$data[0]);
            $sql = $this->db->get_where('apiauth_user as a')->result();
		   }else {
				$dataCode = md5($data[0]);	
				$otp = rand(pow(10, 5 - 1), pow(10, 5) - 1);
				$data1 = array(
					'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'keyCode' => $dataCode,
					'otp' => $otp,
					'hp' => $data[0]
					
					);
				$this->db->insert('apiauth_user',$data1);
				$this->db->select('a.idauthuser, a.firstname,a.lastname,a.username,a.email,a.hp,a.keyCode,a.otp, b.urlimage');
				$this->db->join('apiauth_user_images as b', 'b.idauthuser = a.idauthuser', 'left');
				$this->db->where('hp',$data[0]);
				$sql = $this->db->get_where('apiauth_user as a')->result();
                $massage = ' Kode OTP dari https://rabbani.id adalah ' . $otp . ' Jangan Memberikan Kode INI Selain Untuk LOGIN Anda';
                //$this->sms->SendSms($data[0], $massage);
                $this->otp->SendOtp($data[0], $massage);
		   }

        if (!empty($sql)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            // $response['totalData'] = count($sql);
            $response['data'] = $sql;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	 public function loginotp($data = '') {
         //print_r($data[0]);
         //exit;
		 $sql = $this->db->query("SELECT otp FROM apiauth_user where otp ='$data[0]'");
         $cek_id = $sql->num_rows();
			
	       if ($cek_id > 0 ) {
				

			$sql = $this->db->get_where('apiauth_user', array('otp' => $data[0]))->result();
			//print_r($cek_otp);
			//exit;
			
		   }else {
			   return $this->otp_response();
		   }

        if (!empty($sql)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            // $response['totalData'] = count($sql);
            $response['data'] = $sql;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

     public function register($data = '') {
		
		 $sql = $this->db->query("SELECT hp FROM apiauth_user where hp ='$data[4]'");
         $cek_id = $sql->num_rows();
		 $sql = $this->db->query("SELECT username FROM apiauth_user where username ='$data[0]'");
         $cek_user = $sql->num_rows();
        if (empty($data[0]) || empty($data[1])|| empty($data[2])|| empty($data[3])|| empty($data[4])|| empty($data[5])) {
			return $this->empty_response();
		} else {
			
				if ($cek_id > 0 ) {
					return $this->duplicate_response();
			} else {

			$data = array(
				'timeCreate' => date('H:i:s'),
				'dateCreate' => date('Y-m-d'),
				'username' => strtolower($data[0]),
				'password' => md5($data[1]),
				'firstname' => ($data[2]),
				'lastname' => ($data[3]),
				'hp' => ($data[4]),
				'email' => ($data[5])
			);

			//print_r($data[hp]);
			//exit;
            $supdate = $this->db->insert('apiauth_user', $data);
			
			
			$message = 'rmall.id : Pendaftaran Berhasil, Jazakallah Ka ' . $data[firstname] . ',Ayo Belanja di Rabbani Mall Online';
            
            $this->sms->SendSms($data[hp], $message);
					
			 
			
			}
		}

        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            $response['data'] = $data;
            return $response;
        }

    }
	
	
	public function newregister($data = '') {
		
		 $sql = $this->db->query("SELECT hp FROM apiauth_user where hp ='$data[4]'");
         $cek_id = $sql->num_rows();
		 $sql = $this->db->query("SELECT username FROM apiauth_user where username ='$data[0]'");
         $cek_user = $sql->num_rows();
        if (empty($data[0]) || empty($data[1])|| empty($data[2])|| empty($data[3])|| empty($data[4])|| empty($data[5])) {
			return $this->empty_response();
		} else {
			
				if ($cek_id > 0 ) {
					return $this->duplicate_response();
			} else {

			$data = array(
				'timeCreate' => date('H:i:s'),
				'dateCreate' => date('Y-m-d'),
				'username' => strtolower($data[0]),
				'password' => md5($data[1]),
				'firstname' => ($data[2]),
				'lastname' => ($data[3]),
				'hp' => ($data[4]),
				'email' => ($data[5])
			);

			//print_r($data[hp]);
			//exit;
            $supdate = $this->db->insert('apiauth_user', $data);
			
			
			$message = 'rmall.id : Pendaftaran Berhasil, Jazakallah Ka ' . $data[firstname] . ',Ayo Belanja di Rabbani Mall Online';
            
            $this->sms->SendSms($data[hp], $message);
					
			 
			
			}
		}

        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            $response['data'] = $data;
            return $response;
        }

    }

    public function otp($data = '') {
        //print_r($data);
        //exit;


        $data = array(
            'timeCreate' => date('H:i:s'),
            'dateCreate' => date('Y-m-d'),
            'hp' => ($data[0]),
            'otp' => rand(pow(10, 5 - 1), pow(10, 5) - 1)
        );
        //print_r($data);
        // exit;

        $dataCat = $this->db->get_where('apiauth_user', array('hp' => $data['hp']))->result();


        if (empty($dataCat)) {
            $supdate = $this->db->insert('apiauth_user', $data);
            #$this->load->library('sms');
            $massage = $data['otp'];
            $this->sms->SendSms($data['hp'], $massage);
        } else {
            $this->db->set($data);
            $this->db->where('hp', $dataCat[0]->hp);
            $supdate = $this->db->update('apiauth_user', $data);
            #$this->load->library('sms');
            $massage = 'Kode ' . $data['otp'] . '';
            $this->sms->SendSms($data['hp'], $massage);
        }
        // $dataCat = $this->db->get_where('apiauth_user', $data)->result();
        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data already exists.';
            $response['data'] = $dataCat;
            return $response;
        }
    }

    public function historytrans($data = '') {
// print_r($data);
// exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                //$db2 = $this->load->database('db2', TRUE);
                $this->db->select('a.*,b.idproduct,b.productName,b.qty');
                $this->db->where('idauthuser', $verify[0]->idauthuser);
                $this->db->order_by('a.idtransaction', 'DESC');
		$this->db->join('transaction_details as b', 'b.idtransaction = a.idtransaction');
                $dataCat = $this->db->get_where('transaction as a')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function historytransdetail($data = '') {
        // print_r($data); exit;
               if (empty($data[0])||empty($data[1])) {
                   return $this->empty_response();
               } else {
                   $verify = $this->verfyAccount($data[0], $data[1]);
                   // print_r($verify); exit;
                   if (!empty($verify)) {
                       //$db2 = $this->load->database('db2', TRUE);
                       $this->db->select('a.*,b.idproduct,b.productName,b.qty');
                       $this->db->where('idauthuser', $verify[0]->idauthuser);
                       $this->db->where('noInvoice', $data[1]);
                       $this->db->order_by('a.idtransaction', 'DESC');
                       // $this->db->join('voucher_new as c', 'c.voucher_code = a.voucher');
                       $this->db->join('transaction_details as b', 'b.idtransaction = a.idtransaction');
                       $dataCat = $this->db->get_where('transaction as a')->result();
                       $voucher = $this->db->get_where('voucher_new', array('voucher_code' => $dataCat[0]->voucher))->result();
                       // print_r($voucher);exit;
                   } else {
                       return $this->token_response();
                   }
       
                   if ($dataCat) {
                       $response['status'] = 200;
                       $response['error'] = false;
                       $response['totalData'] = count($dataCat);
                       $response['data'] = $dataCat;
                       $response['voucher'] = $voucher;
                       return $response;
                   } else {
                       $response['status'] = 502;
                       $response['error'] = true;
                       $response['message'] = 'Data failed to receive or data empty.';
                       return $response;
                   }
               }
           }

    public function cart($data = '') {
		 
		// $this->db->where('idauthuser', $verify[0]->idauthuser);
		/// $cart = $this->db->get_where('shop_cart')->result();
	//if (!empty($cart)) {
		 //print_r($cart);exit;
	
		 

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                //$db2 = $this->load->database('db2', TRUE);
                $this->db->select('a.*,b.skuPditails,b.price,b.stock,b.collor,b.size,b.realprice,b.priceDiscount,c.idproduct,c.productName,d.urlImage');
                $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
                $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');
                $this->db->join('product_images_ditails as d', 'd.idpditails = a.idpditails');
                $this->db->where('idauthuser', $verify[0]->idauthuser);
                $this->db->group_by('d.idpditails');
                $dataCat = $this->db->get_where('shop_cart as a')->result();
                //print_r($dataCat);
                //exit;
                $sql = $this->db->query("SELECT vouchercode FROM voucher where vouchercode ='$data[1]'");
                $cek_id = $sql->num_rows();
                if (!empty($dataCat)) {
                    if ($cek_id > 0) {
                        $voucher = $this->db->get_where('voucher', array('vouchercode' => $data[1]))->result();
                        foreach ($dataCat as $ditail) {
                            // print_r($ditail);


                            $voucher1 = (($ditail->price) * ($ditail->qty));
                        }
                        $subtotal[] = $voucher1;
                        $voucher2 = (array_sum($subtotal) * ($voucher[0]->voucherdisc) / 100);
                    } else {
                        $voucher2 = '0';
                    }
                } else {
                    $voucher2 = '0';
                }
            } else {
                return $this->token_response();
            }
		
		//}  
	//} else {
               //  $supdate = $verify;
  //  }
		

	
            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                $response['voucher'] = $voucher2;
                return $response;
            } else {
				$response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
		}
    }
	
	  public function cartnew($data = '') {
		 
		// $this->db->where('idauthuser', $verify[0]->idauthuser);
		/// $cart = $this->db->get_where('shop_cart')->result();
	//if (!empty($cart)) {
		 //print_r($cart);exit;
	
		 

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                //$db2 = $this->load->database('db2', TRUE);
                $this->db->select('a.*,b.skuPditails,b.price,b.stock,b.collor,b.size,b.realprice,b.priceDiscount,c.idproduct,c.productName,d.urlImage');
                $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
                $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');
                $this->db->join('product_images_ditails as d', 'd.idpditails = b.idpditails', 'left');
                $this->db->where('idauthuser', $verify[0]->idauthuser);
                $this->db->group_by('d.idpditails');
                $dataCat = $this->db->get_where('shop_cart as a')->result();
                //print_r($dataCat);
                //exit;
                $sql = $this->db->query("SELECT vouchercode FROM voucher where vouchercode ='$data[1]'");
                $cek_id = $sql->num_rows();
                if (!empty($dataCat)) {
                    if ($cek_id > 0) {
                        $voucher = $this->db->get_where('voucher', array('vouchercode' => $data[1]))->result();
                        foreach ($dataCat as $ditail) {
                            // print_r($ditail);


                            $voucher1 = (($ditail->price) * ($ditail->qty));
                        }
                        $subtotal[] = $voucher1;
                        $voucher2 = (array_sum($subtotal) * ($voucher[0]->voucherdisc) / 100);
                    } else {
                        $voucher2 = '0';
                    }
                } else {
                    $voucher2 = '0';
                }
            } else {
                return $this->token_response();
            }
		
		//}  
	//} else {
               //  $supdate = $verify;
  //  }
		

	
            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                $response['voucher'] = $voucher2;
                return $response;
            } else {
                $response['status'] = 200;
                $response['error'] = false;
                //$response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
               // $response['voucher'] = $voucher2;
                return $response;
            }
		}
    }

    public function addcart($data = '') {
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {

                $data1 = json_decode($data[1]);
                // print_r($data1);
                //exit;
                foreach ($data1 as $dataOrders) {
                    //$sql = $this->db->query("SELECT idpditails FROM shop_cart where idpditails ='$dataOrders->idpditails'");
                    //$cek_id = $sql->num_rows();
                    $product = $this->db->get_where('shop_cart', array('idauthuser' => $verify[0]->idauthuser, 'idpditails' => $dataOrders->idpditails))->result();
                    //print_r($product);
                    //exit;
                    if (empty($product)) {

                        //if ($cek_id > 0) {

                        $dataOrdersx = array(
                            'idauthuser' => $verify[0]->idauthuser,
                            'idpditails' => $dataOrders->idpditails,
                            'qty' => $dataOrders->qty,
							'date_cart' => date('Y-m-d')
                        );

                        // print_r($dataOrdersx);
                        $queryz = $this->db->insert('shop_cart', $dataOrdersx);
						$query = $this->db->get_where('shop_cart', array('idauthuser' => $verify[0]->idauthuser))->result();
                    } else {

                        $this->db->set('qty', 'qty+' . $dataOrders->qty, FALSE);
                        $this->db->where('idauthuser', $verify[0]->idauthuser);
                        $queryz = $this->db->update('shop_cart');
						$query = $this->db->get_where('shop_cart', array('idauthuser' => $verify[0]->idauthuser))->result();
                    }
                }
            } else {
                return $this->token_response();
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }
	
	 public function addcartnew($data = '') {
		 //print_r($data);
		 //exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {

                $data1 = json_decode($data[1]);
				$product = $this->db->get_where('shop_cart', array('idauthuser' => $verify[0]->idauthuser))->result();
              // print_r($product);
			   //exit;
			    if (empty($product)) {
                foreach ($data1 as $dataOrders) {

                        $dataOrdersx = array(
                            'idauthuser' => $verify[0]->idauthuser,
                            'idpditails' => $dataOrders->idpditails,
                            'qty' => $dataOrders->qty,
							'date_cart' => date('Y-m-d')
                        );

                        // print_r($dataOrdersx);
                        $queryz = $this->db->insert('shop_cart', $dataOrdersx);
						$query = $this->db->get_where('shop_cart', array('idauthuser' => $verify[0]->idauthuser))->result();
						  }
                    } else {

                        //$this->db->set('qty', 'qty+' . $dataOrders->qty, FALSE);
                        $this->db->where('idauthuser', $verify[0]->idauthuser);
                        $queryz = $this->db->delete('shop_cart');
						 foreach ($data1 as $dataOrders) {

                        $dataOrdersx = array(
                            'idauthuser' => $verify[0]->idauthuser,
                            'idpditails' => $dataOrders->idpditails,
                            'qty' => $dataOrders->qty,
							'date_cart' => date('Y-m-d')
                        );

                        // print_r($dataOrdersx);
                        $queryz = $this->db->insert('shop_cart', $dataOrdersx);
						$query = $this->db->get_where('shop_cart', array('idauthuser' => $verify[0]->idauthuser))->result();
						  }
                    }
              
            } else {
                return $this->token_response();
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $product;
                return $response;
            }
        }
    }

    public function delcart($data = '') {
        //print_r($data);
        //exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {

                $this->db->where('idcart', $data[1]);
                $query = $this->db->delete('shop_cart');
            } else {
                return $this->token_response();
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function detailsOrders($data = '') {
        //print_r($data);
        //exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                $this->db->select('a.*, b.urlImage');
                $this->db->join('product_images as b', 'b.idproduct = a.idproduct', 'left');
                $this->db->where('a.idtransaction', $data[1]);
                $this->db->group_by('idtransactiondetails');
                $query = $this->db->get_where('transaction_details as a')->result();
            } else {
                return $this->token_response();
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
				$response['count'] = count($query);
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function debitStock($idpditails = '', $sku = '', $debit = '') {
        $this->db->set('stock', 'stock-' . $debit, FALSE);
        // $this->db->set('physical', 'physical-' . $debit, FALSE);
        $this->db->where('idpditails', $idpditails);
        $this->db->where('skuPditails', $sku);
        $this->db->update('product_ditails');
    }

    public function s($data = '', $v = '') {

//print_r($data);
//exit;
        if (empty($data[2])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {
								
				//$flashsale = $this->db->query("SELECT idproduct FROM flashsale where idproduct ='$dataOrders->idpditails'");
                $data1 = json_decode($data[2]);

                $dataTrx = array(
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'noInvoice' => $verify[0]->idauthuser . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
                    'shipping' => ($data1->shipping),
                    'shippingprice' => ($data1->shippingprice),
                    'idauthuser' => $verify[0]->idauthuser,
                    'idpeople' => ($data1->idpeople),
                    'payment' => ($data1->payment)
                );

                $supdate = $this->db->insert('transaction', $dataTrx);
                $insert_id = $this->db->insert_id();


                if (!empty($data)) {
                    foreach ($data1->dataOrders as $dO) {
                        $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
                        $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');

                        $dataProduct = $this->db->get_where('shop_cart as a', array('a.idcart' => $dO->idcart))->result();
                        //print_r($dataProduct);
                        //exit;
                        $voucher = $this->db->get_where('voucher', array('vouchercode' => $data1->voucher))->result();
                        //print_r($voucher);
                        //exit;
                        if (!empty($dataProduct)) {
                            $dataOrdersx = array(
                                'idtransaction' => $insert_id,
                                'idproduct' => $dataProduct[0]->idproduct,
                                'idpditails' => $dataProduct[0]->idpditails,
                                'productName' => $dataProduct[0]->productName,
                                'skuPditails' => $dataProduct[0]->skuPditails,
                                'voucher' => $voucher[0]->voucherdisc,
                                'collor' => $dataProduct[0]->collor,
                                'size' => $dataProduct[0]->size,
                                'price' => $dataProduct[0]->price,
                                'disc' => $dataProduct[0]->priceDiscount * $dataProduct[0]->qty,
                                'qty' => $dataProduct[0]->qty,
                                'weight' => ($dataProduct[0]->weight) * $dataProduct[0]->qty,
                                'subtotal' => ($dataProduct[0]->price) * $dataProduct[0]->qty
                            );

                            $subtotal[] = $dataOrdersx['subtotal'];
                            $subdisc[] = $dataOrdersx['disc'];
                            $totalweight[] = ($dataOrdersx['weight']);


                            $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataProduct[0]->qty);
                            $this->db->insert('transaction_details', $dataOrdersx);
                            //$this->db->where('idcart', $dO->idcart);
                            //$this->db->delete('shop_cart');
                        }
                    }

                    $cost = $data1->shippingprice * ceil(array_sum($totalweight) / 1000);

                    $this->db->set('cost', ($cost), true);
                    $this->db->set('subtotal', array_sum($subtotal), true);
                    $this->db->set('discount', array_sum($subdisc), true);
                    $sql = $this->db->query("SELECT vouchercode FROM voucher where vouchercode ='$data1->voucher'");
                    $cek_id = $sql->num_rows();
                    if ($cek_id > 0) {
                        $voucher = $this->db->get_where('voucher', array('vouchercode' => $data1->voucher))->result();
                        $voucher1 = ((array_sum($subtotal) - array_sum($subdisc)) * ($voucher[0]->voucherdisc / 100));
                    } else {
                        $voucher1 = 0;
                    }
                    //$this->db->insert('transaction_details', array('discvoucher' => $voucher1));
                    $total = (array_sum($subtotal) + ($cost) - array_sum($subdisc) - ($voucher1)+ $data1->kodeunik);
                    $this->db->set('discvoucher', $voucher1);
                    $this->db->set('totalpay', array_sum($subtotal) + ($cost) - array_sum($subdisc) - ($voucher1)+ $data1->kodeunik, true);
                    $this->db->where('idtransaction', $insert_id);
                    $this->db->update('transaction');

                    $people = $this->db->get_where('sensus_people', array('idpeople' => $data1->idpeople))->result();

                    if ($v != 2) {
                        $message = 'rabbani.id : Silakan Transfers Rp ' . $total . ',  BCA 7771503334, MANDIRI 1310012668739, BNI 308050850 AN Rabbani Asysa, Batas Pembayaran 1x24 Jam';
                        $message1 = 'order ' . $people[0]->name . ' ';
                        #$this->load->library('sms');
                        //$notif = '081386118382';
                        $this->sms->SendSms($verify[0]->hp, $message);
                        $this->sms->SendSms($people[0]->phone, $message);
                        //$this->sms->SendSms($notif, $message1);
                    }
                }
            } else {
                return $this->token_response();
            }




            if (!empty($dataProduct)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['dataTransaction'] = array(
                    'ordersDay' => $dataTrx['dateCreate'],
                    //'corp' => $dataTrx['orderBy'],
                    'noInvoice' => $dataTrx['noInvoice'],
                    'shipping' => $dataTrx['shipping'],
                    'VocherDiscount' => $voucher1,
                        // 'addressSender' => $dataTrx['addressSender'],
                        // 'addressRecipient' => $dataTrx['addressRecipient'],
                );
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function addressditail($data = '') {


        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);


            if (!empty($verify)) {
                //$db2 = $this->load->database('db2', TRUE);
                //$db2->select('*');
                $this->db->where('idauthuser', $verify[0]->idauthuser);
                $this->db->where('idpeople', $data[1]);
				$this->db->join('sensus as b', 'b.idsensus = a.id_dis', 'left');
                $query = $this->db->get_where('sensus_people as a')->result();
                //print_r($query);
                //exit;
            } else {
                return $this->empty_response();
            }


            if ($query) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $query;
                return $response;
            }
        }
    }

    public function address($data = '') {


        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);


            if (!empty($verify)) {
                $db2 = $this->load->database('db2', TRUE);
                //$db2->select('*');
                $db2->where('idauthuser', $verify[0]->idauthuser);
                $db2->where('delpeople', 0);
				$db2->join('sensus as b', 'b.idsensus = a.id_dis', 'left');
                $query = $db2->get_where('sensus_people as a')->result();
            } else {
                return $this->empty_response();
            }


            if ($query) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $query;
                return $response;
            }
            //  }
        }
    }

    public function addressUseradd($data = '') {

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $data = json_decode($data[1]);

                $data2 = array(
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'address' => $data->address,
                    'rt' => $data->rt,
                    'rw' => $data->rw,
                    'pos' => $data->pos,
                    'phone' => $data->phone,
                    'id_vill' => $data->id_vill,
                    'id_dis' => $data->id_dis,
                    'id_city' => $data->id_city,
                    'id_prov' => $data->id_prov,
                    'idauthuser' => $verify[0]->idauthuser
                );
                //print_r($data2);
                //exit;

                $xupdate = $this->db->insert('sensus_people', $data2);
            }
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($xupdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $data2;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $xupdate;
                return $response;
            }
            //  }
        }
    }

    public function address2($data = '') {
        //print_r($data);
        //exit;

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $data = json_decode($data[1]);
                //  print_r($data);
                //exit;


                $data2 = array(
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'address' => $data->address,
                    'id_city' => $data->id_city,
                    'id_prov' => $data->id_prov,
                    'idauthuser' => $verify[0]->idauthuser
                );
                //print_r($data2);
                //exit;

                $xupdate = $this->db->insert('sensus_people', $data2);
            }
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($xupdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $data2;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $xupdate;
                return $response;
            }
            //  }
        }
    }

    public function district($data = '') {
        //print_r($data);
        //exit;

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('sensus');
                //$this->db->join('1015_city as b', 'b.province_id = a.province_id', 'left');
                //$this->db->group_by('idsensus');
                $this->db->where('city_id', $data[1]);
                $queryx = $this->db->get()->result();
            }
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $queryx;
                return $response;
            }
            //  }
        }
    }
	
	 public function districtditails($data = '') {
       // print_r($data);
        //exit;

                $this->db->select('*');
                $this->db->from('sensus');
                
                $this->db->where('idsensus', $data[0]);
                $queryx = $this->db->get()->result();
          
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data Not Found.';
                $response['data'] = $queryx;
                return $response;
            }
          
        
    }
	
	 public function district2($data = '') {
        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {


                $this->db->select('idsensus,district_name');
                $this->db->from('sensus');
                
                
                $queryx = $this->db->get()->result();
          
           
				}

            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data Not Found.';
                $response['data'] = $queryx;
                return $response;
            }
          
        }
    }
	
	public function districtnew($data = '') {
		//print_r($data);exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {


              //  $this->db->select('idsensus,district_name');
                $this->db->where('idsensus',$data[1]);
                $queryx = $this->db->get_where(sensus)->result();

				}

            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data Not Found.';
                $response['data'] = $queryx;
                return $response;
            }
          
        }
    }

    public function city($data = '') {
        // print_r($data);
        //exit;

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('1015_province as a');
                $this->db->join('1015_city as b', 'b.province_id = a.province_id', 'left');
                $this->db->where('city_id', $data[1]);
                $queryx = $this->db->get()->result();
            }
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $queryx;
                return $response;
            }
            //  }
        }
    }

    public function city3($data = '') {
        // print_r($data);
        //exit; 

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('sensus');
                //$this->db->join('1015_city as b', 'b.province_id = a.province_id', 'left');
                $this->db->group_by('city_id');
                $this->db->where('province_id', $data[1]);
                $queryx = $this->db->get()->result();
            }
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $queryx;
                return $response;
            }
            //  }
        }
    }

    public function address3($data = '') {
        // print_r($data);
        //exit;

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $data = json_decode($data[1]);
                //  print_r($data);
                //exit;


                $data2 = array(
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'address' => $data->address,
                    'id_city' => $data->id_city,
                    'id_prov' => $data->id_prov,
                    'id_dis' => $data->id_dis,
                    'idauthuser' => $verify[0]->idauthuser
                );
                //print_r($data2);
                //exit;

                $xupdate = $this->db->insert('sensus_people', $data2);
            }
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($xupdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $data2;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $xupdate;
                return $response;
            }
            //  }
        }
    }

    public function province($data = '') {
      //print_r($data);
	  //exit;

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
		//print_r($verify);
       // exit;
            if (!empty($verify)) {

                $this->db->like('CITY_NAME', $data[1]);
				$this->db->or_like('DISTRICT_NAME', $data[1]);
				$this->db->or_like('SUBDISTRICT_NAME', $data[1]);
				$this->db->or_like('PROVINCE_NAME', $data[1]);
				$this->db->limit(100);
                $queryx = $this->db->get_where('sensus')->result();
				//print_r($queryx);
				//exit;
                 
            } else {
				return $this->token_response();
			}
            


            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $xupdate;
                return $response;
            }
            //  }
        }
    }

    public function province2($data = '') {
        // print_r($data);
        //exit;

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('sensus');
                //$this->db->join('1015_city as b', 'b.province_id = a.province_id', 'left');
                $this->db->group_by('province_id');
                $queryx = $this->db->get()->result();
            }
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $xupdate;
                return $response;
            }
            //  }
        }
    }

    public function province3($data = '') {
        //print_r($data);
        //exit;

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('sensus');
                $this->db->group_by('province_id');
                $this->db->where('province_id', $data[1]);
                $queryx = $this->db->get()->result();
            }
            //$this->db->where('idauthuser',$data2['phone']);
            //$this->db->insert('apiauth_user', array('hp' => $data2['phone']));


            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $xupdate;
                return $response;
            }
            //  }
        }
    }

    public function addressUserupdate($data = '') {

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $data = json_decode($data[1]);

                $data2 = array(
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'address' => $data->address,
                    'rt' => $data->rt,
                    'rw' => $data->rw,
                    'pos' => $data->poscode,
                    'phone' => $data->phone,
                    'id_vill' => $data->id_vill,
                    'id_dis' => $data->id_dis,
                    'id_city' => $data->id_city,
                    'id_prov' => $data->id_prov,
                    'idauthuser' => $verify[0]->idauthuser
                );
                $this->db->where('idpeople', $data->idpeople);
                $xupdate = $this->db->update('sensus_people', $data2);
            }


            if ($xupdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $data2;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $dataCat;
                return $response;
            }
            //  }
        }
    }

    public function addressUserdel($data = '') {

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {


                $this->db->set('delpeople', 1);
                $this->db->where('idpeople', $data[1]);
                $xupdate = $this->db->update('sensus_people');
            } else {
                return $this->token_response();
            }



            if ($xupdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $xupdate;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $dataCat;
                return $response;
            }
            //  }
        }
    }

    public function whishlist($data = '') {
// print_r($data);
// exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                //$db2 = $this->load->database('db2', TRUE);
                $this->db->select('c.productName,a.*,b.realprice,b.idproduct,b.stock,b.collor,b.size,d.urlImage');
                $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
                $this->db->join('product as c', 'c.idproduct = b.idproduct', 'left');
                $this->db->join('product_images as d', 'd.idproduct = b.idproduct', 'left');
                $this->db->group_by('idpditails');
                $this->db->where('idauthuser', $verify[0]->idauthuser);
                $dataCat = $this->db->get_where('whishlist as a')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function whishlistview($data = '') {


        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                //$db2 = $this->load->database('db2', TRUE);
                $this->db->select('*');
                $this->db->where('idauthuser', $verify[0]->idauthuser);
                $dataCat = $this->db->get_where('whishlist')->result();

                if (!empty($dataCat)) {
                    foreach ($dataCat as $details) {

                        $this->db->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
                        //$this->db->join('product as b', 'b.idpditails = a.idpditails', 'left');
                        $dataProduct = $this->db->get_where('whishlist as a', array('a.idpditails' => $details->idpditails))->result();

                        //print_r($dataProduct);
                        //exit;
                    }
                }
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataProduct;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function addwhishlist($data = '') {
        //print_r($data);
        // exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {

                $data = json_decode($data[1]);
                foreach ($data->dataOrders as $dataOrders) {
                    $sql = $this->db->query("SELECT idpditails FROM whishlist where idpditails ='$dataOrders->idpditails'");
                    $cek_id = $sql->num_rows();

                    // print_r($dataOrders);




                    if ($cek_id > 0) {

                        //return $this->duplicate_response();


                        $this->db->where('idpditails', $dataOrders->idpditails);
                        $this->db->delete('whishlist');

                        // $dataOrdersx = array(
                        //     'idauthuser' => $verify[0]->idauthuser,
                        //     'idpditails' => $dataOrders->idpditails,
                        //     'qty' => $dataOrders->qty,
                        // );
                        // $query = $this->db->insert('shop_cart', $dataOrdersx); 
                    } else {
                        $dataOrdersx = array(
                            'idauthuser' => $verify[0]->idauthuser,
                            'idpditails' => $dataOrders->idpditails,
                        );
                        $query = $this->db->insert('whishlist', $dataOrdersx);
                    }
                }
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $query;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function store($data = '') {
 //print_r($data); exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                $this->db->group_by('a.idstore');
               $this->db->join('1015_city as c', 'c.province_id = a.id_prov');
                $this->db->join('1015_province as b', 'b.province_id = a.id_prov');
                $dataCat = $this->db->get_where('store as a')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	 public function storedetails($data = '') {
// print_r($data);
 //exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                $this->db->where('a.id_city', $data[1]);
                $this->db->group_by('a.idstore');
                $this->db->join('1015_city as c', 'c.province_id = a.id_prov');
                $this->db->join('1015_province as b', 'b.province_id = a.id_prov');
                $dataCat = $this->db->get_where('store as a')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function faq($data = '') {
// print_r($data);
// exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                $db2 = $this->load->database('db2', TRUE);
                $db2->select('*');
                //$this->db->where('idstore', $data[1]);
                $dataCat = $db2->get_where('faq')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

   // public function quest($data = '') {
 //print_r($data);
 //exit;
       // $dataCode = array(
            //'keyCode' => md5(time() . rand(pow(10, 5 - 1), pow(10, 5) - 1)),
     //  );

        //$this->db->insert('apiauth_user', $dataCode);


      // if ($dataCode) {
          // $response['status'] = 200;
          // $response['error'] = false;
           // $response['totalData'] = count($dataCode);
           // $response['data'] = $dataCode;
           // return $response;
     //   } else {
           //$response['status'] = 502;
          // $response['error'] = true;
          // $response['message'] = 'Data failed to receive or data empty.';
           // return $response;
      // }
   //}
	
	public function getflashsale() {
	     $dataCatx = $this->db->get_where('flashsale')->result();
		 //print_r($dataCatx);exit;
		 if (!empty($dataCatx)) {
                
				
               
				//print_r($dataCatx);
				//exit;
				
				 $data = json_decode($dataCatx[0]->idproduct);
				 //print_r($data);
				 //exit;
			  foreach ($data as $ddt) {
				  
				 $datax= array(
                            'idproduct' => $ddt->idproduct,
							
                          
                        );
						
				
				$this->db->where('a.delproduct', 0);
				$this->db->where('b.delproductditails', 0);
				$this->db->where('b.stock>0');
				$this->db->where('a.idproduct', $ddt->idproduct );
				$this->db->join('product_images as c', 'c.idproduct = a.idproduct');
				$this->db->join('product_ditails as b', 'b.idproduct = a.idproduct');
				$this->db->group_by('a.idproduct');
				$this->db->order_by('a.idproduct', 'DESC');
				$datay[]= $this->db->get_where('product as a')->result();
				//print_r($datay);
						
			  }
	   }else {
		    return $this->empty_response();
	   }
                
                if ($datay) {
                    $response['status'] = 200;
                    $response['error'] = false;
                    $response['message'] = 'Data received successfully.';
                    $response['data1'] = $datay;
					$response['data2'] = $dataCatx;
                    return $response;
                } else {
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCatx;
                    return $response;
                }
            
        
    }
	
	
	public function search($data = '') {
	    //print_r($data);
		//exit;
		
		if (empty($data[0])) {
            $supdate = 0;
        } else {
            $datax = array(
                'keyword' => $data[0],
                'datetime' => date('Y-m-d H:i:s')
            );
			$this->db->insert('log_keyword', $datax);
        }
		
			 
         //$this->db->select('a.*,b.*,c.*');
//		 $this->db->where('a.delproduct', 0);
//		 $this->db->where('b.delproductditails', 0);
//		 $this->db->where('b.stock>2');
//         $this->db->limit('100');
//		 $this->db->group_by('a.idproduct');
//		 $this->db->like('productName', $data[0]);
//		
//		 $this->db->join('product_images as c', 'c.idproduct = a.idproduct');
//		 $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct');
//		 $datax= $this->db->get_where('product as a')->result();
                 $this->db->where('c.imageFile !=',"");
		 $this->db->where('b.delproduct', 0);
		 $this->db->where('a.delproductditails', 0);
		 $this->db->where('a.stock>2');
                 $this->db->limit('100');
		 $this->db->group_by('a.idproduct');
		 $this->db->like('b.productName', $data[0]);
		
		 $this->db->join('product_images_ditails as c', 'c.idpditails = a.idpditails');
		 $this->db->join('product as b', 'b.idproduct = a.idproduct');
		 $datax= $this->db->get_where('product_ditails as a')->result();
		 
		 
		 
		 
        
				
           
                
                if ($datax) {
                    $response['status'] = 200;
                    $response['error'] = false;
                    $response['message'] = 'Data received successfully.';
					$response['totalData'] = count($datax);
                    $response['data'] = $datax;
					
                    return $response;
                } else {
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $datax;
                    return $response;
                }
            
        
    }
	
	 public function voucher($data = '') {
		   // print_r($data);
		//exit;
		
					
					
					
		$this->db->where('vouchercode',strtoupper($data[0]));
		$supdate = $this->db->get_where('voucher')->result();
			

        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($supdate);
            $response['data'] = $supdate;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
	
	public function vouchernew($data = '') {
		 
		$query = $this->db->get_where('voucher_new', array('voucher_code' => $data[0]))->result();
          // print_r($query);exit;
		$sql = $this->db->query("SELECT voucher_code FROM voucher_new where voucher_code='$data[0]'");
        $cek_id = $sql->num_rows();
		
		$awal = date_create($query[0]->date_end);
		$akhir = date_create();
        $diff = date_diff($awal, $akhir);
	
		// print_r($diff->invert);exit;
                if (!empty($query)) {
					if ($diff->invert>0) {
					
					$this->db->where('voucher_code',strtoupper($data[0]));
					$supdate = $this->db->get_where('voucher_new')->result();
					} else {
					 return $this->voucher2_response();
					}
		 
				} else {
					 return $this->voucher_response();
				}

        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($supdate);
            $response['data'] = $supdate;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
	
	public function getpromo() {
	     $dataCatx = $this->db->get_where('promo')->result();
       if (!empty($dataCatx)) {
                
				
               
				//print_r($dataCatx);
				//exit;
				
				 $data = json_decode($dataCatx[0]->idproductpromo);
				 //print_r($data);
				 //exit;
			  foreach ($data as $ddt) {
				 $datax= array(
                            'idproduct' => $ddt->idproduct,
							
                          
                        );
						
				//print_r($datax);
				$this->db->where('a.delproduct', 0);
				$this->db->where('b.delproductditails', 0);
				$this->db->where('b.stock>0');
				$this->db->where('a.idproduct', $ddt->idproduct );
				$this->db->join('product_images as c', 'c.idproduct = a.idproduct','left');
				$this->db->join('product_ditails as b', 'b.idproduct = a.idproduct','left');
				$this->db->group_by('a.idproduct');
				$this->db->order_by('a.idproduct', 'DESC');
				$datay[]= $this->db->get_where('product as a')->result();
						
			  }
	   }else {
		    return $this->empty_response();
	   }
				
				
           
                
                if ($datay) {
                    $response['status'] = 200;
                    $response['error'] = false;
                    $response['message'] = 'Data received successfully.';
                    $response['data1'] = $datay;
					$response['data2'] = $dataCatx;
                    return $response;
                } else {
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCatx;
                    return $response;
                }
            
        
    }
	
	 public function forget($data = '') {
		// print_r($data);
		// exit;
       if (empty($data[0])) {
			return $this->empty_response();
		} else {
		 $sql = $this->db->query("SELECT hp FROM apiauth_user where hp ='$data[0]'");
         $cek_id = $sql->num_rows();
		// $sql = $this->db->query("SELECT otp FROM apiauth_user where username ='$data[0]'");
         //$cek_user = $sql->num_rows();
      
			
		if ($cek_id > 0 ) {
			
			$otp = rand(pow(10, 5 - 1), pow(10, 5) - 1);
			$massage = 'Kode dari https://rabbani.id ' . $otp . ' Jangan Memberikan Kode INI Selain Untuk Merubah Password Anda';
            $this->sms->SendSms($data['0'], $massage);
			$cek_otp = $this->db->get_where('apiauth_user', array('hp' => $data[0]))->result();
			//print_r($cek_otp);
			//exit;
			$data1 = array(
                    'otp' => $otp,
                  
                );
			if ($cek_otp[0]->otp != '') {
			$this->db->set('otp',$otp);
			$this->db->where('idauthuser', $cek_otp[0]->idauthuser);
			$supdate = $this->db->update('apiauth_user');
			} else {
			$this->db->where('idauthuser', $cek_otp[0]->idauthuser);
			$supdate = $this->db->insert('apiauth_user', $data1);
			}
					
			} else {
				
				return $this->pass_response();
			}

        if ($data1) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count(data1);
            $response['data'] = $data1;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
}
	
	 public function password($data = '') {
		 //print_r($data);
		 //exit;
		 
		 if (empty($data[0])) {
			return $this->empty_response();
		} else {
         
		 $sql = $this->db->query("SELECT otp FROM apiauth_user where otp ='$data[0]'");
         $cek_id = $sql->num_rows();
		// $sql = $this->db->query("SELECT otp FROM apiauth_user where username ='$data[0]'");
         //$cek_user = $sql->num_rows();
      
			
		if ($cek_id > 0 ) {
			
			$cek_otp = $this->db->get_where('apiauth_user', array('otp' => $data[0]))->result();
			//print_r($cek_otp);
			//exit;
			
			
			
			
			$data1 = array(
                    'password' => md5($data[1]),
                  
                );
			//print_r($data1[password]);
			//exit;
			$this->db->set('password',$data1[password]);
			$this->db->where('idauthuser', $cek_otp[0]->idauthuser);
			$supdate = $this->db->update('apiauth_user');
			$massage = 'Password Berhasil Di Ubah Silakan Login rabbani.id dengan username : ' . $cek_otp[0]->username . ' dan password : ' . $data[1] . ' ';
            $this->sms->SendSms($cek_otp[0]->hp, $massage);
			
					
			} else {
				
				return $this->otp_response();
			}

        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count(supdate);
            $response['data'] = $supdate;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
}

 public function getblog() {
       
        $dataCat = $this->db->get_where('blog')->result();
       

        if ($dataCat) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCat);
            $response['data'] = $dataCat;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
	
	 public function getcomment($data = '') {
		  

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			 //print_r($verify);
				//exit;
            if (!empty($verify)) {
                
			
				//print_r($datax);
				//exit;
				$this->db->select('a.*,b.firstname,b.lastname');
                $this->db->where('idproduct', $data[1]);
				$this->db->join('apiauth_user b', 'b.idauthuser = a.idauthuser');
                $dataCat = $this->db->get_where('comment as a')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	 public function addcomment($data = '') {
          

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            // print_r($verify[0]->idauthuser);
            //exit;
            if (!empty($verify)) {
                    
                    //DEL OLD COMMENT//
                        $idauthuser = $verify[0]->idauthuser;
                        $idproduct = $data[1];
                        $removeComment = $this->db->where('idproduct', $idproduct)->where('idauthuser', $idauthuser)->delete('comment');
						$removestar = $this->db->where('idproduct', $idproduct)->where('idauthuser', $idauthuser)->delete('star');
                    //END DELL

                 $datax = array(
                    'comment' => $data[2],
                    'idproduct' => $data[1],
                    'idauthuser' => $verify[0]->idauthuser,
					'star' => $data[3],
                    'datecomment' => date('Y-m-d H:i:s')
                  
                );
                //print_r($datax);
                //exit;
                
                //$this->db->where('idproduct', $data[1]);
                $dataCat = $this->db->insert('comment', $datax);
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	
	 public function updatecomment($data = '') {
	//print_r($data);
				//exit;	  

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			 //print_r($verify);
				//exit;
            if (!empty($verify)) {
                
				// $datax = array(
                  //  'comment' => $data[2],
					
                  
              //  );
				
				
				$this->db->set('star', $data[3]);
				$this->db->set('comment', $data[2]);
				$this->db->where('idcomment', $data[1]);
				$this->db->where('idauthuser', $verify[0]->idauthuser);
                $dataCat = $this->db->update('comment');
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	 public function getreview($data = '') {
		  

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			 //print_r($verify);
				//exit;
            if (!empty($verify)) {
                
			
				//print_r($datax);
				//exit;
				
                $this->db->select('a.*,b.firstname,b.lastname');
                //$this->db->where('idproduct', $data[1]);
				$this->db->join('apiauth_user b', 'b.idauthuser = a.idauthuser');
                $dataCat = $this->db->get_where('comment as a')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	 public function addreview($data = '') {
		 // print_r($data);
				//exit;
				

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			 //print_r($verify);
				//exit;
            if (!empty($verify)) {
                
				 $datax = array(
                    'star' => $data[2],
					'idproduct' => $data[1],
					//'datereview' => date('Y-m-d H:i:s'),
					'idauthuser' => $verify[0]->idauthuser
                  
                );
				//print_r($datax);
				//exit;
				
                //$this->db->where('idproduct', $data[1]);
                $dataCat = $this->db->insert('comment', $datax);
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	
	 public function updatereview($data = '') {
	//print_r($data);
				//exit;	  

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			 //print_r($verify);
				//exit;
            if (!empty($verify)) {
                
			
				
                
				$this->db->set('star', $data[2]);
				$this->db->where('idreview', $data[1]);
				$this->db->where('idauthuser', $verify[0]->idauthuser);
                $dataCat = $this->db->update('comment');
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	 public function freegift() {
      
        $dataCat = $this->db->get_where('freegift')->result();
       
        if ($dataCat) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCat);
            $response['data'] = $dataCat;
           
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
	
	public function cekfreegift($data = '') {
		
		 $sql = $this->db->query("SELECT idproduct FROM freegift where idproduct='$data[0]'");
         $cek_cat = $sql->num_rows();
		 //print_r($data[0]);
		 //exit;
		  if ($cek_cat > 0) {
			 
			 //$this->db->select('*');
			 $this->db->where('idproduct', $data[0]);
             $dataCat = $this->db->get_where('freegift')->result();
          
          } else {
         return $this->empty_response();
          
          }
      
       
        if ($dataCat) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCat);
            $response['data'] = $dataCat;
           
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
	
	
	public function test($data = '') {
			 //print_r($data);exit;
			 $this->db->set('delpeople',1);
			 $this->db->where('id_dis',$data[0]);
             $dataCat = $this->db->update('sensus_people');
		
			
       
        if ($dataCat) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCat);
            $response['data'] = $dataCat;
           
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
	
	 public function affiliate($data = '') {
		  //	print_r($data);exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			 //print_r($verify);exit;
            if (!empty($verify)) {	
                //$this->db->select('a.*,b.firstname,b.lastname');
                //$this->db->where('idproduct', $data[1]);
				//$this->db->join('apiauth_user b', 'b.idauthuser = a.idauthuser');
                $dataCat = $this->db->get_where('affiliate')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	
	public function addaffiliate($data = '') {
		  //	print_r($data);exit;
        if (empty($data[0])|| empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			 //print_r($verify);exit;
            if (!empty($verify)) {	
				$otp = rand(pow(10, 5 - 1), pow(10, 5) - 1);
				$kode = 'RB' ;
				//echo $kode . ' ' . $otp . '<br>';
				$awal =( $kode.$otp);
				$user = $this->db->get_where('affiliate',array('idauthuser' => $verify[0]->idauthuser))->result();
				$otp = $this->db->get_where('affiliate',array('kodeaff' => $otp))->result();
				if (!empty($user)) {	
					 return $this->duplicate_response();
				} else if (!empty($otp))  {
					 return $this->duplicate_response();
                
				}else {
					$datax = array (
					'idauthuser' => $verify[0]->idauthuser,
                    'discount' => $data[1],
					'kodeaff' =>$awal
				);
				//print_r($datax);exit;
				$this->db->insert('affiliate', $datax);
				$this->db->where('idauthuser', $verify[0]->idauthuser);
                $dataCat = $this->db->get_where('affiliate')->result();
				}
				
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	public function archery() {
	     $dataCatx = $this->db->get_where('archery')->result();
                
            if ($dataCatx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $dataCatx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            } 
        
    }
	
	public function addarchery($data = '') {
         // print_r($data);exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
			
			$datay = json_decode($data[0]);
			//print_r($datay);exit;
			$cek = $this->db->get_where('archery', array('wa' => $datay->wa))->result();
			//print_r($cek);exit;
			if (!empty($cek[0]->wa)) {
				return $this->duplicate_response();
			} else {
            $datax = array(
                'nama' => $datay->nama,
                'wa' => $datay->wa,
				'alamat' => $datay->alamat,
				'ig' => $datay->ig,
				'email' => $datay->email
                );
			$this->db->insert('archery',$datax);
			}
                 
        }
            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                //$response['data'] = $dataCat;
                return $response;
            }
        
    }
	
	 public function reviewaff($data = '') {
	//print_r($data);exit;	  
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			 //print_r($verify);exit;
            if (!empty($verify)) {
				$this->db->set('status', 1);
				$this->db->where('idauthuser', $verify[0]->idauthuser);
                $this->db->update('apiauth_user');
				$dataCat = $this->db->get_where('apiauth_user',array('idauthuser' => $verify[0]->idauthuser))->result();
            } else {
                return $this->token_response();
            }

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }
	 public function email($data = '') {
	print_r($data[0]);exit;	  
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $dataCat = json_decode($data[0]);
			
			print_r($dataCat);exit;
            $this->email->EmailSend($dataCat);
			//$this->sms->SendSms($cek_otp[0]->hp, $massage);
			//print_r($datay);exit;

            if ($dataCat) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($data[0]);
                $response['data'] = $dataCat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function newproduct($page = '') {
        
         
         $this->db->select('a.idproduct');
         $this->db->where('e.delproduct', 0);
         $this->db->where('e.status', 0);
         $this->db->where('a.delproductditails', 0);
         $this->db->where('a.stock>2');
        
         $this->db->limit(10, $page);
        
         $this->db->order_by('e.dateCreate', 'DESC');
         $this->db->order_by('e.timeCreate', 'DESC');
         $this->db->group_by('a.idproduct');
         $this->db->join('product as e', 'e.idproduct = a.idproduct');
         $this->db->join('product_images_ditails as d', 'd.idpditails = a.idpditails');
         $datax= $this->db->get_where('product_ditails as a')->result();
         // print_r($datax);exit;
         
         foreach ($datax as $q){
             $this->db->select('b.idproduct,b.delproduct,e.*,b.productName,e.price,e.realprice');
             $this->db->order_by('e.idpditails', 'DESC');
             $this->db->group_by('b.idproduct');
             $this->db->where('e.stock>2');
             $this->db->where('e.delproductditails', 0);
             $this->db->where('b.delproduct', 0);
             //$this->db->join('product_images as c', 'c.idproduct = b.idproduct');
             $this->db->join('product_ditails as e', 'e.idproduct = b.idproduct');
             $user = $this->db->get_where('product as b', array('b.idproduct' => $q->idproduct))->result();
             
             foreach ($user as $y){
                $this->db->select('a.urlImage');
                $this->db->group_by('e.idpditails');
                $this->db->where('e.stock>2');
                $this->db->where('e.delproductditails', 0);
               $this->db->join('product_ditails as e', 'e.idproduct = a.idproduct');
                $image = $this->db->get_where('product_images_ditails as a', array('a.idproduct' => $y->idproduct))->result();
             
             
         }
             $dataCatx[] = array(
                        'Product' => $user,
                        'Image' => $image
                    );
            
         }
         
          
             

        if (!empty($dataCatx)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($dataCatx);
            $response['data'] = $dataCatx;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
	
	
	
}



