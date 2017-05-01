[![Build Status](https://travis-ci.org/h4kuna/assets.svg?branch=master)](https://travis-ci.org/h4kuna/assets)
[![Downloads this Month](https://img.shields.io/packagist/dm/h4kuna/assets.svg)](https://packagist.org/packages/h4kuna/assets)
[![Latest stable](https://img.shields.io/packagist/v/h4kuna/assets.svg)](https://packagist.org/packages/h4kuna/assets)

If you need automaticly invalid browser cache on development machine, use this extension.

Install via composer.
```sh
$ composer require h4kuna/assets
```

Version 0.1.3 support PHP 5.3. Newer support PHP 5.6+.

How to use
==========
For first step you need only register extension, other parameters are optional. You have available new filter **asset** automaticaly.

```sh
extensions:
	assetsExtension: h4kuna\Assets\DI\AssetsExtension

assetsExtension:
	# optional
	debugMode: %debugMode%
	wwwDir: %wwwDir% # Where is your www dir?
	tempDir: %tempDir% # If you want change temp dir.
```
Advantigies.

- $basePath is not need
- path is relative to your wwwDir
- cache is build if found unknown file
- behavior is same on production and develop machine

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

### Own cache builder - advanced use
This create cache in compile time, default is on fly.

```sh
assetsExtension:
	cacheBuilder: \CacheBuilder
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
