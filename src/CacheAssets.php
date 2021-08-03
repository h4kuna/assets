<?php declare(strict_types=1);

namespace h4kuna\Assets;

use Nette\Safe;
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


	public function __construct(bool $debugMode, string $tempDir)
	{
		$this->debugMode = $debugMode;
		$this->tempFile = $tempDir . DIRECTORY_SEPARATOR . '_assets';
		if (!is_file($this->tempFile)) {
			Utils\FileSystem::createDir($tempDir);
			$this->files = [];
		}
	}


	public function load(string $pathname): int
	{
		$this->loadCache();
		if (isset($this->files[$pathname])) {
			return $this->files[$pathname];
		}

		$this->save = true;
		return $this->files[$pathname] = Safe::filemtime($pathname);
	}


	private function loadCache(): void
	{
		if ($this->files !== null) {
			return;
		} elseif ($this->debugMode === true) {
			$this->files = [];
		} else {
			$this->files = require $this->tempFile;
		}
	}


	/**
	 * Clear local cache
	 * @return static
	 */
	public function clear()
	{
		$this->files = [];
		$this->save = true;
		return $this;
	}


	public function __destruct()
	{
		if ($this->debugMode === false && $this->save === true) {
			Safe::file_put_contents($this->tempFile, '<?php return ' . var_export($this->files, true) . ';');
		}
	}

}
