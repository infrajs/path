<?php
namespace infrajs\path;
require_once(__DIR__.'/../../../vendor/autoload.php');

Path::mkdir(Path::$conf['cache']);
Path::mkdir(Path::$conf['data']);
