<?php
namespace infrajs\path;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');
	require_once('vendor/autoload.php');
}

$src='~hi/world.tpl';
$ans=array();

$src=Path::resolve($src);
$src=Path::resolve($src);
$src=Path::pretty($src);
$src=Path::pretty($src);

if($src!='~hi/world.tpl') return Ans::err($ans,'Error on line:'.__LINE__);


return Ans::ret($ans);