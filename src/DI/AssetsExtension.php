<?php

namespace h4kuna\Assets\DI;

use Nette\DI as NDI;

/**
 * Use in template
 * {='css/final.css'|asset}
 */
class AssetsExtension extends \Nette\DI\CompilerExtension
{

	private $defaults = array(
		'version' => '1.0',
		'wwwDir' => '%wwwDir%',
		'debugMode' => '%debugMode%'
	);

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();
		$assetFile = $builder->addDefinition($this->prefix('file'))
			->setClass('h4kuna\\Assets\\File', array($config['wwwDir']))
			->addSetup('setDebugMode', array($config['debugMode']))
			->addSetup('setVersion', array($config['version']));


		$builder->getDefinition('latte.latteFactory')
			->addSetup('addFilter', array('asset', new NDI\Statement("array(?, 'createUrl')", array($assetFile))));
	}

}


