<?php

chdir('../../../');	

require_once('vendor/autoload.php');

infrajs\path\Path::init();

//В адресе $_SERVER['REQUEST_URI'] некоторое значение. После Path::init понятно что это не файл. 
//Вероятно какая-то инструкция для показа страницы сайта, самое время её обработать в контроллере и что-то показать
//Это делают другие плагины, а path только предоставляет такую возможность.

echo '<h1>'.$query.'</h1>';
echo '404 Not Found';
http_response_code(404);
exit;