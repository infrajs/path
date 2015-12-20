<?php
namespace infrajs\path;
use Michelf\Markdown;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
}
require_once('vendor/autoload.php');

$query=Path::init();

//В адресе строка, некоторое значение. После Path::init понятно что это не файл. 
//$query - какая-то инструкция для показа сайта, самое время её обработать в контроллере и что-то показать на странице
//Это делают другие плагины, а path только предоставляет такую возможность.
//Если используется просто Path то здесь выводится справка. Предполагается что в проектах текущий файл не используется.

$text = file_get_contents(Path::theme('-path/README.md'));
$body = Markdown::defaultTransform($text);
$html = file_get_contents(Path::theme('-path/index.tpl'));
$html = str_replace('{body}', $body, $html);
echo $html;
