<?php
require_once(__DIR__.'/digitalocean/spaces.php');
use \libphonenumber\PhoneNumberUtil;


if (!function_exists('failed_format')) {
    function failed_format($code, array $message = []) {
        return  [
            'status' => 'failed',
            'message' => $message,
            'code' => $code
        ];
    }
}


if (!function_exists('success_format')) {
    function success_format($data, $message = '', $total_item = 0, $total_page = 0, $meta = [], $include = []) {
        $response = [
            'code' => 200,
            'data' => $data
        ];
        
        if (strlen($message) > 0) {
            $response['message'] = $message;
        }

        if ($total_page > 0) {
            $response['total_item'] = $total_item;
            $response['total_page'] = $total_page;
        }

        return $response;
    }
}

if (!function_exists('generate_invoice_number')) {
    function generate_invoice_number() {
        $date = date('Ymd');
        $uniqid = uniqid();
        $uniq = substr(uniqid(), strlen($uniqid)-2, 2).date('His');
        return strtoupper($date.$uniq);
    }
}


if (!function_exists('upload_image')) {
    function upload_image($name, $resizePercentage = null) {
        $ci =& get_instance();
        $image_path = $ci->config->item('image_path');

        $ci =& get_instance();

        $config = [
            'upload_path' => $image_path,
            'allowed_types' => '*',
            'encrypt_name' => true
        ];

        $ci->load->library('upload', $config);
        
        if (!isset($_FILES[$name])) return false;
        if (empty($_FILES[$name])) return false;
        
        if (!is_dir($image_path))
            mkdir($image_path, 0777, true);
        
        if (!$ci->upload->do_upload($name)) throw new Exception($ci->upload->display_errors());

        $image = $ci->upload->data();
        $image['upload_path'] = $image_path;
        list($currentWidth, $currentHeight) = getimagesize($image['full_path']);
        $result = [
            'original_name' => $image['orig_name'],
            'file_name' => $image['file_name'],
            'file_ext' => $image['file_ext'],
            'width' => $currentWidth,
            'height' => $currentHeight
        ];

        $image['cloud_path'] = upload_to_cloud($image['full_path'], $image['file_name']);
        $result['cdn_url'] = $image['cloud_path']['cdn_url'];
        if (!empty($resizePercentage) && is_image(mime_content_type($image['full_path']))) {
            $manipulationWidth = $currentWidth*$resizePercentage/100;
            $manipulationHeight = $currentHeight*$resizePercentage/100;
            $config = [
                'image_library' => 'gd2',
                'source_image' => $image_path. '/' . $image['file_name'],
                'create_thumb' => false,
                'maintain_ratio' => true,
                'width' => $manipulationWidth,
                'height' => $manipulationHeight,
                'new_image' => $image_path .'/'. $image['raw_name'] .'-'. $resizePercentage.$image['file_ext'],
                // 'quality' => 70
            ];
            
            $ci->load->library('image_lib', $config);

            $result['cloud']['path'] = $image['cloud_path']['ObjectURL'];
            $result['cloud']['cdn_path'] = $image['cloud_path']['cdn_url'];
            if($ci->image_lib->resize()) {
                $ci->image_lib->clear();
                $image['full_path_resize'] = $config['new_image'];
                $config = [];
                if (in_array($image['image_type'], ['jpg', 'jpeg'])) {
                    $imgdata=exif_read_data($image['full_path'], 'IFD0');
                    $config = [
                        'image_library' => 'gd2',
                        'source_image' => $image['full_path_resize'],
                    ];
                    if (!empty($imgdata)) {
                        switch($imgdata['Orientation']) {
                            case 3:
                                $config['rotation_angle']='180';
                                break;
                            case 6:
                                $config['rotation_angle']='270';
                                break;
                            case 8:
                                $config['rotation_angle']='90';
                                break;
                        }  
                    }
                }

                $ci->image_lib->initialize($config);
                $ci->image_lib->rotate();

                $image['cloud_path_resize'] = upload_to_cloud($image['full_path_resize'], $image['raw_name'].'-'.$resizePercentage.$image['file_ext']);
                $result['resize'] = [
                    'original_name' => $image['orig_name'],
                    'file_name' => $image['raw_name'].'-'.$resizePercentage.$image['file_ext'],
                    'file_ext' => $image['file_ext'],
                    'width' => $manipulationWidth,
                    'height' => $manipulationHeight,
                    'cloud' => [
                        'path' => $image['cloud_path_resize']['ObjectURL'],
                        'cdn_path' => $image['cloud_path_resize']['cdn_url']
                    ]
                ];
                unlink($image['full_path_resize']);
            }
        }
        unlink($image['full_path']);

        return $result;
    }

    function upload_to_cloud ($path, $filename) {
        $cdnLink = 'https://file.1itmedia.co.id';
        $key = "PCN6LIHBK6AEYBHC47JE";
        $secret = "cI00RyzhK9tUe7HxhkooTfUuAOpsw7Lk8q+rSWW6pHk";
        $space_name = "sim";
        $region = "sgp1";
        $space = new SpacesConnect($key, $secret, $space_name, $region);

        $mime = mime_content_type($path);
        $allowed_mime = [
            'image/jpeg',
            'image/png',
            'image/svg+xml',
            'image/webp',
            'video/x-msvideo',
            'video/mpeg',
            'video/ogg',
            'video/webm',
            'video/3gpp',
            'video/3gpp2'
        ];
        if (!in_array($mime, $allowed_mime)) {
            $mime = 'application/octet-stream';
        }
        $result = $space->UploadFile($path, "public", $filename, mime_content_type($path));
        $result['cdn_url'] = sprintf('%s/%s', $cdnLink, $filename);
        return $result;
    }

    function delete_from_cloud ($path, $config = 'old') {
        $key = "PCN6LIHBK6AEYBHC47JE";
        $secret = "cI00RyzhK9tUe7HxhkooTfUuAOpsw7Lk8q+rSWW6pHk";
        $space_name = "sim-cdn";
        $region = "nyc3";
        if ($config == 'new') {
            $key = "PCN6LIHBK6AEYBHC47JE";
            $secret = "cI00RyzhK9tUe7HxhkooTfUuAOpsw7Lk8q+rSWW6pHk";
            $space_name = "sim";
            $region = "sgp1";
        }
        $space = new SpacesConnect($key, $secret, $space_name, $region);

        $result = $space->DeleteObject($path);
        return $result;
    }

    function is_image($mime) {
        $allowedMimeImages = [
            'image/jpeg',
            'image/png',
            'image/svg+xml',
            'image/webp'
        ];

        return in_array($mime, $allowedMimeImages);
    }
}


