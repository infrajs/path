<?php
namespace infrajs\path;
use infrajs\once\Once;
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
	public static $exec = false;
	public static function init()
	{

		//	site.ru/(req/?param) query
		$query=Path::getQuery();

		$root=static::getRoot();

		$req=static::getRequest();
		$param=urldecode($_SERVER['QUERY_STRING']);
	

		$conf=static::$conf;	
		$res=static::analyze($param);
		$queryfile = Path::theme($res['query']);


		if(static::$exec&&$conf['sefurl']){
			if(static::$exec==$query) {
				/**
				 * При включенном sefurl 
				 * Рекурсия появляется при обращении самого к себе -path/
				 * modrewrite файл не найдёт и последует обращение к обработчику
				 * к "path/"?-path Обработчик найдёт php файл и запустит его подменив окружение 
				 * кроме REQUEST_URI который так и будет содержать -path/
				 * Подменять $_SERVER['REQUEST_URI'] нельзя так как как в скриптах нужно знать 
				 * правильный относительный путь от реального текущего запроса для вывода html и для header location
				 * по этому на второй интерации цикла в static::$exec видим обработку всё тогоже $query с $req. 
				 * делаем редирект в корень на адрес без req
				 *
				 * При sefurl=false  вернёт 404 
				 * при ?-path/?-path или vendor/infrajs/path/?-path/?-path будет конечная цепочка инклудов, 
				 * Проигнорировать инклуд заменить на редирект нельзя так как не известно будет ли в нём вызов Path::init()
				 * так как $_GET в отличие от $_SERVER['REQUEST_URI'] корректно подменяется для каждого следующего.
				 **/
				//Убираем из $query $req составляющую (путь)
				$query=urldecode($_SERVER['QUERY_STRING']);

				//ТАкое бывает только для Php файлов
				
			}
		}
		static::$exec=$query;
		

		//Проверить что запрос соответствует режиму работы sefurl
		if (static::$conf['sefurl']&&$queryfile) {
			/**
			 * Проблема /-admin/?login и /-admin/?-tester/
			 * Редирект произойдёт только для -tester/ Так как такой файл будет найден
			 **/
			//Редирект на адресо со слэшом
			$res=static::analyze($param);
			if ($res['query']) {
				header('Location: ./'.$root.$res['query'].$res['params']);
				exit;
			}
			//}
		} 

		if (!static::$conf['sefurl']) {
			//Редирект на адрес с вопросом. Если $req не найден
			//Поверяем Path::theme($req) так как обращение к файлу с Path::init может быть напрямую. И не требуется уходить с него.
			if($req&&!Path::theme($req)) { //Если есть какой-нибудь запрос в чати пути с папками и файлом
				$param=urldecode($_SERVER['QUERY_STRING']);
				$res=static::analyze($param);
				if ($res['query']) {
					//echo 'Location: ./'.$root.$res['query'].$res['params'];
					header('Location: ./'.$root.$res['query'].$res['params']);
					exit;
				}
				if ($query) $query='&'.$query;
				header('Location: ./'.$root.'?'.$req.$query);
				exit;
			}
		}

		if ($query) {
			$ch=$query{0};
			if ( in_array($ch, array('-', '~', '!')) ) {
				//файл не проверяем. отсутствует всёравно идём в go
				if(Path::isdir($query)){
					$p=explode('?', $query, 2);
					$p[0] .='index.php';
					$query=implode('?', $p);
				}
				Path::go($query);
				exit;
			} else {

				$file=Path::theme($query);
				if($file) { //файл отсутствует проходим дальше
					if(Path::isdir($file)){
						$p=explode('?', $file, 2);
						$p[0] .='index.php';
						$file=implode('?', $p);
					}
					if($file) {
						Path::go($file);
						exit;
					}
				}
			}
		}
		return $query;
	}
	/**
	 * Разбираем строку, ест ли в ней строка запроса и отдельно строка параметров
	 * Строкой запроса считается часть в начале параметров если она не содержит знак = и это имя не содержит слэш
	 **/
	private static function analyze($query)
	{
		$amp = explode('&', $query, 2);

		$eq = explode('=', $amp[0], 2);
		$sl = explode('/', $eq[0], 2);
		if (sizeof($eq) !== 1 && sizeof($sl) === 1) {
			//В первой крошке нельзя использовать символ "=" для совместимости с левыми параметрами для главной страницы, которая всё равно покажется
			$params = $query;
			$query = '';
		} else {
			$params = (string) @$amp[1];
			$query = $amp[0];
		}
		if($params)$params='?'.$params;
		return array(
			'query'=>$query,
			'params'=>$params
		);
	}
	public static function go($query)
	{
		$query=Path::theme($query);
		if (!$query) {
			http_response_code(404);
			return false;
		}

		$p=explode('?', $query, 2);
		if ($p[0] && (preg_match("/\/\./", $p[0]) || ($p[0]{0} == '.' && $p[0]{1} != '/'))) {
			http_response_code(403); //Forbidden
			return false;
		}

		

		if(strpos(realpath($p[0]), realpath('./')) !== 0) { //Проверка что доступ к внутреннему ресурсу
			http_response_code(403);
			return false;
		}
		//Узнать текущий путь браузера можно из REQUEST_URI, но узнать какая из папок в этом адресе является корнем проекта невозможно. 
		//Эта задача решаема только для частных случаев.
		//В нашем случае мы полагаем либо к файлу было прямое обращение по месту расположения (site/vendor/infrajs/path/)
		//и Path::$root='../../../' либо файл запущен из корня сайта Path::$root='./' (site/)
		//Соответственно текущий файл может быть подключен только в корень проекта (chdir тогда не меняется).

		//Альтернативный вариант полагать, что корень сервера совпадает с корнем проекта тогда работал путь '/'.$src но такого соглашения нет.
		$ext = static::getExt($query);
		
		
		if ($ext=='php') return static::inc($query);
		$isdir = static::isdir($query);
		if ($isdir) return static::inc($query);

		header('Location: '.static::getRoot().$query);
		return true;
	}
	public static function isdir($src){
		$p=explode('?',$src, 2);
		$path=$p[0];
		if($path[strlen($path)-1]=='/')return true;
		return false;
	}
	public static function getQuery()
	{
		$conf=static::$conf;
		$query=urldecode($_SERVER['QUERY_STRING']);
		if (!$conf['sefurl']) {
			return $query;
		}
		$req=static::getRequest();
		if(!$req)$req=$query;
		else if($query)$req.='?'.$query;

		return $req;
		
	}
	public static function getRoot()
	{
		/**
		 * Мы знаем где корень проекта getcwd(), но не знаем где корень вебсервера!
		 * SCRIPT_NAME это то что исполняется(точка входа с точки зрения сервера)
		 * PHP_SELF это то что было вызвано (точка входа с точки зрения клиента). 
		 * REQUEST_URI почти PHP_SELF более понятный, так как буквально означает строку адреса в браузере
		 * Заголовок location сработает относительно клиентского REQUEST_URI
		 *
		 * Вычесляем относительный путь от REQUEST_URI до Корня проекта (getcwd()) чтобы адреса от корня проекта работали и при наличиии текущего REQUEST_URI в адресной строке. 
		 * Суть проблемы: 
		 * /xampp/httdoc/git/infrajs/ - корень проекта
		 * /git/infrajs/test/check/?asdf - REQUEST_URI строка запроса
		 * ../../ - правильный результат. К нему можно добавить любой путь и он будет сработает в браузере благодаря этой поправки.
		 * Фактически это путь для <base> когда открыт текущий REQUEST_URI чтобы все ссылки работали хотя указаны от корня проекта.
		 **/
		$req=static::getRequest();
		$req=explode('/', $req);
		//Вычитаем из uri папки которые также находятся в видимости вебсервера
		//Чтобы получить на какую глубину надо отойти от текущего uri чтобы попасть в корень вебсервера
		$deep=sizeof($req)-1;
		
		$root=str_repeat('../', $deep);
		return $root;
	}
	/**
	 * Возвращает запрос, который есть сейчас в адресной строке от корня проекта
	 * Хотябы один пустой есть всегда
	 **/
	public static function getRequest()
	{
		$uri=$_SERVER['REQUEST_URI'];
		$p=explode('?',$uri,2);
		$uri=urldecode($p[0]);
		
		$proj=getcwd().'/';
		
		$p=explode(':',$proj,2);
		if (sizeof($p)!=1) {
			$proj=$p[1];
		}
		$proj=str_replace('\\','/',$proj);


		$proj=explode('/',$proj);
		$uri=explode('/',$uri);
		//Удалить пустые элементы
		
		$proj = array_diff($proj, array(''));

		
		$temp=array_pop($uri);//Последний элемент может быть пустым
		if(sizeof($uri)) {
			$uri = array_diff($uri, array(''));
		}
		$uri[]=$temp;
			
			
		
		/*
			proj
			Array
			(
			    [1] => xampp
			    [2] => htdocs
			    [3] => git
			    [4] => infrajs
			)
			uri
			Array
			(
			    [1] => git
			    [2] => infrajs
			    [3] => vendor
			    [4] => infrajs
			    [5] => tester
			)

		*/
		$uri=array_reverse($uri);
		$proj=array_reverse($proj);
		

		$try=array();
		$r=false;
		foreach($uri as $i=>$u){
			if($uri[$i]!=$proj[0])continue;
			$try=array_slice($uri, $i);
			//В try каждый элемент должен входить в proj
			$r=true;
			foreach($try as $ii=>$t){
				if($proj[$ii]!=$t) {
					$r=false;
					break;
				}
			}
			if($r) break;
			
		}
		if (!$r) $try=array();
		$req=array_slice(array_reverse($uri),sizeof($try));
		$req=implode('/',$req);
		return $req;
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

				if ($is_fn($str)) return $str; //Корень важней search и clutch

				$p=explode('/', $str); //file.ext folder/ folder/file.ext folder/dir/file.ext
				if(sizeof($p)>1){
					if(!empty($conf['clutch'][$p[0]])) {
						foreach ($conf['clutch'][$p[0]] as $dir) {
							if ($is_fn($dir.$str)) return $dir.$str;
						}
					}
				}
				$str = Path::tofs($str);
				
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
