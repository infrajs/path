<?php
namespace infrajs\path;

use infrajs\config\Config;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
}
require_once('vendor/autoload.php');

//pathsearch Расширяет список вендоров у которых ищутся плагины для infrajs
Config::add('pathsearch', function ($name, $value, &$conf) {
	$path = &Path::$conf;
	if (is_string($value)) $value = [$value];
	for ($i=0, $l=sizeof($value); $i < $l; $i++) {
		$path['search'][]=$value[$i];
	}
});