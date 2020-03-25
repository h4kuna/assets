<?php declare(strict_types=1);

namespace h4kuna\Assets\DI;

use h4kuna\Assets;
use Nette\DI as NDI;
use Nette\Safe;
use Nette\Utils;
use Nette\Schema;

class AssetsExtension extends NDI\CompilerExtension
{
	/** @var array<string, bool> */
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


	public static function createSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'debugMode' => Schema\Expect::bool(false),
			'wwwDir' => Schema\Expect::string(''),
			'tempDir' => Schema\Expect::string(''),
			'wwwTempDir' => Schema\Expect::string(''),
			'cacheBuilder' => Schema\Expect::mixed(null),
			'externalAssets' => Schema\Expect::arrayOf('string')
		]);
	}


	public function getConfigSchema(): Schema\Schema
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
			$this->createAssetsCache($config['cacheBuilder'], $config['debugMode'], $config['tempDir'], $config['wwwDir']);
		}
	}


	private function createAssetsCache(string $cacheBuilderClass, bool $debugMode, string $tempDir,string $wwwDir): void
	{
		/* @var $cacheBuilder ICacheBuilder */
		$cacheBuilder = new $cacheBuilderClass;
		if (!$cacheBuilder instanceof ICacheBuilder) {
			throw new Assets\Exceptions\InvalidStateException('Option cacheBuilder must be class instance of ' . __NAMESPACE__ . '\\ICacheBuilder');
		}

		$cache = new Assets\CacheAssets($debugMode, $tempDir);
		$cache->clear();
		$cacheBuilder->create($cache, $wwwDir);
	}


	/**
	 * @param array<string|int, string> $files
	 */
	private function saveExternalAssets(array $files, string $destination): void
	{
		foreach ($files as $key => $file) {
			if (self::isHttp($file)) {
				$path = $this->fromHttp($file, (string) $key, $destination);
				if ($path === '') {
					continue;
				}
				$mtime = self::mtimeHttp($file);
			} else {
				$path = $this->fromFs($file, (string) $key, $destination);
				$mtime = Safe::filemtime($file);
			}

			Safe::touch($path, $mtime);
		}
	}


	private function fromFs(string $file, string $newName, string $destination): string
	{
		if (is_numeric($newName)) {
			$newName = basename($file);
		}
		$path = $destination . DIRECTORY_SEPARATOR . $newName;
		if (!is_file($file)) {
			throw new Assets\Exceptions\FileNotFoundException($file);
		}
		Utils\FileSystem::createDir(dirname($path));
		$this->checkDuplicity($path);
		Safe::copy($file, $path);// todo
		return $path;
	}


	private static function isHttp(string $file): bool
	{
		return Utils\Strings::match($file, '~^http~') !== NULL;
	}


	private function fromHttp(string $url, string $hash, string $destination): string
	{
		$name = basename($url);
		$filename = $destination . DIRECTORY_SEPARATOR . $name;
		$this->checkDuplicity($filename);
		if (is_file($filename)) {
			return '';
		}

		$content = @file_get_contents($url);
		if (!$content) {
			throw new Assets\Exceptions\DownloadFaildFromExternalUrlException($url);
		}

		Safe::file_put_contents($filename, $content);

		if (!is_numeric($hash)) {
			[$function, $token] = explode('-', $hash, 2);
			$secureToken = base64_encode(hash($function, $content, true));
			if ($secureToken !== $token) {
				throw new Assets\Exceptions\CompareTokensException('Expected token: ' . $token . ' and actual is: ' . $secureToken . '. Hash function is: "' . $function . '".');
			}
		}

		return $filename;
	}


	private function checkDuplicity(string $filename): void
	{
		if (isset($this->duplicity[$filename])) {
			throw new Assets\Exceptions\DuplicityAssetNameException($filename);
		}
		$this->duplicity[$filename] = true;
	}


	private static function mtimeHttp(string $url): int
	{
		foreach (Safe::get_headers($url) as $header) {
			$find = Utils\Strings::match($header, '~Last-Modified: (?P<date>.*)~');
			if ($find === null) {
				continue;
			}
			$datetime = \DateTime::createFromFormat('D, d M Y H:i:s T', $find['date']);
			if ($datetime === false) {
				throw new Assets\Exceptions\InvalidStateException('Bad date format for parsing.');
			}
			return (int) $datetime->format('U');
		}
		throw new Assets\Exceptions\HeaderLastModifyException('Header Last-Modified not found for url: "' . $url . '". You can\'t use this automatically download. Let\'s save manually.');
	}

}
