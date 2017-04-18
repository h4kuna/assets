
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
	# optional
	debugMode: %debugMode%
	wwwDir: %wwwDir% # Where is your www dir?
	tempDir: %tempDir% # If you want change temp dir.
```
How to use in latte. After install extension, you have available filter **asset**.

- $basePath is not need
- path is relative to your wwwDir
- cache is build if found unknown file

```html
<link rel="stylesheet" href="{='css/main.css'|asset}">
<script src="{='js/main.js'|asset}"></script>
```

Output looks like ``?file mtime``.
```html
<link rel="stylesheet" href="/css/main.css?123456">
<script src="/js/main.js?456789"></script>
```

Absolute path is posible with double slash.
```html
<link rel="stylesheet" href="{='//css/main.css'|asset}">
```

### Own cache builder
This create cache in compile time, default is on fly.

```sh
assetsExtension:
	cacheFactory: \CacheBuilder
```

Use prepared interface
```php
class CacheBuilder implements \h4kuna\Assets\DI\ICacheBuilder
{
	public function create(\h4kuna\Assets\CacheAssets $cache, $wwwDir)
	{
		$finder = Nette\Utils\Finder::findFiles('*')->in($wwwDir . '/config');
		foreach ($finder as $file) {
			/* @var $file \SplFileInfo */
			$cache->load($file->getPathname());
		}

	}
}
```
