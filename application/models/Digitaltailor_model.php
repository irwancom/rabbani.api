<?php

class Digitaltailor_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
        $this->load->helper('date');
    }

    public function empty_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field tidak boleh kosong';
        return $response;
    }

     public function user_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Username Salah';
        return $response;
    }

     public function address_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Alamat Belum Di isi';
        return $response;
    }




    public function null_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Tidak Ada Data Di Database';
        return $response;
    }

    public function password_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Password tidak sama';
        return $response;
    }

    public function pass_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Password Salah';
        return $response;
    }
	 
	public function resi_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Resi Tidak Terdaftar';
        return $response;
    }

    public function wa_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'WA Sudah Terdaftar';
        return $response;
    }

     public function email_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Email Sudah Terdaftar';
        return $response;
    }

    public function token_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Token tidak boleh salah';
        return $response;
    }

    public function duplicate_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field Sudah Terdaftar!!!';
        return $response;
    }
	
	public function cek_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'No Order Sudah Pernah DiCEK!!!';
        return $response;
    }

    public function ukuran_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Ukuran Gambar Salah Sudah';
        return $response;
    }

    public function verfyAccount($keyCode = '') {
        $data = array(
            "keyCode" => $keyCode
           // "secret" => $secret
        );
        //$this->db->select('c.namestore, a.*');
        //$this->db->Join('store as c', 'c.idstore = a.idstore', 'left' );
        $query = $this->db->get_where('apiauth_user', $data)->result();
        return $query;
    }

    public function addtailor($data = '') {
    //print_r($data[0]);exit;     
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $datax = json_decode($data[0]);
            $cek = $this->db->get_where('tailor', array('email' => $datax->email))->result();
            //print_r($cek);exit;   
            if (!empty($cek)) {
                return $this->duplicate_response();
                } else {
                    $datay = array(
                        'date' =>  date('Y-m-d'),
                        'time' => date('H:i:s'),
                        'name' => $datax->nama,
                        'wa' => $datax->wa,
                        'email' => $datax->email,
                        'address' => $datax->alamat
                    );
                    $supdate = $this->db->insert('tailor', $datay);
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

    public function viewtailor() {
    //print_r($data[0]);exit;     
       
            $this->db->order_by('idtailor', 'DESC');
            $datay = $this->db->get_where('tailor')->result();
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

    
   
    public function datauser($data = '') {
       // print_r($data);exit;
                $this->db->limit(10,$data[0]);
                $this->db->where('source', 'tailordigital.id');
                $this->db->where('status', '0');
                $this->db->order_by('idauthuser', 'desc');
                $dataCatx = $this->db->get_where('apiauth_user')->result();
          
            if ($dataCatx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCatx);
                $response['data'] = $dataCatx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        
    }

     public function ditailsuser($data = '') {

        
            $verify = $this->verfyAccount($data[0]);
           // print_r($verify);exit;
            if (!empty($verify)) {
                $this->db->select('a.*,b.id_prov,b.id_city,b.id_dis,b.idpeople,b.pos,c.*');
                $this->db->where('a.status', '0');
                $this->db->where('a.idauthuser', $data[1]);
                $this->db->group_by('a.idauthuser');
                $this->db->join('apiauth_user_images as c', 'c.idauthuser = a.idauthuser','left');
                $this->db->join('sensus_people as b', 'b.idauthuser = a.idauthuser','left');
                $dataCatx = $this->db->get_where('apiauth_user as a')->result();
                // print_r($dataCatx);exit;
                 if(!empty($dataCatx)){
                $this->db->where('e.idauthuser', $data[1]);
                $this->db->where('e.id_dis', $dataCatx[0]->id_dis);
                $this->db->group_by('e.idauthuser');
                $this->db->join('sensus as d', 'd.idsensus = e.id_dis','left');
                $address = $this->db->get_where('sensus_people as e')->result();  
                }
                
                
            } else {
                return $this->token_response();
            }
       
            if (!empty($dataCatx)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCatx);
                $response['data'] = $dataCatx;
                $response['address'] = $address;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        
    }

    public function adduser($data = '') {
        // print_r($data);exit;
        $datay = json_decode($data[0]);
        //print_r($datay);exit;
        if (empty($datay->namalengkap)||empty($datay->email)||empty($datay->wa)||empty($datay->password)||empty($datay->confirm)) {
            return $this->empty_response();
        } else if (($datay->password)!==($datay->confirm)) {
             return $this->password_response();
        } else {
            
            $cek = $this->db->get_where('apiauth_user', array('hp' => $datay->wa))->result();
            $cek1 = $this->db->get_where('apiauth_user', array('email' => $datay->email))->result();
            
            //print_r($cek);exit;
            if (!empty($cek[0]->hp)) {
                return $this->wa_response();
            } else if (!empty($cek1[0]->email)) {

                return $this->email_response();
            } else {
            $datax = array(
                'timeCreate' => date('H:i:s'),
                'dateCreate' => date('Y-m-d'),
                'firstname' => $datay->namalengkap,
                'email' => $datay->email,
                'hp' => $datay->wa,
                'password' => md5($datay->confirm),
                'source' => 'tailordigital.id',

               
                );
            $this->db->insert('apiauth_user',$datax);
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
                return $response;
            }
    }

    public function updateuser($data = '') {


        $verify = $this->verfyAccount($data[0]);
           // print_r($verify);exit;
        if (!empty($verify)) {
            $datay = json_decode($data[1]);

            if(!empty($datay->password)AND!empty($datay->confirm)AND($datay->password)==($datay->confirm)) {
                    $datax = array(
                        'timeCreate' => date('H:i:s'),
                        'dateCreate' => date('Y-m-d'),
                        'firstname' => $datay->namalengkap,
                        'email' => $datay->email,
                        'hp' => $datay->wa,
                        'password' => md5($datay->confirm)
               
                );
            $this->db->where('idauthuser',$verify[0]->idauthuser);
            $this->db->update('apiauth_user',$datax);
             } else  {
                 $datax = array(
                        'timeCreate' => date('H:i:s'),
                        'dateCreate' => date('Y-m-d'),
                        'firstname' => $datay->namalengkap,
                        'hp' => $datay->wa,
                        'email' => $datay->email
                );
            $this->db->where('idauthuser',$verify[0]->idauthuser);
            $this->db->update('apiauth_user',$datax);
            
            }
        } else {
            return $this->token_response();
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
                return $response;
            }
    }

     public function UserUpdatePhoto($data = '') {

       // print_r($data);exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
             $verify = $this->verfyAccount($data[0]);
            // print_r($verify);exit;
            if (!empty($verify)) {
                $cek = $this->db->get_where('apiauth_user_images', array('idauthuser' => $verify[0]->idauthuser))->result();
                if(!empty($cek)){
                    $dataz = array(
                    'urlImage' => $data[1]['upload_data']['file_url'],
                    'dir' => $data[2],
                    'imageFile' => $data[1]['upload_data']['file_name'],
                    'size' => $data[1]['upload_data']['file_size'],
                    'type' => $data[1]['upload_data']['image_type']
                );
                $this->db->where('idauthuser', $verify[0]->idauthuser);
                $this->db->update('apiauth_user_images', $dataz);
               
                } else {
                    $dataz = array(
                    'idauthuser' => $verify[0]->idauthuser,
                    'urlImage' => $data[1]['upload_data']['file_url'],
                    'dir' => $data[2],
                    'imageFile' => $data[1]['upload_data']['file_name'],
                    'size' => $data[1]['upload_data']['file_size'],
                    'type' => $data[1]['upload_data']['image_type']
                );
                $this->db->insert('apiauth_user_images', $dataz);
                
                }
                $this->db->where('a.idauthuser', $verify[0]->idauthuser);
                $this->db->join('apiauth_user_images as b', 'b.idauthuser = a.idauthuser');
                $update = $this->db->get_where('apiauth_user as a')->result(); 
                
                
            
    } else {
        return $this->token_response();
    }
                    
            if ($update) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $update;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function disableuser($data = '') {
        //print_r($data);exit;
         if (empty($data[0])) {
            return $this->empty_response();
         } else {
            $cek = $this->db->get_where('apiauth_user', array('idauthuser' => $data[0]))->result();
          // print_r($cek[0]->status);exit;
           if (empty($cek)) {
            return $this->null_response();
                } else if ($cek[0]->status==1)  {
                    $this->db->set('status',0);
                    $this->db->where('idauthuser',$data[0]);
                    $this->db->update('apiauth_user');
                    $this->db->select('status');
                    $datax = $this->db->get_where('apiauth_user', array('idauthuser' => $data[0]))->result();
                } else {
                    $this->db->set('status',1);
                    $this->db->where('idauthuser',$data[0]);
                    $this->db->update('apiauth_user');
                    $this->db->select('status');
                    $datax = $this->db->get_where('apiauth_user', array('idauthuser' => $data[0]))->result();
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
                return $response;
            }
    }

    public function useraddress($data = '') {
        
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);

            if (!empty($verify)) {

                $datax = json_decode($data[1]);
                 // print_r($datax);exit;
                $cek = $this->db->get_where('sensus_people', array('idauthuser' =>$verify[0]->idauthuser))->result();
                    
                if(!empty($cek)){
                    $data2 = array(
                    'pos' => $datax->pos,
                    'address' => $datax->address,
                    'id_city' => $datax->id_city,
                    'id_prov' => $datax->id_prov,
                    'id_dis' => $datax->id_dis,
                   

                );
                   // print_r($data2);exit;
                $this->db->where('idpeople', $cek[0]->idpeople );
                $this->db->where('idauthuser', $verify[0]->idauthuser);
                $xupdate = $this->db->update('sensus_people', $data2);
                // print_r($data2);exit;
            } else {
                $data1 = array(
                    'pos' => $datax->pos,
                    'address' => $datax->address,
                    'id_city' => $datax->id_city,
                    'id_prov' => $datax->id_prov,
                    'id_dis' => $datax->id_dis,
                    'idauthuser' => $verify[0]->idauthuser,
                   
                );
                // print_r($data2);exit;
                // $this->db->where('idauthuser', $verify[0]->idauthuser);
                $xupdate = $this->db->insert('sensus_people', $data1);
            }
            $this->db->where('b.idpeople', $cek[0]->idpeople);
            $this->db->where('a.idauthuser', $verify[0]->idauthuser);
            $this->db->join('sensus_people as b', 'b.idauthuser = a.idauthuser');
            $update = $this->db->get_where('apiauth_user as a')->result(); 
        } else {
            return $this->token_response();
        }


            if ($update) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $update;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data already exists.';
                $response['data'] = $update;
                return $response;
            }
            //  }
        }
    }


     public function logintailor($data = '') {
    //print_r($data[0]);exit;     
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $datax = json_decode($data[0]);
             $datay = array(
                "hp" => $datax->hp,
                "password" => md5($datax->password),
                //"idstore" => 1,
                //"status" => 0
            );
            // print_r($datay);exit;
            $checkAuth = $this->db->get_where('apiauth_user', $datay)->result();

                if  (!empty($checkAuth)) {
                    
                   
                    $dataCode = array(
                     'keyCode' => md5($datax->email . $datax->password . $datax->hp)
                    );
                    $this->db->set($dataCode);
                    $this->db->where('hp', $datax->hp);
                    $this->db->or_where('email', $datax->email);
                    $update = $this->db->update('apiauth_user');
                    if($update){
                    $this->db->where('email', $datax->email);
                    $this->db->or_where('hp', $datax->hp);
                    $dataz = $this->db->get_where('apiauth_user')->result();
                    }

                    
               
                    //print_r($dataz);exit;  
            } else {
                 return $this->user_response();
            }
            if ($dataz) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataz);
                $response['data'] = $dataz;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

     public function forgettailor($data = '') {
         // print_r($data);exit;
         
         if (empty($data[0])) {
            return $this->empty_response();
        } else {
         
         $sql = $this->db->query("SELECT hp FROM apiauth_user where hp ='$data[0]'");
         $cek_id = $sql->num_rows();
        // $sql = $this->db->query("SELECT otp FROM apiauth_user where username ='$data[0]'");
         //$cek_user = $sql->num_rows();
      
            
        if ($cek_id > 0 ) {
            
           
          
            $pass =  rand(pow(10, 5 - 1), pow(10, 5) - 1);
            $pass1 = md5($pass);
            // print_r($pass1);exit;
            $this->db->set('password',$pass1);
            $this->db->where('hp', $data[0]);
            $supdate = $this->db->update('apiauth_user');
            $massage = 'Password Berhasil Di Ubah Silakan Login tailordigital.id dengan username : ' . $data[0] . ' dan password : ' . $pass . ' ';
            $this->sms->SendSms($data[0], $massage);
            $supdate = $this->db->get_where('apiauth_user', array('hp' => $data[0]))->result();
                    
            } else {
                
                return $this->null_response();
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

public function addorder($data = '') {
    
         
         if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0]);
            if (!empty($verify)) {
                 

                $datax = json_decode($data[1]);
                // print_r($datax);exit;
                $user = $this->db->get_where('sensus_people', array('idpeople' => $datax->idpeople))->result();
                // print_r($user);exit;
                if (!empty($user)) {
                $dataTrx = array(
                    // 'idauth' => $verify[0]->idauthstaff,
                    'idauthuser' => $user[0]->idauthuser,
                    // 'idstore' => $verify[0]->idstore,
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'orderBy' => 'tailordigital',
                    'noInvoice' => $user[0]->idauthuser . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
                    'shipping' => 'JNE',
                    'shippingprice' => ($datax->shippingprice),
                    // 'addressSender' => $verify[0]->namestore,
                    'idpeople' => ($datax->idpeople),
                    'payment' => ($datax->payment)
                );
                // print_r($dataTrx);exit;

                $supdate = $this->db->insert('transaction', $dataTrx);
                $insert_id = $this->db->insert_id();

                $this->db->join('product as b', 'b.idproduct = a.idproduct', 'left');
                $dataProduct = $this->db->get_where('product_ditails as a', array('a.idpditails' => $datax->idpditails))->result();
                   

                    if (!empty($dataProduct)) {
                        $dataOrdersx = array(
                            'idtransaction' => $insert_id,
                            'idproduct' => $dataProduct[0]->idproduct,
                            'idpditails' => $dataProduct[0]->idpditails,
                            'productName' => $dataProduct[0]->productName,
                            'skuPditails' => $datax->fitting,
                            'collor' => $dataProduct[0]->collor,
                            // 'size' => $dataProduct[0]->size,
                            'price' => $dataProduct[0]->price,
                            // 'qty' => $dataOrders->qty,
                            'weight' => ($dataProduct[0]->weight),
                            // 'disc' => ($dataProduct[0]->priceDiscount) * $dataOrders->qty,
                            //'cost' => $dataOrders->cost * $dataProduct[0]->weight,
                            // 'subtotal' => ($dataProduct[0]->price) * $dataOrders->qty
                        );

                         // print_r($dataOrdersx);

                        // $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataOrders->qty);

                        $this->db->insert('transaction_details', $dataOrdersx);
                        // $subtotal[] = $dataOrdersx['subtotal'];
                        // $subdisc[] = $dataOrdersx['disc'];
                        // $totalweight[] = ($dataOrdersx['weight']);
                        //print_r($cost);
                    } else {
                        return $this->null_response();
                    }
                
                $cost = $data->shippingprice * ceil(array_sum($dataOrdersx['weight']) / 1000);
                $this->db->set('subtotal', array_sum( $dataOrdersx['price']), true);
                $this->db->set('cost', ($cost), true);
                // $this->db->set('discount', array_sum($subdisc), true);
                $this->db->set('totalpay',  $dataOrdersx['price'] + ($cost), true);
                $this->db->where('idtransaction', $insert_id);
                $this->db->update('transaction');
                // $stUpdate = 1;
            } else {
                $stUpdate = 0;
            }
                } else {
                     return $this->address_response();
                }


                
            if (!empty($dataProduct)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['dataTransaction'] = array(
                    'ordersDay' => $dataTrx['dateCreate'],
                    'corp' => $dataTrx['orderBy'],
                    'noInvoice' => $dataTrx['noInvoice'],
                    'shipping' => $dataTrx['shipping'],
                    // 'addressSender' => $dataTrx['addressSender'],
                    'shippingprice' => $cost,
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

    public function dataorder($data = '') {

                
                $this->db->select('a.*,b.*,c.id_prov,c.id_city,c.id_dis,c.address,c.pos,d.firstname,d.lastname,d.username,d.hp,d.email');
                $this->db->where('orderBy','tailordigital');
                $this->db->group_by('a.idtransaction');
                // $this->db->limit(10,0);
                $this->db->Join('transaction_details as b', 'b.idtransaction = a.idtransaction', 'left' );
                 $this->db->Join('sensus_people as c', 'c.idpeople = a.idpeople' );
                 $this->db->Join('apiauth_user as d', 'd.idauthuser = a.idauthuser' );
        $data = $this->db->get_where('transaction as a')->result();

                
            if (!empty($data)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['Count'] = count($data);
                $response['Data'] = $data;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        
    }

     public function ditailorders($data = '') {
        // print_r($data);exit;

                $this->db->select('a.*,b.*,c.id_prov,c.id_city,c.id_dis,c.address,c.pos,d.firstname,d.lastname,d.username,d.hp,d.email');
                $this->db->where('a.idtransaction',$data[0]);
                $this->db->where('a.orderBy','tailordigital');
                // $this->db->group_by('a.idtransaction');
                $this->db->limit(10,0);
                $this->db->Join('transaction_details as b', 'b.idtransaction = a.idtransaction', 'left' );
                $this->db->Join('sensus_people as c', 'c.idpeople = a.idpeople' );
                $this->db->Join('apiauth_user as d', 'd.idauthuser = a.idauthuser' );
        $data = $this->db->get_where('transaction as a')->result();

                
            if (!empty($data)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['Count'] = count($data);
                $response['Data'] = $data;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        
    }

    public function dataproduct($data = '') {

                $this->db->where('status',1);
                $this->db->Join('product_images as c', 'c.idproduct = a.idproduct', 'left' );
                $update = $this->db->get_where('product as a')->result();
                // print_r($update);exit;

                foreach ($update as $product) {
                      // print_r($product->idproduct);
                $this->db->where('idproduct',$product->idproduct);
                $query = $this->db->get_where('product_ditails')->result();

          

                 $datax[] = array(
                        'product' => $product,
                        'totalvar' => count($query),
                        'variasiProduct' => $query,
                        // 'imageProduct' => $queryq
                    );
                       }
                 

                 // print_r($query);exit;
      
            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
    
    }

     public function productcategory($data = '') {

                $this->db->where('status',1);
                $this->db->where('delproduct',0);
                $this->db->where('dtcategory',$data[0]);
                $this->db->Join('product_images as c', 'c.idproduct = a.idproduct', 'left' );
                $update = $this->db->get_where('product as a')->result();
                // print_r($update);exit;

                foreach ($update as $product) {
                      // print_r($product->idproduct);
                $this->db->where('idproduct',$product->idproduct);
                $query = $this->db->get_where('product_ditails')->result();

          

                 $datax[] = array(
                        'product' => $product,
                        'totalvar' => count($query),
                        'variasiProduct' => $query,
                        // 'imageProduct' => $queryq
                    );
                       }
                 

                 // print_r($query);exit;
      
            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['total'] = count($datax);
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
    
    }

     public function ditailproduct($data = '') {

       // print_r($data);exit;
                $this->db->where('a.status',1);
                $this->db->where('a.idproduct',$data[0]);
                $this->db->Join('product_images as c', 'c.idproduct = a.idproduct', 'left' );
                $update = $this->db->get_where('product as a')->result();

                foreach ($update as $product) {
                    // print_r($product->idproduct);exit;
                   $this->db->where('a.idproduct',$product->idproduct);
                   $this->db->Join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left' );
                   $query = $this->db->get_where('product_ditails as a')->result();

                }

                 $datax[] = array(
                        'product' => $product,
                        'totalvar' => count($query),
                        'variasiProduct' => $query,
                        // 'imageProduct' => $queryq
                    );
      
            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datax;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
    
    }

 public function productAddData($data = '') {
        // print_r($data);exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {

                $datam = json_decode($data[0]);
                // print_r($datam);exit;
                // $cek = $this->db->get_where('product', array('productName' => $datam->productname))->result();
                // if(!empty($cek)) {
                //      return $this->duplicate_response();
                // } else {
                    $datac = array(
                            'dtcategory' => $datam->category,
                            'timeCreate' => date('H:i:s'),
                            'dateCreate' => date('Y-m-d'),
                            'productName' => rawurldecode($datam->productname),
                            'status' => 1
                    );
                    $this->db->insert('product', $datac);
                    $idproduct = $this->db->insert_id();

                    $datax = array(
                        'urlImage' => $data[1]['upload_data']['file_url'],
                        'dir' => $data[2],
                        'imageFile' => $data[1]['upload_data']['file_name'],
                        'size' => $data[1]['upload_data']['file_size'],
                        'type' => $data[1]['upload_data']['image_type'],
                        'idproduct' => $idproduct
                    );

                    $this->db->insert('product_images',$datax);

                // }
                

            if (!empty($datac)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['count'] = count($datac);
                $response['data'] = $datac;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

     public function productditailsadd($data = '') {
        // print_r($data);exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {

                $datam = json_decode($data[0]);
                // print_r($datam);exit;
                // $cek = $this->db->get_where('product_ditails', array('collor' => $datam->variasi))->result();
                // if(!empty($cek)) {
                //      return $this->duplicate_response();
                // } else {
                    $datac = array(
                            'idproduct' => $datam->idproduct,
                            'collor' => $datam->variasi,
                            'price' => $datam->harga
                    );
                    $this->db->insert('product_ditails', $datac);
                    $idpditails = $this->db->insert_id();

                    $datax = array(
                        'urlImage' => $data[1]['upload_data']['file_url'],
                        'dir' => $data[2],
                        'imageFile' => $data[1]['upload_data']['file_name'],
                        'size' => $data[1]['upload_data']['file_size'],
                        'type' => $data[1]['upload_data']['image_type'],
                        'idpditails' => $idpditails,
                        'idproduct' => $datam->idproduct
                    );

                    $this->db->insert('product_images_ditails',$datax);

                // }
                

            if (!empty($datac)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['count'] = count($datac);
                $response['data'] = $datac;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }


    public function productDisableData($data = '') {
          // print_r($data); exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
           
                 $cek = $this->db->get_where('product', array('idproduct' => $data[0]))->result();
                 // print_r($cek[0]);exit; 
                if (!empty($cek)) {
                    if ($cek[0]->delproduct==0) {
                        $this->db->set('delproduct', 1);
                        $this->db->where('idproduct', $data[0]);
                        $supdate = $this->db->update('product');
                        $this->db->select('delproduct');
                        $datacat = $this->db->get_where('product', array('idproduct' => $data[0]))->result();
                    } else {
                        $this->db->set('delproduct', 0);
                        $this->db->where('idproduct', $data[0]);
                        $supdate = $this->db->update('product');
                        $this->db->select('delproduct');
                        $datacat = $this->db->get_where('product', array('idproduct' => $data[0]))->result();
                    }
                }

            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datacat;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

     public function productDeleteData($data = '') {
        // print_r($data); exit;
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            
                $this->db->where('idproduct',$data[0]);
                $supdate = $this->db->delete('product');
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



    public function viewbalapsarung() {
    //print_r($data[0]);exit;     
       
            $this->db->order_by('idbalapsarung', 'DESC');
            $datay = $this->db->get_where('balapsarung')->result();
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

     public function addbalapsarung($data = '') {
    // print_r($data[0]);exit;     
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $datax = json_decode($data[0]);
            $cek = $this->db->get_where('balapsarung', array('wa' => $datax->wa))->result();
            //print_r($cek);exit;   
            if (!empty($cek)) {
                return $this->duplicate_response();
                } else {
                    $datay = array(
                        'date' =>  date('Y-m-d'),
                        'time' => date('H:i:s'),
                        'nama' => $datax->nama,
                        'wa' => $datax->wa,
                        'alamat' => $datax->alamat,
                        'ig' => $datax->ig,
                        'tiktok' => $datax->tiktok
                    );
                    $supdate = $this->db->insert('balapsarung', $datay);
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

     public function viewsedekah() {
    //print_r($data[0]);exit;     
            $this->db->select('SUM(nominal) as total');
            $total = $this->db->get_where('sedekah')->result();
            // print_r($total[0]->total);exit;
            $this->db->order_by('idsedekah', 'DESC');
            $datay = $this->db->get_where('sedekah')->result();
           
            if ($datay) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($datay);
                $response['totalDonasi'] = $total[0]->total;
                $response['data'] = $datay;

                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        
    }

     public function addsedekah($data = '') {
    // print_r($data[0]);exit;     
        if (empty($data[0])) {
            return $this->empty_response();
        } else {
            $datax = json_decode($data[0]);
            //$cek = $this->db->get_where('sedekah', array('ponsel' => $datax->wa))->result();
            //print_r($cek);exit;   
           // if (!empty($cek)) {
                //return $this->duplicate_response();
               // } else {
                    $datay = array(
                        'date' =>  date('Y-m-d'),
                        'time' => date('H:i:s'),
                        'nominal' => $datax->nominal,
                        'rekening' => $datax->rek,
                        'donatur' => $datax->donatur,
                        'ponsel' => $datax->ponsel,
                        //'tiktok' => $datax->tiktok
                    );
                    $supdate = $this->db->insert('sedekah', $datay);
               // }
            

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


    

    //END CRUD
}
