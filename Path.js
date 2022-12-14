let Path = {
	theme: function (path) {
		if (/^http/.test(path)) return path;
		if (/^\//.test(path)) return path;
		return '/' + path;
	},
	translit: function (str) {
		var ru = {
			'α': 'a', 'β': 'b', 'γ': 'y', 'δ': 'd', 
			'³': '3', '²': '3',
			'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
			'е': 'e', 'ё': 'e', 'ж': 'j', 'з': 'z', 'и': 'i', 'й': 'y',
			'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
			'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
			'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch', 'ш': 'sh',
			'щ': 'shch', 'ы': 'y', 'э': 'e', 'ю': 'yu', 'я': 'ya'
		}, n_str = [];

		str = str.replace(/[ъь]+/g, '');

		for (var i = 0; i < str.length; ++i) {
			n_str.push(ru[str[i]] || str[i]);
		}

		return n_str.join('');
	},
	encode: function (str, space) //forFS
	{
		//Описание в php файле

		var conf = Config.get('path');
		str = str.replace(/[\+]/g, 'p');
		str = str.replace(/[\˚\'\`"\.×,№%\*<>\‐\-\–\—\'"\|\;\:\/\\\\#\!\?\$&\s]/g, ' ');
		if (!conf.parenthesis) str = str.replace(/[\(\)]/g, ' ');

		if (conf.encodelower || conf.translit) str = str.toLowerCase();
		if (conf.translit) str = Path.translit(str);

		str = str.replace(/^\s+/g, '');
		str = str.replace(/\s+$/g, '');
		str = str.replace(/\s+/g, ' ');

		var conf = Config.get('path');
		//if (!conf.space) 
		if (!space) str = str.replace(/\s/g, '-');

		if (!conf.translit && (str.lenght > conf.encodelimit)) console.error('Слишком длинная строка Path.encode', str);
		return str;
	}
}
window.Path = Path
export { Path }