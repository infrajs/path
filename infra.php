<?php
namespace infrajs\path;

use infrajs\config\Config;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
}
require_once('vendor/autoload.php');

$path = Path::$conf;

if (!is_dir($path['cache'])) {
	Config::$install = true;
	mkdir($path['cache']);
}
if ($path['fs']&&is_file($path['data'].'update')) {
	header('Infrajs-Path-Update:true');
	Path::fullrmdir($path['cache']);
	Config::$install = true;
	unlink($path['data'].'update');
}