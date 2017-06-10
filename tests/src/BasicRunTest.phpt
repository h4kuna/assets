<?php

use h4kuna\Assets,
	Tester\Assert;

$container = require __DIR__ . '/../bootsrap.php';

$time = 536284800;
touch(__DIR__ . '/../config/php-unix.ini', $time);

/* @var $file Assets\File */
$file = $container->getByType(Assets\File::class);

Assert::type(Assets\File::class, $file);

Assert::same('/config/php-unix.ini?' . $time, $file->createUrl('config/php-unix.ini'));

Assert::same('//www.example.com/config/test.neon', preg_replace('~\?.*~', '', $file->createUrl('//config/test.neon')));
