<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Upload extends CI_Upload {

    public $s3_status = FALSE;

    /**
     * Image file sizing
     *
     * Ukuran panjang(length) image untuk setiap jenis ukuran
     * Bisa diganti-ganti sesuai kebutuhan
     * 
     * @var mixed
     * @access private
     */
    private $sizes = array(
        'small' => 250,
        'medium' => 450,
        'large' => 750
    );

    /**
     * Constructor
     * 
     * @access public
     * @return void
     */
    public function __construct($config = array()) {
        parent::__construct($config);

        if (isset($config['use_storage_service'])) {
            $this->s3_status = (bool) $config['use_storage_service'];
        }
    }

    /**
     * Perform the file upload
     * 
     * @access public
     * @param string $field (default: 'userfile')
     * @return void
     */
    public function do_upload($field = 'userfile') {
        if (isset($_FILES[$field])) {
            $_file = $_FILES[$field];
        } elseif (($c = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $field, $matches)) > 1) {
            $_file = $_FILES;

            for ($i = 0; $i < $c; $i++) {
                if (($field = trim($matches[0][$i], '[]')) === '' OR ! isset($_file[$field])) {
                    $_file = NULL;
                    break;
                }

                $_file = $_file[$field];
            }
        }

        if (!isset($_file)) {
            $this->set_error('upload_no_file_selected', 'debug');
            return FALSE;
        }

        if (!$this->validate_upload_path() && $this->s3_status == FALSE) {
            return FALSE;
        }

        if (!is_uploaded_file($_file['tmp_name'])) {
            $error = isset($_file['error']) ? $_file['error'] : 4;

            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                    $this->set_error('upload_file_exceeds_limit', 'info');
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $this->set_error('upload_file_exceeds_form_limit', 'info');
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $this->set_error('upload_file_partial', 'debug');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->set_error('upload_no_file_selected', 'debug');
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->set_error('upload_no_temp_directory', 'error');
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $this->set_error('upload_unable_to_write_file', 'error');
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $this->set_error('upload_stopped_by_extension', 'debug');
                    break;
                default:
                    $this->set_error('upload_no_file_selected', 'debug');
                    break;
            }

            return FALSE;
        }

        $this->file_temp = $_file['tmp_name'];
        $this->file_size = $_file['size'];

        if ($this->detect_mime !== FALSE) {
            $this->_file_mime_type($_file);
        }

        $this->file_type = preg_replace('/^(.+?);.*$/', '\\1', $this->file_type);
        $this->file_type = strtolower(trim(stripslashes($this->file_type), '"'));
        $this->file_name = $this->_prep_filename($_file['name']);
        $this->file_ext = $this->get_extension($this->file_name);
        $this->client_name = $this->file_name;

        if (!$this->is_allowed_filetype()) {
            $this->set_error('upload_invalid_filetype', 'debug');
            return FALSE;
        }

        if ($this->_file_name_override !== '') {
            $this->file_name = $this->_prep_filename($this->_file_name_override);

            if (strpos($this->_file_name_override, '.') === FALSE) {
                $this->file_name .= $this->file_ext;
            } else {
                $this->file_ext = $this->get_extension($this->_file_name_override);
            }

            if (!$this->is_allowed_filetype(TRUE)) {
                $this->set_error('upload_invalid_filetype', 'debug');
                return FALSE;
            }
        }

        if ($this->file_size > 0) {
            $this->file_size = round($this->file_size / 1024, 2);
        }

        if (!$this->is_allowed_filesize()) {
            $this->set_error('upload_invalid_filesize', 'info');
            return FALSE;
        }

        if (!$this->is_allowed_dimensions()) {
            $this->set_error('upload_invalid_dimensions', 'info');
            return FALSE;
        }

        $this->file_name = $this->_CI->security->sanitize_filename($this->file_name);

        if ($this->max_filename > 0) {
            $this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
        }

        if ($this->remove_spaces === TRUE) {
            $this->file_name = preg_replace('/\s+/', '_', $this->file_name);
        }

        if ($this->file_ext_tolower && ($ext_length = strlen($this->file_ext))) {
            $this->file_name = substr($this->file_name, 0, -$ext_length) . $this->file_ext;
        }

        $this->orig_name = $this->file_name;

        if (FALSE === ($this->file_name = $this->set_filename($this->upload_path, $this->file_name))) {
            return FALSE;
        }

        if ($this->xss_clean && $this->do_xss_clean() === FALSE) {
            $this->set_error('upload_unable_to_write_file', 'error');
            return FALSE;
        }

        if ($this->s3_status) {
            $this->_CI->load->library('S3_Storage');

            $this->set_image_properties($this->file_temp);

            $this->upload_path = str_replace(FCPATH, '', $this->upload_path);

            if ($this->is_image()) {
                foreach ($this->sizes as $key => $value) {
                    $uri = $this->upload_path . $key . '/' . $this->file_name;
                    $old_image = $this->file_temp;
                    $new_image = $this->file_temp . $key . $this->file_ext;

                    if (@copy($old_image, $new_image)) {
                        $image = new Image($old_image);
                        $image->resize($value, 0);
                        $image->save($new_image);

                        S3_Storage::put_object_file($new_image, $uri, S3_Storage::DEFAULT_BUCKET, S3_Storage::ACL_PUBLIC_READ, array(), $this->file_type);

                        if (file_exists($new_image)) {
                            unlink($new_image);
                        }
                    }
                }

                $this->upload_path = $this->upload_path . 'large/';
            } else {
                S3_Storage::put_object_file($this->file_temp, $this->upload_path . $this->file_name, S3_Storage::DEFAULT_BUCKET, S3_Storage::ACL_PUBLIC_READ, array(), $this->file_type);
            }

            if (file_exists($this->file_temp)) {
                unlink($this->file_temp);
            }
        } else {
            if (!@copy($this->file_temp, $this->upload_path . $this->file_name)) {
                if (!@move_uploaded_file($this->file_temp, $this->upload_path . $this->file_name)) {
                    $this->set_error('upload_destination_error', 'error');
                    return FALSE;
                }
            }

            $this->set_image_properties($this->upload_path . $this->file_name);
        }

        return TRUE;
    }

    /**
     * Finalized Data Array
     *
     * Returns an associative array containing all of the information
     * related to the upload, allowing the developer easy access in one array.
     *
     * @param	string	$index
     * @return	mixed
     */
    public function data($index = NULL) {
        $data = array(
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_url' => ($this->s3_status ? S3_Storage::get_url($this->upload_path . $this->file_name) : ''),
            'file_path' => $this->upload_path,
            'full_path' => $this->upload_path . $this->file_name,
            'raw_name' => substr($this->file_name, 0, -strlen($this->file_ext)),
            'orig_name' => $this->orig_name,
            'client_name' => $this->client_name,
            'file_ext' => $this->file_ext,
            'file_size' => $this->file_size,
            'is_image' => $this->is_image(),
            'image_width' => $this->image_width,
            'image_height' => $this->image_height,
            'image_type' => $this->image_type,
            'image_size_str' => $this->image_size_str,
        );

        if (!empty($index)) {
            return isset($data[$index]) ? $data[$index] : NULL;
        }

        return $data;
    }

}

