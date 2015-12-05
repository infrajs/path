<?php
namespace infrajs\path;

$root = './';
if (!is_file('vendor/autoload.php')) {
	$root = '../../../';
	chdir($root);	
}
require_once('vendor/autoload.php');
Path::$root=$root;

$query=urldecode($_SERVER['QUERY_STRING']);


return Path::go($query);
