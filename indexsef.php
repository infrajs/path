<?php
namespace infrajs\path;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
}
/**
 * require_once('vendor/autoload.php'); вынесено из условия, чтобы файл можно было перенести 
 * из infrajs/path в корень проекта и он бы работал. 
 * В корне проекта по положению файла autoload.php нельзя определть был ли он подключён или нет.
 **/
require_once('vendor/autoload.php');


/*header_register_callback(function(){
	$code=http_response_code();
	if($code!=200){
		echo $code;
	}
});
*/


Path::$conf['sefurl']=true;

Path::req('-path/index.php');
