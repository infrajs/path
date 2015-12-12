<?php
namespace infrajs\path;
use infrajs\infra\Infra;
use infrajs\event\Event;

$conf=&Infra::config('path');
Path::$conf=array_merge(Path::$conf, $conf);
$conf=Path::$conf;

if ($conf['fs']) { //Возможна ситуация что папки cache в принципе нет и на диск ничего не записывается
	if (!Path::theme('|')) Event::fire('oninstall');
}

Event::handler('oninstall', function () {
	Path::mkdir(Path::$conf['cache']);
	Path::mkdir(Path::$conf['data']);
});
