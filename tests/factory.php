<?php

namespace h4kuna\Assets;

function createAssets($url = '', $debug = FALSE)
{
	return new Assets(createFile($url, $debug));
}

function createCache($debug = FALSE)
{
	return new CacheAssets($debug, TEMP_DIR);
}

function createUrl($url = '')
{
	return (new \Salamium\Testinium\HttpRequestFactory())->create($url)->getUrl();
}

function createFile($url = '', $debug = FALSE)
{
	return new File(__DIR__, createUrl($url), createCache($debug));
}
