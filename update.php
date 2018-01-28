<?php
use infrajs\path\Path;

Path::mkdir(Path::$conf['cache']);
//Path::mkdir(Path::$conf['data']); //Нужно создавать вручную, если надо.
if (Path::theme(Path::$conf['data'])) {
	//Если вручную не создана data значит auto тоже не будет использоваться
	Path::mkdir(Path::$conf['auto']); //Папка для писем с сайта, и разных генерируемых данных, которые нельзя удалять как кэш.
}