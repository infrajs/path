<?php
namespace infrajs\path;

use infrajs\config\Config;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
}
require_once('vendor/autoload.php');

$path = Path::$conf;

if ($path['fs']&&!is_dir($path['cache'])) {
	Config::update();
}
