<?php

namespace h4kuna\Assets;

use Tester\Assert;

require __DIR__ . '/../bootsrap.php';

test(function () {
	$cache = createCache()->clear();
	Assert::same($cache->load(__FILE__), $cache->load(__FILE__));
});

test(function () {
	$cache = createCache()->clear();
	$time = $cache->load(__FILE__);
	unset($cache);
	sleep(1);
	touch(__FILE__, time());
	$cache = createCache();
	Assert::same($time, $cache->load(__FILE__));
});

test(function () {
	$cache = createCache()->clear();
	$time = $cache->load(__FILE__);
	unset($cache);
	sleep(1);
	touch(__FILE__, time());

	$cache = createCache(true);
	Assert::notSame($time, $cache->load(__FILE__));
});
