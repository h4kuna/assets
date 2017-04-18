
If you need automaticly invalid browser cache on development machine, use this extension.

Install via composer.
```sh
$ composer require h4kuna/assets
```

PHP: 5.3+

```sh
extensions:
	assetsExtension: h4kuna\Assets\DI\AssetsExtension

assetsExtension:
	version: 2017-04-17 # YYYY-MM-DD # any production flag, like last day or build number

	# optional
	wwwDir: %wwwDir% # Where is your www dir?
	debugMode: %debugMode% # If you need production behavior.
```
How to use in latte. After install extension, you have available filter **asset**.

- $basePath is not need
- path is relative to your wwwDir

```html
<link rel="stylesheet" href="{='css/main.css'|asset}">
<script src="{='js/main.js'|asset}"></script>
```

Output with development mode looks like ``?file mtime``.
```html
<link rel="stylesheet" href="/css/main.css?123456">
<script src="/js/main.js?456789"></script>
```

Output with production mode looks like.
```html
<link rel="stylesheet" href="/css/main.css?2017-04-17">
<script src="/js/main.js?2017-04-17"></script>
```

Absolute path is posible with double slash.
```html
<link rel="stylesheet" href="{='//css/main.css'|asset}">
```