class Image {

    private $file;
    private $image;
    private $info;

    /**
     * Constructor
     * 
     * @access public
     * @param string $file
     * @return void
     */
    public function __construct($file) {
        if (file_exists($file)) {
            $this->file = $file;

            $info = getimagesize($file);

            $this->info = array(
                'width' => $info[0],
                'height' => $info[1],
                'bits' => $info['bits'],
                'mime' => $info['mime']
            );

            $this->image = $this->create($file);
        } else {
            exit('Error: Could not load image ' . $file . '!');
        }
    }

    /**
     * Create
     * 
     * @access private
     * @param string $image
     * @return string
     */
    private function create($image) {
        $mime = $this->info['mime'];

        if ($mime == 'image/gif') {
            return imagecreatefromgif($image);
        } elseif ($mime == 'image/png') {
            return imagecreatefrompng($image);
        } elseif ($mime == 'image/jpeg') {
            return imagecreatefromjpeg($image);
        }
    }

    /**
     * Save
     * 
     * @access public
     * @param mixed $file
     * @param int $quality
     * @return void
     */
    public function save($file, $quality = 90) {
        $info = pathinfo($file);

        $extension = strtolower($info['extension']);

        if (is_resource($this->image)) {
            if ($extension == 'jpeg' || $extension == 'jpg') {
                imagejpeg($this->image, $file, $quality);
            } elseif ($extension == 'png') {
                imagepng($this->image, $file);
            } elseif ($extension == 'gif') {
                imagegif($this->image, $file);
            }

            imagedestroy($this->image);
        }
    }

    /**
     * Resize
     * 
     * @access public
     * @param int $width
     * @param int $height
     * @param bool $crop_center
     * @return void
     */
    public function resize($width = 0, $height = 0, $crop_center = false) {
        if (!$this->info['width'] || !$this->info['height']) {
            return;
        }

        $xpos = 0;
        $ypos = 0;
        $scale = 1;

        if ($width == 0 && $height > 0) {
            $width = ($height * $this->info['width']) / $this->info['height'];
        } elseif ($width > 0 && $height == 0) {
            $height = ($width * $this->info['height']) / $this->info['width'];
        } elseif ($width == 0 && $height == 0) {
            return;
        }

        $scale_w = $width / $this->info['width'];
        $scale_h = $height / $this->info['height'];

        if ($crop_center) {
            $ratio_source = $this->info['width'] / $this->info['height'];
            $ratio_dest = $width / $height;

            if ($ratio_dest < $ratio_source) {
                $scale = $scale_h;
            } else {
                $scale = $scale_w;
            }
        } else {
            $scale = min($scale_w, $scale_h);
        }

        if ($scale == 1 && $scale_h == $scale_w && $this->info['mime'] != 'image/png') {
            return;
        }

        $new_width = (int) ($this->info['width'] * $scale);
        $new_height = (int) ($this->info['height'] * $scale);
        $xpos = (int) (($width - $new_width) / 2);
        $ypos = (int) (($height - $new_height) / 2);

        $image_old = $this->image;
        $this->image = imagecreatetruecolor($width, $height);

        if (isset($this->info['mime']) && $this->info['mime'] == 'image/png') {
            imagealphablending($this->image, false);
            imagesavealpha($this->image, true);
            $background = imagecolorallocatealpha($this->image, 255, 255, 255, 127);
            imagecolortransparent($this->image, $background);
        } else {
            $background = imagecolorallocate($this->image, 255, 255, 255);
        }

        imagefilledrectangle($this->image, 0, 0, $width, $height, $background);
        imagecopyresampled($this->image, $image_old, $xpos, $ypos, 0, 0, $new_width, $new_height, $this->info['width'], $this->info['height']);
        imagedestroy($image_old);

        $this->info['width'] = $width;
        $this->info['height'] = $height;
    }

}
