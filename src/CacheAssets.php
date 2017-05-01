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
	private $save = FALSE;

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

		$this->save = TRUE;
		return $this->files[$pathname] = filemtime($pathname);
	}

	/**
	 * Clear local cache
	 */
	public function clear()
	{
		$this->files = [];
		$this->save = TRUE;
	}

	private function loadCache()
	{
		if ($this->files !== NULL) {
			return;
		} elseif ($this->debugMode === TRUE) {
			$this->files = [];
		} else {
			$this->files = require $this->tempFile;
		}
	}

	public function __destruct()
	{
		if ($this->debugMode === FALSE && $this->save === TRUE) {
			file_put_contents($this->tempFile, '<?php return ' . var_export($this->files, TRUE) . ';');
		}
	}

}
