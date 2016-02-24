<?php
namespace infrajs\path;

$path = Path::$conf;
if (!is_dir($path['cache'])) {
	mkdir($path['cache']);
}
?>