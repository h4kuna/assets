<?php

namespace h4kuna\Assets;

use Nette\Http;

class File
{

	/** @var string */
	private $rootFs;

	/** @var CacheAssets */
	private $cache;

	/** @var Http\Url */
	private $url;

	public function __construct($rootFs, Http\UrlScript $url, CacheAssets $cache)
	{
		$this->rootFs = $rootFs;
		$this->cache = $cache;
		$this->url = $url;
	}

	public function createUrl($file)
	{
		$host = $this->url->getBasePath();
		if (substr($file, 0, 2) == '//') {
			$host = $this->url->getHostUrl() . '/';
			$file = substr($file, 2);
		}

		return $host . $file . '?' . $this->cache->load($this->rootFs . DIRECTORY_SEPARATOR . $file);
	}

}
