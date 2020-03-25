<?php

namespace h4kuna\Assets\DI;

use h4kuna\Assets,
	Nette\DI as NDI,
	Nette\Utils;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use function print_r;
use const DIRECTORY_SEPARATOR;

class AssetsExtension extends NDI\CompilerExtension
{

	/** @var array */
	private $duplicity = [];

	/** @var string|null */
	private $wwwDir;

	/** @var string|null */
	private $wwwTempDir;

	/** @var string|null */
	private $tempDir;

	public function __construct(?string $wwwDir = null, ?string $tempDir = null)
	{
		if ($wwwDir !== null) {
			$this->wwwDir = $wwwDir;
			$this->wwwTempDir = $wwwDir . '/temp';
		}

		if ($tempDir !== null) {
			$this->tempDir = $tempDir . DIRECTORY_SEPARATOR . 'cache';
		}
	}

	public static function createSchema(): Schema
	{
		return Expect::structure([
			'debugMode' => Expect::bool(false),
			'wwwDir' => Expect::string(''),
			'tempDir' => Expect::string(''),
			'wwwTempDir' => Expect::string(''),
			'cacheBuilder' => Expect::mixed(null),
			'externalAssets' => Expect::arrayOf('string')
		]);
	}

	public function getConfigSchema(): Schema
	{
		return self::createSchema();
	}

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = (array) $this->getConfig();

		if ($config['wwwDir'] === '' && $this->wwwDir !== null) {
			$config['wwwDir'] = $this->wwwDir;
		}

		if ($config['tempDir'] === '' && $this->tempDir !== null) {
			$config['tempDir'] = $this->tempDir;
		}

		if ($config['wwwTempDir'] === '' && $this->wwwTempDir !== null) {
			$config['wwwTempDir'] = $this->wwwTempDir;
		}

		$cacheAssets = $builder->addDefinition($this->prefix('cache'))
			->setFactory(Assets\CacheAssets::class, [$config['debugMode'], $config['tempDir']])
			->setAutowired(false);

		$assetFile = $builder->addDefinition($this->prefix('file'))
			->setFactory(Assets\File::class, [
				$config['wwwDir'],
				new \Nette\DI\Definitions\Statement('?->getUrl()', ['@http.request']),
				$cacheAssets
			]);

		$builder->addDefinition($this->prefix('assets'))
			->setFactory(Assets\Assets::class);

		$builder->getDefinition('latte.latteFactory')
			->getResultDefinition()
			->addSetup('addFilter', ['asset', new \Nette\DI\Definitions\Statement("[?, 'createUrl']", [$assetFile])]);

		if ($config['externalAssets']) {
			$this->saveExternalAssets($config['externalAssets'], $config['wwwTempDir']);
		}

		// build own cache
		if ($config['cacheBuilder'] && $config['debugMode'] === false) {
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
		foreach ($files as $key => $file) {
			if (self::isHttp($file)) {
				$path = $this->fromHttp($file, $key, $destination);
				if ($path === null) {
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
		if (!@copy($file, $path)) {
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
			return null;
		}

		$content = @file_get_contents($url);
		if (!$content) {
			throw new Assets\DownloadFaildFromExternalUrlException($url);
		}

		$isSaved = @file_put_contents($filename, $content);
		if (!$isSaved) {
			throw new Assets\DirectoryIsNotWriteableException(dirname($filename));
		}

		if (!is_numeric($hash)) {
			[$function, $token] = explode('-', $hash, 2);
			$secureToken = base64_encode(hash($function, $content, true));
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
		$this->duplicity[$filename] = true;
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
