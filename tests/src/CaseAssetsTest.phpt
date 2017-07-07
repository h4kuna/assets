<?php

use h4kuna\Assets,
	Tester\Assert;

$container = require __DIR__ . '/../bootsrap.php';

$temp = __DIR__ . '/../temp/cache-assets';
\Nette\Utils\FileSystem::delete($temp);
\Nette\Utils\FileSystem::createDir($temp);

test(function () use ($temp) {
	$cache = new Assets\CacheAssets(FALSE, $temp);
	Assert::same($cache->load(__FILE__), $cache->load(__FILE__));
});


test(function () use ($temp) {
	$cache = new Assets\CacheAssets(FALSE, $temp);
	$time = $cache->load(__FILE__);
	$cache->clear();
	unset($cache);

	$cache = new Assets\CacheAssets(FALSE, $temp);
	Assert::same($time, $cache->load(__FILE__));
});

test(function () use ($temp) {
	$cache = new Assets\CacheAssets(FALSE, $temp);
	$cache->clear();
	$time = $cache->load(__FILE__);
	unset($cache);

	$cache = new Assets\CacheAssets(FALSE, $temp);
	Assert::same($time, $cache->load(__FILE__));
});

test(function () use ($temp) {
	$cache = new Assets\CacheAssets(FALSE, $temp);
	$time = $cache->load(__FILE__);
	unset($cache);
	touch(__FILE__, time());

	$cache = new Assets\CacheAssets(TRUE, $temp);
	Assert::notSame($time, $cache->load(__FILE__));
});
