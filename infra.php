<?php
namespace infrajs\path;
use infrajs\infra\Config;

$conf=Config::get('path');

Path::$conf=array_merge(Path::$conf, $conf);


Path::$conf['get'] = function ($src) {
	return $src;
};

Path::$conf['theme'] = function ($str) {
	//Повторно для адреса не работает Путь только отностельно корня сайта или со звёздочкой
	//Скрытые файлы доступны

	$str = Path::toutf($str);
	$origstr=$str;
	$dirs = Config::dirs();
	if (!$str) return;
	$q = explode('?', $str, 2);
	$str = $q[0];
	$is_fn = (mb_substr($str, mb_strlen($str) - 1, 1) == '/' || in_array($str,array('*', '~', '|'))) ? 'is_dir' : 'is_file';
	
	$query = '';
	if (isset($q[1])) $query = '?'.$q[1];

	$ch=mb_substr($str, 0, 1);
	if ($ch == '~') {
		$str = mb_substr($str, 1);
		$str = Path::tofs($str);
		if ($is_fn($dirs['data'].$str)) return $dirs['data'].$str.$query;
	} else if ($ch == '*') {
		$str = mb_substr($str, 1);

		if ($is_fn('./'.$str)) return './'.$str.$query; //Корень важней search и external

		$p=explode('/', $str); //file.ext folder/ folder/file.ext folder/dir/file.ext
		if(sizeof($p)>1){
			if(!empty($dirs['external'][$p[0]])) {
				foreach ($dirs['external'][$p[0]] as $dir) {
					if ($is_fn($dir.$str)) return $dir.$str.$query;
				}
			}
		}
		$str = Path::tofs($str);
		
		foreach ($dirs['search'] as $dir) {
			if ($is_fn($dir.$str)) return $dir.$str.$query;
		}

	} else if ($ch == '|') {
		$str = mb_substr($str, 1);
		$str = Path::tofs($str);
		if ($is_fn($dirs['cache'].$str)) return $dirs['cache'].$str.$query;
	} else {
		//Проверка что путь уже правильный... происходит когда нет звёздочки... Неопределённость может возникнуть только с явными путями
		//if($is_fn($str))return $str.$query;//Относительный путь в первую очередь, если повторный вызов для пути попадём сюда
		
		$str = Path::tofs($str);
		if ($is_fn($str)) return $str.$query;
	}
};
