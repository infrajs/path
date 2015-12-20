<?php
namespace infrajs\path;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
}
require_once('vendor/autoload.php');

Path::$conf['sefurl']=true;

Path::req('-path/index.php');
