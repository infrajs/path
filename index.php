<?php
namespace infrajs\path;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
	require_once('vendor/autoload.php');
	Path::$root='../../../';//Если скрипт будет подключен другим файлом не в корне сайта без указания $root, даже если chdir есть редирект будет неправильным
}

$query=urldecode($_SERVER['QUERY_STRING']);

return Path::go($query); //go потому что другие адресов здесь нет. Не обрабатывается "./"
