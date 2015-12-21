<?php
namespace infrajs\path;


$query=URN::parse();

echo '<pre>';
print_r($query);


$res=$_GET;
$res['hello']='world';
echo '<pre>';
print_r($res);