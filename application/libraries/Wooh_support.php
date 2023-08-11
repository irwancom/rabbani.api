<?php
defined('BASEPATH') OR exit('No direct script access allowed');
Class Wooh_support{
    public function __construct(){ $this->wooh =& get_instance(); }

    public function tripayConfig ($option = []) {
        $config = array();
        $config['env'] = (isset($option['env']) && !empty($option['env'])) ? $option['env'] : 'production';
        $config['code'] = (isset($option['code']) && !empty($option['code'])) ? $option['code'] : 'T13840';
        $config['key'] = (isset($option['key']) && !empty($option['key'])) ? $option['key'] : 'XW1h01alwrID3xpwcqV2jIa9lUFxV9o89fQMwso2';
        $config['secret'] = (isset($option['secret']) && !empty($option['secret'])) ? $option['secret'] : 'CNvlC-sMN5n-14mnA-EtIq5-pC7Z8';
        return $config;
    }

    public function wablasConfig ($option = []) {
        $config = array();
        $config['domain'] = (isset($option['domain']) && !empty($option['domain'])) ? $option['domain'] : 'https://solo.wablas.com';
        $config['token'] = (isset($option['token']) && !empty($option['token'])) ? $option['token'] : 'CZrRIT5qo1GNYdiFXySxc0oW4oINZ5WZmLi40HlHHAushg4S1GlSfnSTHQfJEQgs';
        return $config;
    }

    public function mailConfig ($option = []) {
        $config = array();
        $config['from'] = (isset($option['from']) && !empty($option['from'])) ? $option['from'] : 'Admin <no-reply@1itmedia.co.id>';
        $config['domain'] = (isset($option['domain']) && !empty($option['domain'])) ? $option['domain'] : 'mg.1itmedia.co.id';
        $config['key'] = (isset($option['key']) && !empty($option['key'])) ? $option['key'] : 'key-0d89204653627cc8cbba67684cfff390';
        return $config;
    }

    public function driveConfig ($option = []) {
        $config = array();
        $config['link'] = (isset($option['link']) && !empty($option['link'])) ? $option['link'] : 'https://file.1itmedia.co.id';
        $config['key'] = (isset($option['key']) && !empty($option['key'])) ? $option['key'] : 'PCN6LIHBK6AEYBHC47JE';
        $config['secret'] = (isset($option['secret']) && !empty($option['secret'])) ? $option['secret'] : 'cI00RyzhK9tUe7HxhkooTfUuAOpsw7Lk8q+rSWW6pHk';
        $config['space'] = (isset($option['space']) && !empty($option['space'])) ? $option['space'] : 'sim';
        $config['region'] = (isset($option['region']) && !empty($option['region'])) ? $option['region'] : 'sgp1';
        return $config;
    }

    public function uploadConfig ($filePath = '', $name = '') {
        if(!$name || empty($name) || is_null($name)){
            return false;
        }
        $filePath = ($filePath && !empty($filePath) && !is_null($filePath)) ? $filePath : 'files';
        $filePath = 'upload/'.$filePath;
        $config = [
            'upload_path' => $filePath,
            'allowed_types' => '*',
            'encrypt_name' => true
        ];
        $this->wooh->load->library('upload', $config);
        if (!is_dir($filePath))
            mkdir($filePath, 0777, true);
        if (!$this->wooh->upload->do_upload($name)) return 'gagal';
        return $this->wooh->upload->data();
    }

    public function printData($data){ return '<pre>' . var_export($data, true) . '</pre>'; }
    public function qrCode($data){ return "https://chart.googleapis.com/chart?cht=qr&chl=".$data."&chs=300x300&chld=H|0"; }
    public function emptyData(){ return '<span class="badge badge-light badge-dim">Data not found</span>'; }
    public function resData($type, $msg = '', $data = [], $pager = [], $params = [], $multi = false) {
        $isResData = $this->statusResData($type);
        $isResData['msg'] = $msg;
        if($data && !is_null($data)){
            if(!$multi){
                $isResData['data'] = $data;
            }else{
                $isResData['data'] = array();
                $isResData['data']['result'] = $data;
                if($pager && !is_null($pager)){
                    foreach($pager as $k_pg=>$pg){ $isResData['data'][$k_pg] = $pg; }
                }
            }
        }
        if($params && !is_null($params)) $isResData['params'] = $params;
        return $this->resultData($isResData);
    }
    public function resultData($data) { return $this->wooh->output->set_content_type('application/json')->set_output(json_encode($data)); }
    public function statusResData($type = ''){
        $codeRes = array();
        $codeRes['success'] = 200;
        $codeRes['empty'] = 204;
        $codeRes['bad'] = 400;
        $codeRes['not_access'] = 401;
        $codeRes['forbiden'] = 403;
        $codeRes['not_found'] = 404;
        $codeRes['not_allowed'] = 405;
        $codeRes['not_support'] = 415;
        $codeRes['failed'] = 500;
        $codeRes['other'] = 100;
        
        $readyCode = (isset($codeRes[$type]) && $codeRes[$type]) ? true : false;
        $statusCode = ($readyCode) ? (($type=='success') ? true : false) : true;
        $thisCode = ($readyCode) ? $codeRes[$type] : $codeRes['other'];
        return array('code'=>$thisCode, 'status'=>$type, 'success'=>$statusCode);
    }
    public function pagerData($data = [], $count = 0, $search = [], $attribute = []){
        $countData = intval($count); $countPage = 1;
        $currentPage = (isset($data['page']) && $data['page'] && !empty($data['page']) && $this->isNomor($data['page']))?intval($data['page']):1;
        $perPage = (isset($data['limit']) && $data['limit'] && !empty($data['limit']) && $this->isNomor($data['limit']))?intval($data['limit']):20;
        if($countData>$perPage) $countPage = ceil($countData/$perPage);
        $lastData = ($currentPage*$perPage) - $perPage; $offset = $lastData;
        if($countData<=$perPage) $offset = 0;
        $contentPager = array('limit'=>$perPage,'total_data'=>$countData,'last_data'=>$lastData,'max_page'=>$countPage,'current_page'=>$currentPage);
        $sortBy = (isset($data['sort_by']) && in_array($data['sort_by'], $search)) ? $data['sort_by'] : ((isset($attribute['sort']))?$attribute['sort']:'created');
        $sortValue = (isset($data['sort_value']) && ($data['sort_value']=='ASC' || $data['sort_value']=='DESC')) ? $data['sort_value'] : 'DESC';
        return ['data'=>$contentPager,'offset'=>$offset,'sort_by'=>$sortBy,'sort_value'=>$sortValue];
    }
    public function validationNeedData($readyData = [], $needData = []){
        foreach($needData as $data){
            if(!isset($readyData[$data]) || !$readyData[$data] || empty($readyData[$data]) || is_null($readyData[$data]) || strlen($readyData[$data])==0){
                return array('success'=>false, 'data'=>$data, 'msg'=>$data.' is required'); die;
            }
        }
        return array('success'=>true, 'msg'=>'Data validated'); die;
    }
    
//**======================== StringNumber Manage ===========================**/
    public function stringToSlug($string) {
       $string = str_replace(' ', '-', $string);
       $string = preg_replace('/[^A-Za-z0-9\-]/', '-', $string);
       return strtolower($string);
    }
    public function randomString($length, $type=''){
        $data = 'ABCDEFGHQRSTU1234567890VWXYZ0123456789IJKLMNOP';
        if($type=='number'){ $data = '1234567890'; }else if($type=='text'){ $data = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; }
        $string = ''; for($i = 0; $i < $length; $i++) { $pos = rand(0, strlen($data)-1); $string .= $data[$pos]; } return $string;
    }
    public function isBase64($data){
        return (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) ? true : false;
    }
    public function isNomor($data){
        return (preg_match('/^[0-9]+$/i', $data)) ? true : false;
    }
    public function validSlug($data){
        return (preg_match('/^[A-Za-z-]+$/i', $data)) ? true : false;
    }
    public function aliasName($string){
        $setString = explode(" ", $string); $alias = substr($string, 0,2);
        if(count($setString)>1){ $alias = ''; foreach($setString as $str){ $al = substr($str, 0,1); $alias = $alias.$al; } }
        return $alias;
    }
    public function getPassword($password, $type=false, $confirm=''){
        return ($type) ? password_verify($password, $confirm) : password_hash($password, PASSWORD_DEFAULT);
    }
    public function codeData($awal='', $length=3){
        $data = 'ABCDEFGHIJKLMNOPQRSTU1234567890VWXYZ'; $random = '';
        for($i = 0; $i < $length; $i++) { $pos = rand(0, strlen($data)-1); $random .= $data[$pos]; }
        $no_acak = rand(100,10000); $cak = rand(0,9); $cak_no=$no_acak + $cak; $_code =  sprintf("%03s", $cak_no);
        return $awal.'-'.$_code.$random.date('dmyhis');
    }
    public function secretData($codeData){
        $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $string = '';
        for($i = 0; $i < 5; $i++) { $pos = rand(0, strlen($data)-1); $string .= $data[$pos]; }
        $rand_string = $string; $awal = 'MySecret'; $no_acak = rand(100,10000); $cak = rand(0,9); $cak_no=$no_acak + $cak;
        $_code =  sprintf("%010s", $cak_no); $code = $awal.$_code.$rand_string; $done_code = $code.date('dmyHis');
        $this_set_code = $done_code.'_'.$codeData; return base64_encode($this_set_code);
    }
    public function generateSecret($code){
        return hash('sha256', base64_encode($code));
    }
    public function textToCapital($str=''){
        $strings = explode('.', $str); $titleCased = []; foreach($strings as $s){ $titleCased[] = ucfirst(trim($s)); } return join(".", $titleCased);
    }
    public function valToString($nilai) {
        $nilai = abs($nilai); $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " ". $huruf[$nilai];
        } else if ($nilai <20) {
            $temp = $this->valToString($nilai - 10). " belas";
        } else if ($nilai < 100) {
            $temp = $this->valToString($nilai/10)." puluh". $this->valToString($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " seratus" . $this->valToString($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = $this->valToString($nilai/100) . " ratus" . $this->valToString($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " seribu" . $this->valToString($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = $this->valToString($nilai/1000) . " ribu" . $this->valToString($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = $this->valToString($nilai/1000000) . " juta" . $this->valToString($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = $this->valToString($nilai/1000000000) . " milyar" . $this->valToString(fmod($nilai,1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = $this->valToString($nilai/1000000000000) . " trilyun" . $this->valToString(fmod($nilai,1000000000000));
        }     
        return $temp;
    }
    public function linkFromString($string) {
        $reg_pattern = "/(((http|https|ftp|ftps)\:\/\/)|(www\.))[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\:[0-9]+)?(\/\S*)?/";
        return preg_replace($reg_pattern, '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>', $string);
    }

//**======================== Phone Manage ===========================**/
    public function phoneID($nomor){
        $long_number = strlen($nomor); $first = substr($nomor, 0, 1); $second = substr($nomor, 0, 2); $three = substr($nomor, 0, 3);
        if($three=='+62'){
            $nomor = '0'.substr($nomor, 3, $long_number);
        }else if($second=='62'){
            $nomor = '0'.substr($nomor, 2, $long_number);
        }else if($first!='0'){
            $nomor = '0'.$nomor;
        }
        return $nomor;
    }
    public function whatsappNomorID($nomor, $isCode = '62'){
        $setCode = (!$isCode || empty($isCode) || is_null($isCode)) ? '62' : $isCode;
        $long_number = strlen($nomor); $first = substr($nomor, 0, 1); $this_nomor = $nomor;
        if($first==0){ $this_nomor = $setCode.substr($nomor, 1, $long_number); }else if($first=='+'){ $this_nomor = substr($nomor, 1, $long_number); }
        $second = substr($this_nomor, 0, 2); if($second!=$setCode){ $this_nomor = $setCode.substr($this_nomor, 2, $long_number); }
        return $this_nomor;
    }
    public function linkWhatsapp($phone, $text){
        $set_wa = '';
        if($phone && !empty($phone) && !is_null($phone) && strlen($phone)>0){
            $first_number = substr($phone, 0, 1); $two_number = substr($phone, 0, 2); $count_number = strlen($phone);
            $next_number = substr($phone, 1, $count_number);
            if($first_number==0){ $set_number = '62'.$next_number; }else{ $set_number = '62'.$phone; if($two_number=='62'){ $set_number = $phone;} }
            $set_wa = 'https://api.whatsapp.com/send?phone='.$set_number.'&text='.$text;
        }
        return $set_wa;
    }
    public function linkShare($url){
        $share['wa'] = 'https://api.whatsapp.com/send?phone=&text='.$url;
        $share['fb'] = 'https://www.facebook.com/sharer/sharer.php?u='.$url;
        $share['ig'] = 'https://www.instagram.com/?url='.$url;
        return $share;
    }

//**======================== Notif Manage ===========================**/
    public function notifNoData($data) { return 'Data '.$data.' not found or not ready'; }
    public function notifNotFoundData($data) { return 'Data '.$data.' not found or no longer available'; }
    public function notifUseData($type) { return 'Data '.$type.' already used. Use '.$type.' other'; }
    public function notifMsg($type) {
        if($type=='failed' || $type=='error'){
            $msg = 'Failed to process data. Refresh the page or try again';
        }else if($type=='failed-login'){
            $msg = 'Failed to login, check again username and password';
        }else if($type=='success-login'){
            $msg = 'Login successfully';
        }else if($type=='success-register'){
            $msg = 'Register successfully';
        }else if($type=='success-add'){
            $msg = 'The new data is successfully saved';
        }else if($type=='error-add'){
            $msg = 'Failed to save data. Make sure the data is correct or Refresh the page and try again';
        }else if($type=='success-edit'){
            $msg = 'Data updated successfully';
        }else if($type=='error-edit'){
            $msg = 'Failed to update data. Make sure the data is correct or Refresh the page and try again';
        }else if($type=='success-remove'){
            $msg = 'Data deleted successfully';
        }else if($type=='error-remove'){
            $msg = 'Failed to delete the data';
        }else if($type=='success-status'){
            $msg = 'Data status updated successfully';
        }else if($type=='error-status'){
            $msg = 'Failed to update data state';
        }else if($type=='no-data'){
            $msg = 'Data not found or no data to execute';
        }else if($type=='no-access'){
            $msg = 'There is no access to manage the data';
        }else if($type=='no-active'){
            $msg = 'Data or account is inactive (suspend). Reload page or login again';
        }else if($type=='no-complete'){
            $msg = 'Complete and re-check the data';
        }else if($type=='no-auth'){
            $msg = 'No access. Reload the page or re-login';
        }
        return $msg;
    }
    public function validationData($type, $data) {
        $status = true; $resMsg = 'Format '.$type.' sesuai.';
        if($type=='name'){
            if(strlen($data)<3 || !preg_match('/^[A-Za-z ]+$/i', $data)){ $status = false; $resMsg = 'Name can only be letters (Min. 3 letters)'; }
        }else if($type=='phone'){
            if(strlen($data)<9 || strlen($data)>14 || !preg_match('/^[0-9]+$/i', $data)){
                $status = false; $resMsg = 'Phone can only be 9 to 14 digits long';
            }
        }else if($type=='wa'){
            if(strlen($data)<9 || strlen($data)>15 || !preg_match('/^[0-9]+$/i', $data)){ $status = false; $resMsg = 'WhatsApp number can only be numbers (9-15 digits)'; }
        }else if($type=='email'){
            if(!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i', $data)){ $status = false; $resMsg = 'Incorrect email format (example@gmail.com)'; }
        }else if($type=='nik'){
            if(!empty($data)){
                if(strlen($data)<16 || strlen($data)>16 || !preg_match('/^[0-9]+$/i', $data)){ $status = false; $resMsg = 'NIK can only be numbers (16 digits)'; }
            }
        }else if($type=='gender'){
            if($data!='laki-laki' && $data!='perempuan'){ $status = false; $resMsg = 'Gender format does not match (L/F)'; }
        }else if($type=='url'){
            if (!filter_var($data, FILTER_VALIDATE_URL)) {
                $status = false; $resMsg = 'Incorrect url format (ex. http//domain.com, https://domain.com, http://www.domain.com, https://www.domain.com)';
            }
        }
        return array('success'=>$status,'msg'=>$resMsg);
    }

//**======================== Directory Manage ===========================**/
    public function createFolder($place){ if(!file_exists($place)){ mkdir($place, 0755, true); } }
    public function createDirectory($place){
        $root = str_replace('index.php','',$_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']); $place = $root.$place;
        if(!file_exists($place)){ mkdir($place, 0755, true); } return true;
    }
    public function delFileDirectory($place){
        $root = str_replace('index.php','',$_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']);
        $isFile = $root.$place; if(file_exists($isFile)){ unlink($isFile); } return true;
    }
    public function existDirectory($place){
        $root = str_replace('index.php','',$_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']); $isFile = $root.$place;
        if(file_exists($isFile)){  return true; }else{ return false; }
    }
    public function copyFile($place, $from, $to){ if(!file_exists($place)){ mkdir($place, 0755, true);} copy($from,$to); }
    public function uploadFile($source, $path, $tmp, $name){
        $root = str_replace('index.php','',$_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']);
        $place = $root.$source.'/'.$path; $resData = false;
        if(!file_exists($place)){ mkdir($place, 0755, true);} $pathFile= $place.'/'.$name;
        $upload=move_uploaded_file($tmp, $pathFile);
        if($upload){
            $resData = array('source'=>$source,'path'=>$path,'name'=>$name);
        }
        return $resData;
    }
    public function readyFileUpload($isFile){
        $resFile = false;
        if($isFile && !empty($isFile) && !is_null($isFile) && is_array($isFile)){
            if(isset($isFile['tmp_name']) && $isFile['tmp_name'] && !empty($isFile['tmp_name']) && !is_null($isFile['tmp_name'])){
                $resFile = true;
            }
        }
        return $resFile;
    }
    public function typeFile($ext){
        $resData = false;
        $images = array("webp","png","jpg","jpeg","gif","wemp","jfif");
        $videos = array("webm","mp4","ogv");
        $audios = array("mp3","mpeg");
        $documents = array("txt","pdf","docx","xlsx");
        if(in_array($ext, $images)){
            $resData = array('type'=>'image', 'ext'=>'webp');
        }else if(in_array($ext, $videos)){
            $resData = array('type'=>'video', 'ext'=>'mp4');
        }else if(in_array($ext, $audios)){
            $resData = array('type'=>'audio', 'ext'=>'mp3');
        }else if(in_array($ext, $documents)){
            $resData = array('type'=>'document', 'ext'=>$ext);
        }
        return $resData;
    }
    public function validationUpload($isData = '', $isFile = [], $isOnly = []){
        $extFile = pathinfo($isFile['name'], PATHINFO_EXTENSION);
        $isTypeFile = $this->typeFile($extFile);
        if(!$isTypeFile || is_null($isTypeFile)){
            return array('success'=>false, 'msg'=>'The '.$isData.' file type is not supported'); die;
        }
        if($isOnly && !is_null($isOnly) && !in_array($isTypeFile['type'], $isOnly)){
            $isSupportFile = implode(',', $isOnly);
            return array('success'=>false, 'msg'=>$isData.' file types are not supported, only ('.$isSupportFile.') files are allowed'); die;
        }
        $isformatsize = $this->formatSizeFile($isFile['size'], true);
        if(!$isformatsize['success']){
            return array('success'=>false, 'msg'=>$isData.' file size is not supported, max '.$isformatsize['max'].$isformatsize['type']); die;
        }
        return array('success'=>true, 'msg'=>'File validated', 'detail'=>$isTypeFile);
    }

//**======================== Date Manage ===========================**/
    public function nameTime(){
        $time = date('H');
        if($time>=5 && $time<11){ $name_time = 'Pagi'; }else if($time>=11 && $time<15){ $name_time = 'Siang'; }else if($time>=15 && $time<19){ $name_time = 'Sore'; }else if($time>=19){ $name_time = 'Malam'; } return $name_time;
    }
    public function nameDay($number){
        $name_day = 'Menyesuaikan'; 
        if($number==1){ $name_day = 'Senin'; }else if($number==2){ $name_day = 'Selasa'; }else if($number==3){ $name_day = 'Rabu'; }else if($number==4){ $name_day = 'Kamis'; }else if($number==5){ $name_day = 'Jumat'; }else if($number==6){ $name_day = 'Sabtu'; }else if($number==7){ $name_day = 'Minggu'; } return $name_day;
    }
    public function nameMonth($number, $type=''){
        $name_month = 'Menyesuaikan'; $name_romawi = '-';
        if($number==1){
            $name_month = 'Januari'; $name_romawi = 'I';
        }else if($number==2){
            $name_month = 'Februari'; $name_romawi = 'II';
        }else if($number==3){
            $name_month = 'Maret'; $name_romawi = 'III';
        }else if($number==4){
            $name_month = 'April'; $name_romawi = 'IV';
        }else if($number==5){
            $name_month = 'Mei'; $name_romawi = 'V';
        }else if($number==6){
            $name_month = 'Juni'; $name_romawi = 'VI';
        }else if($number==7){
            $name_month = 'Juli'; $name_romawi = 'VII';
        }else if($number==8){
            $name_month = 'Agustus'; $name_romawi = 'VIII';
        }else if($number==9){
            $name_month = 'September'; $name_romawi = 'IX';
        }else if($number==10){
            $name_month = 'Oktober'; $name_romawi = 'X';
        }else if($number==11){
            $name_month = 'November'; $name_romawi = 'XI';
        }else if($number==12){
            $name_month = 'Desember'; $name_romawi = 'XII';
        }
        $forMonth = $name_month; if($type=='romawi'){ $forMonth = $name_romawi; } return $forMonth;
    }
    public function monthList(){ return array('Januari','Februari','Maret','April','Mei','Juni','Juli ','August','September','Oktober','November','Desember'); }
    public function yearList($from, $to){
        $selisih = $to-$from; $tahuns = array(); $no_tahun = 0;
        for($x=$from;$x<=$to;$x++){ $tahuns[$no_tahun] = $x; $no_tahun++; } return $tahuns;
    }
    public function timeAbsolute() {
        $times = array('00:00','00:30','01:00','01:30','02:00','02:30','03:00','03:30','04:00','04:30','05:00','05:30','06:00','06:30','07:00','07:30','08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30','20:00','20:30','21:00','21:30','22:00','22:30','23:00','23:30','24:00','24:30');
        return $times;
    }
    public function dayList() {
        $results = array();
        for($d=0; $d<7; $d++){
            $results[$d]['no'] = $d+1;
            $results[$d]['name'] = $this->nameDay($d+1);
        }
        return $results;
    }
    public function validationDate($date, $format = 'Y-m-d H:i:s'){
        $d = date_create_from_format($format, $date);
        return $d && $d->format($format) == $date;
    }
    public function listDateRange($first, $last, $count = '+1', $step = 'day', $output_format = 'Y-m-d' ) {
        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);
        while( $current <= $last ) {
            $dates[] = date($output_format, $current);
            $current = strtotime($count.' '.$step, $current);
        }
        return $dates;
    }
    public function listTimeRange($first, $last, $count = '+1', $step = 'minutes', $output_format = 'H:i' ) {
        $times = array();
        $current = strtotime($first);
        $last = strtotime($last);
        while( $current <= $last ) {
            $times[] = date($output_format, $current);
            $current = strtotime($count.' '.$step, $current);
        }
        return $times;
    }

//**======================== Media Manage ===========================**/

    public function displayMedia($type, $data=[]){
        $url_file = base_url($data['source'].'/'.$data['path'].'/'.$data['name']);
        $showFile = false;
        if($type=='image'){
            $showFile = '<div style="height: 150px;overflow: hidden;width: 150px;" class="row no-margin align-items-center justify-content-center"><img style="width: auto;height: auto;max-height: 100%;max-width: 100%;" class="'.$type.'" src="'.$url_file.'"></div>';
        }else if($type=='video'){
            $showFile = '<video style="width:auto;height:150px;" class="'.$type.'" controls><source src="'.$url_file.'" type="video/mp4"></video>';
        }else if($type=='audio'){
            $showFile = '<audio style="height:35px;" class="'.$type.'" controls><source src="'.$url_file.'" type="audio/mp3"></audio>';
        }else if($type=='document'){
            $showFile = '<a href="'.$url_file.'" target="_blank" class="btn btn-xs btn-secondary"><em class="icon ni ni-card-view"></em><span>'.$data['name'].'</span></a>';
        }
        return ['url'=>$url_file,'embed'=>$showFile];
    }

    public function formatSizeFile($bytes, $confirm  = false){
        $resByte = 0; $typeSize = ''; $approvedSize = true; $minSize = 0; $maxSize = 0;
        if ($bytes >= 1073741824){
            $resByte = number_format($bytes / 1073741824, 2); $typeSize = 'gb'; $maxSize = 1;  $approvedSize = false;
        }else if ($bytes >= 1048576){
            $resByte = number_format($bytes / 1048576, 2); $typeSize = 'mb'; $maxSize = 5;
        }else if ($bytes >= 1024){
            $resByte = number_format($bytes / 1024, 2); $typeSize = 'kb';
        }else if ($bytes >= 1){
            $resByte = $bytes; $typeSize = 'b';
        }

        $resSuccess = ($approvedSize) ? true : false;
        if($confirm && $approvedSize && $maxSize && !empty($maxSize) && !is_null($maxSize) && $maxSize>0){
            $resSuccess = ($resByte<=$maxSize) ? true : false;
        }
        return array('success'=>$resSuccess,'size'=>$resByte, 'type'=>$typeSize, 'min'=>$minSize, 'max'=>$maxSize);
    }

    public function mimeType($ext){
        $types = array (
            '3dmf'      => 'x-world/x-3dmf',
            '3dm'       => 'x-world/x-3dmf',
            'avi'       => 'video/x-msvideo',
            'ai'        => 'application/postscript',
            'bin'       => 'application/octet-stream',
            'bin'       => 'application/x-macbinary',
            'bmp'       => 'image/bmp',
            'cab'       => 'application/x-shockwave-flash',
            'c'         => 'text/plain',
            'c++'       => 'text/plain',
            'class'     => 'application/java',
            'css'       => 'text/css',
            'csv'       => 'text/comma-separated-values',
            'cdr'       => 'application/cdr',
            'doc'       => 'application/msword',
            'dot'       => 'application/msword',
            'docx'      => 'application/msword',
            'dwg'       => 'application/acad',
            'eps'       => 'application/postscript',
            'exe'       => 'application/octet-stream',
            'gif'       => 'image/gif',
            'gz'        => 'application/gzip',
            'gtar'      => 'application/x-gtar',
            'flv'       => 'video/x-flv',
            'fh4'       => 'image/x-freehand',
            'fh5'       => 'image/x-freehand',
            'fhc'       => 'image/x-freehand',
            'help'      => 'application/x-helpfile',
            'hlp'       => 'application/x-helpfile',
            'html'      => 'text/html',
            'htm'       => 'text/html',
            'ico'       => 'image/x-icon',
            'imap'      => 'application/x-httpd-imap',
            'inf'       => 'application/inf',
            'jpe'       => 'image/jpeg',
            'jpeg'      => 'image/jpeg',
            'jpg'       => 'image/jpeg',
            'webp'      => 'image/webp',
            'js'        => 'application/x-javascript',
            'java'      => 'text/x-java-source',
            'latex'     => 'application/x-latex',
            'log'       => 'text/plain',
            'm3u'       => 'audio/x-mpequrl',
            'midi'      => 'audio/midi',
            'mid'       => 'audio/midi',
            'mov'       => 'video/quicktime',
            'mp3'       => 'audio/mpeg',
            'mpeg'      => 'video/mpeg',
            'mpg'       => 'video/mpeg',
            'mp2'       => 'video/mpeg',
            'ogg'       => 'application/ogg',
            'phtml'     => 'application/x-httpd-php',
            'php'       => 'application/x-httpd-php',
            'pdf'       => 'application/pdf',
            'pgp'       => 'application/pgp',
            'png'       => 'image/png',
            'pps'       => 'application/mspowerpoint',
            'ppt'       => 'application/mspowerpoint',
            'ppz'       => 'application/mspowerpoint',
            'pot'       => 'application/mspowerpoint',
            'ps'        => 'application/postscript',
            'qt'        => 'video/quicktime',
            'qd3d'      => 'x-world/x-3dmf',
            'qd3'       => 'x-world/x-3dmf',
            'qxd'       => 'application/x-quark-express',
            'rar'       => 'application/x-rar-compressed',
            'ra'        => 'audio/x-realaudio',
            'ram'       => 'audio/x-pn-realaudio',
            'rm'        => 'audio/x-pn-realaudio',
            'rtf'       => 'text/rtf',
            'spr'       => 'application/x-sprite',
            'sprite'    => 'application/x-sprite',
            'stream'    => 'audio/x-qt-stream',
            'swf'       => 'application/x-shockwave-flash',
            'svg'       => 'text/xml-svg',
            'sgml'      => 'text/x-sgml',
            'sgm'       => 'text/x-sgml',
            'tar'       => 'application/x-tar',
            'tiff'      => 'image/tiff',
            'tif'       => 'image/tiff',
            'tgz'       => 'application/x-compressed',
            'tex'       => 'application/x-tex',
            'txt'       => 'text/plain',
            'vob'       => 'video/x-mpg',
            'wav'       => 'audio/x-wav',
            'wrl'       => 'model/vrml',
            'wrl'       => 'x-world/x-vrml',
            'xla'       => 'application/msexcel',
            'xls'       => 'application/msexcel',
            'xls'       => 'application/vnd.ms-excel',
            'xlc'       => 'application/vnd.ms-excel',
            'xml'       => 'text/xml',
            'zip'       => 'application/x-zip-compressed',
            'zip'       => 'application/zip',
        );
        $resType = false;
        if(isset($types[$ext]) && $types[$ext] && !empty($types[$ext]) && !is_null($types[$ext])){ $resType = $types[$ext]; }
        return $resType;
    }

//===================================================== END LINE =====================================================//
}
?>