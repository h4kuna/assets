<?php

namespace h4kuna\Assets;

use Tester\Assert;

function test(\Closure $closure)
{
	$closure();
}

$container = require __DIR__ . '/../bootsrap.php';

$time = 1490036475;
touch(__DIR__ . '/../config/php-unix.ini', $time);

test(function() use ($container, $time) {
	/* @var $assets Assets */
	$assets = $container->getByType(Assets::class);
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


test(function() use ($container, $time) {
	/* @var $assets Assets */
	$assets = $container->getByType(Assets::class);
	Assert::same('', (string) $assets->renderCss());

	Assert::exception(function() use ($assets) {
		Assert::same('', (string) $assets->renderCss());
	}, InvalidStateException::class);


	Assert::exception(function() use ($assets) {
		$assets->addCss('foo.css');
	}, InvalidStateException::class);
});
