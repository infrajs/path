<?php
namespace infrajs\path;
use infrajs\infra\Infra;
use infrajs\event\Event;

/**
 * Интеграция с infrajs/infra
 **/

//Общий конфиг
$conf=&Infra::config('path');
Path::$conf=array_merge(Path::$conf, $conf);
$conf=Path::$conf;


//При инсталяции создание папок cache и data
Event::handler('oninstall', function () {
	Path::mkdir(Path::$conf['cache']);
	Path::mkdir(Path::$conf['data']);
});
