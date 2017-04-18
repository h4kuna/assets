<?php

namespace h4kuna\Assets\DI;

use Nette\DI as NDI;

class AssetsExtension extends \Nette\DI\CompilerExtension
{

	private $defaults = array(
		'wwwDir' => '%wwwDir%',
		'tempDir' => '%tempDir%'
	);

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$cache = $builder->addDefinition($this->prefix('cache'))
			->setClass('h4kuna\\Assets\\CacheAssets', array($config['tempDir']));

		$assetFile = $builder->addDefinition($this->prefix('file'))
			->setClass('h4kuna\\Assets\\File', array($config['wwwDir'], $cache));

		$builder->getDefinition('latte.latteFactory')
			->addSetup('addFilter', array('asset', new NDI\Statement("array(?, 'createUrl')", array($assetFile))));
	}

}
