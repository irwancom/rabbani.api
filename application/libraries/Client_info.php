<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client_info{

    public static function nameToCode($string) {
       $string = str_replace(' ', '-', $string);
       $string = preg_replace('/[^A-Za-z0-9\-]/', '-', $string);
       return strtolower($string);
    }

    public static function get_user_agent() {
        return  $_SERVER['HTTP_USER_AGENT'];
    }

    public static function get_info() {
        $os = self::get_os();
        $osCode = self::nameToCode($os);
        $browser = self::get_browser();
        $browserCode = self::nameToCode($browser);
        $device = self::get_device();
        $deviceCode = self::nameToCode($device);

        $result = array();
        $result['ip'] = self::get_ip();
        $result['os'] = ['name'=>$os,'code'=>$osCode];
        $result['browser'] = ['name'=>$browser,'code'=>$browserCode];
        $result['device'] = ['name'=>$device,'code'=>$deviceCode];
        $result['detail'] = self::get_user_agent();
        return $result;
    }

    public static function get_ip() {
        $mainIp = '';
        if (getenv('HTTP_CLIENT_IP'))
            $mainIp = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $mainIp = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $mainIp = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $mainIp = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $mainIp = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $mainIp = getenv('REMOTE_ADDR');
        else
            $mainIp = 'unidentified';
        return $mainIp;
    }

    public static function get_os() {

        $user_agent = self::get_user_agent();
        $os_platform    =   "Unidentified";
        $os_array       =   array(
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile',
            '/postman/i'            =>  'Postman'
        );

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform    =   $value;
            }
        }   
        return $os_platform;
    }

    public static function  get_browser() {

        $user_agent= self::get_user_agent();

        $browser        =   "Unidentified";

        $browser_array  =   array(
            '/mobile/i'     =>  'Handheld Browser',
            '/firefox/i'    =>  'Firefox',
            '/chrome/i'     =>  'Chrome',
            '/msie/i'       =>  'Internet Explorer',
            '/Trident/i'    =>  'Internet Explorer',
            '/safari/i'     =>  'Safari',
            '/edge/i'       =>  'Edge',
            '/opera/i'      =>  'Opera',
            '/opr/i'      =>  'Opera',
            '/netscape/i'   =>  'Netscape',
            '/maxthon/i'    =>  'Maxthon',
            '/konqueror/i'  =>  'Konqueror',
            '/ubrowser/i'   =>  'UC Browser',
            '/miui/i'   =>  'Miui Browser',
            '/oppo/i'   =>  'Oppo Browser',
            '/samsung/i'   =>  'Samsung Internet',
            '/vivo/i'   =>  'Vivo Browser',
            '/heytap/i'   =>  'Heytap Browser',
            '/instagram/i'   =>  'Instagram',
            '/facebook/i'   =>  'Facebook',
            '/realme/i'   =>  'Realme Browser',
            '/postman/i'   =>  'Postman Request',
        );

        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $browser    =   $value;
            }
        }

        if($browser=='Safari' && preg_match('/chrome/i', $user_agent)){
            $browser = 'Chrome';
        }
        return $browser;
    }

    public static function  get_device(){

        $tablet_browser = 0;
        $mobile_browser = 0;

        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $tablet_browser++;
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $mobile_browser++;
        }

        if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
            $mobile_browser++;
        }

        $mobile_ua = strtolower(substr(self::get_user_agent(), 0, 4));
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda ','xda-');

        if (in_array($mobile_ua,$mobile_agents)) {
            $mobile_browser++;
        }

        if (strpos(strtolower(self::get_user_agent()),'opera mini') > 0) {
            $mobile_browser++;
                //Check for tablets on opera mini alternative headers
            $stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])?$_SERVER['HTTP_X_OPERAMINI_PHONE_UA']:(isset($_SERVER['HTTP_DEVICE_STOCK_UA'])?$_SERVER['HTTP_DEVICE_STOCK_UA']:''));
            if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
                $tablet_browser++;
            }
        }

        if ($tablet_browser > 0) {
               // do something for tablet devices
            return 'Tablet';
        }
        else if ($mobile_browser > 0) {
               // do something for mobile devices
            return 'Mobile';
        }
        else {
               // do something for everything else
            return 'Desktop';
        }   
    }

}