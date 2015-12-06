# Path - единая форма адреса
Поддерживаются windows c CP1251 и файловые системы с UTF8

# Принципы работы с адресом
1. Адресация всегда относительно корня и для браузера, и для сервера. Адрес записывается в наиболее короткой форме. Без первого слэша. 
2. Недопустимо хранение и обработка абсолютных путей и путей содержающих последовательности ../ . // \
3. Функции не меняют форму адреса, используемые в нём символы или его части и если необходимо генерируют только свою часть адреса и следят только за её правильностью. 
4. Функции исходят из того что форма получаемого адреса соответствует указанным принципам и строка адреса уникальна. 
5. Изменение формы пути может быть в момент получения от пользователя или от системы незнакомой с указанными принципами.
6. Слэш в конце означает только папку.
7. Отстутствие слэша в конце означает только файл.
8. Правильный слэш прямой "/"
9. Путь может содержать параметры после ? в этом случае нужно отдельно выполнять ```explode('?', $src, 2)```
10. Есть некая папка c данными пользователя, которая не попадает в репозиторий, может содержать credentials и может использоваться для хранения данных, путь до папки указан в Path::$conf['data']. Доступна для записи.
11. Есть некая папка с кэшем. Эта папка может быть удалена полностью в любой момент Path::$conf['cache'] папка не загружается в репозиторий. Доступна для записи.
12. Работа с файловой системой на запись не должна выполняться при наличии ключа Path::$conf['fs'] = false. Path::mkdir учитывает этот ключ.


# Поддержка системы infrajs
1. В скрипте работающего standalone есть условие с изменением текущей дирректории chdir на корень проекта.
```php
if (!is_file('vendor/autoload.php')) {
	chdir('../../../');
	require_once('vendor/autoload.php');
}
```
2. Перед использование адреса в стандартных php функциях необходимо пропускать его через $src=Path::theme($src). Функция проверяет наличие указанного файла или папки. Использовать надо путь, который эта функция вернула. Path::theme выполняет роль стандартных is_file is_dir file_exists папка или файла определяется наличием слэша в конце адреса.
3. Пути до кэша и данных должны основываться на ```Path::$conf['cache']``` и ```Path::$conf['data']``` или использоваться символы ~ * |. После функции Path::theme символы заменяются.

# Работа с путями
В ```Path::$conf``` указывается по каким папкам 
* искать скрипты - search (\*),
* где будет папка данных - data (~), 
* где будет папка кэша - cache (|). 

По умолчанию Path::$conf:
```php
Path::$conf = array(
	'data' => 'data/',
	'cache' => 'cache/',
	'fs' => true,
	'search' => array(
		'vendor/infrajs/',
		'vendor/components/',
		'bower_components/'
	),
	/**
	 * Одно расширение, может подменить файлы другого расширения. 
	 * Записывается так "catalog"=>array("vendor/infrajs/cards/")
	 * Файлы в папке *catalog/ будудут заменены на файлы в vendor/infrajs/cards/catalog/ при наличии
	 **/
	'external' => array()
);
``` 
 
* ```vendor/infrajs/path/?~mypic.jpg``` - редирект на data/mypic.jpg
* ```vendor/infrajs/path/?*path/test.jpg``` - редирект на vendor/infrajs/path/test.jpg

```php
$src = Path::theme('~mypic.jpg'); //data/mypic.jpg
```
