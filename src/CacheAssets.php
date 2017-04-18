<?php

namespace h4kuna\Assets;

use Nette\Utils;

class CacheAssets
{

	/** @var string */
	private $tempFile;

	/** @var int[] */
	private $files;

	/** @var bool */
	private $save = FALSE;

	public function __construct($tempDir)
	{
		$this->tempFile = $tempDir . DIRECTORY_SEPARATOR . '_assets';
		if (!is_file($this->tempFile)) {
			Utils\FileSystem::createDir($tempDir);
			$this->saveFile(array());
		}
	}

	/**
	 * @param string $pathname
	 * @return int|NULL
	 */
	public function load($pathname)
	{
		$this->loadCache();
		if (isset($this->files[$pathname])) {
			return $this->files[$pathname];
		}

		return $this->save($pathname);
	}

	private function loadCache()
	{
		if ($this->files === NULL) {
			$this->files = require $this->tempFile;
		}
	}

	private function save($pathname)
	{
		$mtime = filemtime($pathname);
		$this->files[$pathname] = $mtime;
		if ($this->save === FALSE) {
			$this->save = TRUE;
		}
		return $mtime;
	}

	private function saveFile(array $data)
	{
		file_put_contents($this->tempFile, '<?php return ' . var_export($data, TRUE) . ';');
	}

	public function __destruct()
	{
		if ($this->save === TRUE) {
			$this->saveFile($this->files);
		}
	}

}
