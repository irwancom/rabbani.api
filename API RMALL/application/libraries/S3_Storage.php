<?php

/**
 * CodeIgniter S3_Storage
 *
 * @package		CodeIgniter
 * @author		Adi Setiawan
 * @copyright	Copyright (c) 2020, Adi Setiawan
 * @since		Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class S3_Storage {

    const HOSTNAME = 'ewr1.vultrobjects.com';
    const ACCESS_KEY = '3A0AQ1V82IW7FW8EHAE9';
    const SECRET_KEY = '9sYDtLH7ufWmq1OYunV5c44CIPgPat7gRxTPMpFC';
    const DEFAULT_BUCKET = 'imgrmall';
    const USE_SUBDOMAIN = true;
    const DEFAULT_URL = 'https://' . self::HOSTNAME . '/';
    const ACL_PRIVATE = 'private';
    const ACL_PUBLIC_READ = 'public-read';
    const ACL_PUBLIC_READ_WRITE = 'public-read-write';
    const STORAGE_CLASS_STANDARD = 'STANDARD';
    const STORAGE_CLASS_RRS = 'REDUCED_REDUNDANCY';

    private static $_ci;

    /**
     * Constructor
     * 
     * @access public
     * @return void
     */
    public function __construct() {
        self::$_ci = & get_instance();
        self::$_ci->load->helper('file');
    }

    /**
     * List buckets
     * 
     * @access public
     * @static
     * @return array
     */
    public static function list_buckets() {
        $rest = new S3_Request('GET', '', '');
        $rest = $rest->get_response();

        if ($rest->error === false && $rest->code !== 200) {
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        }

        if ($rest->error !== false) {
            trigger_error(sprintf('S3_Storage::list_buckets(): [%s] %s', $rest->error['code'], $rest->error['message']), E_USER_WARNING);
            return false;
        }

        $results = array();
        $contents = simplexml_load_string($rest->body);

        if (isset($contents->Buckets)) {
            foreach ($contents->Buckets->Bucket as $bucket) {
                $results[] = array(
                    'name' => (string) $bucket->Name,
                    'time' => strtotime((string) $bucket->CreationDate)
                );
            }
        }

        return $results;
    }

    /**
     * Mengambil isi dari sebuah bucket. List file object dalam sebuah bucket.
     *
     * @param string $bucket	Opsional diisi nama bucket, jika dikosongi akan mengambil dari DEFAULT_BUCKET
     * @return array | false
     */
    public static function get_bucket($bucket = self::DEFAULT_BUCKET) {
        $rest = new S3_Request('GET', $bucket, '');
        $response = $rest->get_response();

        if ($response->error === false && $response->code !== 200) {
            $response->error = array('code' => $response->code, 'message' => 'Unexpected HTTP status');
        }

        if ($response->error !== false) {
            trigger_error(sprintf('S3_Storage::get_bucket(): [%s] %s', $response->error['code'], $response->error['message']), E_USER_WARNING);
            return false;
        }

        $results = array();
        $contents = simplexml_load_string($response->body);

        if (isset($contents->Contents)) {
            foreach ($contents->Contents as $c) {
                $results[] = array(
                    'name' => (string) $c->Key,
                    'time' => strToTime((string) $c->LastModified),
                    'size' => (int) $c->Size,
                    'hash' => substr((string) $c->ETag, 1, -1)
                );
            }
        }

        return $results;
    }

    /**
     * Create bucket
     *
     * @param string $bucket	Opsional diisi nama bucket, jika dikosongi akan mengambil dari DEFAULT_BUCKET
     * @param string $acl	Opsional (default: self::ACL_PUBLIC_READ). Bisa diisi dengan S3_Storage::ACL_PRIVATE, S3_Storage::ACL_PUBLIC_READ, atau S3_Storage::ACL_PUBLIC_READ_WRITE
     * @return boolean
     */
    public static function put_bucket($bucket = self::DEFAULT_BUCKET, $acl = self::ACL_PUBLIC_READ) {
        $rest = new S3_Request('PUT', $bucket, '');
        $rest->set_amz_header('x-amz-acl', $acl);
        $rest = $rest->get_response();

        if ($rest->error === false && $rest->code !== 200) {
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        }

        if ($rest->error !== false) {
            trigger_error(sprintf('S3_Storage::put_bucket(): [%s] %s', $rest->error['code'], $rest->error['message']), E_USER_WARNING);
            return false;
        }

        return true;
    }

    /**
     * Menghapus bucket. Fungsi ini seharusnya jarang sekali digunakan kecuali untuk menghapus seluruh isi bucket. Harap berhati-hati.
     *
     * @param string $bucket	Opsional diisi nama bucket, jika dikosongi akan mengambil dari DEFAULT_BUCKET
     * @return boolean
     */
    public static function delete_bucket($bucket = self::DEFAULT_BUCKET) {
        $rest = new S3_Request('DELETE', $bucket);
        $rest = $rest->get_response();

        if ($rest->error === false && $rest->code !== 204) {
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        }

        if ($rest->error !== false) {
            trigger_error(sprintf('S3_Storage::delete_bucket(): [%s] %s', $rest->error['code'], $rest->error['message']), E_USER_WARNING);
            return false;
        }

        return true;
    }

    /**
     * Upload file
     * 
     * @access public
     * @static
     * @param string $file			Local filename (fullpath)
     * @param string $uri			Object atau file path tanpa domain. Contoh: images/no_image.jpg
     * @param string $bucket		Opsional diisi nama bucket, jika dikosongi akan mengambil dari DEFAULT_BUCKET
     * @param string $acl			Opsional (default: self::ACL_PUBLIC_READ). Bisa diisi dengan S3_Storage::ACL_PRIVATE, S3_Storage::ACL_PUBLIC_READ, atau S3_Storage::ACL_PUBLIC_READ_WRITE
     * @param array $meta_headers	Opsional (default: array())
     * @param string $content_type	Opsional (default: null)
     * @return boolean
     */
    public static function put_object_file($file, $uri, $bucket = self::DEFAULT_BUCKET, $acl = self::ACL_PUBLIC_READ, $meta_headers = array(), $content_type = null) {
        return self::put_object(self::input_file($file), $uri, $bucket, $acl, $meta_headers, $content_type);
    }

    /**
     * Upload file object RAW
     * 
     * @access public
     * @param string $string		Content RAW dari file object yang akan diupload
     * @param string $uri			Object atau file path tanpa domain. Contoh: images/no_image.jpg
     * @param string $bucket		Opsional diisi nama bucket, jika dikosongi akan mengambil dari DEFAULT_BUCKET
     * @param string $acl			Opsional (default: self::ACL_PUBLIC_READ). Bisa diisi dengan S3_Storage::ACL_PRIVATE, S3_Storage::ACL_PUBLIC_READ, atau S3_Storage::ACL_PUBLIC_READ_WRITE
     * @param array $meta_headers	Opsional (default: array())
     * @param string $content_type	Opsional (default: 'text/plain'). Bisa diisi 'text/plain' untuk file berupa text, 'image/jpeg' atau 'image/png' untuk file berupa gambar. Silahkan merujuk pada file config/mimes.php untuk lebih detilnya
     * @return boolean
     */
    public static function put_object_string($string, $uri, $bucket = self::DEFAULT_BUCKET, $acl = self::ACL_PUBLIC_READ, $meta_headers = array(), $content_type = 'text/plain') {
        return self::put_object($string, $uri, $bucket, $acl, $meta_headers, $content_type);
    }

    /**
     * Mengambil detail file object
     *
     * @param string $uri	Object atau file path tanpa domain. Contoh: images/no_image.jpg
     * @param string $bucket	Opsional diisi nama bucket, jika dikosongi akan mengambil dari DEFAULT_BUCKET
     * @param mixed $save_to	Local file path yang akan digunakan untuk menyimpan kedalam server lokal. Jika dikosongi, maka akan menghasilkan array detil informasi dari file object
     * @return mixed
     */
    public static function get_object($uri = '', $bucket = self::DEFAULT_BUCKET, $save_to = false) {
        $rest = new S3_Request('GET', $bucket, $uri);

        if ($save_to !== false) {
            if (is_resource($save_to)) {
                $rest->fp = & $save_to;
            } else {
                if (($rest->fp = @fopen($save_to, 'wb')) == false) {
                    $rest->response->error = array(
                        'code' => 0,
                        'message' => 'Unable to open save file for writing: ' . $save_to
                    );
                }
            }
        }

        if ($rest->response->error === false) {
            $rest->get_response();
        }

        if ($rest->response->error === false && $rest->response->code !== 200) {
            $rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
        }

        if ($rest->response->error !== false) {
            trigger_error(sprintf('S3_Storage::get_object(): [%s] %s', $rest->response->error['code'], $rest->response->error['message']), E_USER_WARNING);
            return false;
        }

        $rest->file = realpath($save_to);

        return $rest->response;
    }

    /**
     * Menghapus file object
     *
     * @param string $uri	Object atau file path tanpa domain. Contoh: images/no_image.jpg
     * @param string $bucket	Opsional diisi nama bucket, jika dikosongi akan mengambil dari DEFAULT_BUCKET
     * @return boolean
     */
    public static function delete_object($uri = '', $bucket = self::DEFAULT_BUCKET) {
        $rest = new S3_Request('DELETE', $bucket, $uri);
        $rest = $rest->get_response();

        if ($rest->error === false && $rest->code !== 204) {
            $rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
        }

        if ($rest->error !== false) {
            trigger_error(sprintf('S3_Storage::delete_object(): [%s] %s', $rest->error['code'], $rest->error['message']), E_USER_WARNING);
            return false;
        }

        return true;
    }

    /**
     * Generate url dari uri dan nama bucket
     * 
     * @access public
     * @static
     * @param string $uri		Object atau file path tanpa domain. Contoh: images/no_image.jpg
     * @param string $bucket	Opsional diisi nama bucket, jika dikosongi akan mengambil dari DEFAULT_BUCKET
     * @return string
     */
    public static function get_url($uri, $bucket = self::DEFAULT_BUCKET) {
        if (self::USE_SUBDOMAIN) {
            return 'https://' . $bucket . '.' . self::HOSTNAME . '/' . $uri;
        } else {
            return 'https://' . self::HOSTNAME . '/' . $bucket . '/' . $uri;
        }
    }

    /**
     * Put an object
     *
     * @param mixed $input Input object
     * @param string $uri Object URI
     * @param string $bucket Bucket name
     * @param constant $acl ACL constant
     * @param array $meta_headers Array of x-amz-meta-* headers
     * @param string $content_type Content type
     * @return boolean
     */
    private static function put_object($input, $uri, $bucket = self::DEFAULT_BUCKET, $acl = self::ACL_PUBLIC_READ, $meta_headers = array(), $content_type = null) {
        $rest = new S3_Request('PUT', $bucket, $uri);

        if (is_string($input)) {
            $input = array(
                'data' => $input,
                'size' => strlen($input),
                'md5sum' => base64_encode(md5($input, true))
            );
        }

        if (isset($input['fp'])) {
            $rest->fp = & $input['fp'];
        } elseif (isset($input['file'])) {
            $rest->fp = @fopen($input['file'], 'rb');
        } elseif (isset($input['data'])) {
            $rest->data = $input['data'];
        }

        if (isset($input['size']) && $input['size'] > 0) {
            $rest->size = $input['size'];
        } else {
            if (isset($input['file'])) {
                $rest->size = filesize($input['file']);
            } elseif (isset($input['data'])) {
                $rest->size = strlen($input['data']);
            }
        }

        if ($content_type !== null) {
            $input['type'] = $content_type;
        } elseif (!isset($input['type']) && isset($input['file'])) {
            $input['type'] = get_mime_by_extension($input['file']);
        } else {
            $input['type'] = 'application/octet-stream';
        }

        if ($rest->size > 0 && ($rest->fp !== false || $rest->data !== false)) {
            $rest->set_header('Content-Type', $input['type']);

            if (isset($input['md5sum'])) {
                $rest->set_header('Content-MD5', $input['md5sum']);
            }

            $rest->set_amz_header('x-amz-acl', $acl);

            foreach ($meta_headers as $h => $v) {
                $rest->set_amz_header('x-amz-meta-' . $h, $v);
            }

            $rest->get_response();
        } else {
            $rest->response->error = array('code' => 0, 'message' => 'Missing input parameters');
        }

        if ($rest->response->error === false && $rest->response->code !== 200) {
            $rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
        }

        if ($rest->response->error !== false) {
            trigger_error(sprintf('S3_Storage::put_object(): [%s] %s', $rest->response->error['code'], $rest->response->error['message']), E_USER_WARNING);
            return false;
        }

        return true;
    }

    /**
     * Create input info array
     * 
     * @access private
     * @static
     * @param string $file Input file
     * @param mixed $md5sum Use MD5 hash (supply a string if you want to use your own)
     * @return array | false
     */
    private static function input_file($file, $md5sum = true) {
        if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
            trigger_error('S3_Storage::input_file(): Unable to open input file: ' . $file, E_USER_WARNING);
            return false;
        }

        return array(
            'file' => $file,
            'size' => filesize($file),
            'md5sum' => $md5sum !== false ? (is_string($md5sum) ? $md5sum : base64_encode(md5_file($file, true))) : ''
        );
    }

    /**
     * Generate the auth string
     *
     * This uses the hash extension if loaded
     *
     * @internal Signs the request
     * @param string $string String to sign
     * @return string
     */
    public static function get_signature($string) {
        return 'AWS ' . self::ACCESS_KEY . ':' . base64_encode(extension_loaded('hash') ? hash_hmac('sha1', $string, self::SECRET_KEY, true) : pack('H*', sha1((str_pad(self::SECRET_KEY, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) . pack('H*', sha1((str_pad(self::SECRET_KEY, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $string)))));
    }

}

/**
 * S3_Request class
 * Handle all requests for the S3 API
 * 
 * @final
 */
final class S3_Request {

    private $verb;
    private $bucket;
    private $uri = '';
    private $resource = '';
    private $parameters = [];
    private $amz_headers = [];
    private $headers = [
        'Host' => '',
        'Date' => '',
        'Content-MD5' => '',
        'Content-Type' => ''
    ];
    public $fp = false;
    public $size = 0;
    public $data = false;
    public $response;

    /**
     * Constructor
     *
     * @param string $verb Verb
     * @param string $bucket Bucket name
     * @param string $uri Object URI
     * @return mixed
     */
    public function __construct($verb, $bucket = '', $uri = '') {
        $this->verb = $verb;
        $this->bucket = strtolower($bucket);
        $this->uri = $uri !== '' ? '/' . $uri : '/';

        if ($this->bucket !== '') {
            $this->bucket = explode('/', $this->bucket);
            $this->resource = '/' . $this->bucket[0] . $this->uri;
            $this->headers['Host'] = $this->bucket[0] . '.' . S3_Storage::HOSTNAME;
            $this->bucket = implode('/', $this->bucket);
        } else {
            $this->headers['Host'] = S3_Storage::HOSTNAME;

            if (strlen($this->uri) > 1) {
                $this->resource = '/' . $this->bucket . $this->uri;
            } else {
                $this->resource = $this->uri;
            }
        }

        $this->headers['Date'] = gmdate('D, d M Y H:i:s T');
        $this->response = new STDClass;
        $this->response->error = false;
    }

    /**
     * Set request parameter
     *
     * @param string $key Key
     * @param string $value Value
     * @return void
     */
    public function set_parameter($key, $value) {
        $this->parameters[$key] = $value;
    }

    /**
     * Set request header
     *
     * @param string $key Key
     * @param string $value Value
     * @return void
     */
    public function set_header($key, $value) {
        $this->headers[$key] = $value;
    }

    /**
     * Set x-amz-meta-* header
     *
     * @param string $key Key
     * @param string $value Value
     * @return void
     */
    public function set_amz_header($key, $value) {
        $this->amz_headers[$key] = $value;
    }

    /**
     * Get the S3 response
     *
     * @return object | false
     */
    public function get_response() {
        $query = '';

        if (sizeof($this->parameters) > 0) {
            $query = substr($this->uri, -1) !== '?' ? '?' : '&';
            foreach ($this->parameters as $var => $value) {
                if ($value == null || $value == '') {
                    $query .= $var . '&';
                } else {
                    $query .= $var . '=' . $value . '&';
                }
            }

            $query = substr($query, 0, -1);
            $this->uri .= $query;

            if (isset($this->parameters['acl']) || !isset($this->parameters['logging'])) {
                $this->resource .= $query;
            }
        }

        $url = (extension_loaded('openssl') ? 'https://' : 'http://') . $this->headers['Host'] . $this->uri;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERAGENT, 'S3/php');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_URL, $url);

        $headers = array();
        $amz = array();

        foreach ($this->amz_headers as $header => $value) {
            if (strlen($value) > 0) {
                $headers[] = $header . ': ' . $value;
            }
        }

        foreach ($this->headers as $header => $value) {
            if (strlen($value) > 0) {
                $headers[] = $header . ': ' . $value;
            }
        }

        foreach ($this->amz_headers as $header => $value) {
            if (strlen($value) > 0) {
                $amz[] = strToLower($header) . ':' . $value;
            }
        }

        $amz = (sizeof($amz) > 0) ? "\n" . implode("\n", $amz) : '';

        $headers[] = 'Authorization: ' . S3_Storage::get_signature(
                        $this->verb . "\n" .
                        $this->headers['Content-MD5'] . "\n" .
                        $this->headers['Content-Type'] . "\n" .
                        $this->headers['Date'] . $amz . "\n" . $this->resource
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '_response_write_callback'));
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, '_response_header_callback'));

        switch ($this->verb) {
            case 'GET': break;
            case 'PUT':
                if ($this->fp !== false) {
                    curl_setopt($curl, CURLOPT_PUT, true);
                    curl_setopt($curl, CURLOPT_INFILE, $this->fp);

                    if ($this->size > 0) {
                        curl_setopt($curl, CURLOPT_INFILESIZE, $this->size);
                    }
                } elseif ($this->data !== false) {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);

                    if ($this->size > 0) {
                        curl_setopt($curl, CURLOPT_BUFFERSIZE, $this->size);
                    }
                } else {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                }
                break;
            case 'HEAD':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
                curl_setopt($curl, CURLOPT_NOBODY, true);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default: break;
        }

        if (curl_exec($curl)) {
            $this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } else {
            $this->response->error = array(
                'code' => curl_errno($curl),
                'message' => curl_error($curl),
                'resource' => $this->resource
            );
        }

        @curl_close($curl);

        if ($this->response->error === false && isset($this->response->headers['type']) && $this->response->headers['type'] == 'application/xml' && isset($this->response->body)) {
            $this->response->body = simplexml_load_string($this->response->body);

            if (!in_array($this->response->code, array(200, 204)) && isset($this->response->body->Code, $this->response->body->Message)) {
                $this->response->error = array(
                    'code' => (string) $this->response->body->Code,
                    'message' => (string) $this->response->body->Message
                );

                if (isset($this->response->body->Resource)) {
                    $this->response->error['resource'] = (string) $this->response->body->Resource;
                }

                unset($this->response->body);
            }
        }

        if ($this->fp !== false && is_resource($this->fp)) {
            fclose($this->fp);
        }

        return $this->response;
    }

    /**
     * CURL write callback
     *
     * @param resource &$curl CURL resource
     * @param string &$data Data
     * @return integer
     */
    private function _response_write_callback(&$curl, &$data) {
        $this->response->body = '';

        if ($this->response->code == 200 && $this->fp !== false) {
            return fwrite($this->fp, $data);
        } else {
            $this->response->body .= $data;
        }

        return strlen($data);
    }

    /**
     * CURL header callback
     *
     * @param resource &$curl CURL resource
     * @param string &$data Data
     * @return integer
     */
    private function _response_header_callback(&$curl, &$data) {
        if (($strlen = strlen($data)) <= 2) {
            return $strlen;
        }

        if (substr($data, 0, 4) == 'HTTP') {
            $this->response->code = (int) substr($data, 9, 3);
        } else {
            list($header, $value) = explode(': ', trim($data));

            if ($header == 'Last-Modified') {
                $this->response->headers['time'] = strtotime($value);
            } elseif ($header == 'Content-Length') {
                $this->response->headers['size'] = (int) $value;
            } elseif ($header == 'Content-Type') {
                $this->response->headers['type'] = $value;
            } elseif ($header == 'ETag') {
                $this->response->headers['hash'] = substr($value, 1, -1);
            } elseif (preg_match('/^x-amz-meta-.*$/', $header)) {
                $this->response->headers[$header] = is_numeric($value) ? (int) $value : $value;
            }
        }

        return $strlen;
    }

}
