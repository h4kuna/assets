<?php

namespace h4kuna\Assets;

function createAssets($url = '', $debug = false)
{
	return new Assets(createFile($url, $debug));
}

function createCache($debug = false)
{
	return new CacheAssets($debug, TEMP_DIR);
}

function createUrl($url = '')
{
	return (new \Salamium\Testinium\HttpRequestFactory())->create($url)->getUrl();
}

function createFile($url = '', $debug = false)
{
	return new File(__DIR__, createUrl($url), createCache($debug));
}
