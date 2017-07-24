<?php

namespace h4kuna\Assets;

use Tester\Assert;

require __DIR__ . '/../bootsrap.php';

test(function() {
	$time = filemtime(__DIR__ . '/../config/php-unix.ini');

	/* @var $assets Assets */
	$assets = createAssets();
	$assets->addJs('//example.com/foo.js');
	$assets->addJs('http://example.com/foo.js');
	$assets->addJs('config/php-unix.ini');
	Assert::same('<script src="//example.com/foo.js"></script><script src="http://example.com/foo.js"></script><script src="/config/php-unix.ini?' . $time . '"></script>', (string) $assets->renderJs());

	Assert::exception(function() use ($assets) {
		Assert::same('', (string) $assets->renderJs());
	}, InvalidStateException::class);

	Assert::exception(function() use ($assets) {
		$assets->addJs('config/php-unix.ini');
	}, InvalidStateException::class);
});


test(function() {
	/* @var $assets Assets */
	$assets = createAssets();
	$assets->addCss('//example.com/foo.css');
	Assert::same('<link rel="stylesheet" type="text/css" href="//example.com/foo.css">', (string) $assets->renderCss());

	Assert::exception(function() use ($assets) {
		Assert::same('', (string) $assets->renderCss());
	}, InvalidStateException::class);


	Assert::exception(function() use ($assets) {
		$assets->addCss('foo.css');
	}, InvalidStateException::class);
});
