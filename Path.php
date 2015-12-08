<?php
namespace infrajs\path;
use infrajs\once\Once;
class Path {
	public static $conf = array(
		'data' => 'data/',
		'cache' => 'cache/',
		'fs' => true,
		'search' => array(
			'vendor/infrajs/',
			'vendor/components/',
			'bower_components/'
		),
		/**
		 * Одно расширение, может содержать файлы для  подмены другого расширения. 
		 * Записывается так "catalog"=>array("vendor/infrajs/cards/")
		 * Файлы в папке *catalog/ будудут заменены на файлы в vendor/infrajs/cards/catalog/ при наличии
		 **/
		'external' => array()
	);
	/**
	 * Path::init($query) запускается только из корня проекта. 
	 * $query может взять из QUERY_STRING или из URI_REQUEST если используется modrewrite
	 * Если в $query начинается с символов ./ или *~| будет проверка файла на доступность и переадресация на него
	 **/
	public static $root='./';
	public static function go($src)
	{
		if (!$src) {
			header('HTTP/1.0 400 Bad Request');
			return;
		}
		$src=Path::theme($src);
		if (!$src) {
			header('HTTP/1.0 404 Not Found');
			return;
		}
		$p=explode('?', $src, 2);
		if ($p[0] && (preg_match("/\/\./", $p[0]) || ($p[0]{0} == '.' && $p[0]{1} != '/'))) {
			header('HTTP/1.0 403 Forbidden');
			return;
		}
		if(strpos(realpath($p[0]), realpath('./')) !== 0) { //Проверка что доступ к внутреннему ресурсу
			header('HTTP/1.0 403 Forbidden');
			return;
		}
		//Узнать текущий путь браузера можно из REQUEST_URI, но узнать какая из папок в этом адресе является корнем проекта невозможно. 
		//Эта задача решаема только для частных случаев.
		//В нашем случае мы полагаем либо к файлу было прямое обращение по месту расположения (site/vendor/infrajs/path/)
		//и Path::$root='../../../' либо файл запущен из корня сайта Path::$root='./' (site/)
		//Соответственно текущий файл может быть подключен только в корень проекта (chdir тогда не меняется).

		//Альтернативный вариант полагать, что корень сервера совпадает с корнем проекта тогда работал путь '/'.$src но такого соглашения нет.
		$ext=static::getExt($src);
		if ($ext=='php') return static::inc($src);
		
		header('Location: '.static::$root.$src);
		return true;
	}
	public static function inc($src){
		$p=explode('?', $src, 2);
		$query = (sizeof($p) == 2) ? '?'.$p[1] : '';
		$getstr = preg_replace("/^\?/", '', $query);
		parse_str($getstr, $get);
		if (!$get) $get = array();
		$GET = $_GET;
		$_GET = $get;
		$REQUEST = $_REQUEST;
		$_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
		$SERVER_QUERY_STRING = $_SERVER['QUERY_STRING'];
		$_SERVER['QUERY_STRING'] = $getstr;
		//chdir($p['folder']);
		return include $p[0];
	}
	public static function init($query)
	{
		if (!$query) return;
		$char = substr($query, 0, 2);
		if ( $char == './' ) {
			Path::go(substr($query, 2));
			exit;
		}
		$ch=$query{0};
		if ( in_array($ch, array('*', '~', '|')) ) {
			Path::go($query);
			exit;
		}
	}
	public static function theme($src)
	{
		$p=explode('?', $src, 2);
		$query = (sizeof($p) == 2) ? '?'.$p[1] : '';
		$args=array($p[0]);
		$src=Once::exec('Path::theme', function ($str) {
			//Повторно для адреса не работает Путь только отностельно корня сайта или со звёздочкой
			//Скрытые файлы доступны

			$str = Path::toutf($str);
			$origstr=$str;
			$conf = Path::$conf;
			if (!$str) return;
			
			
			$is_fn = (mb_substr($str, mb_strlen($str) - 1, 1) == '/' || in_array($str,array('*', '~', '|'))) ? 'is_dir' : 'is_file';
			

			$ch=mb_substr($str, 0, 1);
			if ($ch == '~') {
				$str = mb_substr($str, 1);
				$str = Path::tofs($str);
				if ($is_fn($conf['data'].$str)) return $conf['data'].$str;
			} else if ($ch == '*') {
				$str = mb_substr($str, 1);

				if ($is_fn('./'.$str)) return './'.$str; //Корень важней search и external

				$p=explode('/', $str); //file.ext folder/ folder/file.ext folder/dir/file.ext
				if(sizeof($p)>1){
					if(!empty($conf['external'][$p[0]])) {
						foreach ($conf['external'][$p[0]] as $dir) {
							if ($is_fn($dir.$str)) return $dir.$str;
						}
					}
				}
				$str = Path::tofs($str);
				
				foreach ($conf['search'] as $dir) {
					if ($is_fn($dir.$str)) return $dir.$str;
				}

			} else if ($ch == '|') {
				$str = mb_substr($str, 1);
				$str = Path::tofs($str);
				if ($is_fn($conf['cache'].$str)) return $conf['cache'].$str;
			} else {
				//Проверка что путь уже правильный... происходит когда нет звёздочки... Неопределённость может возникнуть только с явными путями
				//if($is_fn($str))return $str;//Относительный путь в первую очередь, если повторный вызов для пути попадём сюда
				
				$str = Path::tofs($str);
				if ($is_fn($str)) return $str;
			}
			return false;
		}, $args);

		return $src.$query;
	}
	public static function toutf($str)
	{
		if (!is_string($str)) return $str;
		if (preg_match('//u', $str)) return $str;

		return mb_convert_encoding($str, 'UTF-8', 'CP1251');
	}
	public static function tofs($str)
	{
		if(isset($_SERVER['WINDIR'])){
			$str = Path::toutf($str);
			$str = iconv('UTF-8', 'CP1251', $str);
		}
		return $str;
	}
	public static function encode($str) //forFS
	{
		//Начинаться и заканчиваться пробелом не может
		//два пробела не могут идти подряд
		//символов ' " /\#&?$ быть не может удаляются
		//& этого символа нет, значит не может быть htmlentities
		//символов <> удаляются из-за безопасности
		//В адресной строке + заменяется на пробел, значит и тут удаляем
		//Виндовс запрещает символы в именах файлов  \/:*?"<>|
		//% приводит к ошибке malfomed URI при попадании в адрес так как там используется decodeURI
		//Пробельные символы кодируются в адресе и не приняты в файловой системе, но из-за совместимости пока остаются. Папки каталога давно созданы и нельзя изменить логику, так как папки перестанут совпадать с именем
		$str = preg_replace('/[\+%\*<>\'"\|\:\/\\\\#\?\$&\s]/', ' ', $str);
		$str = preg_replace('/^\s+/', '', $str);
		$str = preg_replace('/\s+$/', '', $str);
		$str = preg_replace('/\s+/', ' ', $str);
		//$str = preg_replace('/\s/', '-', $str);
		if (mb_strlen($str) > 50) $str = md5($str);//У файловых систем есть ограничение на длину имени файла
		return $str;
	}
	public static function getExt($src){
		$p=explode('?',$src, 2);
		$path=$p[0];
		return strtolower(pathinfo($path, PATHINFO_EXTENSION));
	}
	public static function mkdir($src) //forFS
	{
		if (!is_file('vendor/autoload.php')) throw new Exception("You should setting chdir() on site root directory with vendor/ folder"); 
		$conf=static::$conf;
		if(!$conf['fs']) return;
		$src=static::resolve($src);
		if (!is_dir($src)) return mkdir($src);
		return true;
	}
	/**
	 * Возвращает путь который можно использовать в стандартных функциях php. 
	 * В infrajs ~ и | можно заменить, а * подставить нельзя без обращения к файловой системе, если путь содержит * генерируется исключение
	 **/
	public static function resolve($src) 
	{
		if (!$src) return $src;
		$ch=$src{0};
		if ($ch == '*') throw new Exception('Symbol * contain multiple paths and cant be resolving without request to the filesystem. Use theme() or fix src');
		else if($ch == '~') return static::$conf['data'].substr($src, 1);	
		else if($ch == '|') return static::$conf['cache'].substr($src, 1);	
		return $src;
	}
	public static function reqif($path)
	{
		if (Path::theme($path)) return static::req($path);
	}
	public static function req($path)
	{
		$args=array($path);
		Once::exec('Load::req', function($path) {
			$rpath = Path::theme($path);
			if (!$rpath) throw new \Exception('Load::req - не найден путь '.$path);
			require_once $rpath;//Просто require позволяет загрузить самого себя. А мы текущий скрипт не добавляем в список подключённых
		}, $args);
	}
	/**
	 * Удалить или очистить дирректорию
	 *
	 **/
	public static function fullrmdir($delfile, $ischild = true)
	{
		if (!static::$conf['fs']) throw new Exception('Work with filesystem forbbiden conf.path.fs');
		$delfile = Path::theme($delfile);
		if (file_exists($delfile)) {		
			if (is_dir($delfile)) {
				$handle = opendir($delfile);
				while ($filename = readdir($handle)) {
					if ($filename != '.' && $filename != '..') {
						$src = $delfile.$filename;
						if (is_dir($src)) $src .= '/';
						$r=static::fullrmdir($src, true);
						if(!$r)return false;
					}
				}
				closedir($handle);
				if ($ischild) {
					return rmdir($delfile);
				}

				return true;
			} else {
				return unlink($delfile);
			}
		}
		return true;
	}
}
