<?php

class Sensus_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
    }

    public function duplicate_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field Sudah Terdaftar';
        return $response;
    }

    public function get_prov() {
        $query = $this->db->get('sensus_province');
        $query = $query->result();

        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function get_city($id) {
        $query = $this->db->get_where('sensus_city', array('id_prov' => $id));
        $query = $query->result();

        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function get_districts($id) {
        $query = $this->db->get_where('sensus_districts', array('id_city' => $id));
        $query = $query->result();

        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function get_village($id) {
        $query = $this->db->get_where('sensus_village', array('id_dis' => $id));
        $query = $query->result();

        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data failed to receive or data empty.';
            return $response;
        }
    }

    public function addPeople($data) {
        $query = $this->db->get_where('sensus_people', $data)->result();
        if (empty($query)) {
            $supdate = $this->db->insert('sensus_people', $data);
            $voucher = rand(000000, 999999);
            $insert_id = $this->db->insert_id();
            $dataVoucher = array(
                'idpeople' => $insert_id,
                'voucher' => $voucher
            );
            $this->db->insert('sensus_peopleVoucher', $dataVoucher);
        } else {
            $supdate = 0;
        }
        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['codeVoucher'] = $voucher;
            $response['data'] = $data;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data already exists.';
            $response['data'] = $data;
            return $response;
        }
    }

    public function searchVillage($data) {
        $this->db->like($data);
        $this->db->join('sensus_districts as b', 'b.id_dis = a.id_dis', 'left');
        $this->db->join('sensus_city as c', 'c.id_city = b.id_city', 'left');
        $this->db->join('sensus_province as d', 'd.id_prov = c.id_prov', 'left');
        $query = $this->db->get('sensus_village as a')->result();
        if (!empty($query)) {
            $supdate = $query;
        } else {
            $supdate = 0;
        }
        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data already exists.';
            $response['data'] = $data;
            return $response;
        }
    }

    public function searchDistricts($data) {
        $this->db->like($data);
        $this->db->join('sensus_city as b', 'b.id_city = a.id_city', 'left');
        $this->db->join('sensus_province as c', 'c.id_prov = b.id_prov', 'left');
        $query = $this->db->get('sensus_districts as a')->result();
        if (!empty($query)) {
            $supdate = $query;
        } else {
            $supdate = 0;
        }
        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data already exists.';
            $response['data'] = $data;
            return $response;
        }
    }

    public function searchCity($data) {
        $this->db->like($data);
        $this->db->join('sensus_province as b', 'b.id_prov = a.id_prov', 'left');
        $query = $this->db->get('sensus_city as a')->result();
        if (!empty($query)) {
            $supdate = $query;
        } else {
            $supdate = 0;
        }
        if ($supdate) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['totalData'] = count($query);
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data already exists.';
            $response['data'] = $data;
            return $response;
        }
    }

    public function reseller($data = '') {



        if (empty($data[0])) {
            return $this->empty_response();
        } else {

            $data = json_decode($data[0]);
           

            //$sql = $this->db->query("SELECT hp FROM apiauth_user where hp='" . $data->wa . "'");
           // $cek_cat = $sql->num_rows();
			
            //if ($cek_cat > 0) {
              //  return $this->duplicate_response();
           // } else if ($cek_cat = 1) {
				

                $data2 = array(
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d '),
                    'firstname' => $data->nama,
                    //'hp' => $data->wa,
                    'username' => $data->wa,
                    'password' => md5($data->password),
                    'email' => $data->email
                );
                $xupdate = $this->db->insert('apiauth_user', $data2);
                $insert_id = $this->db->insert_id();
				 
                $data3 = array(
                    'name' => $data->nama,
                    'address' => $data->datapenerima[0]->alamat,
                    'rt' => $data->datapenerima[0]->rt,
                    'rw' => $data->datapenerima[0]->rw,
                    'pos' => $data->datapenerima[0]->pos,
                    'id_vill' => $data->datapenerima[0]->id_vill,
                    'id_dis' => $data->datapenerima[0]->id_dis,
                    'id_city' => $data->datapenerima[0]->id_city,
                    'id_prov' => $data->datapenerima[0]->id_prov,
                    'email' => $data->email,
                    'phone' => $data->wa,
                    'idauthuser' => $insert_id
                );
				
                $yupdate = $this->db->insert('sensus_people', $data3);
				$sql = $this->db->query("SELECT wa FROM reseller where wa='" . $data->wa . "'");
				$cek_wa = $sql->num_rows();
				
               if ($cek_wa > 0) {
				return $this->duplicate_response();
				} else {
                $data4 = array(
                    'ktp' => $data->ktp,
					'wa' => $data->wa,
                    'idauthuser' => $insert_id
                );
                $zupdate = $this->db->insert('reseller', $data4);
                $wa = '08112370111';
                $message1 = '*Reseller Baru* 
WA : https://wa.me/+62' . substr($data->wa,1) . ', 
Nama : _' . ($data->nama) . '_,
Email : _' . ($data->email) . '_,
KTP : _' . ($data->ktp) . '_,
Alamat : _' . ($data->datapenerima[0]->alamat) . '_,
Kode Pos : _' . ($data->datapenerima[0]->pos) . '_,
Tolong Di FU';
					
                $message = '*Reseller Rabbani Rumahan*

Terima Kasih Kak _' . strtoupper($data->nama) . '_, 
Anda Sudah Mendaftar Jadi Member *REBAHAN*,
Team Kami Akan Segera Menghubungi Anda,
Untuk Info Lanjut Juga Bisa Wa *https://wa.me/628112370111* ';
                $this->wa->SendWa($data->wa, $message);
                $this->wa->SendWa($wa, $message1);
				} 
		
        
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
            $response['data'] = $data2;
            return $response;
        }
    }

    public function dataStaffStore() {
        $this->db->select('a.name, a.phone, a.codeRshare,b.namestore');
        $this->db->join('store as b', 'b.idquantum = a.codeRshare', 'left');
        $this->db->order_by('a.codeRshare', 'ASC');
        $query = $this->db->get('store_cashier as a')->result();

        if ($query) {
            $response['status'] = 200;
            $response['error'] = false;
            $response['message'] = 'Data received successfully.';
            $response['data'] = $query;
            return $response;
        } else {
            $response['status'] = 502;
            $response['error'] = true;
            $response['message'] = 'Data error.';
            return $response;
        }
    }

    public function catalog($data = '') {
        //print_r($data);
        //exit;
        $sql = $this->db->query("SELECT hp FROM apiauth_user where hp='" . $data[1] . "'");
        $cek_cat = $sql->num_rows();
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {

            if ($cek_cat > 0) {
                return $this->duplicate_response();
            } else {

                $data2 = array(
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d '),
                    'firstname' => $data[0],
                    'hp' => $data[1],
                    'username' => $data[1]
                );

                $message = '*Rabbani Bagi2 Voucher*

Alhamdulillah _' . $data[0] . '_, Anda mendapatkan potongan *DISKON 30% ALL ITEM*
Kode Voucher:
_*RABBANI30*_ (Untuk Rabbani)
_*BANIDIS30*_ (Untuk Bani Batuta)

Syarat dan ketentuan:
1. Voucher potongan Diskon 30% semua produk
2. Vocher tidak dapat diuangkan dan bisa ditukarkan di *Toko Rabbani seluruh Indonesia*, www.rabbanimallonline.com dan seluruh Market Place Official Store Rabbani, sbb:

(Jaringan Online Rabbani)
http://www.rabbanimallonline.com/
https://shopee.co.id/rabbani.official
https://www.lazada.co.id/shop/rabbani-official/
https://www.bukalapak.com/rabbani-official
https://www.blibli.com/merchant/rabbani-official-store/RAO-60047

(Jaringan Online Bani Batuta)
https://shopee.co.id/bani.batuta
https://www.lazada.co.id/shop/bani-batuta-official
https://www.bukalapak.com/u/banibatuta15

3. Voucher hanya bisa di tukarkan 1 kali diperiode yang sama
4. Tunjukan dan informasikan kode voucher yang anda dapatkan dan no wa yang mendapatkan pesan ini kepada Kasir Rabbani
5. Kode voucher akan di validasi oleh Kasir Rabbani
6. Minimal transaksi pembelian Rp. 100.000 setelah diskon.

Buruan kode vouchernya aktif hingga 23 April 2020 dan terbatas loh untuk 1.000 pelanggan pertama :).

Info lebih lanjut WA https://wa.me/62811248838';
                $this->wa->SendWa($data[1], $message);

                $xupdate = $this->db->insert('apiauth_user', $data2);
            }
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
            $response['data'] = $data2;
            return $response;
        }
    }

}
