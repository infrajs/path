<?php
namespace infrajs\path;

use infrajs\config\Config;
use infrajs\load\Load;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
}
require_once('vendor/autoload.php');

$comp = Load::loadJSON('composer.json');
if ($comp && !empty($comp['require'])) {
	
	foreach ($comp['require'] as $n => $v) {
		$r = explode('/', $n);
		
		if (sizeof($r)!=2) continue;
		$path = 'vendor/'.$r[0].'/';
		if (!in_array($path, Path::$conf['search'])){
			Path::$conf['search'][] = $path;
		}
	}
}

//pathsearch Расширяет список вендоров у которых ищутся плагины для infrajs
Config::add('pathsearch', function ($name, $value, &$conf) {
	$path = &Path::$conf;
	if (is_string($value)) $value = [$value];
	for ($i = 0, $l = sizeof($value); $i < $l; $i++) {
		$path['search'][] = $value[$i];
	}
});