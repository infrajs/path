<?php
namespace infrajs\path;

use infrajs\once\Once;
use infrajs\nostore\Nostore;
use infrajs\config\Config;

class Path {
	//Для конфига Path дейстует исключение - его параметры самые важные тут, а не в корне.
	//ERROR параметры Path изменять нельзя в проекте, только заменой самого Path. Кроме space он не указан здесь.
	public static $conf = array(
		'data' => 'data/',
		'cache' => 'cache/',
		'fs' => true,
		//"replaceable" => true, //Меняет порядок наследования, так как это свойство ядерное
		'space' => false,
		'parenthesis' => false,
		//'search' => array(
		//	'vendor/infrajs/'
		//),
		//'clutch' => array()
	);
	
	/**
	 * Path::init($query)
	 * $query может взять из QUERY_STRING или из URI_REQUEST если используется modrewrite
	 * Если в $query начинается с символов ./ или -~! будет проверка файла на доступность и переадресация на него
	 * @return (bool) найдено соответствие или нет
	 **/
	public static function init()
	{
		//return Once::func( function () {
			$res = URN::parse();
			
			$res['request2ch'] = $res['request2'] ? in_array($res['request2']{0}, array('-', '~', '!')) : false;
				
				
			/*
				Ситуация
				site.ru/adsf?-admin/
				site.ru/sadf?vendor/infrajs/admin/
				site.ru/asdf?vendor/infrajs/admin/index.php
			*/
			
			if ( $res['request2ch'] || Path::theme($res['request2']) ) {
				if($res['param2']) Path::redirect($res['request2'].'?'.$res['param2']);
				else Path::redirect($res['request2']);
				return true;
			}


			/*
				Ситуация
				site/?login = site/login
				site/-asdf?login = site/-asdf?login
				site/catalog?contacts = site/contacts
			*/
			$res['requestch'] = $res['request'] ? in_array($res['request']{0}, array('-', '~', '!')) : false;
			if(!$res['requestch']&&!Path::theme($res['request'])&&$res['request2']) {
				//Чтобы работали старые ссылки
				if($res['param2']) Path::redirect($res['request2'].'?'.$res['param2']);
				else Path::redirect($res['request2']);
				return true;
			}
			
			//exit;
			//$res['request2dir'] = Path::isdir($res['request2']);
			//$res['request2ext'] = Path::getExt($res['request2']);
			
			$res['requestdir'] = Path::isdir($res['request']);
			if (!$res['request']) {
				$res['requestdir'] = true;
				$file='./';
			} else {
				$file = Path::theme($res['request']);
			}
			

			$query = $res['query'];
			if ( $res['requestch'] ) { //Есть специальный символ в запросе

				//файл не проверяем. отсутствует всёравно идём в go
				$src = Path::themeq($query);
				if ($src) {
					Path::go($query);
					return true;
				}


				
			} else {

				if ($file) { //Если файл отсутствует проходим дальше
					if ($res['requestdir']) {
						$p=explode('?', $query, 2);
						$p[0] .='index.php';
						$file = implode('?', $p);
						if (!Path::theme($file)) {
							$p = explode('?', $query, 2);
							$p[0] .= 'index.html';
							$file = implode('?', $p);
							if (!Path::theme($file)) {
								$p = explode('?', $query, 2);
								$p[0] .= 'index.htm';
								$file = implode('?', $p);
							}
						}
					}
					if (Path::theme($file)) { //Если есть index.php в папке или просто указанный файл есть
						Path::_go($file);
						return true;
					}
				}
			}
			return false;
		//});
	}
	public static function themeq($query) {
		$requestdir = Path::isdir($query);
		$requestch = in_array($query{0}, array('-', '~', '!'));
		
		if (!$requestch) return Path::theme($query);
		if (!$requestdir) {
			if (Path::theme($query)) return Path::theme($query);
		}

		$p = explode('?', $query, 2);
		$ff = explode('/', $p[0]);

		if (!$requestdir) {
			array_push($ff,'');
		}
		array_push($ff,'');
		
		do {
			array_pop($ff);
			$ff[sizeof($ff)-1] = 'index.php';
			$p[0] = implode('/', $ff);
			$query = implode('?', $p);

			/*if (!Path::theme($query)) {
				$ff[sizeof($ff)-1] = 'index.html';
				$p[0] = implode('/', $ff);
				$query = implode('?', $p);

			}*/
		} while (!Path::theme($query) && sizeof($ff) > 2);

		
		if (Path::theme($query)) return Path::theme($query);
	}
	private static function redirect($src)
	{
		Nostore::pub();
		$root = URN::getRoot();
		$src = Path::toutf($src);
		header('Location: ./'.$root.$src, true, 301);
		exit;
	}
	public static function go($src)
	{
		$query = Path::themeq($src);
		if (!$query) {
			http_response_code(404);
			return;
		}
		$_SERVER['REQUEST_URI'] = '/'.$src;

		$ext = static::getExt($query);
		$isdir = static::isdir($query);
		if ($isdir || $ext=='php') {
			static::inc($query);
		} else {
			Path::redirect($query);
		}
	}
	
