<?php

class Sellercenter_model extends CI_Model {

    public function __construct() {
        parent::__construct();

        $this->load->database();
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
                $this->db->select('*');
                $dataCat = $this->db->get_where('category', array('parentidcategory' => 0))->result();
                // print_r($dataCat);
                // exit;
                foreach ($dataCat as $dC) {
                    // print_r($dC);
                    // exit;
                    $dataSubCat = $this->db->get_where('category', array('parentidcategory' => $dC->idcategory))->result();
                    // print_r($dataSubCat);
                    // exit;
                    $dataCatx[] = array(
                        'idcategory' => $dC->idcategory,
                        'categoryName' => $dC->categoryName,
                        'dataSubCat' => $dataSubCat
                    );
                }
                $supdate = $dataCatx;
            } else {
                $supdate = $verify;
            }

            if ($supdate) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['totalData'] = count($dataCat);
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
                }
                if (empty($dataCat)) {
                    $supdate = $this->db->insert('category', $data);
                } else {
                    $supdate = '';
                }
                $dataCat = $this->db->get_where('category', $data)->result();
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
                if (empty($data[2])) {
                    return $this->empty_response();
                } else {
                    if ($cek_cat > 0) {
                        return $this->duplicate_response();
                    } else {
                        $datac = array(
                            'categoryName' => strtoupper($data[2]),
                            'idcategory' => strtoupper($data[3])
                        );
                        // print_r($datac);
                        // exit;
                        $dataCat = $this->db->get_where('category', $data)->result();
                    }
                }
                if (empty($dataCat)) {
                    $this->db->set('categoryName', strtoupper($data[2]));
                    $this->db->where('idcategory', $data[3]);
                    $this->db->update('category');
                    $supdate = $this->db->get_where('category', $datac)->result();
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
                    $supdate = '';
                }
                $dataCat = $this->db->get_where('category', $data)->result();
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

    //END CRUD CATEGORY
    //CRUD PRODUCT
    public function productGetData($data = '') {
        print_r($data);
        exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;

                $this->db->select('a.idproduct, a.idcategory,a.idstore, a.sku, a.timeCreate, a.productName, a.descr, a.descr_en, a.descrDitails, a.descrDitails_en, b.categoryName');
                $this->db->from('product as a');
                $this->db->join('category as b', 'b.idcategory = a.idcategory', 'left');
                // $this->db->join('product_images as c', 'c.idproduct = a.idproduct', 'left');
                $this->db->where('a.idstore', $verify[0]->idstore);
                // $this->db->where('a.idproduct', 1);
                // print_r($verify);
                // exit;
                $query = $this->db->get()->result();
                // print_r($query);
                // exit;


                foreach ($query as $q) {

                    $this->db->select('a.*,b.urlImage as imagesVariable');
                    $this->db->from('product_ditails as a');
                    $this->db->where('a.idproduct', $q->idproduct);
                    // $this->db->where('idstore', $verify[0]->idstore);
                    $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                    $query = $this->db->get()->result();
                    //     print_r($query);
                    // exit;
                    $dataq = array(
                        'idproduct' => $q->idproduct
                    );
                    $this->db->select('*');
                    $queryq = $this->db->get_where('product_images', $dataq)->result();

                    //$this->db->where('a.idproduct', $q->idproduct);
                    $datax[] = array(
                        'product' => $q,
                        'totalsku' => count($query),
                        'variableProduct' => $query,
                        'imageProduct' => $queryq
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

    public function productAddData($data = '') {

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
                $sql = $this->db->query("SELECT sku FROM product where sku='$data->sku'");
                $cek_sku = $sql->num_rows();
                if (empty($data->sku) || empty($data->idcategory) || empty($data->productName) || empty($data->descr)) {
                    return $this->empty_response();
                } else {
                    if ($cek_sku > 0) {
                        return $this->duplicate_response();
                    } else {
                        $datac = array(
                            'idcategory' => $data->idcategory,
                            'idstore' => $verify[0]->idstore,
                            'sku' => $data->sku,
                            'timeCreate' => date('H:i:s'),
                            'dateCreate' => date('Y-m-d'),
                            'productName' => $data->productName,
                            'descr' => $data->descr,
                            'descr_en' => $data->descr_en,
                            'descrDitails' => $data->descrDitails,
                            'descrDitails_en' => $data->descrDitails_en
                        );
                    }
                }
                // print_r($datac);
                // exit;
                //$query = $this->db->get_where('product', array('sku' => $data->sku));
                // $query = $this->db->get_where('product', array('idstore=>1'));
                $query = '';
                if (empty($query)) {
                    $this->db->insert('product', $datac);
                }
                // print_r($datac);
                // exit;
                $idproduct = $this->db->insert_id();
                //    } else {
                //     $supdate = '';

                foreach ($data->productDitails as $ddt) {
                    // print_r($data->productDitails);
                    // exit;
                    $dvariable = json_decode($ddt->variable);
                    $sql1 = $this->db->query("SELECT sku FROM product_ditails where sku='$ddt->sku'");
                    $cek_sku_ditail = $sql1->num_rows();
                    if (empty($ddt->sku)) {
                        return $this->empty_response();
                        // } else {
                        //     if($cek_sku_ditail > 0){
                        //                          return $this->duplicate_response();
                        //print_r($ddt->sku);
                        //exit;
                    } else {
                        $datax = array(
                            'idproduct' => $idproduct,
                            'sku' => $ddt->sku,
                            'variable' => strtoupper($ddt->variable),
                            'collor' => strtoupper($dvariable->collor),
                            'size' => strtoupper($dvariable->size),
                            'priceQuantum' => $ddt->priceQuantum,
                            'priceQuantumReport' => $ddt->priceQuantumReport,
                            'price' => $ddt->price,
                            'priceDiscount' => $ddt->priceDiscount,
                            'stock' => $ddt->stock
                        );

                        //  }
                    }
                    // print_r($datax);
                    //     exit;
                    $datay[] = array(
                        //'product' => $q,
                        //'totalsku' => count($query),
                        'Product' => $datac,
                        'Product Details' => $datax
                    );
                    //  $query = $this->db->get_where('product_ditails', array('sku' => $ddt->sku));
                    //     print_r($datay);
                    // exit;
                    $query = '';
                    if (empty($query)) {
                        $this->db->insert('product_ditails', $datax);
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
                        'sku' => $data->sku,
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
                // $this->db->from('apiauth_staff as a');
                $this->db->join('apiauth_staff_images as b', 'b.idauthstaff = a.idauthstaff', 'left');
                $this->db->where('a.idstore', $verify[0]->idstore);
                $dataCat = $this->db->get_where('apiauth_staff as a')->result();
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

    public function StaffaddData($data = '') {
        // print_r($data);
        // exit;
        $sql = $this->db->query("SELECT username FROM apiauth_staff where username='$data[5]'");
        $cek_username = $sql->num_rows();
        $sql1 = $this->db->query("SELECT name FROM apiauth_staff where name='$data[3]'");
        $cek_name = $sql1->num_rows();
        // print_r($sql);
        // exit;

        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                if (empty($data[3]) || empty($data[4]) || empty($data[5]) || empty($data[6])) {
                    return $this->empty_response();
                    //ds } else if {
                } else {
                    if ($cek_username > 0 || $cek_name > 0) {
                        return $this->duplicate_response();
                    } else {

                        $data = array(
                            'idstore' => $verify[0]->idstore,
                            'level' => ($data[2]),
                            //'status' => ($data[3]),
                            'name' => ($data[3]),
                            'phone' => ($data[4]),
                            'username' => ($data[5]),
                            'password' => md5($data[6])
                                //'password' => md5($this->input->post('password1'))
                        );
                        // print_r($verify);
                        // exit;
                        // $this->db->where('idstore', $verify[0]->idstore);
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
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            if (!empty($verify)) {
//                if(empty($data[5]) || empty($data[4])){
//                    return $this->empty_response();
//
//                // }else {
//                //     if($cek_username > 0){
//                //         return $this->duplicate_response();
//
//                }else{
                $datac = array(
                    'level' => ($data[2]),
                    'name' => ($data[3]),
                    //'username' => ($data[5]),
                    'password' => ($data[4]),
                    'status' => ($data[5])
                );


                $dataCat = $this->db->get_where('apiauth_staff', $data)->result();
            }


            if (empty($dataCat)) {

                $this->db->set('level', strtoupper($data[2]));


                $this->db->set('name', ($data[3]));
                //$this->db->set('username', ($data[5]));
                $this->db->set('password', md5($data[4]));
                $this->db->set('status', ($data[5]));
                $this->db->where('idauthstaff', $verify[0]->idauthstaff);
                $this->db->where('idstore', $verify[0]->idstore);
                $this->db->update('apiauth_staff');

                $supdate = 1;
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
        //}
    }

    // public function dataUser($data = '') {
    //     // print_r($data);
    //     // exit;
    //     if (empty($data[0]) || empty($data[1])) {
    //         return $this->empty_response();
    //     } else {
    //         $verify = $this->verfyAccount($data[0], $data[1]);
    //         if (!empty($verify)) {
    //             // print_r($verify);
    //             // exit;
    //             $this->db->select('a.*,b.urlImage');
    //             //$this->db->where('idauthuser', $verify[0]->idauthuser);
    //             $this->db->join('apiauth_user_images as b', 'b.idauthuser = a.idauthuser', 'left');
    //             $dataCat = $this->db->get_where('apiauth_user as a')->result();
    //             $supdate = $dataCat;
    //         } else {
    //             $supdate = $verify;
    //         }
    //         if ($supdate) {
    //             $response['status'] = 200;
    //             $response['error'] = false;
    //             $response['totalData'] = count($dataCat);
    //             $response['data'] = $dataCat;
    //             return $response;
    //         } else {
    //             $response['status'] = 502;
    //             $response['error'] = true;
    //             $response['message'] = 'Data failed to receive or data empty.';
    //             return $response;
    //         }
    //     }
    // }
    // public function UseraddData($data = '') {
    //     $sql = $this->db->query("SELECT hp FROM apiauth_user where hp='$data[5]'");
    //     $cek_hp = $sql->num_rows();
    //     $sql1 = $this->db->query("SELECT email FROM apiauth_user where email='$data[3]'");
    //     $cek_email = $sql1->num_rows();
    //     // print_r($data);
    //     // exit;
    //     if (empty($data[0]) || empty($data[1]) || empty($data)) {
    //         return $this->empty_response();
    //     } else {
    //         $verify = $this->verfyAccount($data[0], $data[1]);
    //         //    print_r($verify);
    //         // exit;
    //         if (!empty($verify)) {
    //             if(empty($data[2]) || empty($data[3]) || empty($data[5])){
    //                 return $this->empty_response();
    //              }else {
    //                  if($cek_hp > 0 || $cek_email > 0){
    //                      return $this->duplicate_response();
    //             }else{
    //             $data = array(
    //                 'name' => ($data[2]),
    //                 'email' => ($data[3]),
    //                 'password' => md5($data[4]),
    //                 'hp' => ($data[5]),
    //                 //'foto' => ($data[6]),
    //             );
    //             //$this->db->where('name !=', $data[2]);
    //             $dataCat = $this->db->get_where('apiauth_user', $data)->result();
    //         }
    //     }
    //            // $this->db->where('name !=', $data);
    //             if (empty($dataCat)) {
    //                 $supdate = $this->db->insert('apiauth_user', $data);
    //             } else {
    //                 $supdate = '';
    //             }
    //             $dataCat = $this->db->get_where('apiauth_user', $data)->result();
    //             if ($supdate) {
    //                 $response['status'] = 200;
    //                 $response['error'] = false;
    //                 $response['message'] = 'Data received successfully.';
    //                 $response['data'] = $dataCat;
    //                 return $response;
    //             } else {
    //                 $response['status'] = 502;
    //                 $response['error'] = true;
    //                 $response['message'] = 'Data already exists.';
    //                 $response['data'] = $dataCat;
    //                 return $response;
    //             }
    //         }
    //     }
    // }
    // public function UserupdateData($data = '') {
    //     $sql = $this->db->query("SELECT hp FROM apiauth_user where hp='$data[5]'");
    //     $cek_hp = $sql->num_rows();
    //     $sql1 = $this->db->query("SELECT email FROM apiauth_user where email='$data[3]'");
    //     $cek_email = $sql1->num_rows();
    //     // print_r($data);
    //     // exit;
    //     if (empty($data[0]) || empty($data[1]) || empty($data)) {
    //         return $this->empty_response();
    //     } else {
    //         $verify = $this->verfyAccount($data[0], $data[1]);
    //         if (!empty($verify)) {
    //              if(empty($data[2]) || empty($data[3]) || empty($data[4]) || empty($data[5]) || empty($data[6])){
    //                 return $this->empty_response();
    //             }else {
    //                 if($cek_hp > 0 || $cek_email > 0){
    //                     return $this->duplicate_response();
    //             }else{
    //             // print_r($data);
    //             // exit;
    //             $datac = array(
    //                 //'idstore' => strtoupper($data[2]),
    //                 'name' => ($data[2]),
    //                 'email' => ($data[3]),
    //                 'password' => ($data[4]),
    //                 'hp' => ($data[5]),
    //                 //'foto' => ($data[6]),
    //                 'status' => ($data[6])
    //             );
    //             // print_r($datac);
    //             //exit;  
    //             $dataCat = $this->db->get_where('apiauth_user', $data)->result();
    //         }
    //     }
    //             // print_r($dataCat);
    //             // exit;       
    //             if (empty($dataCat)) {
    //                 $this->db->set('name', ($data[2]));
    //                 $this->db->set('email', ($data[3]));
    //                 $this->db->set('password', md5($data[4]));
    //                 $this->db->set('hp', ($data[5]));
    //                 //$this->db->set('foto', ($data[6]));
    //                 $this->db->set('status', ($data[6]));
    //                 $this->db->where('idauthuser', $verify[0]->idauthstaff);
    //                 $this->db->update('apiauth_user');
    //                 $supdate = 1;
    //                 // print_r($dataCat);
    //                 // exit;
    //             } else {
    //                 $supdate = '';
    //             }
    //             if ($supdate) {
    //                 $response['status'] = 200;
    //                 $response['error'] = false;
    //                 $response['message'] = 'Data received successfully.';
    //                 $response['data'] = $supdate;
    //                 return $response;
    //             } else {
    //                 $response['status'] = 502;
    //                 $response['error'] = true;
    //                 $response['message'] = 'Data failed to receive or data empty.';
    //                 return $response;
    //             }
    //         }
    //     }
    // }



    public function ditailsGetData($data = '') {
        print_r($data);
        exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;

                $this->db->select('a.*,b.categoryName');
                $this->db->from('product as a');
                $this->db->join('category as b', 'b.idcategory = a.idcategory', 'left');
                $this->db->where('a.idstore', $verify[0]->idstore);
                $this->db->where('a.idproduct', $data[2]);

                // print_r($verify);
                // exit;
                $query = $this->db->get()->result();
                //$this->db->where('a.idproduct', $query->idproduct);
                //$dataCat = $this->db->get_where('idproduct', $query)->result();
                // print_r($query);
                // exit;

                foreach ($query as $q) {
                    $this->db->select('a.*,b.urlImage as imagesVariable');
                    $this->db->from('product_ditails as a');
                    $this->db->where('a.idproduct', $q->idproduct);
                    $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                    $query = $this->db->get()->result();
                    //     print_r($q);
                    // exit;
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
            } else {
                $supdate = $verify;
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
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;
                $idproduct = $data[3];
                $data = json_decode($data[2]);
                // print_r($data);
                // exit;
                //    $datac = array(
                //        'idcategory' => $data->idcategory,
                //        'idstore' => $verify[0]->idstore,
                //        'sku' => $data->sku,
                //        'timeCreate' => date('Y-m-d H:i:s'),
                //        'productName' => $data->productName,
                //        'descr' => $data->descr,
                //        'descr_en' => $data->descr_en,
                //        'descrDitails' => $data->descrDitails,
                //        'descrDitails_en' => $data->descrDitails_en
                //    );
                //    // print_r($datac);
                //    // exit;
                //    //$query = $this->db->get_where('product', array('sku' => $data->sku));
                //   // $query = $this->db->get_where('product', array('idstore=>1'));
                //    $query = '';
                //    if (empty($query)) {
                //        $this->db->insert('product', $datac);
                //    }
                //    $idproduct = $this->db->insert_id();
                // //    } else {
                //     $supdate = '';
                // print_r($data);
                // exit;
                foreach ($data->productDitails as $ddt) {
                    $dvariable = json_decode($ddt->variable);
                    // print_r($ddt);
                    // exit;
                    if (empty($ddt->sku)) {
                        return $this->empty_response();
                    } else {
                        $datax = array(
                            'idproduct' => $idproduct,
                            'sku' => $ddt->sku,
                            'variable' => strtoupper($ddt->variable),
                            'collor' => strtoupper($dvariable->collor),
                            'size' => strtoupper($dvariable->size),
                            'priceQuantum' => $ddt->priceQuantum,
                            'priceQuantumReport' => $ddt->priceQuantumReport,
                            'price' => $ddt->price,
                            'priceDiscount' => $ddt->priceDiscount,
                            'stock' => $ddt->stock
                        );
                    }
                    //$queryq = $this->db->get_where('product_images', $dataq)->result();
                    $query = $this->db->get_where('product_ditails', $datax)->result();
                    //$this->db->where('sku !=', $ddt->sku);
                    $datay[] = array(
                        //'product' => $q,
                        //'totalsku' => count($query),
                        // 'Product' => $datac,
                        'Product Details' => $datax
                    );
                    if (empty($query)) {
                        $this->db->insert('product_ditails', $datax);
                    }
                }
                $query = 1;
            } else {
                $supdate = $verify;
            }
            $query = $this->db->get_where('product_ditails', $datax)->result();
            if (!empty($datay)) {
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

    public function ditailsUpdateData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $data = json_decode($data[2]);
//                print_r($dat);
//                exit;
//                 $datac = array(
//                     'idcategory' => $data->idcategory,
//                     'idstore' => $verify[0]->idstore,
//                     'sku' => $data->sku,
//                     'productName' => $data->productName,
//                     'descr' => $data->descr,
//                     'descr_en' => $data->descr_en,
//                     'descrDitails' => $data->descrDitails,
//                     'descrDitails_en' => $data->descrDitails_en
//                 );
// //                print_r($datac);
// //                exit;
//                 $this->db->set($datac);
//                 // print_r($datac);
//                 // exit;
//                 $this->db->where('idproduct', $data->idproduct);
//                 $this->db->where('idstore', $verify[0]->idstore);
//                 $this->db->update('product');

                foreach ($data->productDitails as $ddt) {
                    $dvariable = json_decode($ddt->variable);
                    if (empty($ddt->sku)) {
                        return $this->empty_response();
                    } else {
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
                    }
                    $this->db->set($datax);
                    $this->db->where('sku', $ddt->sku);
                    //$this->db->where('idstore', $verify[0]->idstore);
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

    public function transactionGetData($data = '') {
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
                $this->db->where('idstore', $verify[0]->idstore);
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

    public function transactionAddData($data = '') {
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
                    //'idtransaction' => $data->idtransaction,
                    'idauth' => $data->idauth,
                    'orderBy' => $data->orderBy,
                    'timeCreate' => date('Y-m-d H:i:s'),
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
                    $dvariable = json_decode($ddt->variable);
                    // print_r($dvariable);
                    // exit;
                    $datax = array(
                        'idtransaction' => $idtransaction,
                        // 'idproduct' => $idproduct,
                        'sku' => $ddt->sku,
                        'qty' => $ddt->qty,
                        'price' => $ddt->price,
                        'disc' => $ddt->disc,
                        'subtotal' => $ddt->subtotal,
                        'productName' => strtoupper($ddt->productName),
                        'idpditails' => ($ddt->idpditails),
                        'variable' => strtoupper($ddt->variable),
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
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $data = json_decode($data[3]);
                // print_r($data);
                // exit;
                $datac = array(
                    'idauth' => $data->idauth,
                    //'idstore' => $verify[0]->idstore,
                    'orderBy' => $data->orderBy,
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
                $this->db->set($datac);
                // print_r($datac);
                // exit;

                $this->db->where('idtransaction', $data->idtransaction);
                //$this->db->where('idstore', $verify[0]->idstore);
                $this->db->update('transaction');
                // print_r($datac);
                // exit;

                foreach ($data->transactionDetails as $ddt) {
                    $dvariable = json_decode($ddt->variable);
                    $datax = array(
                        'sku' => $ddt->sku,
                        // 'idstore' => $verify[0]->idstore,
                        'productName' => $ddt->productName,
                        'variable' => strtoupper($ddt->variable),
                        'collor' => strtoupper($dvariable->collor),
                        'size' => strtoupper($dvariable->size)
                            // 'priceQuantum' => $ddt->priceQuantum,
                            // 'priceQuantumReport' => $ddt->priceQuantumReport,
                            // 'price' => $ddt->price,
                            // 'priceDiscount' => $ddt->priceDiscount,
                            // 'stock' => $ddt->stock
                    );
                    $this->db->set($datax);
                    $this->db->where('idpditails', $ddt->idpditails);
                    // $this->db->where('idstore', $verify[0]->idstore);
                    $this->db->update('transaction_details');
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

    public function dataStore($data = '') {
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
                //$this->db->where('idauthuser', $verify[0]->idauthuser);

                $dataCat = $this->db->get_where('store')->result();
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
                    'phonestore' => ($data[9]),
                    'pic' => ($data[10])
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
                    'phonestore' => ($data[9]),
                    'pic' => ($data[10])
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
                    $this->db->set('pic', ($data[10]));

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
                $this->db->from('transaction_details');
                //$this->db->join('category as b', 'b.idcategory = a.idcategory', 'left');
                $this->db->where('idtransaction', $data[2]);
                //$this->db->where('a.idproduct', $data[2]);
                // print_r($verify);
                // exit;
                $query = $this->db->get()->result();
                //$this->db->where('a.idproduct', $query->idproduct);
                //$dataCat = $this->db->get_where('idproduct', $query)->result();
                // print_r($query);
                // exit;
                // foreach ($query as $q) {
                //     $this->db->select('a.*,b.urlImage as imagesVariable');
                //     $this->db->from('product_ditails as a');
                //     $this->db->where('a.idproduct', $q->idproduct);
                //     $this->db->join('product_images_ditails as b', 'b.idpditails = a.idpditails', 'left');
                //     $query = $this->db->get()->result();
                // //     print_r($q);
                // // exit;
                //     $dataq = array(
                //         'idproduct' => $q->idproduct
                //     );
                //     $this->db->select('urlImage, imageFile');
                //     $queryq = $this->db->get_where('product_images', $dataq)->result();
                //     //$this->db->where('a.idproduct', $q->idproduct);
                $datax[] = array(
                    //'product' => $q,
                    'totalsku' => count($query),
                    'variableProduct' => $query,
                        //'imageProduct' => $queryq
                );
                //}
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

    public function transactiondetailsAddData($data = '') {
        // print_r($data);
        // exit;   
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                // print_r($verify);
                // exit;
                $idtransaction = $data[3];
                $data = json_decode($data[2]);
                // print_r($data);
                // exit;
                $datac = array(
                    'idtransaction' => $idtransaction,
                    'productName' => $data->productName,
                    'sku' => $data->sku,
                    'variable' => $data->variable,
                    'collor' => $data->collor,
                    'size' => $data->size,
                    'price' => $data->price,
                    'disc' => $data->disc,
                    'qty' => $data->qty,
                    'subtotal' => $data->subtotal
                );
                // print_r($datac);
                // exit;
                //$query = $this->db->get_where('product', array('sku' => $data->sku));
                // $query = $this->db->get_where('idtransaction', $data[3]);
                $query = $this->db->get_where('transaction_details', $datac)->result();
                //$query = '';
                if (empty($query)) {
                    $this->db->insert('transaction_details', $datac);
                }
                //    $idproduct = $this->db->insert_id();
                // //    } else {
                //       $supdate = '';
                //    print_r($data);
                //    exit;
                //    foreach ($data->productDitails as $ddt) {
                //        $dvariable = json_decode($ddt->variable);
                //        $datax = array(
                //            'idproduct' => $idproduct,
                //            'sku' => $ddt->sku,
                //            'variable' => strtoupper($ddt->variable),
                //            'collor' => strtoupper($dvariable->collor),
                //            'size' => strtoupper($dvariable->size),
                //            'priceQuantum' => $ddt->priceQuantum,
                //            'priceQuantumReport' => $ddt->priceQuantumReport,
                //            'price' => $ddt->price,
                //            'priceDiscount' => $ddt->priceDiscount,
                //            'stock' => $ddt->stock
                //        );
                //         //$queryq = $this->db->get_where('product_images', $dataq)->result();
                //        $query = $this->db->get_where('product_ditails', $datax)->result();
                //        $this->db->where('sku !=', $ddt->sku);
                //      // $this->db->where(‘name !=’, $name);
                //       // $this->db->where('idproduct' );
                //    //     print_r($datax);
                // exit;
                // $query = '';
                //     if (empty($query)) {
                //         $this->db->insert('product_ditails', $datax);
                //     }
                // }
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

    public function transactiondetailsUpdateData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {

                $idtransactiondetails = $data[3];
                $data = json_decode($data[2]);
                //

                $datac = array(
                    'idtransaction' => $data->idtransaction,
                    'productName' => $data->productName,
                    'sku' => $data->sku,
                    'variable' => $data->variable,
                    'collor' => $data->collor,
                    'size' => $data->size,
                    'price' => $data->price,
                    'disc' => $data->disc,
                    'qty' => $data->qty,
                    'subtotal' => $data->subtotal
                );
//                print_r($datac);
//                exit;
                $this->db->set($datac);
                // print_r($datac);
                // exit;
                //$this->db->where('idproduct', $data->idproduct);
                $this->db->where('idtransactiondetails', $idtransactiondetails);
                $this->db->update('transaction_details');

                // foreach ($data->productDitails as $ddt) {
                //     $dvariable = json_decode($ddt->variable);
                //     $datax = array(
                //         'sku' => $ddt->sku,
                //        // 'idstore' => $verify[0]->idstore,
                //         'variable' => strtoupper($ddt->variable),
                //         'collor' => strtoupper($dvariable->collor),
                //         'size' => strtoupper($dvariable->size),
                //         'priceQuantum' => $ddt->priceQuantum,
                //         'priceQuantumReport' => $ddt->priceQuantumReport,
                //         'price' => $ddt->price,
                //         'priceDiscount' => $ddt->priceDiscount,
                //         'stock' => $ddt->stock
                //     );
                //     $this->db->set($datax);
                //     $this->db->where('sku', $ddt->sku);
                //     //$this->db->where('idstore', $verify[0]->idstore);
                //     $this->db->update('product_ditails');
                // }
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
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            // print_r($verify);
            // exit;
            // if (!empty($verify)) {
            //     // if (empty($verify)) {
            //     //    unlink($data[4] . $data[3]['upload_data']['file_name']) or die("Couldn't delete file");
            //         $response['status'] = 502;
            //         $response['error'] = true;
            //         $response['message'] = 'Data failed to receive.';
            //         return $response;
            //         exit;
            //     //}
            $query = $this->db->get_where('product', array('idproduct' => $data[2]))->result();
            if (!empty($query)) {
                $data = array(
                    'idproduct' => $data[2],
                    //'urlImage' => 'http://imgsandbox.rmall.id/' . $data[3]['upload_data']['file_name'],
                    'urlImage' => $data[3]['upload_data']['file_url'],
                    'dir' => $data[4],
                    'imageFile' => $data[3]['upload_data']['file_name'],
                    'size' => $data[3]['upload_data']['file_size'],
                    'type' => $data[3]['upload_data']['image_type']
                );
                // print_r($data);
                // exit;

                $this->db->insert('product_images', $data);
            } else {
                $supdate = $verify;
            }

            if ($query) {
                $response['status'] = 200;
                $response['error'] = false;
                $response['message'] = 'Data received successfully.';
                return $response;
            } else {
                //unlink($data[3]['upload_data']['full_path']) or die("Couldn't delete file");
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

    public function delPic($data = '') {
        // print_r($data);
        // exit;
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
                //'idproduct' => $data[2],
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
        // print_r($data[2]);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data[3]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            //if (!empty($verify)) {
            // print_r($verify);
            // exit;
            // if (empty($verify)) {
            //     //unlink($data[4] . $data[3]['upload_data']['file_name']) or die("Couldn't delete file");
            //     $response['status'] = 502;
            //     $response['error'] = true;
            //     $response['message'] = 'Data failed to receive.';
            //     return $response;
            //     exit;
            // }
            //$query = $this->db->get_where('product_images', array('idpditails' => $data[2]))->result();
            // print_r($query);
            // exit;
            if (!empty($verify)) {
                $data = array(
                    'idpditails' => $data[2],
                    //'urlImage' => 'http://imgsandbox.rmall.id/' . $data[3]['upload_data']['file_name'],
                    'urlImage' => $data[3]['upload_data']['file_url'],
                    'dir' => $data[4],
                    'imageFile' => $data[3]['upload_data']['file_name'],
                    'size' => $data[3]['upload_data']['file_size'],
                    'type' => $data[3]['upload_data']['image_type']
                );
                // print_r($data);
                // exit;

                $this->db->insert('product_images_ditails', $data);
                //}
            } else {
                $supdate = $verify;
            }

            if ($data) {
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
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1]) || empty($data[3]) || empty($data)) {
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
                //'idpditails' => $data[2],
                'idpimagesdetails' => $data[3]
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
                    'urlImage' => 'http://sandbox.rmall.id/file/img/' . $data[3]['upload_data']['file_name'],
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

    public function staffPicadd($data = '') {

        if (empty($data[0]) || empty($data[1]) || empty($data[3]) || empty($data)) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);

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
                    'idauthstaff' => $verify[0]->idauthstaff,
                    'urlImage' => 'http://sandbox.rmall.id/file/img/' . $data[2]['upload_data']['file_name'],
                    'dir' => $data[3],
                    'imageFile' => $data[2]['upload_data']['file_name'],
                    'size' => $data[2]['upload_data']['file_size'],
                    'type' => $data[2]['upload_data']['image_type']
                );

                $this->db->where('idauthstaff', $verify[0]->idauthstaff);
                $this->db->insert('apiauth_staff_images', $data);
                $supdate = $data;
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

    public function dashboard($data = '') {
        // print_r($data);
        // exit;
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
                $product = $this->db->get_where('product')->result();
                // $this->db->select('count(*) as user');
                //$user = $this->db->get_where('apiauth_user')->result();
                $this->db->select('count(*) as transaction');
                $transaction = $this->db->get_where('transaction')->result();
                //$this->db->select('count(*) as store');
                //$store = $this->db->get_where('store')->result();
                $this->db->select('count(*) as newproduct');
                $tgl = date('Y-m-d');
                $this->db->where('timeCreate', $tgl);
                $newproduct = $this->db->get_where('product')->result();
                $this->db->select('count(*) as newuser');
                $tgl1 = date('Y-m-d');
                $this->db->where('timeCreate', $tgl1);
                $newuser = $this->db->get_where('apiauth_user')->result();
                $this->db->select('count(*) as newtransaction');
                $tgl2 = date('Y-m-d');
                $this->db->where('timeCreate', $tgl2);
                $newtransaction = $this->db->get_where('transaction')->result();



                // $category = $this->db->get()->result();

                $datax[] = array(
                    'Category' => $category,
                    'Product' => ($product),
                    //'Konsumen' => ($user),
                    'Transaction' => ($transaction),
                    //'Store' => ($store),
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

    public function debitStock($idpditails = '', $sku = '', $debit = '') {
        $this->db->set('stock', 'stock-' . $debit, FALSE);
        // $this->db->set('physical', 'physical-' . $debit, FALSE);
        $this->db->where('idpditails', $idpditails);
        $this->db->where('sku', $sku);
        $this->db->update('product_ditails');
    }

    public function addOrders($data = '') {
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
                // $sku1 = ($data->dataOrders[0]->sku);
                // // print_r($sku1);
                // // exit;
                // $sql = $this->db->query("SELECT sku FROM product where sku='$sku1'");
                // // print_r($sql);
                // // exit;
                // $cek_sku = $sql->num_rows();
                //     // print_r($cek_sku);
                //     // exit;
                //  if($cek_sku  ){
                //         return $this->empty_response();
                //   }else {  


                $dataTrx = array(
                    'idauth' => $verify[0]->idauthstaff,
                    'idstore' => $verify[0]->idstore,
                    'timeCreate' => date('H:i:s'),
                    'dateCreate' => date('Y-m-d'),
                    'orderBy' => ($data->orderBy),
                    'noInvoice' => $verify[0]->idauthstaff . time() . rand(pow(10, 5 - 1), pow(10, 5) - 1),
                    'shipping' => ($data->shipping),
                    'addressSender' => $verify[0]->namestore,
                    'addressRecipient' => json_encode($data->shippingSend->recipient)
                );
                //}
                // print_r($dataTrx);
                // exit;
                $supdate = $this->db->insert('transaction', $dataTrx);
                $insert_id = $this->db->insert_id();

                foreach ($data->dataOrders as $dataOrders) {
                    // print_r($dataOrders);
                    // exit;
                    $dataProduct = $this->dataProduct($dataOrders->idProducts, $dataOrders->sku);
                    // print_r($dataProduct);
                    // exit;

                    if (!empty($dataProduct)) {
                        $dataOrdersx = array(
                            'idtransaction' => $insert_id,
                            'idproduct' => $dataProduct->idproduct,
                            'idpditails' => $dataProduct->idpditails,
                            'productName' => $dataProduct->productName,
                            'sku' => $dataProduct->sku,
                            'variable' => $dataProduct->variable,
                            'collor' => $dataProduct->collor,
                            'size' => $dataProduct->size,
                            'price' => $dataProduct->price,
                            'disc' => $dataProduct->priceDiscount,
                            'qty' => $dataOrders->qty,
                            'subtotal' => ($dataProduct->price - $dataProduct->priceDiscount) * $dataOrders->qty
                        );
                        // print_r($dataOrdersx);
                        // exit;
                        $this->debitStock($dataProduct->idpditails, $dataProduct->sku, $dataOrders->qty);

                        $this->db->insert('transaction_details', $dataOrdersx);
                        $subtotal[] = $dataOrdersx['subtotal'];
                        $subdisc[] = $dataOrdersx['disc'];
                    } else {
                        $subtotal[] = 0;
                    }
                }
                // print_r($subdisc);
                // exit;
                $this->db->set('subtotal', array_sum($subtotal), true);
                $this->db->set('discount', array_sum($subdisc), true);
                $this->db->set('totalpay', array_sum($subtotal) - array_sum($subdisc), true);
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
                    'ordersTime' => $dataTrx['timeCreate'],
                    'corp' => $dataTrx['orderBy'],
                    'noInvoice' => $dataTrx['noInvoice'],
                    'shipping' => $dataTrx['shipping'],
                    'addressSender' => $dataTrx['addressSender'],
                    'addressRecipient' => $dataTrx['addressRecipient'],
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

    public function statusGetData($data = '') {
        // print_r($data);
        // exit;
        if (empty($data[0]) || empty($data[1])) {
            return $this->empty_response();
        } else {
            $verify = $this->verfyAccount($data[0], $data[1]);
            if (!empty($verify)) {
                $this->db->select('status');
                $supdate = $this->db->get_where('transaction')->result();
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
                // $response['totalData'] = count($supdate
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

    //END CRUD PRODUCT
}
