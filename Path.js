if(!window.infra) infra = {};
window.Path = {
	theme: function (path) {
		if (/^http/.test(path)) return path;
		if (/^\//.test(path)) return path;
		return '/'+path;
	},
	encode: function (str, space) //forFS
	{
		//Описание в php файле

		var conf = Config.get('path');
		str = str.replace(/[\+]/g,'p');
		str = str.replace(/[\'\`"\.×,№%\*<>\‐\-\'"\|\;\:\/\\\\#\!\?\$&\s]/g,' ');
		if (!conf.parenthesis) str = str.replace(/[\(\)]/g,' ');

		str = str.replace(/^\s+/g,'');
		str = str.replace(/\s+$/g,'');
		str = str.replace(/\s+/g,' ');

		var conf = Config.get('path');
		//if (!conf.space) 
		if (!space) str = str.replace(/\s/g, '-');
		if (str.lenght > 50) console.error('Слишком длинная строка Path.encode', str);
		return str;
	}
}
