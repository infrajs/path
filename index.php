<?php
namespace infrajs\path;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
}
require_once('vendor/autoload.php');

$query = Path::init();

//В адресе строка, некоторое значение. После Path::init понятно что это не файл. 
//$query - какая-то инструкция для показа сайта, самое время её обработать в контроллере и что-то показать на странице
//Это делают другие плагины, а path только предоставляет такую возможность.
//Если используется просто Path то здесь выводится справка. Предполагается что в проектах текущий файл не используется.