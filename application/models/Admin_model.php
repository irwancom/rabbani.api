<?php

class Admin_model extends CI_Model {

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

    public function token_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Token tidak boleh salah';
        return $response;
    }

    public function duplicate_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Field Sudah Terdaftar';
        return $response;
    }

    public function ukuran_response() {
        $response['status'] = 502;
        $response['error'] = true;
        $response['message'] = 'Ukuran Gambar Salah Sudah';
        return $response;
    }

    public function verfyAccount($keyCode = '', $secret = '') {
        $data = array(
            "keyCodeStaff" => $keyCode,
            "secret" => $secret
        );
        $this->db->select('c.namestore, a.*');
        $this->db->Join('store as c', 'c.idstore = a.idstore', 'left');
        $query = $this->db->get_where('apiauth_staff as a', $data)->result();
        return $query;
    }

    public function dataProduct($idproduct = '', $sku = '') {
        $this->db->from('product as a');
        $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct');
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

    //CRUD CATEGORY
    public function dataCategory($data = '') {
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {
                $this->db->select('a.*,b.urlImage');
                $this->db->join('category_images as b', 'b.idcategory = a.idcategory', 'left');

                $this->db->where('a.delcat', '0');
                $this->db->order_by('a.categoryName', 'ASC');
                $dataCat = $this->db->get_where('category as a', array('a.parentidcategory' => 0))->result();
                //print_r($dataCat);
                //exit;
                foreach ($dataCat as $dC) {
                    //print_r($dataCat);
                    //exit;
                    $this->db->select('a.parentidcategory,a.idcategory,a.categoryName,b.urlImage');
                    $this->db->join('category_images as b', 'b.parentidcategory = a.parentidcategory', 'left');
                    $dataSubCat = $this->db->get_where('category as a', array('a.parentidcategory' => $dC->idcategory))->result();


                    $dataCatx[] = array(
                        'idcategory' => $dC->idcategory,
                        'categoryName' => $dC->categoryName,
                        'imagecategory' => $dC->urlImage,
                        //'imageicon' => $data1,
                        'subCategory' => $dataSubCat
                    );
                }
                $this->db->select('a.*,b.urlImage');
                $this->db->join('category_images_icon as b', 'b.idcategory = a.idcategory', 'left');
                $data1 = $this->db->get_where('category as a')->result();
            } else {
                return $this->token_response();
            }

            if ($dataCatx) {
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
    }

    public function CataddData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($data[2])) {
                    return $this->empty_response();
                } else {

                    $data = array(
                        'categoryName' => strtoupper($data[2])
                    );
                    $dataCat = $this->db->get_where('category', $data)->result();
                    // print_r($dataCat);
                    // exit;
                }
                if (empty($dataCat)) {
                    $supdate = $this->db->insert('category', $data);
                } else {
                    $delcat = '0';
                    $this->db->set('delcat', $delcat);
                    $supdate = $this->db->update('category');
                }
                //$dataCat = $this->db->get_where('category', $data)->result();
                if ($supdate) {
                    $response['status'] = 200;
                    $response['error'] = false;
                    $response['message'] = 'Data received successfully.';
                    $response['data'] = $supdate;
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
    }

    public function CatupdateData($data = '') {
        $sql = $this->db->query("SELECT categoryName FROM category where categoryName='$data[2]'");
        $cek_cat = $sql->num_rows();
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;
                if (empty($data[2])) {
                    return $this->empty_response();
                } else {
                    if ($cek_cat > 0) {
                        return $this->duplicate_response();
                    } else {
                        $this->db->set('categoryName', strtoupper($data[2]));
                        $this->db->where('idcategory', $data[3]);
                        $supdate = $this->db->update('category');
                    }
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
    }

    public function CatdeleteData($data = '') {


        //  print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;

                $this->db->set('delcat', 1);
                $this->db->where('idcategory', $data[2]);
                $supdate = $this->db->update('category');
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

    public function ParentidcategoryaddData($data = '') {
        // print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($data[2]) || empty($data[3])) {
                    return $this->empty_response();
                } else {

                    $data = array(
                        'categoryName' => strtoupper($data[2]),
                        'parentidcategory' => strtoupper($data[3])
                    );
                    //
                    $dataCat = $this->db->get_where('category', $data)->result();
                }

                if (empty($dataCat)) {
                    //$this->db->where('idcategory', $data[3]);
                    $supdate = $this->db->insert('category', $data);
                } else {

                    $this->db->set('delcat', 0);
                    $supdate = $this->db->update('category');
                }
            } else {
                return $this->empty_response();
            }



            if ($data) {
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
    }

    public function ParentidcategoryupdateData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($data[4])) {
                    return $this->empty_response();
                } else {
                    $datac = array(
                        'categoryName' => strtoupper($data[2]),
                        'idcategory' => strtoupper($data[3]),
                        'parentidcategory' => strtoupper($data[4])
                    );
                    // print_r($datac);
                    // exit;
                    $dataCat = $this->db->get_where('category', $data)->result();
                }
                if (empty($dataCat)) {
                    $this->db->set('categoryName', strtoupper($data[2]));
                    $this->db->set('parentidcategory', strtoupper($data[4]));
                    $this->db->where('idcategory', $data[3]);
                    $this->db->update('category');
                    $supdate = $this->db->get_where('category', $datac)->result();
                    // } else {
                    //     $supdate = '';
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
    }

    public function parentCatdeleteData($data = '') {


        //  print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;
                $delcat = '1';
                $this->db->set('delcat', $delcat);
                $this->db->where('parentidcategory', $data[2]);
                $supdate = $this->db->update('category');
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

    //END CRUD CATEGORY
    //CRUD PRODUCT

    public function searchProduct($data = '') {


        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            //print_r($verify);
            //exit;
            if (!empty($verify)) {

                $this->db->select('a.*,b.*');
                $this->db->from('product as a');
                $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct', 'left');
                $this->db->where('delproduct', 0);
                $this->db->group_by('skuProduct');
                $this->db->like('productName', $data[2]);
                $this->db->or_like('skuPditails', $data[2]);
                $sql = $this->db->get()->result();
            } else {
                $supdate = $verify;
            }


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
    }

    public function searchTransaction($data = '') {


        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            //print_r($verify);
            //exit;
            if (!empty($verify)) {

                $this->db->select('a.*,b.*');
                $this->db->from('transaction as a');
                $this->db->join('apiauth_user as b', 'b.idauthuser = a.idauthuser', 'left');
                $this->db->join('sensus_people as c', 'c.idpeople = a.idpeople', 'left');
                $this->db->like('noInvoice', $data[2]);
                $this->db->or_like('firstname', $data[2]);
                $this->db->or_like('name', $data[2]);
                $this->db->or_like('totalpay', $data[2]);
                $this->db->or_like('address', $data[2]);
                $this->db->or_like('phone', $data[2]);
                $this->db->or_like('hp', $data[2]);
                $this->db->or_like('username', $data[2]);
                $sql = $this->db->get()->result();
            } else {
                return $this->empty_response();
            }


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
    }

    public function searchUser($data = '') {


        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('apiauth_user');
                $this->db->like('firstname', $data[2]);
                $this->db->or_like('username', $data[2]);
                $this->db->or_like('email', $data[2]);
                $this->db->or_like('hp', $data[2]);
                $sql = $this->db->get()->result();
            } else {
                $supdate = $verify;
            }


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
    }

    public function searchStore($data = '') {


        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            //print_r($verify);
            //exit;
            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('store');
                //$this->db->join('apiauth_user as b', 'b.idauthuser = a.idauthuser', 'left');
                $this->db->like('namestore', $data[2]);
                //$this->db->or_like('firstname', $data[2]);
                $sql = $this->db->get()->result();
            } else {
                return $this->empty_response();
            }


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
    }

    function jlh_data() {
        return $this->db->get('product')->num_rows();
    }

    public function productGetData($data = '') {

        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {


                $this->db->select('a.*,b.categoryName');
                $this->db->from('product as a');
                $this->db->join('category as b', 'b.idcategory = a.idcategory', 'left');

                if (!empty($data[2])) {
                    $paging = $data[2] * 0;
                } else {
                    $paging = 0;
                }
                $this->db->limit(10, $paging);
                $this->db->where('delproduct', '0');
                $this->db->order_by('idproduct', 'ASC');
                $queryx = $this->db->get()->result();
                $this->db->select('count(*) as product');
                $this->db->where('delproduct', '0');
                $product = $this->db->get_where('product')->result();

                $jlh = $product[0]->product;
                $hal = ceil($jlh / 10);



                foreach ($queryx as $q) {
                    $this->db->select('a.*,b.urlImage as imagesVariable,b.idpimagesdetails');
                    $this->db->from('product_ditails as a');
                    $this->db->where('a.idproduct', $q->idproduct);
                    $this->db->where('delproductditails', '0');

                    $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                    $query = $this->db->get()->result();
                    $dataq = array(
                        'idproduct' => $q->idproduct
                    );
                    $this->db->select('*');
                    $queryq = $this->db->get_where('product_images', $dataq)->result();
                    $datax[] = array(
                        'product' => $q,
                        'totalsku' => count($query),
                        'variableProduct' => $query,
                        'imageProduct' => $queryq,
                        'halaman' => $hal
                    );
                }
            } else {
                return $this->token_response();
            }
            if (!empty($datax)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['totalData'] = count($datax);
                $response['totalHalaman'] = ($hal);
                $response['totalProduct'] = ($jlh);
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

    public function productGetData_v2($data = '') {

        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {

                $this->db->select('a.idproduct, a.skuProduct, a.productName, b.urlImage, b.imageFile, c.categoryName, a.delproduct');
                $this->db->from('product as a');
//                $this->db->where('delproduct', '0');
                $this->db->join('product_images as b', 'b.idproduct = a.idproduct', 'left');
                $this->db->join('category as c', 'c.idcategory = a.idcategory', 'left');
                $this->db->order_by('a.productName', 'ASC');
                $this->db->group_by("a.skuProduct");
                $query = $this->db->get()->result();
//                print_r($query);

                $dataPublish = $this->db->query('SELECT count(*) as data FROM product WHERE delproduct=0')->result();
                $dataDraf = $this->db->query('SELECT count(*) as data FROM product WHERE delproduct=1')->result();
                $skuPublish = $this->db->query('SELECT count(*) as data, sum(stock) as stock, sum(valuePrice) as valuePrice FROM product_ditails WHERE delproductditails=0')->result();
                $skuDraf = $this->db->query('SELECT count(*) as data, sum(stock) as stock, sum(valuePrice) as valuePrice FROM product_ditails WHERE delproductditails=1')->result();

                $skuPublishAllBandung = $this->db->query('SELECT count(*) as data, sum(stockAllBandung) as stock, sum(valuePriceAllBandung) as valuePrice FROM product_ditails WHERE delproductditails=0')->result();
                $skuDrafAllBandung = $this->db->query('SELECT count(*) as data, sum(stockAllBandung) as stock, sum(valuePriceAllBandung) as valuePrice FROM product_ditails WHERE delproductditails=1')->result();
            } else {
                return $this->token_response();
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['dataPublish'] = $dataPublish[0]->data;
                $response['dataDraf'] = $dataDraf[0]->data;
                $response['totalData'] = count($query);
                $response['skuPublishRmall'] = $skuPublish[0];
                $response['skuDrafRmall'] = $skuDraf[0];
                $response['skuPublishAllBandung'] = $skuPublishAllBandung[0];
                $response['skuDrafAllBandung'] = $skuDrafAllBandung[0];
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

    public function productGetDetails_v2($data = '') {

        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {

                $this->db->select('a.*, c.*, d.weight');
                $this->db->from('product as a');
//                $this->db->where('delproduct', '0');
                $this->db->join('category as c', 'c.idcategory = a.idcategory', 'left');
                $this->db->join('product_ditails as d', 'd.idproduct = a.idproduct', 'left');
                $this->db->order_by('a.productName', 'ASC');
                $this->db->group_by("d.idproduct");
                $this->db->where('a.idproduct', $data[2]);
                $query = $this->db->get()->result();

                $this->db->select('*');
                $query2 = $this->db->get_where('product_ditails', array('idproduct' => $query[0]->idproduct))->result();
                $this->db->select('idpimages, idproduct, urlImage, imageFile');
                $query3 = $this->db->get_where('product_images', array('idproduct' => $query[0]->idproduct))->result();
                $this->db->select('idpimagesdetails, idproduct, collor, urlImage, imageFile');
                $this->db->group_by("collor");
                $query4 = $this->db->get_where('product_images_ditails', array('idproduct' => $query[0]->idproduct))->result();
            } else {
                return $this->token_response();
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['totalData'] = count($query);
                $response['dataProduct'] = $query;
                $response['dataDetailsProduct'] = $query2;
                $response['images'] = array(
                    'imagesProduct' => $query3,
                    'imagesDetailsProduct' => $query4
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

    public function productUpdate_v2($data = '') {

        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {

                $this->db->set('productName', strtoupper($data[3]));
                $this->db->set('timeCreate', date('H:i:s'));
                $this->db->set('dateCreate', date('Y-m-d'));
                $this->db->set('descr', $data[4]);
                $this->db->set('descr_en', $data[5]);
                $this->db->set('descrDitails', $data[6]);
                $this->db->set('descrDitails_en', $data[7]);
                $this->db->set('delproduct', $data[8]);
                $this->db->set('idcategory', $data[9]);
                $this->db->where('idproduct', $data[2]);
                $query = $this->db->update('product');

                $this->db->set('weight', $data[10]);
                $this->db->where('idproduct', $data[2]);
                $query = $this->db->update('product_ditails');
            } else {
                return $this->token_response();
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function productUpload_v2($data = '', $pg = '') {

        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {
                if ($pg == 'del') {
                    $this->db->delete('product_images', array('idpimages' => $data[2]));
                    $this->load->library('S3_Storage');
                    S3_Storage::delete_object('img/large/' . $data[3]);
                    S3_Storage::delete_object('img/medium/' . $data[3]);
                    S3_Storage::delete_object('img/small/' . $data[3]);
                    $supdate = 1;
                } elseif ($pg == 'imagesDetailsProductDel') {
                    $dataimagesDetailsProductDel = $this->db->get_where('product_images_ditails', array('idpimagesdetails' => $data[2]))->result();
                    if (!empty($dataimagesDetailsProductDel)) {
                        foreach ($dataimagesDetailsProductDel as $didp) {
                            $this->db->delete('product_images_ditails', array('idproduct' => $didp->idproduct, 'collor' => $didp->collor));
                        }
                    }
//                    $this->db->delete('product_images_ditails', array('idpimagesdetails' => $data[2]));
                    $this->load->library('S3_Storage');
                    S3_Storage::delete_object('img/large/' . $data[3]);
                    S3_Storage::delete_object('img/medium/' . $data[3]);
                    S3_Storage::delete_object('img/small/' . $data[3]);
                    $supdate = 1;
                } elseif ($pg == 'imagesDetailsProduct') {
                    $dataidpditails = $this->db->get_where('product_ditails', array('idproduct' => $data[2], 'collor' => $data[3]))->result();
                    if (!empty($dataidpditails)) {
                        foreach ($dataidpditails as $dt) {
                            $dataax = array(
                                'idproduct' => $data[2],
                                'idpditails' => $dt->idpditails,
                                'collor' => $data[3],
                                'urlImage' => $data[4]['upload_data']['file_url'],
                                'imageFile' => $data[4]['upload_data']['file_name']
                            );

                            $this->db->insert('product_images_ditails', $dataax);

                            $this->db->set('delproductditails', '0');
                            $this->db->where('idpditails', $dt->idpditails);
                            $this->db->update('product_ditails');
                        }
                    }
                    $supdate = $dataax;
                } elseif ($pg == 'dataCollor') {
                    $this->db->select('idproduct, collor');
                    $this->db->group_by("collor");
                    $data = $this->db->get_where('product_ditails', array('idproduct' => $data[2]))->result();
                    $supdate = $data;
                } else {

                    $data = array(
                        'idproduct' => $data[2],
                        'urlImage' => $data[3]['upload_data']['file_url'],
                        'imageFile' => $data[3]['upload_data']['file_name']
                    );

                    $this->db->insert('product_images', $data);
                    $supdate = $data;
                }
            } else {
                $supdate = $verify;
            }
            if (!empty($supdate)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $supdate;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function productAddData($data = '') {

        //print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {

//                $str = substr($data[2], 1, strlen($data[2]) - 2); // remove outer ( and )
//                $str = preg_replace("/([a-zA-Z0-9_]+?):/", "\"$1\":", $data[2]); // fix variable names
                // $str = str_replace(array("\n"), "", $data[2]);

                $datam = json_decode($data[4]);
                //print_r($datam->skuProduct);
                //exit;


                $checkDataInsert = $this->db->get_where('product', array('skuProduct' => $datam->skuProduct))->result();
                //print_r($checkDataInsert);
                //exit;
                if (empty($checkDataInsert)) {
                    $datac = array(
                        'idcategory' => $datam->idcategory,
                        'idstore' => $verify[0]->idstore,
                        'skuProduct' => $datam->skuProduct,
                        'timeCreate' => date('H:i:s'),
                        'dateCreate' => date('Y-m-d'),
                        'productName' => rawurldecode($datam->productName),
                        'descr' => rawurldecode($data[2]),
                        'descr_en' => rawurldecode($datam->descr_en),
                        'descrDitails' => rawurldecode($data[3]),
                        'descrDitails_en' => rawurldecode($datam->descrDitails_en)
                    );
                    $this->db->insert('product', $datac);
                    $idproduct = $this->db->insert_id();
                } else {
                    $datac = array(
                        'idcategory' => $datam->idcategory,
                        'skuProduct' => $datam->skuProduct,
                        'productName' => rawurldecode($datam->productName),
                        'descr' => rawurldecode($data[2]),
                        'descr_en' => rawurldecode($datam->descr_en),
                        'descrDitails' => rawurldecode($data[3]),
                        'descrDitails_en' => rawurldecode($datam->descrDitails_en),
                        'delproduct' => 0
                    );
                    //print_r($datac);
                    //exit;
                    $this->db->set($datac);
                    $this->db->where('idproduct', $checkDataInsert[0]->idproduct);
                    $this->db->update('product');
                    $idproduct = $checkDataInsert[0]->idproduct;
                }
//                print_r($datac);
//                exit;
//                if (!empty($datam['productDitails'])) {
//                    foreach ($datam['productDitails'] as $dPd) {
//                        $checkDataInsertDitailsProduct = $this->db->get_where('product_ditails', array('skuPditails' => $dPd['sku']))->result();
//                        print_r($checkDataInsertDitailsProduct);
//                        exit;
//                        $datac = array(
//                            'idproduct' => $idproduct,
//                            'skuPditails' => $dPd['sku'],
//                            'collor' => $dPd['color'],
//                            'size' => $dPd['size'],
//                            'weight' => $dPd['weight'],
//                            'price' => $dPd['price'],
//                            'priceDiscount' => $dPd['priceDiscount'],
//                            'stock' => $dPd['stock']
//                        );
//                        if (empty($checkDataInsertDitailsProduct)) {
//                            $this->db->insert('product_ditails', $datac);
//                        } else {
//                            $this->db->set($datac);
//                            $this->db->where('skuPditails', $checkDataInsertDitailsProduct[0]->idpditails);
//                            $this->db->update('product_ditails');
//                        }
//                    }
//                }
//                print_r($datac);
//                exit;
//                $sql = $this->db->query("SELECT skuProduct FROM product where skuProduct='" . $datam['skuProduct'] . "'");
//                $cek_sku = $sql->num_rows();
//                if (empty($datam['skuProduct'])) {
//                    return $this->empty_response();
//                } else {
//                    if ($cek_sku > 0) {
//                        return $this->duplicate_response();
//                    } else {
//                        $datac = array(
//                            'idcategory' => $datam['idcategory'],
//                            'idstore' => $verify[0]->idstore,
//                            'skuProduct' => $datam['skuProduct'],
//                            'timeCreate' => date('H:i:s'),
//                            'dateCreate' => date('Y-m-d'),
//                            'productName' => $datam['productName'],
//                            'descr' => $datam['descr'],
//                            'descr_en' => $datam['descr_en'],
//                            'descrDitails' => $datam['descrDitails'],
//                            'descrDitails_en' => $datam['descrDitails_en']
//                        );
//                    }
//                }
                // print_r($datac);
                // ex
                //$query = $this->db->get_where('product', array('sku' => $data->sku));
                // $query = $this->db->get_where('product', array('idstore=>1'));
                // $query = '';
                // if (empty($query)) {
//                $this->db->insert('product', $datac);
                // }
                // print_r($datac);
                // exit;
                // $idproduct = $this->db->insert_id();
            } else {
                return $this->token_response();
            }
            if (!empty($datac)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['dataIdProd'] = $idproduct;
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

    public function productUpdateData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $data = json_decode($data[2]);
                if (empty($data->sku) || empty($data->idcategory) || empty($data->productName) || empty($data->descr)) {
                    return $this->empty_response();
                } else {
//                print_r($dat);
//                exit;
                    $datac = array(
                        'idcategory' => $data->idcategory,
                        'idstore' => $verify[0]->idstore,
                        'skuProduct' => $data->sku,
                        'productName' => $data->productName,
                        'descr' => $data->descr,
                        'descr_en' => $data->descr_en,
                        'descrDitails' => $data->descrDitails,
                        'descrDitails_en' => $data->descrDitails_en
                    );
                }
//                print_r($datac);
//                exit;
                $this->db->set($datac);
                // print_r($datac);
                // exit;

                $this->db->where('idproduct', $data->idproduct);
                $this->db->where('idstore', $verify[0]->idstore);
                $this->db->update('product');

                foreach ($data->productDitails as $ddt) {
                    //$dvariable = json_decode($ddt->variable);
                    $datax = array(
                        'skuPditails' => $ddt->sku,
                        'collor' => strtoupper($ddt->collor),
                        'size' => strtoupper($ddt->size),
                        'priceQuantum' => $ddt->priceQuantum,
                        'priceQuantumReport' => $ddt->priceQuantumReport,
                        'price' => $ddt->price,
                        'priceDiscount' => $ddt->priceDiscount,
                        'stock' => $ddt->stock,
                        'weight' => $ddt->berat
                    );
                    $this->db->set($datax);
                    $this->db->where('idpditails', $ddt->idpditails);
                    $this->db->update('product_ditails');
                }
                $query = 1;
            } else {
                $supdate = $verify;
            }
            if (!empty($datac)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
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

    public function productDeleteData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                $delproduct = '1';
                //del product    
                $this->db->set('delproduct', $delproduct);
                $this->db->where('idproduct', $data[2]);
                $this->db->where('idstore', $verify[0]->idstore);
                $supdate = $this->db->update('product');

                // $this->db->get_where('product')->result();
                //del product ditails
                $this->db->set('delproductditails', $delproduct);
                $this->db->where('idproduct', $data[2]);

                $supdate = $this->db->update('product_ditails');
                // $this->db->get_where('product_ditails')->result();
                //$supdate = 1;
                // } else {
                //     $supdate = $verify;
            }
            if (!empty($supdate)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $supdate;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function dataStaff($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {

            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            //    exit;
            if (!empty($verify)) {

                $this->db->select('a.*,b.urlImage');
                $this->db->join('apiauth_staff_images as b', 'b.idauthstaff = a.idauthstaff', 'left');
                $dataCat = $this->db->get_where('apiauth_staff as a')->result();
                $supdate = $dataCat;
            } else {
                $supdate = $verify;
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
    }

    public function StaffaddData($data = '') {
        // print_r($data);
        // exit;
        $sql = $this->db->query("SELECT username FROM apiauth_staff where username='$data[6]'");
        $cek_username = $sql->num_rows();
        $sql1 = $this->db->query("SELECT phone FROM apiauth_staff where phone='$data[5]'");
        $cek_name = $sql1->num_rows();
//         print_r($sql);
//         exit;

        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                //      print_r($verify);
                // exit;

                if (empty($data[3]) || empty($data[4]) || empty($data[5]) || empty($data[6]) || empty($data[7])) {
                    return $this->empty_response();
                } else {
                    if ($cek_username > 0 || $cek_name > 0) {
                        return $this->duplicate_response();
                    } else {

                        $data = array(
                            'idstore' => ($data[3]),
                            'level' => ($data[2]),
                            //'status' => ($data[3]),
                            'name' => ($data[4]),
                            'phone' => ($data[5]),
                            'username' => ($data[6]),
                            'password' => md5($data[7])
                        );

                        $dataCat = $this->db->get_where('apiauth_staff', $data)->result();
                    }
                }

                //$this->db->where('idstore !=', $data[2]);
                // print_r($dataCat);
                // exit;
                if (empty($dataCat)) {
                    $supdate = $this->db->insert('apiauth_staff', $data);
                } else {
                    $supdate = '';
                }
                $dataCat = $this->db->get_where('apiauth_staff', $data)->result();
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
            }
        }
    }

    public function StaffupdateData($data = '') {
        // $sql = $this->db->query("SELECT username FROM apiauth_staff where username='$data[5]'");
        // $cek_username = $sql->num_rows();
         print_r($data);
         exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {
//                if (empty($data[5]) || empty($data[4])) {
//                    return $this->empty_response();
//
//                    // }else {
//                    //     if($cek_username > 0){
//                    //         return $this->duplicate_response();
//                } else {
                $datac = array(
                    'idstore' => ($data[8]),
                    'level' => ($data[2]),
                    'status' => ($data[3]),
                    'name' => ($data[4]),
                    'phone' => ($data[5]),
                    'username' => ($data[6]),
                    'password' => md5($data[7])
                );
                $this->db->where('idauthstaff', $data[9]);
                //$this->db->where('idauthstaff', $verify[0]->idauthstaff);
                $dataCat = $this->db->get_where('apiauth_staff', $data)->result();
            }

            //print_r($verify);

            if (empty($dataCat)) {
                //$this->db->set('idstore', strtoupper($data[2]));
                $this->db->set('level', ($data[2]));
                $this->db->set('status', ($data[3]));
                //$this->db->where('idauthstaff', $data[4]);

                $this->db->set('name', ($data[4]));
                $this->db->set('phone', ($data[5]));
                $this->db->set('username', ($data[6]));
                $this->db->set('password', md5($data[7]));
                $this->db->set('password', md5($data[8]));

                $this->db->where('idauthstaff', $data[9]);
                //$this->db->where('idstore', $verify[0]->idstore);
                $this->db->update('apiauth_staff');

                $supdate = 1;
            } else {
                $supdate = 'verify';
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
        // }
    }

    public function profile($data = '') {
        // $sql = $this->db->query("SELECT username FROM apiauth_staff where username='$data[5]'");
        // $cek_username = $sql->num_rows();
         //print_r($data);
         //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                $datac = array(
                    'name' => ($data[2]),
                    'password' => md5($data[3]),
					'phone' => ($data[4]),
					'staffemail' => ($data[5])
                );

              // print_r($datac);
			   //exit;

                    //$this->db->set('name', ($data[2]));
                    //$this->db->set('password', md5($data[3]));
				//	$this->db-set('$datac');
                    $this->db->where('idauthstaff', $verify[0]->idauthstaff);
                    //$this->db->where('idstore', $verify[0]->idstore);
                    $supdate = $this->db->update('apiauth_staff',$datac);

                  
                } else {
                   return $this->empty_response();
                }

                if ($supdate) {
                    $response['status'] = 200;
                    $response['error'] = false;
                    $response['message'] = 'Data received successfully.';
                    $response['data'] = $datac;
                    return $response;
                } else {
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data failed to receive or data empty.';
                    return $response;
                }
            
        }
    }

    public function staffPic($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data[3]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {
                // if (empty($verify)) {
                //     unlink($data[4] . $data[3]['upload_data']['file_name']) or die("Couldn't delete file");
                //     $response['status'] = 502;
                //     $response['error'] = true;
                //     $response['message'] = 'Data failed to receive.';
                //     return $response;
                //     exit;
                // }
                // $query = $this->db->where('idauthstaff', $verify[0]->idauthstaff);
                //if (!empty($query)) {
                // print_r($data);
                // exit;

                $data = array(
                    // 'idproduct' => $data[2],
                    //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                    'urlImage' => $data[3]['upload_data']['file_url'],
                    'dir' => $data[4],
                    'imageFile' => $data[3]['upload_data']['file_name'],
                    'size' => $data[3]['upload_data']['file_size'],
                    'type' => $data[3]['upload_data']['image_type']
                );
                $this->db->where('idauthstaff', $verify[0]->idauthstaff);
                $this->db->update('apiauth_staff_images', $data);
                $supdate = $data;
                //  }
            } else {
                $supdate = $verify;
            }

            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                return $response;
            } else {
                unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function dataUser($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;

                $this->db->select('a.*,b.urlImage,c.urlImagektp');
                $this->db->from('apiauth_user as a');
                $this->db->join('apiauth_user_images as b', 'b.idauthuser = a.idauthuser', 'left');
                $this->db->join('apiauth_user_ktp as c', 'c.idauthuser = a.idauthuser', 'left');
                $this->db->order_by('idauthuser', 'desc');
                if (!empty($data[2])) {
                  $paging = $data[2] * 10;
                } else {
                   $paging = 0;
                }
                $this->db->limit(10, 0, $paging);

                $queryx = $this->db->get()->result();
                $this->db->select('count(*) as user');
                $user = $this->db->get_where('apiauth_user')->result();
                $jlh = $user[0]->user;
                $hal = ceil($jlh / 10) - 1;
            } else {
                return $this->empty_response();
            }

            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($queryx);
                $response['totalPage'] = ($hal);
                $response['totalUser'] = ($jlh);
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function UserditailsData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;
                $this->db->select('a.*,b.urlImage,c.urlImagektp');
                // $this->db->where('idauthuser', $verify[0]->idauthuser);
                $this->db->join('apiauth_user_images as b', 'b.idauthuser = a.idauthuser', 'left');
                $this->db->join('apiauth_user_ktp as c', 'c.idauthuser = a.idauthuser', 'left');
                $this->db->where('a.idauthuser', $data[2]);

                $dataCat = $this->db->get_where('apiauth_user as a')->result();
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
    }

    public function UseraddData($data = '') {

        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {

                $data = json_decode($data[2]);
                // print_r($data);
                //exit;

                $data1 = array(
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'firstname' => $data->firstname,
                    'lastname' => $data->lastname,
                    'username' => $data->username,
                    'password' => md5($data->password),
                    'email' => $data->email,
                    'hp' => $data->hp
                );

                $supdate = $this->db->insert('apiauth_user', $data1);
                $insert_id = $this->db->insert_id();
                $data2 = array(
                    'name' => $data->firstname,
                    'address' => $data->datapenerima[0]->address,
                    'rt' => $data->datapenerima[0]->rt,
                    'rw' => $data->datapenerima[0]->rw,
                    'pos' => $data->datapenerima[0]->pos,
                    'id_vill' => $data->datapenerima[0]->id_vill,
                    'id_dis' => $data->datapenerima[0]->id_dis,
                    'id_city' => $data->datapenerima[0]->id_city,
                    'id_prov' => $data->datapenerima[0]->id_prov,
                    'email' => $data->email,
                    'phone' => $data->hp,
                    'idauthuser' => $insert_id
                );
                $xupdate = $this->db->insert('sensus_people', $data2);
            } else {
                return $this->empty_response();
            }





            if ($data1) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $data1;
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

    public function UserupdateData($data = '') {
        //print_r($data);
        //exit;

        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                $data = json_decode($data[2]);
                //print_r($data);
                //exit;
                $datac = array(
                    'firstname' => $data->firstname,
                    'lastname' => $data->lastname,
                    'username' => $data->username,
                    'password' => md5($data->password),
                    'email' => $data->email,
                    'hp' => $data->hp,
                    'status' => $data->status
                );

                $this->db->set($datac);
                $this->db->where('idauthuser', $data->idauthuser);
                $this->db->update('apiauth_user');
            } else {
                return $this->empty_response();
            }

            if ($datac) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datac;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function addressUser($data = '') {
        // print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {

            $verify = $this->verfyAccount($data[0], $data[1]);
            //print_r($verify);
            //   exit;
            if (!empty($verify)) {

                $this->db->select('a.*,b.*');

                $this->db->join('sensus_people as b', 'b.idauthuser = a.idauthuser', 'left');
                //$this->db->join('apiauth_user_ktp as c', 'c.idauthuser = a.idauthuser', 'left');

                $this->db->where('a.idauthuser', $data[2]);
                $dataCat = $this->db->get_where('apiauth_user as a')->result();
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
    }

    public function addressdetails($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {

            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            //     exit;
            if (!empty($verify)) {

                $this->db->select('*');

                // $this->db->join('sensus_people as b', 'b.idauthuser = a.idauthuser', 'left');
                //$this->db->join('apiauth_user_ktp as c', 'c.idauthuser = a.idauthuser', 'left');

                $this->db->where('idpeople', $data[2]);
                $dataCat = $this->db->get_where('sensus_people')->result();
                $supdate = $dataCat;
            } else {
                $supdate = $verify;
            }

            if ($supdate) {
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

    public function addressUseradd($data = '') {
        // print_r($data);
        // exit;
        // $sql = $this->db->query("SELECT hp FROM apiauth_user where hp='$data[7]'");
        // $cek_hp = $sql->num_rows();
        // $sql1 = $this->db->query("SELECT email FROM apiauth_user where email='$data[6]'");
        // $cek_email = $sql1->num_rows();
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            //    print_r($verify);
            // exit;
            if (!empty($verify)) {
                // if (empty($data[2]) || empty($data[3]) || empty($data[5])) {
                //     return $this->empty_response();
                // } else {
                //     if ($cek_hp > 0 || $cek_email > 0) {
                //         return $this->duplicate_response();
                //     } else {
                $data = json_decode($data[2]);
                // print_r($data);
                // exit;
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
                    'idauthuser' => $data->idauthuser
                );
                //$this->db->where('idauthuser', $data[3]);
                $xupdate = $this->db->insert('sensus_people', $data2);
            }


            // $supdate = $this->db->insert('apiauth_user', $data1);
            // $insert_id = $this->db->insert_id();


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

    public function addressUserUpdate($data = '') {
        // print_r($data);
        // exit;

        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                $data = json_decode($data[2]);
                // print_r($data);
                //exit;
                $datab = array(
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'email' => $data->email,
                    'rt' => $data->rt,
                    'rw' => $data->rw,
                    'pos' => $data->poscode,
                    'id_vill' => $data->id_vill,
                    'id_dis' => $data->id_dis,
                    'id_city' => $data->id_city,
                    'id_prov' => $data->id_prov
                );
                //print_r($datab);
                //exit;


                $this->db->set($datab);

                $this->db->where('idpeople', $data->idpeople);
                $this->db->update('sensus_people');
            } else {
                return $this->empty_response();
            }

            if ($datab) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datab;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function ditailsGetData($data = '') {
         //print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
			

                $this->db->select('a.*');
                $this->db->from('product as a');
                $this->db->join('category as b', 'b.idcategory = a.idcategory', 'left');
                $this->db->where('delproduct', '0');
                $this->db->where('a.idstore', $verify[0]->idstore);
                $this->db->where('idproduct', $data[2]);


                $query = $this->db->get()->result();
				//print_r($query);
				//exit;


                foreach ($query as $q) {
                    $this->db->select('a.*,b.urlImage as imagesVariable,b.idpimagesdetails');
                    $this->db->from('product_ditails as a');
                    $this->db->where('a.idproduct', $data[2]);
                    $this->db->where('delproductditails', '0');
                    $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                    $query = $this->db->get()->result();
                    //print_r($query);
                    //exit;

                    $dataq = array(
                        'idproduct' => $q->idproduct
                    );
                    $this->db->select('*');
                    $queryq = $this->db->get_where('product_images', $dataq)->result();

                    $datax[] = array(
                        'product' => $q,
                        'totalsku' => count($query),
                        'variableProduct' => $query,
                        'imageProduct' => $queryq
                    );
                }
            } else {
                return $this->token_response();
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
    }

    public function ditailsAddData($data = '') {


        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                //$idproduct = $data[3];
                $data = json_decode($data[2]);
                //print_r($data);
                //exit;


                foreach ($data->productDitails as $ddt) {
                    //print_r($ddt);
                    //exit;
                    $sql = $this->db->query("SELECT skuPditails FROM product_ditails where skuPditails='$ddt->sku'");
                    $cek_sku = $sql->num_rows();

                    if ($cek_sku > 0) {
                        return $this->duplicate_response();
                    } else {
                        $datax[] = array(
                            'idproduct' => $data->idproduct,
                            'skuPditails' => strtoupper($ddt->sku),
                            'collor' => strtoupper($data->color),
                            'size' => strtoupper($ddt->size),
                            'priceQuantum' => $ddt->priceQuantum,
                            'priceQuantumReport' => $ddt->priceQuantumReport,
                            'price' => $ddt->price,
                            'priceDiscount' => $ddt->priceDiscount,
                            'stock' => $ddt->stock,
                            'weight' => $data->weight,
                            'realprice' => $ddt->price - $ddt->priceDiscount
                        );
                    }
                    //print_r($datax);
                    //exit;
                    // $query = $this->db->get_where('product_ditails', $datax)->result();
                    //print_r($query);
                    //exit;
                    //$this->db->where('sku !=', $ddt->sku);
                    //if(empty($query)){
                    //foreach($query as $q){
                    //$datax[] = $ddt;
                    //}
                    //}
                    //if (empty($query)) {
                    // }
                }
            } else {
                return $this->token_response();
            }
            $query = $this->db->get_where('product_ditails')->result();


            if (!empty($query)) {
                foreach ($datax as $dx) {


                    $this->db->insert('product_ditails', $dx);
                }
                $idtransaction = $this->db->insert_id();
                foreach ($query as $dy) {
                    //print_r($dy->idpditails);
                    //exit;

                    $this->db->insert('product_images_ditails', array('idpditails' => $dy->idpditails));
                }
            }

            if (!empty($datax)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
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

    public function ditailsUpdateData($data = '') {

        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $idpditails = ($data[3]);


                $data = json_decode($data[2]);


                foreach ($data->productDitails as $ddt) {
                  
                    $datax = array(
                        'skuPditails' => $ddt->sku,
                        'collor' => strtoupper($ddt->collor),
						'collorcode' => strtoupper($ddt->collorcode),
                        'size' => strtoupper($ddt->size),
                        'priceQuantum' => $ddt->priceQuantum,
                        'priceQuantumReport' => $ddt->priceQuantumReport,
                        'price' => $ddt->price,
                        'priceDiscount' => $ddt->priceDiscount,
                        'stock' => $ddt->stock,
                        'weight' => $ddt->berat
                    );
                    

                    $this->db->set($datax);
                    $this->db->where('idpditails', $idpditails);
                    $this->db->update('product_ditails');
                }
            } else {
                return $this->token_response();
            }
            if (!empty($datax)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
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

    public function ditailsdeleteData($data = '') {

        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {


                $this->db->set('delproductditails', 1);
                $this->db->where('idpditails', $data[2]);

                $supdate = $this->db->update('product_ditails');
            } else {
                return $this->empty_response();
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

    public function transactionGetData($data = '') {

        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {

                $this->db->select('*');
                $this->db->from('transaction');
                $this->db->where('statusPay!=2');
                $this->db->order_by('idtransaction', 'desc');

                // if (!empty($data[2])) {
//                    $paging = $data[2] * 10;
                //   $paging = 0;
                //} else {
                //  $paging = 0;
                //}
                //$this->db->limit(10, $paging);

                $queryx = $this->db->get()->result();
				//print_r($queryx);
				//exit;

                $this->db->select('count(*) as transaction');
                $transaction = $this->db->get_where('transaction')->result();
				
                if ($transaction[0]->transaction = 0) {
                    $queryx = $this->db->get()->result();
                } else {
                    $jlh = $transaction[0]->transaction;
                    $hal = ceil($jlh / 10) - 1;
                }

                foreach ($queryx as $q) {
                    //print_r($q);
                    //	exit;

                    $this->db->select('a.*,b.*,c.*');
                    $this->db->from('transaction_details as a');
                    $this->db->join('product as b', 'b.idproduct = a.idproduct', 'left');
                    $this->db->join('product_ditails as c', 'c.idpditails = a.idpditails', 'left');
                    $this->db->where('idtransaction', $q->idtransaction);
                    //$this->db->order_by('idtransaction', 'DESC');
                    // if (!empty($data[2])) {
                    //     $paging = $data[2] * 10;
                    // } else {
                    //    $paging = 0;
                    // }
                    //$this->db->limit(10, $paging);

                    $queryy = $this->db->get()->result();
                    //print_r($queryy);
                    //exit;
                    //$this->db->select('count(*) as transaction_details');
                    //$transaction_details = $this->db->get_where('transaction_details')->result();
                    //$jlh = $transaction_details[0]->transaction_details;
                    //$hal = ceil($jlh / 10) - 1;
					$this->db->select('count(*) as transaction');
					$transaction = $this->db->get_where('transaction')->result();
					$jlh = $transaction[0]->transaction;
                    $hal = ceil($jlh / 10) - 1;

                    $datax[] = array(
                        'order' => $q,
                        'totaltransaction' => count($queryy),
                        'variableProduct' => $queryy,
                    );
                    ;
                }
            }
            if (!empty($datax)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['totalData'] = count($datax);
                $response['totalPage'] = ($hal);
                $response['totalTransaction'] = ($jlh);
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

    public function transactionAddData($data = '') {
        // print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;
                $data = json_decode($data[2]);
                // print_r($data);
                // exit;
                $datac = array(
                    //'idtransaction' => $data->idtransaction,
                    'idauth' => $data->idauth,
                    'orderBy' => $data->orderBy,
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'noInvoice' => $data->noInvoice,
                    'shipping' => $data->shipping,
                    'trackingCode' => $data->trackingCode,
                    'subtotal' => $data->subtotal,
                    'discount' => $data->discount,
                    'totalpay' => $data->totalpay,
                    'addressSender' => $data->addressSender,
                    'addressRecipient' => $data->addressRecipient,
                    'status' => $data->status,
                    'statusPay' => $data->statusPay,
                    'readData' => $data->readData
                );
                // print_r($datac);
                // exit;
                //$query = $this->db->get_where('product', array('sku' => $data->sku));
                // $query = $this->db->get_where('product', array('idstore=>1'));
                $query = '';
                if (empty($query)) {
                    $this->db->insert('transaction', $datac);
                }
                // print_r($datac);
                // exit;
                $idtransaction = $this->db->insert_id();
                // $idproduct = $this->db->insert_id();
                //    } else {
                //     $supdate = '';

                foreach ($data->transactionDetails as $ddt) {
                    // print_r($data);
                    // exit;
                    //$dvariable = json_decode($ddt->variable);
                    // print_r($dvariable);
                    // exit;
                    $datax = array(
                        'idtransaction' => $idtransaction,
                        // 'idproduct' => $idproduct,
                        'skuPditails' => $ddt->sku,
                        'qty' => $ddt->qty,
                        'price' => $ddt->price,
                        'disc' => $ddt->disc,
                        'subtotal' => $ddt->subtotal,
                        'productName' => strtoupper($ddt->productName),
                        'idpditails' => ($ddt->idpditails),
                        // 'variable' => strtoupper($ddt->variable),
                        'collor' => strtoupper($dvariable->collor),
                        'size' => strtoupper($dvariable->size)
                            // $this->db->where('idtransaction', $q->idtransaction);
                    );
                    //  $query = $this->db->get_where('product_ditails', array('sku' => $ddt->sku));
                    //     print_r($datax);
                    // exit;
                    $query = '';
                    if (empty($query)) {
                        $this->db->insert('transaction_details', $datax);
                        $datay[] = array(
                            'order' => $datac,
                            // 'totaltransaction' => count($datax),
                            'orderProduct' => $datax,
                                //  'imageProduct' => $queryq
                        );
                    }
                }
                $query = 1;
            } else {
                $supdate = $verify;
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $datay;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function transactionUpdateData($data = '') {
        //print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $data = json_decode($data[2]);
                //$db2 = $this->load->database('db2', TRUE);
                //$sql = $db2->query("SELECT statusPay FROM transaction where statusPay ='$data->statusPay'");
                //$cek_pay = $sql->num_rows();
                $tranfers = $this->db->get_where('transaction', array('idtransaction' => $data->idtransaction))->result();
                //print_r($tranfers[0]->statusPay);
                //exit;

                if ($tranfers[0]->statusPay == 1) {
                    $tranfers = '';
                } else if ($data->statusPay == 1) {
                    $people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();
                    $tranfers = $this->db->get_where('transaction', array('idtransaction' => $data->idtransaction))->result();
                    // print_r($tranfers);
                    //exit;  
                    $message = 'rmall.id : Tranfers Berhasil, Senilai Rp ' . $tranfers[0]->totalpay . ' No Invoice ' . $tranfers[0]->noInvoice . ' Pesanan Sedang Di Proses Jazakallah';
                    #$this->load->library('sms');
                    $this->sms->SendSms($people[0]->phone, $message);
                } else {
                    $tranfers = '';
                }

                // $sql = $this->db->query("SELECT status FROM transaction where status ='$data->status'");
                // $cek_resi = $sql->num_rows();
                //print_r($cek_resi);
                //exit;
                $resi = $this->db->get_where('transaction', array('idtransaction' => $data->idtransaction))->result();
                //print_r($resi);
                //exit;
                if ($resi[0]->status == 1) {
                    $tranfers = '';
                } else if ($data->status == 1) {
                    $people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();
                    $tranfers = $this->db->get_where('transaction', array('idtransaction' => $data->idtransaction))->result();
                    // print_r($tranfers);
                    //exit;  
                    $message = 'rmall.id : Pesanan Sudah Dikirm,  No Invoice ' . $tranfers[0]->noInvoice . ',  No Resi ' . $tranfers[0]->trackingCode . ', Jazakallah';
                    #$this->load->library('sms');
                    $this->sms->SendSms($people[0]->phone, $message);
                } else {
                    $tranfers = '';
                }

                //$tranfers = $this->db->get_where('transaction', array('idtransaction' => $data->idtransaction))->result();
                //print_r($tranfers);
                //exit;
                //if ($data->statusPay == 1) {
                //$tranfers = '';
                //} else if ($data->status == 1) {
                // $people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();
                //$tranfers = $this->db->get_where('transaction', array('idtransaction' => $data->idtransaction))->result();
                //print_r($people);
                //exit;  
                //$message = 'rmall.id : Pesanan Sudah Dikirm,  No Invoice ' . $tranfers[0]->noInvoice . ',  No Resi JNE ' . $tranfers[0]->trackingCode . ', Jazakallah';
                #$this->load->library('sms');
                //$this->sms->SendSms($people[0]->phone, $message);
                //} else if ($data->statusPay == 1) {
                // $people = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();
                //$tranfers = $this->db->get_where('transaction', array('idtransaction' => $data->idtransaction))->result();
                //print_r($tranfers);
                //exit;  
                //$message = 'rmall.id : Tranfers Berhasil, Senilai Rp ' . $tranfers[0]->totalpay . ' No Invoice ' . $tranfers[0]->noInvoice . ' Pesanan Sedang Di Proses Jazakallah';
                #$this->load->library('sms');
                //  $this->sms->SendSms($people[0]->phone, $message);
                //} else {
                //$tranfers = '';
                //}
                $datac = array(
                    //'idauth' => $data->idauth,
                    //'idstore' => $verify[0]->idstore,
                    //'orderBy' => $data->orderBy,
                    // 'noInvoice' => $data->noInvoice,
                    'shipping' => $data->shipping,
                    'trackingCode' => $data->trackingCode,
                    'subtotal' => $data->subtotal,
                    'discount' => $data->discount,
                    'totalpay' => $data->totalpay,
                    //'addressSender' => $data->addressSender,
                    'idpeople' => $data->idpeople,
                    'status' => $data->status,
                    'statusPay' => $data->statusPay,
                    'readData' => $data->readData
                );

                $this->db->set($datac);
                $this->db->where('idtransaction', $data->idtransaction);
                $this->db->update('transaction');
            } else {
                return $this->empty_response();
            }
            if (!empty($datac)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
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

    public function dataStore($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {




                $this->db->select('a.*,e.nameVill,d.nameDis,b.nameCity,c.nameProv');
                $this->db->join('sensus_city as b', 'b.id_city = a.id_city', 'left');
                $this->db->join('sensus_province as c', 'c.id_prov = a.id_prov', 'left');
                $this->db->join('sensus_districts as d', 'd.id_dis = a.id_dis', 'left');
                $this->db->join('sensus_village as e', 'e.id_vill = a.id_vill', 'left');
                //$this->db->get_where('store as a');

                if (!empty($data[2])) {
                    $paging = $data[2] * 10;
                } else {
                    $paging = 0;
                }
                $this->db->limit(10, $paging);
                $this->db->where('delstore', '0');
                //$this->db->where('idstore', $data[2]);
                $queryx = $this->db->get_where('store as a')->result();
                $this->db->select('count(*) as store');
                $store = $this->db->get_where('store')->result();
                $jlh = $store[0]->store;
                $hal = ceil($jlh / 10) - 1;




                //$this->db->select('a.*,e.nameVill,d.nameDis,b.nameCity,c.nameProv');
                //$this->db->join('sensus_city as b', 'b.id_city = a.id_city', 'left');
                //$this->db->join('sensus_province as c', 'c.id_prov = a.id_prov', 'left');
                // $this->db->join('sensus_districts as d', 'd.id_dis = a.id_dis', 'left');
                //$this->db->join('sensus_village as e', 'e.id_vill = a.id_vill', 'left');
                //$this->db->where('delstore', 0);
                // $dataCat = $this->db->get_where('store as a')->result();
                // $supdate = $dataCat;
            } else {
                return $this->empty_response();
            }

            if ($queryx) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($queryx);
                $response['totalPage'] = ($hal);
                $response['totalstore'] = ($jlh);
                $response['data'] = $queryx;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive or data empty.';
                return $response;
            }
        }
    }

    public function dataStoreditails($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {


                //$this->db->select('a.*,e.nameVill,d.nameDis,b.nameCity,c.nameProv');
                //$this->db->get_where('store as a');
                //$this->db->join('sensus_city as b', 'b.id_city = a.id_city', 'left');
                //$this->db->join('sensus_province as c', 'c.id_prov = a.id_prov', 'left');
                //$this->db->join('sensus_districts as d', 'd.id_dis = a.id_dis', 'left');
                //$this->db->join('sensus_village as e', 'e.id_vill = a.id_vill', 'left');
                //if (!empty($data[2])) {
                //   $paging = $data[2] * 10;
                //} else {
                //   $paging = 0;
                //}
                //$this->db->limit(10, $paging);
                //$this->db->where('delstore', '0');
                //$this->db->where('idstore', $data[2]);
                //$queryx = $this->db->get()->result();
                //$this->db->select('count(*) as store');
                //$store = $this->db->get_where('store')->result();
                // $jlh = $store[0]->store;
                //$hal = ceil($jlh / 10)-1;



                $this->db->select('a.*,e.nameVill,d.nameDis,b.nameCity,c.nameProv');
                $this->db->join('sensus_city as b', 'b.id_city = a.id_city', 'left');
                $this->db->join('sensus_province as c', 'c.id_prov = a.id_prov', 'left');
                $this->db->join('sensus_districts as d', 'd.id_dis = a.id_dis', 'left');
                $this->db->join('sensus_village as e', 'e.id_vill = a.id_vill', 'left');

                $this->db->where('delstore', 0);
                $this->db->where('idstore', $data[2]);

                $dataCat = $this->db->get_where('store as a')->result();
                $supdate = $dataCat;
            } else {
                return $this->empty_response();
            }

            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($supdate);
                //$response['totalPage'] = ($hal);
                //$response['totalProduct'] = ($jlh);
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

    public function StoreaddData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            //    print_r($verify);
            // exit;
            if (!empty($verify)) {

                $data = array(
                    //'idstore' => ($data[2]),
                    'id_prov' => ($data[3]),
                    'id_city' => ($data[4]),
                    'id_dis' => ($data[5]),
                    'id_vill' => ($data[6]),
                    'namestore' => ($data[7]),
                    'addrstore' => ($data[8]),
                    'phonestore' => ($data[9])
                        //'pic' => ($data[10])
                );
                //$this->db->where('name !=', $data[2]);
                $dataCat = $this->db->get_where('store', $data)->result();
                // $this->db->where('name !=', $data);
                if (empty($dataCat)) {
                    $supdate = $this->db->insert('store', $data);
                } else {
                    $supdate = '';
                }
                $dataCat = $this->db->get_where('store', $data)->result();
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
            }
        }
    }

    public function StoreupdateData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($data);
                //exit;
                $datac = array(
                    // 'idstore' => ($data[2]),
                    'id_prov' => ($data[3]),
                    'id_city' => ($data[4]),
                    'id_dis' => ($data[5]),
                    'id_vill' => ($data[6]),
                    'namestore' => ($data[7]),
                    'addrstore' => ($data[8]),
                    'phonestore' => ($data[9])
                        //'pic' => ($data[10])
                );
                // print_r($datac);
                //exit;  
                $dataCat = $this->db->get_where('store', $data)->result();
                // print_r($dataCat);
                // exit;       
                if (empty($dataCat)) {
                    // print_r($data);
                    // exit;
                    //$this->db->set('idstore', ($data[2]));

                    $this->db->set('id_prov', ($data[3]));
                    $this->db->set('id_city', ($data[4]));
                    $this->db->set('id_dis', ($data[5]));
                    $this->db->set('id_vill', ($data[6]));
                    $this->db->set('namestore', ($data[7]));
                    $this->db->set('addrstore', ($data[8]));
                    $this->db->set('phonestore', ($data[9]));
                    //$this->db->set('pic', ($data[10]));

                    $this->db->where('idstore', $data[2]);
                    $this->db->update('store');

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
    }

    public function storedeleteData($data = '') {


        //  print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;
                $delstore = '1';
                $this->db->set('delstore', $delstore);
                $this->db->where('idstore', $data[2]);
                $supdate = $this->db->update('store');
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

    public function transactiondetailsGetData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;

                $this->db->select('*');
                $this->db->from('transaction');
                //$this->db->join('transaction_details as b', 'b.idtransaction = a.idtransaction', 'left');
                // $this->db->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
                $this->db->where('idtransaction', $data[2]);
                // $this->db->where('a.idproduct', 1);
                // print_r($verify);
                // exit;
                $query = $this->db->get()->result();
                //$this->db->where('idtransaction', $idtransaction);
                //$dataCat = $this->db->get_where('idproduct', $query)->result();
                // print_r($query);
                // exit;

                foreach ($query as $q) {
                    // print_r($q);
                    // exit;
                    $this->db->select('a.*,b.*,c.*');
                    $this->db->from('transaction_details as a');
                    $this->db->where('idtransaction', $q->idtransaction);
                    $this->db->join('product as b', 'b.idproduct = a.idproduct', 'left');
                    $this->db->join('product_ditails as c', 'c.idpditails = a.idpditails', 'left');
                    $query = $this->db->get()->result();
                    //print_r($query);
                    // exit;
                    //  $dataq = array(
                    //      'idproduct' => $q->idproduct
                    //  );
                    // // $this->db->select('urlImage, imageFile');
                    //$queryq = $this->db->get_where('product_images', $dataq)->result();
                    //$this->db->where('a.idproduct', $q->idproduct);
                    $datax[] = array(
                        'order' => $q,
                        'totaltransaction' => count($query),
                        'variableProduct' => $query,
                            //  'imageProduct' => $queryq
                    );
                    //     print_r($datax);
                    //     exit;
                }
            } else {
                $supdate = $verify;
            }
            if (!empty($query)) {
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
    }

    public function productimagesGetData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;

                $this->db->select('*');
                $this->db->from('product_images');
                //$this->db->join('category as b', 'b.idcategory = a.idcategory', 'left');
                //$this->db->where('idtransaction', $data[2]);
                //$this->db->where('a.idproduct', $data[2]);
                // print_r($verify);
                // exit;
                $query = $this->db->get()->result();
                //$this->db->where('a.idproduct', $query->idproduct);
                //$dataCat = $this->db->get_where('idproduct', $query)->result();
                // print_r($query);
                // exit;

                foreach ($query as $q) {
                    // print_r($q);
                    // exit;
                    $this->db->select('a.*,b.*');
                    $this->db->from('product_images as a');
                    $this->db->where('a.idpditails', $q->idpditails);
                    $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                    $query = $this->db->get()->result();
                    //     print_r($q);
                    // exit;
                    //     $dataq = array(
                    //         'idproduct' => $q->idproduct
                    //     );
                    //     $this->db->select('urlImage, imageFile');
                    //     $queryq = $this->db->get_where('product_images', $dataq)->result();
                    //     //$this->db->where('a.idproduct', $q->idproduct);
                    $datax[] = array(
                        'Images' => $q,
                        'totalImage' => count($query),
                        'variableImages' => $query,
                            //'imageProduct' => $queryq
                    );
                }
            } else {
                $supdate = $verify;
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

    public function productimagesAddData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;
                $data = json_decode($data[2]);
                // print_r($data);
                // exit;
                $datac = array(
                    'idpditails' => $data->idpditails,
                    'idproduct' => $data->idproduct,
                    'urlImage' => $data->urlImage,
                    'dir' => $data->dir,
                    'imageFile' => $data->imageFile,
                    'size' => $data->size,
                    'type' => $data->type
                        // 'descrDitails' => $data->descrDitails,
                        // 'descrDitails_en' => $data->descrDitails_en
                );
                // print_r($datac);
                // exit;
                //$query = $this->db->get_where('product', array('sku' => $data->sku));
                // $query = $this->db->get_where('product', array('idstore=>1'));
                $query = '';
                if (empty($query)) {
                    $this->db->insert('product_images', $datac);
                }
                // print_r($datac);
                // exit;
                // $idproduct = $this->db->insert_id();
                //    } else {
                //     $supdate = '';

                foreach ($data->productimagesDitails as $ddt) {
                    //$dvariable = json_decode($ddt->variable);
                    // print_r($ddt);
                    // exit;
                    $datax = array(
                        'idpditails' => $ddt->idpditails,
                        'urlImage' => $ddt->urlImage,
                        'dir' => $ddt->dir,
                        'imageFile' => $ddt->imageFile,
                        'size' => $ddt->size,
                        'type' => $ddt->type
                            // 'priceQuantumReport' => $ddt->priceQuantumReport,
                            // 'price' => $ddt->price,
                            // 'priceDiscount' => $ddt->priceDiscount,
                            // 'stock' => $ddt->stock
                    );
                    //  $query = $this->db->get_where('product_ditails', array('sku' => $ddt->sku));
                    //     print_r($datax);
                    // exit;
                    $query = '';
                    if (empty($query)) {
                        $this->db->insert('product_images_ditails', $datax);
                    }
                }
                $query = 1;
            } else {
                $supdate = $verify;
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
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

    public function productimagesUpdateData($data = '') {
        print_r($data);
        exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $data = json_decode($data[2]);
//                print_r($dat);
//                exit;
                $datac = array(
                    'idpditails' => $data->idpditails,
                    'idproduct' => $data->idproduct,
                    'urlImage' => $data->urlImage,
                    'dir' => $data->dir,
                    'imageFile' => $data->imageFile,
                    'size' => $data->size,
                    'type' => $data->type
                );
//                print_r($datac);
//                exit;
                $this->db->set($datac);
                // print_r($datac);
                // exit;

                $this->db->where('idproduct', $data->idproduct);
                $this->db->where('idstore', $verify[0]->idstore);
                $this->db->update('product');

                foreach ($data->productDitails as $ddt) {
                    $dvariable = json_decode($ddt->variable);
                    $datax = array(
                        'sku' => $ddt->sku,
                        // 'idstore' => $verify[0]->idstore,
                        'variable' => strtoupper($ddt->variable),
                        'collor' => strtoupper($dvariable->collor),
                        'size' => strtoupper($dvariable->size),
                        'priceQuantum' => $ddt->priceQuantum,
                        'priceQuantumReport' => $ddt->priceQuantumReport,
                        'price' => $ddt->price,
                        'priceDiscount' => $ddt->priceDiscount,
                        'stock' => $ddt->stock
                    );
                    $this->db->set($datax);
                    $this->db->where('idpditails', $ddt->idpditails);
                    // $this->db->where('idstore', $verify[0]->idstore);
                    $this->db->update('product_ditails');
                }
                $query = 1;
            } else {
                $supdate = $verify;
            }
            if (!empty($query)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
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

    public function uploadPic($data = '') {
        // print_r($data);
        //  exit;
        //$image_width = $data[3]['upload_data']['image_width'];
        //$image_height = $data[3]['upload_data']['image_height'];


        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($verify)) {
                    //unlink($data[4] . $data[3]['upload_data']['file_name']) or die("Couldn't delete file");
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data failed to receive.';
                    return $response;
                    exit;
                }
                $query = $this->db->get_where('product', array('idproduct' => $data[2]))->result();
                //if (empty($query)) {
                // if ($image_width == 700 AND $image_height == 700) {

                $data = array(
                    'idproduct' => $data[2],
                    //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                    'urlImage' => $data[3]['upload_data']['file_url'],
                    'dir' => $data[4],
                    'imageFile' => $data[3]['upload_data']['file_name'],
                    'size' => $data[3]['upload_data']['file_size'],
                    'type' => $data[3]['upload_data']['image_type'],
                        //'image_width' => $data[3]['upload_data']['image_width'],
                        //'image_height' => $data[3]['upload_data']['image_height']
                );
                // print_r($data);
                //exit;

                $this->db->insert('product_images', $data);
                // } else {
                // return $this->ukuran_response();
                // }
            } else {
                return $this->empty_response();
            }

            if ($data) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                return $response;
            } else {
                // unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function imagecat($data = '') {



        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($verify)) {

                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data failed to receive.';
                    return $response;
                    exit;
                }
                $query = $this->db->get_where('category_images', array('idcategory' => $data[2]))->result();
                $sql = $this->db->query("SELECT idcategory FROM category_images where idcategory='$data[2]'");
                $cek_id = $sql->num_rows();
                if ($cek_id > 0) {
                    $dataProduct = $this->db->get_where('category_images as a', array('idcategory' => $data[2]))->result();
                    //print_r($dataProduct);
                    //exit;
                    //unlink($dataProduct[0]->dir . '/' . $dataProduct[0]->imageFile);
                    $datax = array(
                        //'idcategory' => $data[2],
                        //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                        'urlImage' => $data[3]['upload_data']['file_url'],
                        'dir' => $data[4],
                        'imageFile' => $data[3]['upload_data']['file_name'],
                        'size' => $data[3]['upload_data']['file_size'],
                        'type' => $data[3]['upload_data']['image_type'],
                    );
                    //print_r($datax);
                    //exit;
                    $this->db->where('idcategory', $data[2]);
                    $this->db->update('category_images', $datax);
                } else {




                    $datax = array(
                        'idcategory' => $data[2],
                        //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                        'urlImage' => $data[3]['upload_data']['file_url'],
                        'dir' => $data[4],
                        'imageFile' => $data[3]['upload_data']['file_name'],
                        'size' => $data[3]['upload_data']['file_size'],
                        'type' => $data[3]['upload_data']['image_type'],
                    );
                    //print_r($datax);
                    //exit;
                    //$this->db->insert('idcategory', $data[2]);
                    $this->db->insert('category_images', $datax);
                    // } else {
                    // return $this->ukuran_response();
                }
            } else {
                return $this->empty_response();
            }

            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datax;
                return $response;
            } else {
                // unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function imagecat2($data = '') {



        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($verify)) {

                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data failed to receive.';
                    return $response;
                    exit;
                }
                $query = $this->db->get_where('category_images_icon', array('idcategory' => $data[2]))->result();
                $sql = $this->db->query("SELECT idcategory FROM category_images_icon where idcategory='$data[2]'");
                $cek_id = $sql->num_rows();
                if ($cek_id > 0) {
                    $dataProduct = $this->db->get_where('category_images_icon as a', array('idcategory' => $data[2]))->result();
                    //print_r($dataProduct);
                    //exit;
                    //unlink($dataProduct[0]->dir . '/' . $dataProduct[0]->imageFile);
                    $datax = array(
                        //'idcategory' => $data[2],
                        //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                        'urlImage' => $data[3]['upload_data']['file_url'],
                        'dir' => $data[4],
                        'imageFile' => $data[3]['upload_data']['file_name'],
                        'size' => $data[3]['upload_data']['file_size'],
                        'type' => $data[3]['upload_data']['image_type'],
                    );
                    //print_r($datax);
                    //exit;
                    $this->db->where('idcategory', $data[2]);
                    $this->db->update('category_images_icon', $datax);
                } else {




                    $datax = array(
                        'idcategory' => $data[2],
                        //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                        'urlImage' => $data[3]['upload_data']['file_url'],
                        'dir' => $data[4],
                        'imageFile' => $data[3]['upload_data']['file_name'],
                        'size' => $data[3]['upload_data']['file_size'],
                        'type' => $data[3]['upload_data']['image_type'],
                    );
                    //print_r($datax);
                    //exit;
                    //$this->db->insert('idcategory', $data[2]);
                    $this->db->insert('category_images_icon', $datax);
                    // } else {
                    // return $this->ukuran_response();
                }
            } else {
                return $this->empty_response();
            }

            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datax;
                return $response;
            } else {
                // unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function imagesubcat($data = '') {




        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($verify)) {

                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data failed to receive.';
                    return $response;
                    exit;
                }
                $query = $this->db->get_where('category_images', array('parentidcategory' => $data[2]))->result();
                $sql = $this->db->query("SELECT parentidcategory FROM category_images where parentidcategory='$data[2]'");
                $cek_id = $sql->num_rows();
                if ($cek_id > 0) {
                    $dataProduct = $this->db->get_where('category_images as a', array('parentidcategory' => $data[2]))->result();
                    //print_r($dataProduct);
                    //exit;
                    unlink($dataProduct[0]->dir . '/' . $dataProduct[0]->imageFile);
                    $datax = array(
                        //'idcategory' => $data[2],
                        //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                        'urlImage' => $data[3]['upload_data']['file_url'],
                        'dir' => $data[4],
                        'imageFile' => $data[3]['upload_data']['file_name'],
                        'size' => $data[3]['upload_data']['file_size'],
                        'type' => $data[3]['upload_data']['image_type'],
                    );
                    //print_r($datax);
                    //exit;
                    $this->db->where('parentidcategory', $data[2]);
                    $this->db->update('category_images', $datax);
                } else {




                    $datax = array(
                        'parentidcategory' => $data[2],
                        //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                        'urlImage' => $data[3]['upload_data']['file_url'],
                        'dir' => $data[4],
                        'imageFile' => $data[3]['upload_data']['file_name'],
                        'size' => $data[3]['upload_data']['file_size'],
                        'type' => $data[3]['upload_data']['image_type'],
                    );
                    //print_r($datax);
                    //exit;
                    //$this->db->insert('idcategory', $data[2]);
                    $this->db->insert('category_images', $datax);
                    // } else {
                    // return $this->ukuran_response();
                }
            } else {
                return $this->empty_response();
            }

            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datax;
                return $response;
            } else {
                // unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function delPic($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($verify)) {
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data failed to receive.';
                    return $response;
                    exit;
                }
            } else {
                $supdate = $verify;
            }

            $data = array(
                // 'idproduct' => $data[2],
                'idpimages' => $data[2]
            );
            $query = $this->db->get_where('product_images', $data)->result();
            if (!empty($query)) {
                // unlink($query[0]->dir . $query[0]->imageFile) or die("Couldn't delete file");
                // deleting object from storage service
                $this->load->library('S3_Storage');
                S3_Storage::delete_object('img/large/' . $query[0]->imageFile);
                S3_Storage::delete_object('img/medium/' . $query[0]->imageFile);
                S3_Storage::delete_object('img/small/' . $query[0]->imageFile);

                $this->db->where($data);
                $this->db->delete('product_images');

                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function uploadPicditails($data = '') {
        //print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                $datay = json_decode($data[2]);
                //print_r($datay);
                //exit;

                foreach ($datay->productDitails as $skuPditails) {
                    //print_r($skuPditails);
                    //exit;
                    $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                    $query = $this->db->get_where('product_ditails as a', array('a.skuPditails' => $skuPditails->sku))->result();


                    // $sql = $this->db->query("SELECT id FROM product where sku='$data->sku'");
                    // $cek_sku = $sql->num_rows();
                    //if($cek_sku > 0){

                    $datax = array(
                        'idpditails' => $query[0]->idpditails,
                        //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                        'urlImage' => $data[3]['upload_data']['file_url'],
                        'dir' => $data[4],
                        'imageFile' => $data[3]['upload_data']['file_name'],
                        'size' => $data[3]['upload_data']['file_size'],
                        'type' => $data[3]['upload_data']['image_type']
                    );
                    // print_r($data)

                    $this->db->where('skuPditails', $skuPditails);
                    $sql = $this->db->insert('product_images_ditails', $datax);
                }
            } else {
                return $this->empty_response();
            }


            if ($datax) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $datax;
                return $response;
            } else {
                // unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
                // deleting object from storage service
                if (isset($data[3]['upload_data'])) {
                    $this->load->library('S3_Storage');

                    S3_Storage::delete_object('img/large/' . $data[3]['upload_data']['file_name']);
                    S3_Storage::delete_object('img/medium/' . $data[3]['upload_data']['file_name']);
                    S3_Storage::delete_object('img/small/' . $data[3]['upload_data']['file_name']);
                }

                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function addPicditails($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                $data = array(
                    'idpditails' => $data[2],
                    //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                    'urlImage' => $data[3]['upload_data']['file_url'],
                    'dir' => $data[4],
                    'imageFile' => $data[3]['upload_data']['file_name'],
                    'size' => $data[3]['upload_data']['file_size'],
                    'type' => $data[3]['upload_data']['image_type']
                );
                //$this->db->where('idpditails', $data[2]);
                $supdate = $this->db->insert('product_images_ditails', $data);
            }


            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                return $response;
            } else {
                // unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
                // deleting object from storage service
                if (isset($data[3]['upload_data'])) {
                    $this->load->library('S3_Storage');

                    S3_Storage::delete_object('img/large/' . $data[3]['upload_data']['file_name']);
                    S3_Storage::delete_object('img/medium/' . $data[3]['upload_data']['file_name']);
                    S3_Storage::delete_object('img/small/' . $data[3]['upload_data']['file_name']);
                }

                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function delPicditails($data = '') {
//         print_r($data);
//         exit;
        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($verify)) {
                    $response['status'] = 502;
                    $response['error'] = true;
                    $response['message'] = 'Data failed to receive.';
                    return $response;
                    exit;
                }
            } else {
                $supdate = $verify;
            }

            $data = array(
                // 'idpditails' => $data[2],
                'idpimagesdetails' => $data[2]
            );

            //$data = $this->db->get_where('product_images_ditails', '$data[2]');
            $query = $this->db->get_where('product_images_ditails', $data)->result();

            // $this->db->where()
            // print_r($query);
            // exit;
            if (!empty($query)) {
                // unlink($query[0]->dir . $query[0]->imageFile) or die("Couldn't delete file");
                // if (empty(unlink(fil)ename))) {
                //     # code...
                // }
                // deleting object from storage service
                $this->load->library('S3_Storage');
                S3_Storage::delete_object('img/large/' . $query[0]->imageFile);
                S3_Storage::delete_object('img/medium/' . $query[0]->imageFile);
                S3_Storage::delete_object('img/small/' . $query[0]->imageFile);

                $this->db->where($data);
                $this->db->delete('product_images_ditails');

                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function banner($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $this->db->select('*');
                $this->db->where('delbanner', '0');
				$this->db->order_by('idbanner', 'DESC');
                $supdate = $this->db->get_where('banner')->result();
            } else {
				return $this->token_response();
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
    }

    public function banneradd($data = '') {
        // print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // if (empty($verify)) {
                //     //unlink($data[4] . $data[3]['upload_data']['file_name']) or die("Couldn't delete file");
                //     $response['status'] = 502;
                //     $response['error'] = true;
                //     $response['message'] = 'Data failed to receive.';
                //     return $response;
                //     exit;
                // }
                //$query = $this->db->get_where('product', array('idproduct' => $data[2]))->result();
                // if (!empty($query)) {
                //$image_width = $data[10]['upload_data']['image_width'];
                //$image_height = $data[10]['upload_data']['image_height'];
                //if ($image_width == 900 AND $image_height == 500) {
                $datax = json_decode($data[2]);


                $datay = array(
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'titleBig' => $datax->titleBig,
                    'titleLittle' => $datax->titleLittle,
                    'timeStart' => $datax->timeStart,
                    'timeFinish' => $datax->timeFinish,
                    'position' => $datax->position,
                    'urlLink' => $datax->urlLink,
                    'desc' => $datax->desc,
                    'dimention' => $datax->dimention,
                    //'urlImage' => 'http://img.rmall.id/' . $data[3]['upload_data']['file_name'],
                    'urlImage' => $data[3]['upload_data']['file_url'],
                    'dir' => $data[4],
                    //'image_width' => $data[10]['upload_data']['image_width'],
                    // 'image_height' => $data[10]['upload_data']['image_height'],
                    'imageFile' => $data[3]['upload_data']['file_name'],
                    'size' => $data[3]['upload_data']['file_size'],
                    'type' => $data[3]['upload_data']['image_type']
                );


                $this->db->insert('banner', $datay);
            } else {
                return $this->token_response();
            }


            // }

            if ($data) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                $response['data'] = $data;
                return $response;
            } else {
                //unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function bannerdel($data = '') {

        // $sql = $this->db->query("SELECT statusdel FROM category where statusdel='1'");
        // $cek_cat = $sql->num_rows();
        //  print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {


                //$this->db->set('delbanner', 1);
                $this->db->where('idbanner', $data[2]);

                $supdate = $this->db->delete('banner');
            } else {
				  return $this->token_response();
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

    public function dashboard($data = '') {
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {
                $this->db->select('count(*) as category');
                $category = $this->db->get_where('category')->result();
                $this->db->select('count(*) as product');
                $this->db->where('delproduct', '0');
                $product = $this->db->get_where('product')->result();
                $this->db->select('count(*) as user');
                $user = $this->db->get_where('apiauth_user')->result();
                $this->db->select('count(*) as transaction');
                $transaction = $this->db->get_where('transaction')->result();
                $this->db->select('count(*) as store');
                $store = $this->db->get_where('store')->result();
                $this->db->select('count(*) as newproduct');
                $tgl = date('Y-m-d');
                $this->db->where('dateCreate', $tgl);
                $newproduct = $this->db->get_where('product')->result();
                $this->db->select('count(*) as newuser');
                $tgl1 = date('Y-m-d');
                $this->db->where('dateCreate', $tgl1);
                $newuser = $this->db->get_where('apiauth_user')->result();
                $this->db->select('count(*) as newtransaction');
                $tgl2 = date('Y-m-d');
                $this->db->where('dateCreate', $tgl2);
                $newtransaction = $this->db->get_where('transaction')->result();



                // $category = $this->db->get()->result();

                $datax[] = array(
                    'Category' => $category,
                    'Product' => ($product),
                    'Customer' => ($user),
                    'Transaction' => ($transaction),
                    'Store' => ($store),
                    'NewProduct' => ($newproduct),
                    'NewUser' => ($newuser),
                    'NewTransaction' => ($newtransaction)
                        // 'variableImages' => $query,
                        //'imageProduct' => $queryq
                );
            }


            if (!empty($datax)) {
                // unlink($query[0]->dir . $query[0]->imageFile) or die("Couldn't delete file");
                // if (empty(unlink(fil)ename))) {
                //     # code...
                // }

                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
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

    public function feeddata($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

            if (!empty($verify)) {

                $this->db->select('a.*,b.urlImagefeed');
                $this->db->join('feed_images as b', 'b.idfeed = a.idfeed', 'left');
                $this->db->where('statusdel', '0');
                $query = $this->db->get_where('feed as a')->result();

                //         $response['status'] = 502;
                //         $response['error'] = true;
                //         $response['message'] = 'Data failed to receive.';
                //         return $response;
                //         exit;
                // } else {
                //     $supdate = $verify;
            }



            // print_r($query);
            // exit;
            if (!empty($query)) {
                // unlink($query[0]->dir . $query[0]->imageFile) or die("Couldn't delete file");
                // $this->db->where($data);
                //$this->db->delete('product_images');

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

    public function latestorder($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            // print_r($data);
            // exit;
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {

                $this->db->select('*');
                $tgl = date("Y-m-d");
                $this->db->where('dateCreate', $tgl);
                $this->db->limit(5);
                $this->db->order_by('timeCreate', 'DESC');
                $transaction = $this->db->get_where('transaction')->result();
            }

            if (!empty($transaction)) {
                // unlink($query[0]->dir . $query[0]->imageFile) or die("Couldn't delete file");
                // if (empty(unlink(fil)ename))) {
                //     # code...
                // }

                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $transaction;
                return $response;
            } else {
                $response['status'] = 502;
                $response['error'] = true;
                $response['message'] = 'Data failed to receive.';
                return $response;
            }
        }
    }

    public function latestproduct($data = '') {
        // print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1])) {
            // print_r($data);
            // exit;
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            //exit;
            if (!empty($verify)) {

                $this->db->select('*');
                $tgl = date("Y-m-d");
                $this->db->where('dateCreate', $tgl);
                $this->db->limit(5);
                $this->db->order_by('timeCreate', 'DESC');
                $product = $this->db->get_where('product')->result();
            }

            if (!empty($product)) {
                // unlink($query[0]->dir . $query[0]->imageFile) or die("Couldn't delete file");
                // if (empty(unlink(fil)ename))) {
                //     # code...
                // }

                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['data'] = $product;
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

    public function addOrders($data = '') {
        // print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                $data = json_decode($data[2]);
                $user = $this->db->get_where('sensus_people', array('idpeople' => $data->idpeople))->result();
                //print_r($data);
                //exit;

                $dataTrx = array(
                    'idauth' => $verify[0]->idauthstaff,
                    'idauthuser' => $user[0]->idauthuser,
                    'idstore' => $verify[0]->idstore,
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'orderBy' => ($data->orderBy),
                    'noInvoice' => $verify[0]->idauthstaff . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
                    'shipping' => ($data->shipping),
                    'shippingprice' => ($data->shippingprice),
                    'addressSender' => $verify[0]->namestore,
                    'idpeople' => ($data->idpeople),
                    'payment' => ($data->payment)
                );

                $supdate = $this->db->insert('transaction', $dataTrx);
                $insert_id = $this->db->insert_id();

                foreach ($data->dataOrders as $dataOrders) {
                    //print_r($dataOrders);


                    $this->db->join('product as b', 'b.idproduct = a.idproduct', 'left');
                    $dataProduct = $this->db->get_where('product_ditails as a', array('a.idpditails' => $dataOrders->idpditails))->result();
                    //print_r($dataProduct);

                    if (!empty($dataProduct)) {
                        $dataOrdersx = array(
                            'idtransaction' => $insert_id,
                            'idproduct' => $dataProduct[0]->idproduct,
                            'idpditails' => $dataProduct[0]->idpditails,
                            'productName' => $dataProduct[0]->productName,
                            'skuPditails' => $dataProduct[0]->skuPditails,
                            'collor' => $dataProduct[0]->collor,
                            'size' => $dataProduct[0]->size,
                            'price' => $dataProduct[0]->price,
                            'qty' => $dataOrders->qty,
                            'weight' => ($dataProduct[0]->weight) * $dataOrders->qty,
                            'disc' => ($dataProduct[0]->priceDiscount) * $dataOrders->qty,
                            //'cost' => $dataOrders->cost * $dataProduct[0]->weight,
                            'subtotal' => ($dataProduct[0]->price) * $dataOrders->qty
                        );



                        $this->debitStock($dataProduct[0]->idpditails, $dataProduct[0]->skuPditails, $dataOrders->qty);

                        $this->db->insert('transaction_details', $dataOrdersx);
                        $subtotal[] = $dataOrdersx['subtotal'];
                        $subdisc[] = $dataOrdersx['disc'];
                        $totalweight[] = ($dataOrdersx['weight']);
                        //print_r($cost);
                    } else {
                        $subtotal[] = 0;
                    }
                }
                $cost = $data->shippingprice * ceil(array_sum($totalweight) / 1000);
                $this->db->set('subtotal', array_sum($subtotal), true);
                $this->db->set('cost', ($cost), true);
                $this->db->set('discount', array_sum($subdisc), true);
                $this->db->set('totalpay', array_sum($subtotal) + ($cost) - array_sum($subdisc), true);
                $this->db->where('idtransaction', $insert_id);
                $this->db->update('transaction');
                $stUpdate = 1;
            } else {
                $stUpdate = 0;
            }
            if (!empty($stUpdate)) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data successfully processed.';
                $response['dataTransaction'] = array(
                    'ordersDay' => $dataTrx['dateCreate'],
                    'corp' => $dataTrx['orderBy'],
                    'noInvoice' => $dataTrx['noInvoice'],
                    'shipping' => $dataTrx['shipping'],
                    'addressSender' => $dataTrx['addressSender'],
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

    public function statusprosesData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $this->db->set('status', 1);
                $this->db->where('idtransaction', $data[2]);
                $this->db->where('idstore', $verify[0]->idstore);
                $supdate = $this->db->update('transaction');
            }


            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                // $response['totalData'] = count($supdate);
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

    public function statussendingData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $this->db->set('status', 2);
                $this->db->where('idtransaction', $data[2]);
                $this->db->where('idstore', $verify[0]->idstore);
                $supdate = $this->db->update('transaction');
            }


            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                // $response['totalData'] = count($supdate);
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

    public function statuspayData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $this->db->select('statusPay');
                //$this->db->where('idtransaction', $data[2]);
                $this->db->where('idstore', $verify[0]->idstore);
                $supdate = $this->db->get_where('transaction')->result();
            }


            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                // $response['totalData'] = count($supdate);
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

    public function statuspaypayData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $this->db->set('statuspay', 1);
                $this->db->where('idtransaction', $data[2]);
                $this->db->where('idstore', $verify[0]->idstore);
                $supdate = $this->db->update('transaction');
            }


            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                // $response['totalData'] = count($supdate);
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

    public function statuspaycancelData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                /* CHECK DATA FOR RESTORE STOCK */
                $this->db->select('idproduct,idpditails,qty');
                $query = $this->db->get_where('transaction_details', array('idtransaction' => $data[2]))->result();
                if (!empty($query)) {
                    foreach ($query as $dq) {
                        $this->db->set('stock', 'stock+' . $dq->qty, FALSE);
                        $this->db->set('physical', 'physical+' . $dq->qty, FALSE);
                        $this->db->where('idpditails', $dq->idpditails);
                        $this->db->update('product_ditails');
                    }
                }
                $this->db->set('statuspay', 2);
                $this->db->set('status', 9);
                $this->db->where('idtransaction', $data[2]);
                $supdate = $this->db->update('transaction');
//                print_r($query);
//                exit;
                /* END GET DATA */
            }

            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                // $response['totalData'] = count($supdate);
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

    public function statuspayrefund($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                /* CHECK DATA FOR RESTORE STOCK */
                $this->db->select('idproduct,idpditails,qty');
                $query = $this->db->get_where('transaction_details', array('idtransaction' => $data[2]))->result();
                if (!empty($query)) {
                    foreach ($query as $dq) {
                        $this->db->set('stock', 'stock+' . $dq->qty, FALSE);
                        $this->db->set('physical', 'physical+' . $dq->qty, FALSE);
                        $this->db->where('idpditails', $dq->idpditails);
                        $this->db->update('product_ditails');
                    }
                }
                $this->db->set('statuspay', 3);
                $this->db->set('status', 9);
                $this->db->where('idtransaction', $data[2]);
                $supdate = $this->db->update('transaction');
//                print_r($query);
//                exit;
                /* END GET DATA */
            }

            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                // $response['totalData'] = count($supdate);
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
	
	 public function discount($data = '') {
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {
                $this->db->select('*');
                

              
                $dataCatx = $this->db->get_where('discount')->result();
            } else {
                return $this->token_response();
            }

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
    }
	
	public function adddiscount($data = '') {
	    // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
               
                    $data = array(
                        'discount' => strtoupper($data[2]),
                        'start' => ($data[3]),
                        'end' => ($data[4])
                    );
               // print_r($data['discount']);
               // exit;
                    $supdate = $this->db->update('discount', $data);
            $x= $data['discount']/100;
             $y= (100-$data['discount'])/100;

// print_r($y);
// exit;
                $this->db->set('realprice', 'price*'.$y,FALSE);
                $this->db->set('priceDiscount','price*'.$x,FALSE);
//        $this->db->where('idproduct', '1507');
//        $this->db->where('idproduct', '1505');
//        $this->db->where('idproduct', '130');
               $supdate = $this->db->update('product_ditails');
        
                
                if ($supdate) {
                    $response['status'] = 200;
                    $response['error'] = false;
                    $response['message'] = 'Data received successfully.';
                    $response['data'] = $supdate;
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
    }
	
	public function flashsale($data = '') {
	   //  print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
				$product = json_decode($data[4]);
				 
               
                    $data = array(
                       
                        'startdate' => ($data[2]),
                        'enddate' => ($data[3]),
						'idproduct' => ($data[4]),
						'flashsale' => ($data[5]),
						'limit' => ($data[6])
                    );
               // print_r($data['discount']);
               // exit;
                    $supdate = $this->db->insert('flashsale', $data);
           // $x= $data['discount']/100;
            // $y= (100-$data['discount'])/100;

// print_r($y);
// exit;
               // $this->db->set('realprice', 'price*'.$y,FALSE);
               // $this->db->set('priceDiscount','price*'.$x,FALSE);
//        $this->db->where('idproduct', '1507');
//        $this->db->where('idproduct', '1505');
//        $this->db->where('idproduct', '130');
             //  $supdate = $this->db->update('product_ditails');
			} else {
				 return $this->token_response();
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
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCat;
                    return $response;
                }
            
        }
    }
	
		public function delflashsale($data = '') {
	  //  print_r($data);
         //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
				 $this->db->where('idflashsale', $data[2]);
                $query = $this->db->delete('flashsale');
			} else {
				 return $this->token_response();
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
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCat;
                    return $response;
                }
            
        }
    }
	
	public function getflashsale($data = '') {
	   //  print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $this->db->select('*');  
				
                $dataCatx = $this->db->get_where('flashsale')->result();
				//$this->db->where('idproduct', $ddt->idproduct);
				
				 $data = json_decode($dataCatx[0]->idproduct);
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
			
			//$this->db->select('*');
			//$this->db->where('idproduct', $ddt->idproduct);
			//$datay = $this->db->get_where('discount')->result();	
				//$datay[] = $datax ;
			  }
				
				
            } else {
                return $this->token_response();
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
	
	public function productdiscount($data = '') {
	    // print_r($data);
         //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
               
              
            $x= $data[3]/100;
            $y= (100-$data[3])/100;


            $this->db->set('realprice', 'price*'.$y,FALSE);
			$this->db->set('priceDiscount','price*'.$x,FALSE);
			$this->db->where('idproduct', $data[2]);
            $supdate = $this->db->update('product_ditails');
			} else {
				return $this->token_response();
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
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCat;
                    return $response;
                }
            
        }
    }
	
	public function categorydiscount($data = '') {
	     //print_r($data);
         //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
               
            
			
            $x= $data[3]/100;
            $y= (100-$data[3])/100;


            $this->db->set('realprice', 'price*'.$y,FALSE);
			$this->db->set('priceDiscount','price*'.$x,FALSE);
			
            $this->db->join('product_ditails as b', 'b.idproduct = a.idproduct', 'left');  
			$this->db->where('a.idcategory', $data[2]);
			$dataCat = $this->db->get_where('product as a')->result();
            $supdate = $this->db->update('product_ditails as b');
			} else {
				return $this->token_response();
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
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCat;
                    return $response;
                }
            
        }
    }
	
	public function ditailsdiscount($data = '') {
	    // print_r($data);
         //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
               
              
            $x= $data[3]/100;
            $y= (100-$data[3])/100;


            $this->db->set('realprice', 'price*'.$y,FALSE);
			$this->db->set('priceDiscount','price*'.$x,FALSE);
			$this->db->where('idpditails', $data[2]);
            $supdate = $this->db->update('product_ditails');
			} else {
				return $this->token_response();
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
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCat;
                    return $response;
                }
            
        }
    }
	
	public function dataterms($data = '') {
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {
                $this->db->select('*');
                

              $this->db->where('statusterms', '0');
               $dataCatx = $this->db->get_where('terms')->result();
            } else {
                return $this->token_response();
            }

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
    }
	
	public function addterms($data = '') {
	    // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
               
                    $data = array(
                        'sk' => ($data[2]),
                        
                    );
               // print_r($data['discount']);
               // exit;
                  $supdate = $this->db->insert('terms', $data);
          
              
        
                
                if ($supdate) {
                    $response['status'] = 200;
                    $response['error'] = false;
                    $response['message'] = 'Data received successfully.';
                    $response['data'] = $supdate;
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
    }
	
	 public function termsdraft($data = '') {

        // $sql = $this->db->query("SELECT statusdel FROM category where statusdel='1'");
        // $cek_cat = $sql->num_rows();
        //  print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

				$cekterms = $this->db->get_where('terms', array('idterms' =>  $data[2]))->result();
				//print_r($cekterms[0]->statusterms);
				//exit;
				
				 if ($cekterms[0]->statusterms = 0) {
					 $this->db->set('statusterms', 1);
                $this->db->where('idterms', $data[2]);

                $supdate = $this->db->update('terms');
					 
				 } else {
                $this->db->set('statusterms', 0);
                $this->db->where('idterms', $data[2]);

                $supdate = $this->db->update('terms');
				 }
            } else {
				  return $this->token_response();
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
	
	public function shorturl($data = '') {
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {
                $this->db->select('*');
                

             
               $dataCatx = $this->db->get_where('short_url')->result();
            } else {
                return $this->token_response();
            }

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
    }
	
	public function addshorturl($data = '') {
	     //print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
               
                    $data = array(
						'datecreate' =>  date('Y-m-d'),
						'urlname' => ($data[2]),
                        'urltarget' => ($data[3])
                        
                    );
               // print_r($data['discount']);
               // exit;
                  $supdate = $this->db->insert('short_url', $data);
          
               } else {
                return $this->token_response();
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
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCat;
                    return $response;
                }
            
        }
    }
	
	public function delshorturl($data = '') {
	     //print_r($data);
        //exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
               
                   
				  $this->db->where('idshort',$data[2]);
                  $supdate = $this->db->delete('short_url');
          
              
			} else {
                return $this->token_response();
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
                    $response['message'] = 'Data already exists.';
                    $response['data'] = $dataCat;
                    return $response;
                }
            
        }
    }

    //END CRUD PRODUCT
}
