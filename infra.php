<?php
namespace infrajs\path;
use infrajs\infra\Infra;
use infrajs\event\Event;

/**
 * Интеграция с infrajs/infra
 **/

//Общий конфиг в Infra



//При инсталяции создание папок cache и data
Event::handler('oninstall', function () {
	Path::mkdir(Path::$conf['cache']);
	Path::mkdir(Path::$conf['data']);
});