	public static function _go($query)
	{
		$query = Path::theme($query);

		if (!$query) {
			http_response_code(404);
			return;
		}

		$p=explode('?', $query, 2);
		$queryfile=$p[0];
		if ($p[0] && (preg_match("/\/\./", $p[0]) || ($p[0]{0} == '.' && $p[0]{1} != '/'))) {
			http_response_code(403); //Forbidden
			exit;
		}
		/*
		symlinks data error fix
		if(strpos(realpath($p[0]), realpath('./')) !== 0) { //Проверка что доступ к внутреннему ресурсу
			http_response_code(403);
			exit;
		}*/

		//Узнать текущий путь браузера можно из REQUEST_URI, но узнать какая из папок в этом адресе является корнем проекта невозможно. 
		//Эта задача решаема только для частных случаев.
		//В нашем случае мы полагаем либо к файлу было прямое обращение по месту расположения (site/vendor/infrajs/path/)
		//и Path::$root='../../../' либо файл запущен из корня сайта Path::$root='./' (site/)
		//Соответственно текущий файл может быть подключен только в корень проекта (chdir тогда не меняется).

		//Альтернативный вариант полагать, что корень сервера совпадает с корнем проекта тогда работал путь '/'.$src но такого соглашения нет.
		$ext = static::getExt($query);
		
		
		
		$isdir = static::isdir($query);

		if ($isdir||$ext=='php') {
			static::inc($query);
			exit;
		}
		

		$file = URN::getRoot().$query;
		
		/*//header("X-Sendfile: $file");
		//header("Content-type: application/octet-stream");
		//header('Content-Disposition: attachment; filename="' . basename($file) . '"');


		//header("X-Sendfile: ".$file);
		//header("X-Accel-Redirect: ".static::getRoot().$query);
	
		exit;
		$mime=\mime_content_type($queryfile);
		echo $mime;
		exit;
		//return static::inc($query);
		echo file_get_contents($queryfile);

		exit;*/

		Path::redirect($query);
	}
	public static function isdir($src){
		if (!$src) return false;
		$p=explode('?',$src, 2);
		$path=$p[0];

		if($path[strlen($path)-1]=='/')return true;

		return false;
	}
	public static function getQuery()
	{
		$conf=static::$conf;
		$query=urldecode($_SERVER['QUERY_STRING']);
		return URN::getQuery();
	}
	public static function inc($src)
	{
		$p=explode('?', $src, 2);
		$path=$p[0];

		$query = (sizeof($p) == 2) ? '?'.$p[1] : '';
		$getstr = preg_replace("/^\?/", '', $query);
		parse_str($getstr, $get);
		if (!$get) $get = array();
		$_GET = $get;
		if (!isset($_POST)) $_POST = array();
		$_REQUEST = array_merge($_GET, $_POST);
		$_SERVER['QUERY_STRING'] = $getstr;

		return include getcwd().'/'.$path;
	}
	public static $paths = array();
	public static function themesearch($str) {
		$conf = Path::$conf;
		if (!$str) return false;
		
		$is_fn = (mb_substr($str, mb_strlen($str) - 1, 1) == '/' || in_array($str,array('-', '~', '!'))) ? 'is_dir' : 'is_file';
		
		$ch = mb_substr($str, 0, 1);



		if ($ch == '~') {
			$str = mb_substr($str, 1);
			$fstr = Path::tofs($str);
			if ($is_fn($conf['data'].$fstr)) return $conf['data'].$str;
		} else if ($ch == '-') {
			$str = mb_substr($str, 1);
			$fstr = Path::tofs($str);
			if ($is_fn($fstr)) return $str; //ПОИСК в корне
			
			$p = explode('/', $fstr); //file.ext folder/ folder/file.ext folder/dir/file.ext
			

			
			
			if ($p[0] == 'index') {
				array_shift($p);
				$s = implode('/',$p);
				if ($is_fn($s)) return Path::toutf($s);
			} else {
				$s = 'index/'.$fstr;
				if ($is_fn($s)) return Path::toutf($s);
			}
			

			if (sizeof($p) > 1) { //ПОИСК clutch
				if (!empty($conf['clutch'][$p[0]])) {
					foreach ($conf['clutch'][$p[0]] as $dir) {
						if ($is_fn($dir.$fstr)) return $dir.$str;
					}
				}
			}
			
			foreach ($conf['search'] as $dir) { //ПОИСК search
				if ($is_fn($dir.$fstr)) return $dir.$str;
			}
		} else if ($ch == '!') {
			$str = mb_substr($str, 1);
			$fstr = Path::tofs($str);
			if ($is_fn($conf['cache'].$fstr)) return $conf['cache'].$str;
		} else {
			//Проверка что путь уже правильный... происходит когда нет звёздочки... Неопределённость может возникнуть только с явными путями
			//if($is_fn($str))return $str;//Относительный путь в первую очередь, если повторный вызов для пути попадём сюда
			
			$fstr = Path::tofs($str);
			if ($is_fn($fstr)) return $str;
		}
		return false;
	}
	public static function clear($src) {
		$p = explode('?', $src, 2);
		$query = (sizeof($p) == 2) ? '?'.$p[1] : '';
		$str = $p[0];
		$str = Path::toutf($str);
		unset(Path::$paths[$str]);
	}
	public static function theme($src)
	{
		$p = explode('?', $src, 2);
		$query = (sizeof($p) == 2) ? '?'.$p[1] : '';
		$str = $p[0];
		$str = Path::toutf($str);
		
		if (!isset(Path::$paths[$str])) Path::$paths[$str] = Path::themesearch($str);
		if (!Path::$paths[$str]) return false;
		$src = Path::tofs(Path::$paths[$str]);
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
		if (isset($_SERVER['WINDIR'])){
			if (strstr($str, '‐')!==false) {
				$str = str_replace('‐', '-', $str);
				//die('"'.$str. '" - В строке содержится некорректный символ "-".');
				//Это разные символы. При кодировки в cp1251 первый приводит к ошибке.
			}
			$str = Path::toutf($str);
			$str = iconv('UTF-8', 'CP1251', $str);
		}
		return $str;
	}
	public static function encode($str, $space = false) //forFS
	{
		//Начинаться и заканчиваться пробелом не может
		//два пробела не могут идти подряд
		//символов ' " /\#&?$ быть не может удаляются
		//& этого символа нет, значит не может быть htmlentities
		//символов <> удаляются из-за безопасности
		//В адресной строке + заменяется на пробел, значит и тут удаляем
		//Виндовс запрещает символы в именах файлов  \/:*?"<>|
		//Точка (.) Используется в скртиптах name.prop.value и такое значени может браться из адреса. pro.p.value точка в имени поломает это
		//% приводит к ошибке malfomed URI при попадании в адрес так как там используется decodeURI
		//Пробельные символы кодируются в адресе и не приняты в файловой системе, но из-за совместимости пока остаются. Папки каталога давно созданы и нельзя изменить логику, так как папки перестанут совпадать с именем
		//() нужно убрать, чтобы работали jquery селекторы
		//, используется для перечислений в имени файла, одна картинка для нескольких артикулов
		//× - iconv ругается Detected an illegal character in input string 
		$str = preg_replace('/[\'\`"\.×,№\+%\*<>‐\-\'"\|\:\/\\\\#\!\?\$&\s]/u', ' ', $str);

		if (empty(Path::$conf['parenthesis'])) {
			$str = preg_replace('/[\(\)]/u', ' ', $str);
		}
		$str = preg_replace('/\s+/u', ' ', $str);
		$str = preg_replace('/^\s/u', '', $str);
		$str = preg_replace('/\s$/u', '', $str);
		
		//if (empty(Path::$conf['space'])) {
		if (!$space) $str = preg_replace('/\s/u', '-', $str);
		//}

		if (mb_strlen($str) > 50) $str = md5($str);//У файловых систем есть ограничение на длину имени файла
		return $str;
	}
	public static function getExt($src){
		$p=explode('?',$src, 2);
		$path=$p[0];
		return strtolower(pathinfo($path, PATHINFO_EXTENSION));
	}
	public static function mkdir($isrc) //forFS
	{
		if (!is_file('vendor/autoload.php')) throw new \Exception("You should setting chdir() on site root directory with vendor/ folder"); 
		$conf = static::$conf;
		if (!$conf['fs']) return;
		$src = static::resolve($isrc);
		if (!is_dir($src)) {
			$r = mkdir($src);
			if (!$r) throw new \Exception('Не удалось создать папку '.$src);
			Path::clear($isrc);
			return $src;
		}
		return $src;
	}
	/**
	 * Возвращает путь который можно использовать в стандартных функциях php. 
	 * В infrajs ~ и | можно заменить, а * подставить нельзя без обращения к файловой системе, если путь содержит * генерируется исключение
	 **/
	public static function resolve($src) 
	{
		if (!$src) return $src;
		$ch=$src{0};
		if ($ch == '-') throw new \Exception('Symbol - contain multiple paths and cant be resolving without request to the filesystem. Use theme() or fix src');
		else if($ch == '~') $src = static::$conf['data'].substr($src, 1);	
		else if($ch == '!') $src = static::$conf['cache'].substr($src, 1);

		$src=static::tofs($src);
		return $src;
	}
	public static function pretty($src) 
	{
		if (!$src) return $src;
		$conf = static::$conf;
		
		$path = str_replace('/', '\/', $conf['data']);
		$src = preg_replace('/^'.$path.'/', '~', $src, 1, $count);
		if ($count) return $src;

		$path = str_replace('/', '\/', $conf['cache']);
		$src = preg_replace('/^'.$path.'/', '!', $src, 1, $count);
		if ($count) return $src;

		foreach ($conf['search'] as $path) {
			$path = str_replace('/', '\/', $path);
			$src = preg_replace('/^'.$path.'/', '-', $src, 1, $count);
			if ($count) return $src;
		}
		return $src;
	}
	public static function reqif($path)
	{
		if (Path::theme($path)) {
			static::req($path);
			return true;
		} else {
			return false;
		}
	}
	public static function req($path)
	{
		//$args=array($path);
		//Once::func(function($path) {
			$rpath = Path::theme($path);
			if (!$rpath) {
				echo '<pre>';
				throw new \Exception('Path::req - не найден путь '.$path);
			}
			require_once './'.$rpath;//Просто require позволяет загрузить самого себя. А мы текущий скрипт не добавляем в список подключённых
		//}, $args);
	}
	/**
	 * Удалить (true) или очистить дирректорию (false)
	 *
	 **/
	public static function fullrmdir($delfile, $ischild = false)
	{
		if (!static::$conf['fs']) throw new \Exception('Work with filesystem forbbiden conf.path.fs');
		if(!$delfile) throw new Exception('Нужно указать существующий путь до папки для удаления');
		$delfile = Path::resolve($delfile);
		if (file_exists($delfile)) {		
			if(!$delfile) throw new Exception('Удалить корневую папку нельзя');
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
					//Once::clear('Path::theme', array(Path::resolve($delfile)));
					//Once::clear('Path::theme', array($delfile));
					return rmdir($delfile);
				}

				return true;
			} else {
				//Once::clear('Path::theme', array(Path::resolve($delfile)));
				//Once::clear('Path::theme', array($delfile));
				return unlink($delfile);
			}
		}
		return true;
	}
	/**
	 *  true если путь $dir существует и вложен в $root
	 **/
	public static function isNest($root, $dir) {
		$src = Path::theme($dir);
		if (!$src) return false;
		$src = realpath($src);
		if (!$src) return false;
		$home = Path::theme($root);
		if (!$home) return false;
		$home = realpath($home);
		if (!$home) return false;
		if (preg_match('/\\'.DIRECTORY_SEPARATOR.'\./',$home)) return false;
		if (preg_match('/\\'.DIRECTORY_SEPARATOR.'\./',$src)) return false;
		$p = explode($home, $src, 2);
		if (sizeof($p)!=2||$p[0]||!$p[1]) return false;
		return true;
	}
}
