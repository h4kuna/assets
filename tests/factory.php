<?php

namespace h4kuna\Assets;

use Nette\Http\UrlScript;

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
	return (new \Salamium\Testinium\HttpRequestFactory())->create($url)->getUrl();
}

function createFile(string $url = '', bool $debug = false): File
{
	return new File(__DIR__, createUrl($url), createCache($debug));
}
