<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$username = 'default';
$password = 'AVNS_t1IGaMrx0IOKmkXYqeh';
$host = 'cache-do-user-9387143-0.b.db.ondigitalocean.com';
$port = 25061;

$defaultServer = 'host_tcp';
$servers = [
	'host_tcp' => [
        'scheme' => 'tcp','host' => $host,'port' => $port,'username' => $username,'password' => $password,'database' => 0,'timeout' => 0,'tls' => true,
    ],
    'host_tls' => [
        'scheme' => 'tls','host' => $host,'port' => $port,'username' => $username,'password' => $password,'database' => 0,'timeout' => 0,'tls' => true,
    ],
    'host_redis' => [
        'scheme' => 'redis','host' => $host,'port' => $port,'username' => $username,'password' => $password,'database' => 0,'timeout' => 0,'tls' => true,
    ],
    'host_rediss' => [
        'scheme' => 'rediss','host' => $host,'port' => $port,'username' => $username,'password' => $password,'database' => 0,'timeout' => 0,'tls' => true,
    ],
    'host_http' => [
        'scheme' => 'http','host' => $host,'port' => $port,'username' => $username,'password' => $password,'database' => 0,'timeout' => 0,'tls' => true,
    ],
    'host_unix' => [
        'scheme' => 'unix','host' => $host,'port' => $port,'username' => $username,'password' => $password,'database' => 0,'timeout' => 0,'tls' => true,
    ],
];

$defaultServerDevelop = 'host_tcp';
$serversDevelop = $servers;
$serversDevelop['localhost'] = ['scheme' => 'tcp','host' => 'localhost','port' => 6379,'password' => null,'database' => 0,];

$config['redis'] = [];
switch (ENVIRONMENT) {
    case 'development':
    case 'testing':
        $config['redis'] = ['default_server' => $defaultServerDevelop, 'servers' => $serversDevelop];
        break;
    case 'production':
    	$config['redis'] = ['default_server' => $defaultServer, 'servers' => $servers];
        break;
    default:
        throw new Exception('The application environment is not set correctly.');
}