if (!function_exists('static_url')) {
    function static_url($image) {
        $ci = & get_instance();
        $staticUrl = $ci->config->item('static_url');
        return $staticUrl . '/' . $image;
    }
}


if (!function_exists('dd')) {
    function dd($data, $verbose = false) {
        echo '<pre>';
        if ($verbose) {
            var_dump($data);
            die;
        } 

        print_r($data);
        die;
    }
}

if (!function_exists('generateRandomString')) {
    function generateRandomString ($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $randomString = ''; 
      
        for ($i = 0; $i < $length; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
      
        return $randomString; 
    }
}

if (!function_exists('generateRandomDigit')) {
    function generateRandomDigit ($length) {
        $characters = '0123456789'; 
        $randomString = ''; 
      
        for ($i = 0; $i < $length; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
      
        return $randomString; 
    }
}


function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function stringToBool ($value) {
    if ($value == 'true') {
        return true;
    } else {
        return false;
    }
}

function generateRandomString($length = 10, $type = 'default') {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if ($type == 'alphanumeric_uppercase') {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function uploadLocal ($name) {
    $ci =& get_instance();
    $filePath = $ci->config->item('image_path');

    $ci =& get_instance();

    $config = [
        'upload_path' => $filePath,
        'allowed_types' => '*',
        'encrypt_name' => true
    ];

    $ci->load->library('upload', $config);
    
    if (!isset($_FILES[$name])) return false;
    if (empty($_FILES[$name])) return false;
    
    if (!is_dir($filePath))
        mkdir($filePath, 0777, true);
    
    if (!$ci->upload->do_upload($name)) throw new Exception($ci->upload->display_errors());

    $file = $ci->upload->data();
    return $file;
}

function toRupiahFormat ($amount) {
    $prefix = '';
    if ($amount < 0) {
        $prefix = '-';
        $amount *= -1;
    }
    return sprintf('%sRp %s', $prefix, number_format($amount, 0, ',', '.'));
}

function age ($birthdate) {
    $currentDate = date("d-m-Y");
    $age = date_diff(date_create($birthdate), date_create($currentDate));
    return (int)$age->format("%y");
}

function dateDiff ($startDate, $endDate, $type = null) {
    $date1 = new DateTime($startDate);
    $date2 = new DateTime($endDate);
    $interval = $date1->diff($date2);
    if ($type == 'second') {
        $calc = ($interval->d * 24 * 60 * 60) + ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;
        return $calc;
    }
    return $interval;
}

function validateLatLong($lat, $long) {
  return preg_match('/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?),[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $lat.','.$long);
}

function getFormattedPhoneNumber ($phoneNumber) {
    try {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneNumberUtil->parse($phoneNumber, "ID");
        return '62'.$phoneNumber->getNationalNumber();
    } catch (\Exception $e) {
        return false;
    }
}

function slugify ($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}

function getExcelColumns () {
    return [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ',
        'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
        'DA', 'DB', 'DC', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ',
        'EA', 'EB', 'EC', 'ED', 'EE', 'EF', 'EG', 'EH', 'EI', 'EJ', 'EK', 'EL', 'EM', 'EN', 'EO', 'EP', 'EQ', 'ER', 'ES', 'ET', 'EU', 'EV', 'EW', 'EX', 'EY', 'EZ',
        'FA', 'FB', 'FC', 'FD', 'FE', 'FF', 'FG', 'FH', 'FI', 'FJ', 'FK', 'FL', 'FM', 'FN', 'FO', 'FP', 'FQ', 'FR', 'FS', 'FT', 'FU', 'FV', 'FW', 'FX', 'FY', 'FZ',
        'GA', 'GB', 'GC', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GJ', 'GK', 'GL', 'GM', 'GN', 'GO', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GV', 'GW', 'GX', 'GY', 'GZ',
        'HA', 'HB', 'HC', 'HD', 'HE', 'HF', 'HG', 'HH', 'HI', 'HJ', 'HK', 'HL', 'HM', 'HN', 'HO', 'HP', 'HQ', 'HR', 'HS', 'HT', 'HU', 'HV', 'HW', 'HX', 'HY', 'HZ',
        'IA', 'IB', 'IC', 'ID', 'IE', 'IF', 'IG', 'IH', 'II', 'IJ', 'IK', 'IL', 'IM', 'IN', 'IO', 'IP', 'IQ', 'IR', 'IS', 'IT', 'IU', 'IV', 'IW', 'IX', 'IY', 'IZ',
        'JA', 'JB', 'JC', 'JD', 'JE', 'JF', 'JG', 'JH', 'JI', 'JJ', 'JK', 'JL', 'JM', 'JN', 'JO', 'JP', 'JQ', 'JR', 'JS', 'JT', 'JU', 'JV', 'JW', 'JX', 'JY', 'JZ',
        'KA', 'KB', 'KC', 'KD', 'KE', 'KF', 'KG', 'KH', 'KI', 'KJ', 'KK', 'KL', 'KM', 'KN', 'KO', 'KP', 'KQ', 'KR', 'KS', 'KT', 'KU', 'KV', 'KW', 'KX', 'KY', 'KZ',
        'LA', 'LB', 'LC', 'LD', 'LE', 'LF', 'LG', 'LH', 'LI', 'LJ', 'LK', 'LL', 'LM', 'LN', 'LO', 'LP', 'LQ', 'LR', 'LS', 'LT', 'LU', 'LV', 'LW', 'LX', 'LY', 'LZ',
        'MA', 'MB', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MI', 'MJ', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
        'NA', 'NB', 'NC', 'ND', 'NE', 'NF', 'NG', 'NH', 'NI', 'NJ', 'NK', 'NL', 'NM', 'NN', 'NO', 'NP', 'NQ', 'NR', 'NS', 'NT', 'NU', 'NV', 'NW', 'NX', 'NY', 'NZ',
    ];
}
function convertDateFromTimezone ($date) {
    if (empty($date)) {
        return null;
    }
    $date = strtotime($date);
    $newFormat = date('Y-m-d H:i:s', $date);
    return $newFormat;
}