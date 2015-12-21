<?php
namespace infrajs\path;

use infrajs\once\Once;
use infrajs\nostore\Nostore;

class Path {

	public static $conf = array(
		'sefurl' => false,
		'data' => 'data/',
		'cache' => 'cache/',
		'fs' => true,
		'search' => array(
			'vendor/infrajs/',
			'vendor/components/',
			'bower_components/'
		),
		'clutch' => array()
	);
	
	/**
	 * Path::init($query)
	 * $query может взять из QUERY_STRING или из URI_REQUEST если используется modrewrite
	 * Если в $query начинается с символов ./ или -~! будет проверка файла на доступность и переадресация на него
	 **/
	public static function init()
	{
		return Once::exec('infrajs::Path::init', function () {
			$sefuri=static::$conf['sefurl'];
			$res=URN::parse();
			$res['request2ch'] = $res['request2'] ? in_array($res['request2']{0}, array('-', '~', '!')) : false;
			if ($sefuri) {
				
				
				/*
					Ситуация
					site.ru/adsf?-admin/
					site.ru/sadf?vendor/infrajs/admin/
					site.ru/asdf?vendor/infrajs/admin/index.php
				*/
				
				if ( $res['request2ch'] || Path::theme($res['request2']) ) {
					if($res['param2']) Path::redirect($res['request2'].'?'.$res['param2']);
					else Path::redirect($res['request2']);
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
				}
				
				
				//exit;
				//$res['request2dir'] = Path::isdir($res['request2']);
				//$res['request2ext'] = Path::getExt($res['request2']);
				
				$res['requestdir'] = Path::isdir($res['request']);

				if ($res['request']) {
					
					if ( $res['requestch'] ) {
						//файл не проверяем. отсутствует всёравно идём в go
						$query = $res['query'];
						if($res['requestdir']){
							$p=explode('?', $res['query'], 2);
							$p[0] .='index.php';
							$query=implode('?', $p);
						}
						Path::go($query);
						exit;
					} else {
						$file=Path::theme($res['request']);
						if($file) { //Если файл отсутствует проходим дальше
							if($res['requestdir']){
								$p=explode('?', $res['query'], 2);
								$p[0] .='index.php';
								$file=implode('?', $p);
							}

							if(Path::theme($file)) {
								Path::go($file);
							}
						}
					}
				}				
				return $res['query'];
			} else {

				$file=Path::theme($res['request2']);
				if($file||$res['request2ch']) {
					if (Path::isdir($res['request2'])) {
						if($res['param2']) Path::go($res['request2'].'index.php?'.$res['param2']);
						else Path::go($res['request2'].'index.php');
					} else if($file) {
						Path::go($res['param']);
					}
				}
						
				return $res['param'];
			}
		});
	}
	private static function redirect($src)
	{
		Nostore::pub();
		$root=URN::getRoot();
		header('Location: ./'.$root.$src, true, 301);
		exit;
	}
	public static function go($query)
	{

		$query=Path::theme($query);

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
		if(strpos(realpath($p[0]), realpath('./')) !== 0) { //Проверка что доступ к внутреннему ресурсу
			http_response_code(403);
			exit;
		}

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
		if (!$conf['sefurl']) return $query;
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
		$_REQUEST = array_merge($_GET, $_POST);
		$_SERVER['QUERY_STRING'] = $getstr;

		include $path; //После подключения скрипта работа останавливается. Возвращать старые значения не нужно.
		return true;
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
			$conf = Path::$conf;
			if (!$str) return false;
			

			$is_fn = (mb_substr($str, mb_strlen($str) - 1, 1) == '/' || in_array($str,array('-', '~', '!'))) ? 'is_dir' : 'is_file';
			

			$ch=mb_substr($str, 0, 1);
			if ($ch == '~') {
				$str = mb_substr($str, 1);
				$str = Path::tofs($str);
				if ($is_fn($conf['data'].$str)) return $conf['data'].$str;
			} else if ($ch == '-') {
				$str = mb_substr($str, 1);
				$str = Path::tofs($str);
				
				$p=explode('/', $str); //file.ext folder/ folder/file.ext folder/dir/file.ext
				if(sizeof($p)>1){
					if(!empty($conf['clutch'][$p[0]])) {
						foreach ($conf['clutch'][$p[0]] as $dir) {
							if ($is_fn($dir.$str)) return $dir.$str;
						}
					}
				}
				
				if ($is_fn($str)) return $str; //Корень важней search, clutch важней корня

				foreach ($conf['search'] as $dir) {
					if ($is_fn($dir.$str)) return $dir.$str;
				}

			} else if ($ch == '!') {
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
		if(!$src) return false;
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
		if (!is_file('vendor/autoload.php')) throw new \Exception("You should setting chdir() on site root directory with vendor/ folder"); 
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
		if ($ch == '-') throw new \Exception('Symbol - contain multiple paths and cant be resolving without request to the filesystem. Use theme() or fix src');
		else if($ch == '~') return static::$conf['data'].substr($src, 1);	
		else if($ch == '!') return static::$conf['cache'].substr($src, 1);	
		return $src;
	}
	public static function pretty($src) 
	{
		if (!$src) return $src;
		$conf=static::$conf;
		
		$path = str_replace('/', '\/', $conf['data']);
		$src = preg_replace('/^'.$path.'/', '~', $src);

		$path = str_replace('/', '\/', $conf['cache']);
		$src = preg_replace('/^'.$path.'/', '!', $src);

		foreach($conf['search'] as $path) {
			$path = str_replace('/', '\/', $path);
			$src = preg_replace('/^'.$path.'/', '-', $src);
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
		$args=array($path);
		Once::exec('Path::req', function($path) {
			$rpath = Path::theme($path);
			if (!$rpath) {
				echo '<pre>';
				throw new \Exception('Path::req - не найден путь '.$path);
			}
			require_once $rpath;//Просто require позволяет загрузить самого себя. А мы текущий скрипт не добавляем в список подключённых
		}, $args);
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
