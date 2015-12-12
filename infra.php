<?php
namespace infrajs\path;
use infrajs\infra\Infra;
use infrajs\event\Event;

$conf=&Infra::config('path');
Path::$conf=array_merge(Path::$conf, $conf);
$conf=Path::$conf;

Event::handler('oninstall', function () {
	Path::mkdir(Path::$conf['cache']);
	Path::mkdir(Path::$conf['data']);
});
