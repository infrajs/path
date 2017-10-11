if(!window.infra) infra = {};
window.Path = {
	theme: function (path) {
		if (/^http/.test(path)) return path;
		if (/^\//.test(path)) return path;
		return '/'+path;
	},
	encode: function (str) //forFS
	{
		//Начинаться и заканчиваться пробелом не может
		//два пробела не могут идти подряд
		//символов ' " /\#&?$ быть не может удаляются
		//& этого символа нет, значит не может быть htmlentities
		//символов <> удаляются из-за безопасности
		//В адресной строке + заменяется на пробел, значит и тут удаляем
		//Виндовс запрещает символы в именах файлов  \/:*?"<>|
		//Точка (.) Используется в скртиптах name.prop.value и такое значени может браться из адреса. pro.p.value точка в имени поломает это
		//% приводит к ошибке malfomed URI при попадании в адрес так как там используется decodeURI
		//Пробельные символы кодируются в адресе и не приняты в файловой системе, но из-за совместимости пока остаются. Папки каталога давно созданы и нельзя изменить логику, так как папки перестанут совпадать с именем
		//() нужно убрать, чтобы работали jquery селекторы

		var conf = Config.get('path');

		str = str.replace(/[\'\`"\.,№\+%\*<>\‐\-\'"\|\:\/\\\\#\!\?\$&\s]/g,' ');
		if (!conf.parenthesis) str = str.replace(/[\(\)]/g,' ');

		str = str.replace(/^\s+/g,'');
		str = str.replace(/\s+$/g,'');
		str = str.replace(/\s+/g,' ');

		var conf = Config.get('path');
		//if (!conf.space) 
			str = str.replace(/\s/g, '-');
		if (str.lenght > 50) console.error('Слишком длинная строка Path.encode',str);
		return str;
	}
}
