<?php
namespace Redis;

class Redis {

    public $redis;

    public function __construct () {
        $ci =& get_instance();

        $ip = '128.199.77.34';
        $port = 6379;
        $pass = 'wasalam';
        $this->redis = \Resque::setBackend(sprintf('redis://user:%s@%s:%s', $pass, $ip, $port));
    }
}