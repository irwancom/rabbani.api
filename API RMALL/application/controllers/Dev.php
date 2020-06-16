<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Dev extends CI_Controller {

    /**
     * Constructor
     * 
     * @access public
     * @return void
     */
    public function __construct() {
        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Method: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, x-xsrf-token, X-API-KEY');

        $this->load->helper(array('form', 'url'));
    }

    /**
     * Test for upload
     * 
     * @access public
     * @return json
     */
    public function upload() {
        $json = array();

        $this->load->library('S3_Storage');

        $this->load->library('upload', array(
            'upload_path' => 'images/products/',
            'allowed_types' => 'jpg|png|jpeg',
            'max_size' => '1048',
            'encrypt_name' => true,
            'use_storage_service' => true
        ));

        if (!$this->upload->do_upload()) {
            $json['error'] = $this->upload->display_errors('', '');
        } else {
            $upload_data = $this->upload->data();

            $json['image'] = $upload_data['full_path'];
            $json['thumb'] = $upload_data['file_url'];
            $json['success'] = 'Gambar berhasil diupload!';
            $json['upload_data'] = $upload_data;
        }

        $this->output->set_status_header(200);
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($json));
    }

    /**
     * Delete file from storage
     * 
     * @access public
     * @return json
     */
    public function delete() {
        $json = [];

        $file_name = $this->input->post('uri');

        if ($file_name != '') {
            $this->load->library('S3_Storage');

            if (S3_Storage::delete_object($file_name)) {
                $json['success'] = 'File ' . S3_Storage::get_url($file_name) . ' berhasil dihapus dari Storage!';
            } else {
                $json['error'] = 'Gagal menghapus file!';
            }
        }

        $this->output->set_status_header(200);
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($json));
    }

}
