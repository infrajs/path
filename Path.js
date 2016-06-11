window.Path = {
	theme: function (path) {
		if (/^http/.test(path)) return path;
		if (/^\//.test(path)) return path;
		return '/'+path;
	}
}