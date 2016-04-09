# Path - общая система файловой адресации для сервера и клиента
**Disclaimer:** Module is not complete and not ready for use yet.

## Принципы и порядок работы с адресом
0. Корнем проекта является папка содержащая файл - ```vender/autoload.php```
0. Адрес записывается относительно корня проекта и в данных для браузера, и в данных для сервера. 
0. Недопустимо хранение и обработка абсолютных путей и путей содержающих последовательности "../", "./", "//", "\"
0. Слэш в конце означает только папку. Отстутствие слэша в конце означает только файл.
0. Используется только прямой слэш - "/"
0. Строку-адрес можно использовать как уникальный идентификатор файла. 
0. Адрес может содержать параметры после "?" в этом случае нужно отдельно выполнять ```explode('?', $src, 2)``` при использовании в стандартных php функциях для работы с файловой системой.
0. Есть предопределённая **папка c данными** проекта, которая не попадает в репозиторий системы контроля версий, может содержать credentials и использоваться для хранения данных формируемых во врмя работы проекта. Доступна для записи php. По умолчанию адрес папки с данными ```data/```. Короткое обозначение - символ **"~"**
0. Есть предопределённая **папка с кэшем**. Папка не загружается в репозиторий. Доступна для записи php. По умолчанию адрес папки с кэшем ```cache/```. Короткое обозначение - символ **"!"**
0. Есть предопределённые **папки cо скриптами**, в которых будет осуществляться поиск файла при наличии в начале адреса символа **"-"**. По умолчанию это ```vendor/infrajs/, vendor/components/, bower_components/, ./```.
0. Адрес записывается в наиболее короткой форме и содержит принятые сокращения "-", "~", "!". 
0. Функции не меняют форму адреса, используемые в нём символы или его части и если необходимо генерируют только свою часть адреса и следят только за её правильностью. 
0. Функции исходят из того, что форма получаемого адреса соответствует указанным принципам и не проверяют это.
0. Анализ формы строки адреса и её изменение может быть в момент получения адреса от пользователя или от системы незнакомой с указанными принципами.
0. Работа с файловой системой на запись не должна выполняться при наличии ключа ```Path::$conf['fs'] = false```. Зависимости работающие с системой учитывают этот ключ. Если зависимая библиотека не может работать без файловой системы, то, например, генерируется обычное исключение... throw new \Exception('Я не могу работать без файловой системы');
0. Рабочей дирректорией в файлах php, где бы они не находились, является корень проекта. Рабочая директория устанавливается с помощью ```chdir()```. Скрипт не полагается на своё фактическое расположение и содержит проверку своего расположения по файлу ```vendor/autoload.php```.
0. Перед использование адреса в стандартных php-функциях работающих с файловой системой необходимо пропускать адрес через ```$src=Path::theme($src)```. Функция проверяет наличие указанного файла или папки и возвращает адрес без специальных сокращений. Использовать в стандартных функция можно путь, который эта функция вернула. Path::theme выполняет роль стандартных is_file is_dir file_exists. Папка или файл определяется наличием слэша в конце адреса.
0. Для удаление специальных символов "-", "~", "!" из адреса без обращения к файловой системе предназначена функция ```$src=Path::resolve($src)``` для спецсимвола "-" выполнить эту операцию невозможно, будет сгенерировано исключение.

## Проверка и установка рабочей директории
```php
if (!is_file('vendor/autoload.php')) {
	chdir('../../../'); //Согласно фактическому расположению файла
	require_once('vendor/autoload.php');
}

## Требуется настройка modrewrite в .htaccess
Все запросы для которых нет файла перенаправляются на обработчик vendor/infrajs/path/index.php
```
	RewriteEngine on
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*)$ vendor/infrajs/path/index.php [L,QSA]
``` 

Если сайт использует сторонний контроллер и перенаправлять все запросы нельзя нужно настроить более точное условие и перенаправлять на обработчик только запросы начинающиеся со специальных символов [-~!]
TODO: добавить код точной переадресации

```
## Path конфиг
В ```Path::$conf``` указывается куда ведут принятые сокращения "-", "~", "!":

* где искать скрипты - search (-),
* где папка данных - data (~), 
* где папка кэша - cache (!). 

По умолчанию Path::$conf:
```json
{
	"data": "data/",
	"cache": "cache/",
	"fs": true,
	"search":[
		"vendor/infrajs/",
		"vendor/components/",
		"bower_components/"
	],
	"clutch": {}
);
```

### Расширяемость clutch
Одно расширение, может содержать файлы для подмены другого расширения 
```json
	"clutch":{
		"catalog":["vendor/infrajs/cards/"]
	}
```
Файл "-catalog/some.php" возьмётся из папки vendor/infrajs/cards/catalog/some.php если такой файл там будет иначе будет использоваться файл vendor/infrajs/catalog/some.php
При разрешении адреса начинающегося с символа "-" корень проекта имеет наивысший приоритет, за которым следует папка с данными "~" и затем идут папки ```conf.search``` начиная с последней. Первый путь в ```conf.search``` имеет наименьший приоритет.

## Примеры
* ```site.ru/~mypic.jpg``` - указывает на файл ```site.ru/data/mypic.jpg```
* ```site.ru/-path/test.jpg``` - указывает на файл ```site.ru/vendor/infrajs/path/test.jpg```

## API
```php
$query = Path::init(); //$query содержит запрос для которого не нашлось решения иначе выполнится exit;
echo Path::theme('~mypic.jpg'); //если файл есть "data/mypic.jpg" иначе false
echo Path::resolve('~mypic.jpg'); //всегда "data/mypic.jpg"
Path::req('-path/index.php'); //Аналог require_once с поддержкой спецсимволов
Path::reqif('-path/index.php'); //Не приводит к ошибке если файл отсутствует
echo Path::toutf($str); //Конвертирует строку в кодировку UTF-8
echo Path::tofs($str); //Конвертирует строку в кодировку файловой системы cp1251 под windows, depricated, используется при использовании кирилицы вименах файлов
echo Path::encode($str); //Ковертирует строку в последовательность которую можно использовать в имени файла - удаляются запрещённые символы
echo Path::getExt($str); //Возвращает расширение файла
echo Path::mkdir($str); //Создать папку если fs:true
echo Path::isdir($str);
echo Path::getQuery();//Возвращает текущий запрос
echo Path::pretty('data/mypic.jpg'); //антоним resolve. Результат "~mypic.jpg"
Path::fullrmdir($path, $sefldelete); //Очищает дирректорию и если второй аргумент true то дирректория удаляется
```

Path выполняет узкий набор функций по нахождению файлов и декларации правил работы с адресом. 
Для обработки запросов вида ```/contacts``` ```/about``` используется отедльное расширение [infrajs/controller](https://github.com/infrajs/controller).

# Работа с [infrajs/config](https://github.com/infrajs/config)
Во всех других расширениях ищется ключ в конфиге ```pathsearch```.
```pathsearch``` расширяет область поиска, указанные в нём пути добавляются в конец ```conf.search```. Спецсимвол "-" будет искать совпадения по дополнительным адресам. 
Необходимо для использования незарегистрированный vendor'ов, который поддерживают работу с infrajs/path и указаны как зависимости в composer.json.