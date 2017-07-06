<?php

namespace h4kuna\Assets;

use Tester\Assert;

function test(\Closure $closure)
{
	$closure();
}

$container = require __DIR__ . '/../bootsrap.php';

$time = 536284800;
touch(__DIR__ . '/../config/php-unix.ini', $time);

test(function() use ($container, $time) {
	/* @var $assets Assets */
	$assets = $container->getByType(Assets::class);
	$assets->addJs('//example.com/foo.js');
	$assets->addJs('http://example.com/foo.js');
	$assets->addJs('config/php-unix.ini');
	Assert::same('<script type="text/javascript" src="//example.com/foo.js"></script><script type="text/javascript" src="http://example.com/foo.js"></script><script type="text/javascript" src="/config/php-unix.ini?' . $time . '"></script>', (string) $assets->renderJs());

	Assert::exception(function() use ($assets) {
		Assert::same('', (string) $assets->renderJs());
	}, \RuntimeException::class);

	Assert::exception(function() use ($assets) {
		$assets->addJs('config/php-unix.ini');
	}, \RuntimeException::class);
});


test(function() use ($container, $time) {
	/* @var $assets Assets */
	$assets = $container->getByType(Assets::class);
	Assert::same('', (string) $assets->renderCss());

	Assert::exception(function() use ($assets) {
		Assert::same('', (string) $assets->renderCss());
	}, \RuntimeException::class);


	Assert::exception(function() use ($assets) {
		$assets->addCss('foo.css');
	}, \RuntimeException::class);
});
