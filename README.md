[![Build Status](https://travis-ci.org/h4kuna/assets.svg?branch=master)](https://travis-ci.org/h4kuna/assets)
[![Downloads this Month](https://img.shields.io/packagist/dm/h4kuna/assets.svg)](https://packagist.org/packages/h4kuna/assets)
[![Latest stable](https://img.shields.io/packagist/v/h4kuna/assets.svg)](https://packagist.org/packages/h4kuna/assets)
[![Coverage Status](https://coveralls.io/repos/github/h4kuna/assets/badge.svg?branch=master)](https://coveralls.io/github/h4kuna/assets?branch=master)

If you need the browser to automatically invalid it's cache, use this extension.

Install via composer:
```sh
$ composer require h4kuna/assets
```

# Changelog
- 1.0.0 supports PHP 7.1+ (strict types)
- 0.1.4 0.1.5 newer versions support PHP of version 5.6 and higher
- 0.1.3 supports PHP 5.3

# How to use

For first step you only need to register the extension, other parameters are optional. You have available the new filter **asset** automatically.

```sh
extensions:
	assetsExtension: h4kuna\Assets\DI\AssetsExtension

assetsExtension:
    # required
    wwwDir: %wwwDir%
    debugMode: %debugMode%
    tempDir: %tempDir%
    
    # optional	
    wwwTempDir: %wwwDir%/temp # here is place where move assets from 3rd library (from vendor/ etc.)
    externalAssets:
        - %appDir%/../vendor/nette/nette.js # save to %wwwTempDir%/nette.js
        'ext/nette2.4.js': %appDir%/../vendor/nette/nette.js # save to %wwwTempDir%/ext/nette2.4.js
        
        # download from external source, this is experimental!
        - http://example.com/foo.js # save to %wwwTempDir%/foo.js
        'sha256-secure-token': http://example.com/foo.js # check if is right file
```

### Advantages:

- $basePath is not needed
- path is relative to your wwwDir
- cache is built when new file found, or if you remove %tempDir%/cache/_assets
- behavior is the same in production and development environment

```html
<link rel="stylesheet" href="{='css/main.css'|asset}">
<script src="{='js/main.js'|asset}"></script>
```

Example output:
``?file mtime``.
```html
<link rel="stylesheet" href="/css/main.css?123456">
<script src="/js/main.js?456789"></script>
```

Printing absolute path to the template can be anabled using double slash:
```html
<link rel="stylesheet" href="{='//css/main.css'|asset}">
```

### Assets
Here is an object that can have dependency anything and collect css and js files for render to template.
```php
/* @var $assets \h4kuna\Assets\Assets */
$assets->addJs('ext/nette2.4.js', ['async' => TRUE]);
echo (string) $assets->renderJs();
```
render this
```html
<script src="/temp/ext/nette2.4.js?456789" async></script>
```

### Custom cache builder - advanced usege
This creates the cache in the compile time. By default, assets cache is build on the fly:

```sh
assetsExtension:
	cacheBuilder: \CacheBuilder
```

Use prepared interface:
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
