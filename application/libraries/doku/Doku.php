<?php

if (!function_exists('curl_init')) {
  throw new Exception('CURL PHP extension missing!');
}
if (!function_exists('json_decode')) {
  throw new Exception('JSON PHP extension missing!');
}

require('Initiate.php');
require('Api.php');
require('Library.php');
?>