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

    public function duplicate_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field Sudah Terdaftar';
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

            $this->db->insert('logIp', $data);
        } else {
            $this->db->set('ttlHit', 'ttlHit+1', FALSE);
            $this->db->set('timeAccessUpdate', date('Y-m-d H:i:s'));
            $this->db->where('ipaddress', $data['ipaddress']);
            $this->db->update('logIp');
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

            //$this->db->insert('logIp', $data);
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
			
		public function addcomment($data = ''){
		 //print_r($data);
		 //exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
			// print_r($verify[0]);
			//exit;
            if (!empty($verify)) {
			$datatransaction = $this->db->get_where('transaction', array('idauthuser' => $verify[0]->idauthuser))->result();
					//print_r($datatransaction);
		//	exit;
				$datax = json_decode($data[1]);
				 //print_r($datax);
					//exit;
                 $datay = array(
				'idauthuser' => $verify[0]->idauthuser,
                'comment' => $datax->comment,
                'star' => $datax->star,
				'idtransaction' => $datatransaction[0]->idtransaction
            );
			//print_r($datay);
				//	exit;
			   $this->db->where('$datatransaction[0]->idtransaction', $datax->idtransaction);
               $this->db->insert('comment', $datay);
            } else {
                return $this->token_response();
            }


        if ($datay) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($datay);
            $response['data'] = $datay;
           
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
        $this->db->cache_on();
        $db2 = $this->load->database('db2', TRUE);
        $db2->select('a.*,c.urlImage');
        $db2->from('product as a');



        $db2->join('category as b', 'b.idcategory = a.idcategory', 'left');
        $db2->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $db2->where('delproduct', 0);
        $db2->limit(10, $page);
        $db2->group_by('idproduct');
        $db2->order_by('dateCreate', 'DESC');
        $db2->order_by('timeCreate', 'DESC');

        $query = $db2->get()->result();
		//print_r($query);
		//exit;
        foreach ($query as $q) {
            $db2->select('a.*,b.urlImage as imagesVariable');
            $db2->from('product_ditails as a');
            $db2->where('a.idproduct', $q->idproduct);
            $db2->where('stock>0');
			$db2->where('delproductditails', 0);
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
	
	    public function getDataproduct($page = '') {
        $this->db->cache_on();
        $db2 = $this->load->database('db2', TRUE);
        $db2->select('a.*,c.urlImage');
        $db2->from('product as a');



        $db2->join('category as b', 'b.idcategory = a.idcategory', 'left');
        $db2->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $db2->where('delproduct', 0);
        $db2->limit(10, $page);
        //$db2->group_by('idproduct');
        //$db2->order_by('dateCreate', 'DESC');
        $db2->order_by('idproduct', 'DESC');

        $query = $db2->get()->result();
		//print_r($query);
		//exit;
       
        if (!empty($query)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }
    
        public function getDataproductrandom($page = '') {
        $this->db->cache_on();
        $db2 = $this->load->database('db2', TRUE);
        $db2->select('a.*,c.urlImage');
        $db2->from('product as a');
        $db2->join('category as b', 'b.idcategory = a.idcategory', 'left');
        $db2->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
        $db2->where('delproduct', 0);
        $db2->limit(10, $page);
        //$db2->group_by('idproduct');
        $db2->order_by('idproduct', 'RANDOM');
       

        $query = $db2->get()->result();
		//print_r($query);
		//exit;
       
        if (!empty($query)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($query);
            $response['data'] = $query;
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

    public function getDataByCat($data = '') {
        $this->db->cache_on();
        //print_r($data);
        //exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $db2 = $this->load->database('db2', TRUE);
            $db2->select('a.*,b.*');
            $db2->from('product as a');
            $db2->join('category as b', 'b.idcategory = a.idcategory', 'left');
            $db2->where('a.idcategory', $data[0]);
            //$db2->where('stock>0');
            $db2->where('delproduct', 0);
            $query = $db2->get()->result();
            //print_r($query);
            //exit;
            foreach ($query as $q) {
                $data = array(
                    'idproduct' => $q->idproduct
                );
                $db2->select('a.*,b.urlImage as imagesVariable');
                $db2->from('product_ditails as a');
                $db2->where('a.idproduct', $q->idproduct);
                $db2->where('stock>0');
                $db2->where('delproductditails', 0);
                $db2->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                $query = $db2->get()->result();
                $dataq = array(
                    'idproduct' => $q->idproduct
                );
                $queryq = $db2->get_where('product_images', $dataq)->result();

                $datax[] = array(
                    'product' => $q,
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

    public function ditailsGetData($data = '') {
        $this->db->cache_on();
		$datax = array(
                   
                    'dateview' => date('Y-m-d'),
                    'ip' => ($data[1]),
                    'idauthuser' => ($data[2]),
					'idproduct' => ($data[0]),
					
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

            foreach ($query as $x) {
                $db2->select('size');
                $db2->from('product_ditails');
                $db2->where('idproduct', $x->idproduct);
                $db2->where('delproductditails', 0);
                $db2->where('stock>0');
                $db2->group_by('size');
                $query1 = $db2->get()->result();
            }



            foreach ($query as $x) {
                $db2->select('a.collor');
                $db2->from('product_images_ditails as a');
				$db2->join('product_ditails as b', 'b.idpditails = a.idpditails');
                $db2->where('a.idproduct', $x->idproduct);
                $db2->where('b.delproductditails', 0);
                $db2->where('b.stock>0');
                $db2->group_by('collor');
                $query2 = $db2->get()->result();
            }

            foreach ($query as $x) {
                $db2->select('a.idpditails,a.size,a.collor,a.realprice,a.priceDiscount,a.price,a.stock');
                $db2->from('product_ditails as a');
				$db2->join('product_images_ditails as b', 'b.idpditails = a.idpditails');
                $db2->where('a.delproductditails', 0);
                $db2->where('a.stock>0');
                $db2->where('b.idproduct', $x->idproduct);
				$db2->group_by('a.idpditails');
                $query3 = $db2->get()->result();
            }

            foreach ($query as $q) {
                $db2->select('a.idpditails,a.idproduct,a.skuPditails,a.size,a.collor,a.weight,a.price,a.stock,a.priceDiscount,b.urlImage as imagesVariable');
                $db2->from('product_ditails as a');
                //$this->db->group_by('a.size');
                $db2->where('a.idproduct', $q->idproduct);
                $db2->where('delproductditails', 0);
                //$db2->group_by('a.collor');


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
	
	 public function addOrders($data = '') {


        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {


                $data = json_decode($data[2]);

                $dataTrx = array(
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'noInvoice' => $verify[0]->idauthuser . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
                    'shipping' => ($data->shipping),
                    'shippingprice' => ($data->shippingprice),
                    'idauthuser' => $verify[0]->idauthuser,
                    'idpeople' => ($data->idpeople),
                    'payment' => ($data->payment)
                );

                $supdate = $this->db->insert('transaction', $dataTrx);
                $insert_id = $this->db->insert_id();


                if (!empty($data)) {
                    foreach ($data->dataOrders as $dO) {
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
                                'subtotal' => ($dataProduct[0]->price) * $dataProduct[0]->qty
                            );
                            
                            $subtotal[] = $dataOrdersx['subtotal'];
                            $subdisc[] = $dataOrdersx['disc'];
                            $totalweight[] = ($dataOrdersx['weight']);


                            $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataProduct[0]->qty);
                            $this->db->insert('transaction_details', $dataOrdersx);
                            $this->db->where('idcart', $dO->idcart);
                            $this->db->delete('shop_cart');
                        }
                    }

                    $cost = $data->shippingprice * ceil(array_sum($totalweight) / 1000);

                    $this->db->set('cost', ($cost), true);
                    $this->db->set('subtotal', array_sum($subtotal), true);
                    $this->db->set('discount', array_sum($subdisc), true);
                    $sql = $this->db->query("SELECT vouchercode FROM voucher where vouchercode ='$data->voucher'");
                    $cek_id = $sql->num_rows();
                    if ($cek_id > 0) {
                        $voucher = $this->db->get_where('voucher', array('vouchercode' => $data->voucher))->result();
                        $voucher1 = ((array_sum($subtotal) - array_sum($subdisc)) * ($voucher[0]->voucherdisc / 100));
                    } else {
                        $voucher1 = 0;
                    }
					//$this->db->insert('transaction_details', array('discvoucher' => $voucher1));
                    $total = (array_sum($subtotal) + ($cost) - array_sum($subdisc) - ($voucher1) + $data->kodeunik);
                    $this->db->set('discvoucher',$voucher1);
                    $this->db->set('totalpay', array_sum($subtotal) + ($cost) - array_sum($subdisc) - ($voucher1)+ $data->kodeunik, true);
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
// print_r($data);
// exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
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

    public function search($data = '') {
        $db2 = $this->load->database('db2', TRUE);
		//print_r($verify);
         //exit;
        //$query = "SELECT * FROM product WHERE productName LIKE '%" .$data[0]. "%'";
        //$sql = ("SELECT productName FROM product where productName LIKE '%" .$data[0]. "%'");
        // $sql = $this->mysql_query("select * from product where productName like '%".$data[0]."%'");
        $db2->select('a.*,b.*');
        $db2->from('product as a');
        $db2->join('product_images as b', 'b.idproduct = a.idproduct', 'left');
        $db2->like('productName', $data[0]);
        $db2->where('delproduct', 0);
        //$db2->where('stock>0');
        $db2->group_by('a.idproduct');
		
        $sql = $db2->get()->result();
		if (empty($data[0])) {
                $supdate = 1;
		
		}else {
			$datax = array (
			'keyword' => $data[0],
			'datetime' => date('Y-m-d H:i:s')
		);
		}
		$db2->insert('log_keyword', $datax);
		

        //  $sql = '1';

        if (!empty($sql)) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data successfully processed.';
            $response['totalData'] = count($sql);
            $response['data'] = $sql;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive.';
            return $response;
        }
    }

    public function login($data = '') {
        // print_r($data);
        // exit;

        $data = array(
            'timeCreate' => date('H:i:s'),
            'dateCreate' => date('Y-m-d '),
            'hp' => $data[0],
            'password' => md5($data[1])
        );
        $sql = $this->db->insert('apiauth_user', $data);

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
        //print_r($data);
        //exit;


        $data = array(
            'timeCreate' => date('H:i:s'),
            'dateCreate' => date('Y-m-d'),
            'username' => ($data[0]),
            'password' => md5($data[1]),
            'firstname' => ($data[2]),
            'lastname' => ($data[3]),
            'hp' => ($data[4]),
            'email' => ($data[5])
        );


        $dataCat = $this->db->get_where('apiauth_user', array('hp' => $data['hp']))->result();
        // print_r($dataCat);
        //exit;

        if (empty($dataCat)) {
            $supdate = $this->db->insert('apiauth_user', $data);

            $message = 'Assalamualaikum kak *_' . $data['firstname'] . '_*. 
Selamat datang di WhatsApp *Rabbani!* Melalui kanal ini, Anda akan menerima informasi berupa notifikasi terkait akun dan transaksi Anda di rmall.id';
            // $this->wa->SendWa($data['hp'], $message);
        } else {
            $this->db->set($data);
            $this->db->where('hp', $dataCat[0]->hp);
            $supdate = $this->db->update('apiauth_user', $data);
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
//}
// }
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
                $db2 = $this->load->database('db2', TRUE);
                $db2->select('*');
                $db2->where('idauthuser', $verify[0]->idauthuser);
                $db2->order_by('idtransaction', 'DESC');
                $dataCat = $db2->get_where('transaction')->result();
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

    public function cart($data = '') {

        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                //$db2 = $this->load->database('db2', TRUE);
                $this->db->select('a.*,b.skuPditails,b.price,b.stock,b.collor,b.size,b.realprice,b.priceDiscount,c.productName,,d.urlImage');
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
                $response['message'] = 'Data failed to receive or data empty.';
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
                        );

                        // print_r($dataOrdersx);
                        $query = $this->db->insert('shop_cart', $dataOrdersx);
                    } else {

                        $this->db->set('qty', 'qty+' . $dataOrders->qty, FALSE);
                        $this->db->where('idauthuser', $verify[0]->idauthuser);
                        $query = $this->db->update('shop_cart');
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
//        print_r($data);
//        exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                $this->db->select('a.*, b.urlImage');
                $this->db->join('product_images as b', 'b.idproduct = a.idproduct', 'left');
                $this->db->where('idtransaction', $data[1]);
                $this->db->group_by('productName');
                $query = $this->db->get_where('transaction_details as a')->result();
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

    public function debitStock($idpditails = '', $sku = '', $debit = '') {
        $this->db->set('stock', 'stock-' . $debit, FALSE);
        // $this->db->set('physical', 'physical-' . $debit, FALSE);
        $this->db->where('idpditails', $idpditails);
        $this->db->where('skuPditails', $sku);
        $this->db->update('product_ditails');
    }

    public function 	s($data = '', $v = '') {

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
                        $message = 'rmall.id : Silakan Transfers Rp ' . $total . ',  BCA 7771503334, MANDIRI 1310012668739, BNI 308050850 AN Rabbani Asysa, Batas Pembayaran 1x24 Jam';
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
                $db2 = $this->load->database('db2', TRUE);
                $db2->select('*');
                $db2->where('idauthuser', $verify[0]->idauthuser);
                $db2->where('idpeople', $data[1]);
                $query = $db2->get_where('sensus_people')->result();
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
                $db2->select('*');
                $db2->where('idauthuser', $verify[0]->idauthuser);
                $db2->where('delpeople', 0);
                $query = $db2->get_where('sensus_people')->result();
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
        // print_r($data);
        //exit;

        if (empty($data[0]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('1015_city');
                //$this->db->join('1015_city as b', 'b.province_id = a.province_id', 'left');
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
                $db2 = $this->load->database('db2', TRUE);
                $db2->select('c.productName,a.*,b.realprice,b.idproduct,b.stock,b.collor,b.size,d.urlImage');
                $db2->join('product_ditails as b', 'b.idpditails = a.idpditails', 'left');
                $db2->join('product as c', 'c.idproduct = b.idproduct', 'left');
                $db2->join('product_images_ditails as d', 'd.idpditails = b.idpditails', 'left');
                $db2->group_by('idpditails');
                $db2->where('idauthuser', $verify[0]->idauthuser);
                $dataCat = $db2->get_where('whishlist as a')->result();
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
                $db2 = $this->load->database('db2', TRUE);
                $db2->select('*');
                $db2->where('idauthuser', $verify[0]->idauthuser);
                $dataCat = $db2->get_where('whishlist')->result();

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
                $dataCat = $db2->get_where('store')->result();
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

    public function quest($data = '') {
// print_r($data);
// exit;
        $dataCode = array(
            'keyCode' => md5(time() . rand(pow(10, 5 - 1), pow(10, 5) - 1)),
        );

        $this->db->insert('apiauth_user', $dataCode);


        if ($dataCode) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($dataCode);
            $response['data'] = $dataCode;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }
	
	public function getflashsale() {
	     $dataCatx = $this->db->get_where('flashsale')->result();
       if (!empty($dataCatx)) {
                
				
               
				//print_r($dataCatx);
				//exit;
				
				 $data = json_decode($dataCatx[0]->idproduct);
				 //print_r($dataCatx[0]);
				// exit;
			  foreach ($data as $ddt) {
				 $datax = array(
                            'idproduct' => $ddt->idproduct,
							
                          
                        );
						//print_r($datax);
						
					                 
            $x= $dataCatx[0]->flashsale/100;
            $y= $dataCatx[0]->flashsale/100;
             

            $this->db->set('realprice', 'price*'.$y,FALSE);
			$this->db->set('priceDiscount','price*'.$x,FALSE);
			$this->db->where('idproduct', $datax['idproduct']);
            $supdate = $this->db->update('product_ditails');
				//$datay[] = $datax ;
			  }
	   }else {
		    return $this->empty_response();
	   }
				
				
           
                
                if ($supdate) {
                    $response['status'] = 200;
                    $response['error'] = false;
                    $response['message'] = 'Data received successfully.';
                    $response['data'] = $dataCatx;
                    return $response;
                } else {
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCat;
                    return $response;
                }
            
        
    }

}
