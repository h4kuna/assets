<?php

namespace h4kuna\Assets;

use Nette\Utils;

class CacheAssets
{

	/** @var bool */
	private $debugMode;

	/** @var string */
	private $tempFile;

	/** @var int[] */
	private $files;

	/** @var bool */
	private $save = false;


	public function __construct($debugMode, $tempDir)
	{
		$this->debugMode = $debugMode;
		$this->tempFile = $tempDir . DIRECTORY_SEPARATOR . '_assets';
		if (!is_file($this->tempFile)) {
			Utils\FileSystem::createDir($tempDir);
			$this->files = [];
		}
	}


	/**
	 * @param string $pathname
	 * @return int
	 */
	public function load($pathname)
	{
		$this->loadCache();
		if (isset($this->files[$pathname])) {
			return $this->files[$pathname];
		}

		$this->save = true;
		return $this->files[$pathname] = filemtime($pathname);
	}


	/**
	 * Clear local cache
	 * @return self
	 */
	public function clear()
	{
		$this->files = [];
		$this->save = true;
		return $this;
	}


	private function loadCache()
	{
		if ($this->files !== null) {
			return;
		} elseif ($this->debugMode === true) {
			$this->files = [];
		} else {
			$this->files = require $this->tempFile;
		}
	}


	public function __destruct()
	{
		if ($this->debugMode === false && $this->save === true) {
			file_put_contents($this->tempFile, '<?php return ' . var_export($this->files, true) . ';');
		}
	}

}
