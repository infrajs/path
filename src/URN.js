( function () {

/**
 * schema:://host/site.com/(request?(request2[&?]param2) param) query
 * request2 не содержит знак = до первого символа /
 **/
	var URN = {
		explode: function (separator, str, limit)
		{
		    var arr = str.split(separator);
		    if (limit && arr.length>1) arr.push( arr.splice(limit-1).join(separator) );
		    return arr;
		},
		str_repeat: function (str, len)
		{	// Repeat a string
			var res = '';
			for (var i = 0; i < len; i++){
				res += str;
			}
			return res;
		},
		parse: function(query)
		{
			return Once.exec('infrajs::URN::parse '+query, function () {
				if (typeof(query)=='undefined') query = URN.getQuery();
				
				var res={query: query};
				var p = URN.explode('?', query, 2);
				res['request']=p[0];
				if (p.length==2) {
					res['param'] = p[1];
				} else {
					res['param'] = '';
				}
				//?(catalog&m=asdf) - /(catalog?m=asdf)
				var amp1=URN.explode('?', res['param'], 2);
				var amp2=URN.explode('&', res['param'], 2);
				amp=amp1[0].length<amp2[0].length?amp1:amp2;//Кто раньше встретится ? или &

				var eq = URN.explode('=', amp[0], 2);

				var sl = URN.explode('/', eq[0], 2);
				if (eq.length !== 1 && sl.length === 1) {
					//В первой крошке нельзя использовать символ "=" для совместимости с левыми параметрами для главной страницы, которая всё равно покажется
					res['request2'] = '';
					res['param2'] = res['param'];
					
				} else {
					res['request2'] = amp[0];
					res['param2'] = amp.length==2 ? amp[1] : '';
					
				}
				//Вычитаем из uri папки которые также находятся в видимости вебсервера
				//Чтобы получить на какую глубину надо отойти от текущего uri чтобы попасть в корень вебсервера
				var req=URN.explode('/', res['request']);
				var deep=req.length-1;

				res['root']=URN.str_repeat('../', deep);
				return res;
			});
		},
		getRoot: function ()
		{
			var query = URN.getQuery();
			var res = URN.parse(query);
			return res['root'];
		},
		getAbsRoot: function ()
		{
			var a = URN.analize();
			return a['root'];
		},
		getQuery: function ()
		{
			var a =URN.analize();
			return a['query'];
		},
		analize: function ()
		{
			var query = decodeURI(window.location.pathname.substr(1));
			if (location.search) query+=decodeURI(location.search);
			return { 'root': '/', 'query': query };
		}
	}
	window.URN=URN;
})();