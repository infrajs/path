<?php
	$res=$_GET;
	$res['hello']='world';
	echo '<pre>';
	print_r($res);