<?php
namespace infrajs\path;

use infrajs\once\Once;

/**
 * schema:://host/site.com/(request?(request2[&?]param2) param) query
 * request2 не содержит знак = до первого символа /
 **/
class URN {
	public static function parse($query = null)
	{
		return Once::exec('infrajs::URN::parse '.$query, function () use ($query) {
			if (is_null($query)) $query = static::getQuery();
			
			$res=array('query'=>$query);
			$p = explode('?', $query, 2);
			$res['request']=$p[0];
			if (isset($p[1])) {
				$res['param'] = $p[1];
			} else {
				$res['param'] = '';
			}
			$amp = preg_split('/[&\?]/', $res['param'], 2);
			$eq = explode('=', $amp[0], 2);

			$sl = explode('/', $eq[0], 2);
			if (sizeof($eq) !== 1 && sizeof($sl) === 1) {
				//В первой крошке нельзя использовать символ "=" для совместимости с левыми параметрами для главной страницы, которая всё равно покажется
				$res['request2'] = '';
				$res['param2'] = $res['param'];
				
			} else {
				$res['request2'] = $amp[0];
				$res['param2'] = isset($amp[1]) ? $amp[1] : '';
				
			}


			//Вычитаем из uri папки которые также находятся в видимости вебсервера
			//Чтобы получить на какую глубину надо отойти от текущего uri чтобы попасть в корень вебсервера
			$req=explode('/', $res['request']);
			$deep=sizeof($req)-1;

			$res['root']=str_repeat('../', $deep);


			return $res;
		});
	}
	public static function getRoot()
	{
		$res = static::parse();
		return $res['root'];
	}
	public static function getAbsRoot()
	{
		$a=static::analize();
		return $a['root'];
	}
	public static function getQuery()
	{
		$a=static::analize();
		return $a['query'];
	}
	public static function analize()
	{
		return Once::exec('URN::getQuery', function () {
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
			$root = implode('/', array_reverse($try));
			if($root) $root = '/'.$root;
			$root .= '/';

			$req=array_slice(array_reverse($uri),sizeof($try));
			$req=implode('/',$req);
			

			$query=urldecode($_SERVER['QUERY_STRING']);
			
			if ($query) {
				if ($req) $query = $req.'?'.$query;
				else $query = '?'.$query;
			} else {
				if ($req) $query = $req;
				else $query = '';
			}
			
			return array('root'=>$root, 'query'=>$query);
		});
	}
}