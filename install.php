<?php
namespace infrajs\path;

if (!is_file('vendor/autoload.php')) chdir('../../../');
require_once('vendor/autoload.php');

Path::mkdir(Path::$conf['cache']);
Path::mkdir(Path::$conf['data']);
