<?php declare(strict_types=1);

namespace h4kuna\Assets\DI;

use h4kuna\Assets;
use Nette\DI as NDI;
use Nette\Safe;
use Nette\Schema;
use Nette\Utils;

class AssetsExtension extends NDI\CompilerExtension
{
	/** @var array<string, bool> */
	private $duplicity = [];


	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'debugMode' => Schema\Expect::bool(),
			'wwwDir' => Schema\Expect::string(),
			'tempDir' => Schema\Expect::string(),
			'wwwTempDir' => Schema\Expect::string(),
			'cacheBuilder' => Schema\Expect::mixed(),
			'externalAssets' => Schema\Expect::arrayOf('string'),
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = (array) $this->getConfig();

		$config['wwwDir'] = $this->checkParameter($config, 'wwwDir');
		$config['tempDir'] = $this->checkParameter($config, 'tempDir');
		$config['debugMode'] = $this->checkParameter($config, 'debugMode');
		$config['wwwTempDir'] = $this->checkParameter($config, 'wwwTempDir', $config['wwwDir'] . '/temp');

		$cacheAssets = $this->buildCacheAssets($config['debugMode'], $config['tempDir']);

		$assetFile = $this->buildAssetsFile($cacheAssets, $config['wwwDir'] ?? $builder->parameters['wwwDir']);

		$this->buildAssets();

		$this->registerLatteFilter($assetFile);

		if ($config['externalAssets'] !== []) {
			$this->saveExternalAssets($config['externalAssets'], $config['wwwTempDir']);
		}

		// build own cache
		if ($config['cacheBuilder'] !== null && $config['debugMode'] === false) {
			$this->createAssetsCache($config['cacheBuilder'], $config['debugMode'], $config['tempDir'], $config['wwwDir']);
		}
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


	private static function isHttp(string $file): bool
	{
		return Utils\Strings::match($file, '~^http~') !== null;
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
				throw new Assets\Exceptions\CompareTokensException(sprintf('Expected token: "%s" and actual is: "%s". Hash function is: "%s".', $token, $secureToken, $function));
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
		Safe::copy($file, $path);
		return $path;
	}


	private function createAssetsCache(
		string $cacheBuilderClass,
		bool $debugMode,
		string $tempDir,
		string $wwwDir
	): void
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


	private function buildCacheAssets(bool $debugMode, string $tempDir): NDI\Definitions\ServiceDefinition
	{
		return $this->getContainerBuilder()
			->addDefinition($this->prefix('cache'))
			->setFactory(Assets\CacheAssets::class, [$debugMode, $tempDir])
			->setAutowired(false);
	}


	private function buildAssetsFile(
		NDI\Definitions\Definition $cacheAssets,
		string $wwwDir
	): NDI\Definitions\ServiceDefinition
	{
		return $this->getContainerBuilder()
			->addDefinition($this->prefix('file'))
			->setFactory(Assets\File::class, [
				$wwwDir,
				new NDI\Definitions\Statement('?->getUrl()', ['@http.request']),
				$cacheAssets,
			]);
	}


	private function buildAssets(): void
	{
		$this->getContainerBuilder()
			->addDefinition($this->prefix('assets'))
			->setFactory(Assets\Assets::class);
	}


	/**
	 * @param array<string, mixed> $config
	 * @return mixed
	 */
	private function checkParameter(array $config, string $name, string $default = null)
	{
		if (isset($config[$name])) {
			return $config[$name];
		}

		$builder = $this->getContainerBuilder();
		if (isset($builder->parameters[$name])) {
			return $builder->parameters[$name];
		}

		if ($default === null) {
			throw new Assets\Exceptions\InvalidStateException(sprintf('Parameter %s is required.', $name));
		}

		return $default;
	}


	private function registerLatteFilter(NDI\Definitions\Definition $assetFile): void
	{
		$latteFactory = $this->getContainerBuilder()->getDefinition('latte.latteFactory');
		assert($latteFactory instanceof NDI\Definitions\FactoryDefinition);

		$latteFactory->getResultDefinition()
			->addSetup('addFilter', ['asset', new NDI\Definitions\Statement("[?, 'createUrl']", [$assetFile])]);
	}

}
