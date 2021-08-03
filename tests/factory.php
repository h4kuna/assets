<?php declare(strict_types=1);

namespace h4kuna\Tests\Assets;

use h4kuna\Assets\Assets;
use h4kuna\Assets\CacheAssets;
use h4kuna\Assets\File;
use Nette\Http\UrlScript;
use Salamium\Testinium;

function createAssets(string $url = '', bool $debug = false): Assets
{
	return new Assets(createFile($url, $debug));
}


function createCache(bool $debug = false): CacheAssets
{
	return new CacheAssets($debug, TEMP_DIR);
}


function createUrl(string $url = ''): UrlScript
{
	return (new Testinium\HttpRequestFactory())->create($url)->getUrl();
}


function createFile(string $url = '', bool $debug = false): File
{
	return new File(__DIR__, createUrl($url), createCache($debug));
}
