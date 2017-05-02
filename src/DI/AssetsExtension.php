<?php

namespace h4kuna\Assets\DI;

use h4kuna\Assets,
	Nette\DI as NDI;

class AssetsExtension extends \Nette\DI\CompilerExtension
{

	private $defaults = [
		'debugMode' => '%debugMode%',
		'wwwDir' => '%wwwDir%',
		'tempDir' => '%tempDir%/cache',
		'cacheBuilder' => NULL
	];

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$cacheAssets = $builder->addDefinition($this->prefix('cache'))
			->setClass(Assets\CacheAssets::class, [$config['debugMode'], $config['tempDir']])
			->setAutowired(FALSE);

		$assetFile = $builder->addDefinition($this->prefix('file'))
			->setClass(Assets\File::class, [$config['wwwDir'], new NDI\Statement('?->getUrl()', ['@http.request']), $cacheAssets]);

		$builder->getDefinition('latte.latteFactory')
			->addSetup('addFilter', ['asset', new NDI\Statement("[?, 'createUrl']", [$assetFile])]);

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
			throw new \InvalidArgumentException('Option cacheBuilder must be class instance of ' . __NAMESPACE__ . '\\ICacheBuilder');
		}

		$cache = new Assets\CacheAssets($config['debugMode'], $config['tempDir']);
		$cache->clear();
		$cacheBuilder->create($cache, $config['wwwDir']);
	}

}
