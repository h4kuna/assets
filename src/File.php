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

	/** @var string */
	private $hostUrl;

	/** @var string */
	private $basePath;

	public function __construct($rootFs, Http\UrlScript $url, CacheAssets $cache)
	{
		$this->rootFs = $rootFs;
		$this->cache = $cache;
		$this->url = $url;
	}

	public function createUrl($file)
	{
		$host = $this->getBasePath();
		if (substr($file, 0, 2) == '//') {
			$host = $this->getHostUrl() . '/';
			$file = substr($file, 2);
		}

		return $host . $file . '?' . $this->cache->load($this->rootFs . DIRECTORY_SEPARATOR . $file);
	}

	private function getHostUrl()
	{
		if ($this->hostUrl === NULL) {
			$this->hostUrl = $this->url->getHostUrl();
		}

		return $this->hostUrl;
	}

	private function getBasePath()
	{
		if ($this->basePath === NULL) {
			$this->basePath = $this->url->getBasePath();
		}
		return $this->basePath;
	}

}
