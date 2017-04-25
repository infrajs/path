<?php
use infrajs\path\Path;

Path::mkdir(Path::$conf['cache']);
Path::mkdir(Path::$conf['data']);
Path::mkdir(Path::$conf['auto']);