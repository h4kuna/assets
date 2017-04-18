<?php

use h4kuna\Assets,
	Tester\Assert;

$container = require __DIR__ . '/../bootsrap.php';

touch(__DIR__ . '/../config/test.neon', 536284800);

/* @var $file Fio\Nette\FioFactory */
$file = $container->getByType(Assets\File::class);

Assert::true($file instanceof Assets\File);

Assert::same('/config/test.neon?536284800', $file->createUrl('config/test.neon'));

$file->createUrl('config/php-unix.ini');
