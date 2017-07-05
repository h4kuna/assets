<?php

namespace h4kuna\Assets\DI;

use h4kuna\Assets,
	Nette\DI as NDI,
	Nette\Utils;

class AssetsExtension extends \Nette\DI\CompilerExtension
{

	/** @var array */
	private $duplicity = [];

	/** @var array */
	private $defaults = [
		'debugMode' => '%debugMode%',
		'wwwDir' => '%wwwDir%',
		'tempDir' => '%tempDir%/cache',
		'cacheBuilder' => NULL,
		'wwwTempDir' => '%wwwDir%/temp',
		'externalAssets' => []
	];

	public function loadConfiguration()
	{
		$parameters = $this->getContainerBuilder()->parameters;
		$this->defaults['debugMode'] = $parameters['debugMode'];
		$this->defaults['tempDir'] = $parameters['tempDir'] . '/cache';
		$this->defaults['wwwTempDir'] = $parameters['wwwDir'] . '/temp';

		$config = $this->validateConfig($this->defaults);
		$config['wwwDir'] = $parameters['wwwDir'];
		$builder = $this->getContainerBuilder();

		$cacheAssets = $builder->addDefinition($this->prefix('cache'))
			->setClass(Assets\CacheAssets::class, [$config['debugMode'], $config['tempDir']])
			->setAutowired(FALSE);

		$assetFile = $builder->addDefinition($this->prefix('file'))
			->setClass(Assets\File::class, [$config['wwwDir'], new NDI\Statement('?->getUrl()', ['@http.request']), $cacheAssets]);

		$builder->addDefinition($this->prefix('assets'))
			->setClass(Assets\Assets::class);

		$builder->getDefinition('latte.latteFactory')
			->addSetup('addFilter', ['asset', new NDI\Statement("[?, 'createUrl']", [$assetFile])]);

		if ($config['externalAssets']) {
			$this->saveExternalAssets($config['externalAssets'], $config['wwwTempDir']);
		}

		// build own cache
		if ($config['cacheBuilder'] && $config['debugMode'] === FALSE) {
			$this->createAssetsCache($config);
		}
	}

	private function createAssetsCache(array $config)
	{
		/* @var $cacheBuilder ICacheBuilder */
		$cacheBuilder = new $config['cacheBuilder'];
		if (!$cacheBuilder instanceof ICacheBuilder) {
			throw new Assets\InvalidArgumentException('Option cacheBuilder must be class instance of ' . __NAMESPACE__ . '\\ICacheBuilder');
		}

		$cache = new Assets\CacheAssets($config['debugMode'], $config['tempDir']);
		$cache->clear();
		$cacheBuilder->create($cache, $config['wwwDir']);
	}

	private function saveExternalAssets(array $files, $destination)
	{
		$duplicity = [];
		foreach ($files as $key => $file) {
			if (self::isHttp($file)) {
				$path = $this->fromHttp($file, $key, $destination);
				if ($path === NULL) {
					continue;
				}
				$mtime = self::mtimeHttp($file);
			} else {
				$path = $this->fromFs($file, $key, $destination);
				$mtime = filemtime($file);
			}

			touch($path, $mtime);
		}
	}

	private function fromFs($file, $newName, $destination)
	{
		if (is_numeric($newName)) {
			$newName = basename($file);
		}
		$path = $destination . DIRECTORY_SEPARATOR . $newName;
		if (!is_file($file)) {
			throw new Assets\FileNotFoundException($file);
		}
		Utils\FileSystem::createDir(dirname($path));
		$this->checkDuplicity($path);
		if (!copy($file, $path)) {
			throw new Assets\DirectoryIsNotWriteableException(dirname($path));
		}
		return $path;
	}

	private static function isHttp($file)
	{
		return preg_match('~^http~', $file);
	}

	private function fromHttp($url, $hash, $destination)
	{
		$name = basename($url);
		$filename = $destination . DIRECTORY_SEPARATOR . $name;
		$this->checkDuplicity($filename);
		if (is_file($filename)) {
			return NULL;
		}

		$content = @file_get_contents($url);
		if (!$content) {
			throw new Assets\DownloadFaildFromExternalUrlException($url);
		}

		if (!file_put_contents($filename, $content)) {
			throw new Assets\DirectoryIsNotWriteableException(dirname($filename));
		}

		if (!is_numeric($hash)) {
			list($function, $token) = explode('-', $hash, 2);
			$secureToken = base64_encode(hash($function, $content, TRUE));
			if ($secureToken !== $token) {
				throw new Assets\CompareTokensException('Expected token: ' . $token . ' and actual is: ' . $secureToken . '. Hash function is: "' . $function . '".');
			}
		}

		return $filename;
	}

	private function checkDuplicity($filename)
	{
		if (isset($this->duplicity[$filename])) {
			throw new Assets\DuplicityAssetNameException($filename);
		}
		$this->duplicity[$filename] = TRUE;
	}

	private static function mtimeHttp($url)
	{
		foreach (get_headers($url) as $header) {
			if (!preg_match('~Last-Modified: (?P<date>.*)~', $header, $find)) {
				continue;
			}

			return \DateTime::createFromFormat('D, d M Y H:i:s T', $find['date'])->format('U');
		}
		throw new Assets\HeaderLastModifyException('Header Last-Modified not found for url: "' . $url . '". You can\'t use this automaticaly download. Let\'s save manualy.');
	}

}
