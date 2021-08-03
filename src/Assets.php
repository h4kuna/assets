<?php declare(strict_types=1);

namespace h4kuna\Assets;

use h4kuna\Assets\Exceptions\InvalidStateException;
use Nette\Utils;

class Assets
{
	/** @var File */
	private $file;

	/** @var array<string, array<string, string|int>>|null */
	private $css = [];

	/** @var array<string, array<string, string|int>>|null */
	private $js = [];


	public function __construct(File $file)
	{
		$this->file = $file;
	}


	/**
	 * @param array<string, string|int> $attributes
	 * @return static
	 */
	public function addCss(string $filename, array $attributes = [])
	{
		if ($this->css === null) {
			throw new InvalidStateException('You try add file after renderCss().');
		}
		$this->css[$filename] = $attributes;
		return $this;
	}


	/**
	 * @param array<string, string|int> $attributes
	 * @return static
	 */
	public function addJs(string $filename, array $attributes = [])
	{
		if ($this->js === null) {
			throw new InvalidStateException('You try add file after renderJs().');
		}
		$this->js[$filename] = $attributes;
		return $this;
	}


	public function renderCss(): Utils\Html
	{
		if ($this->css === null) {
			throw new InvalidStateException('renderCss() call onetime per life.');
		}
		$out = new Utils\Html;
		foreach ($this->css as $filename => $attributes) {
			$out[] = Utils\Html::el('link', [
					'rel' => 'stylesheet',
					'type' => 'text/css',
					'href' => $this->createUrl($filename),
				] + $attributes);
		}
		$this->css = null;
		return $out;
	}


	private function createUrl(string $filename): string
	{
		if (preg_match('~[-a-z0-9]+\.[a-z]{2,6}/~i', $filename)) {
			// is it contains domain?
			return $filename;
		}
		return $this->file->createUrl($filename);
	}


	public function renderJs(): Utils\Html
	{
		if ($this->js === null) {
			throw new InvalidStateException('renderJs() call onetime per life.');
		}
		$html = new Utils\Html;
		foreach ($this->js as $filename => $attributes) {
			$html[] = Utils\Html::el('script', [
					'src' => $this->createUrl($filename),
				] + $attributes);
		}
		$this->js = null;
		return $html;
	}

}
