<?php

namespace h4kuna\Assets\DI;

use h4kuna\Assets,
	Nette\DI as NDI;

class AssetsExtension extends \Nette\DI\CompilerExtension
{

	private $defaults = array(
		'debugMode' => '%debugMode%',
		'wwwDir' => '%wwwDir%',
		'tempDir' => '%tempDir%/cache',
		'cacheBuilder' => NULL
	);

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$cacheAssets = $builder->addDefinition($this->prefix('cache'))
			->setClass('h4kuna\\Assets\\CacheAssets', array($config['debugMode'], $config['tempDir']))
			->setAutowired(FALSE);

		$assetFile = $builder->addDefinition($this->prefix('file'))
			->setClass('h4kuna\\Assets\\File', array($config['wwwDir'], $cacheAssets));

		$builder->getDefinition('latte.latteFactory')
			->addSetup('addFilter', array('asset', new NDI\Statement("array(?, 'createUrl')", array($assetFile))));

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
