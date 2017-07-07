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
	tempDir: %tempDir% # If you want change temp dir.
	wwwTempDir: %wwwDir/temp% # here is place where move assets from 3rd library (from vendor/ etc.)
	externalAssets:
	    - %appDir%/../vendor/nette/nette.js # save to %wwwTempDir%/nette.js
        'ext/nette2.4.js': %appDir%/../vendor/nette/nette.js # save to %wwwTempDir%/ext/nette2.4.js

        # download from external source, this is experimental!
		- http://example.com/foo.js # save to %wwwTempDir%/foo.js
		'sha256-secure-token': http://example.com/foo.js # check if is right file
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

### Assets
Here is object whose can have dependency anything and collect css and js files for render to template.
```php
/* @var $assets \h4kuna\Assets\Assets */
$assets->addJs('ext/nette2.4.js', ['async' => TRUE]);
echo (string) $assets->renderJs();
```
render this
```html
<script src="/temp/ext/nette2.4.js?456789" async></script>
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
			$cache->load(self::replaceSlashOnWindows($file));
		}
	}


	private static function replaceSlashOnWindows(SplFileInfo $file)
	{
		static $isWindows;
		if ($isWindows === NULL) {
			$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
		}

		if ($isWindows) {
			return str_replace('\\', '/', $file->getPathname());
		}
		return $file->getPathname();
	}
}
```
