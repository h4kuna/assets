<?php

namespace h4kuna\Assets;

use Nette\Http;

class File
{

	/** @var string */
	private $rootFs;

	/** @var Http\Url */
	private $url;

	/** @var bool */
	private $debugMode = FALSE;

	/** @var string */
	private $version;

	public function __construct($rootFs, Http\Request $request)
	{
		$this->rootFs = $rootFs;
		$this->url = $request->getUrl();
	}

	public function setDebugMode($debugMode)
	{
		$this->debugMode = (bool) $debugMode;
	}

	public function setVersion($version)
	{
		$this->version = $version;
	}

	public function createUrl($file)
	{
		$fsPath = $this->rootFs . DIRECTORY_SEPARATOR . $file;
		if ($this->debugMode) {
			$postfix = filemtime($fsPath);
		} elseif ($this->version) {
			$postfix = $this->version;
		} else {
			$postfix = date('Y-m-d');
		}

		$host = $this->url->getBasePath();
		if (substr($file, 0, 2) == '//') {
			$host = $this->url->getHostUrl() . '/';
			$file = substr($file, 2);
		}

		return $host . $file . '?' . $postfix;
	}

}
