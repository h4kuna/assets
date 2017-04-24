<?php

use h4kuna\Assets,
	Tester\Assert;

$container = require __DIR__ . '/../bootsrap.php';

touch(__DIR__ . '/../config/php-unix.ini', 536284800);

/* @var $file Fio\Nette\FioFactory */
$file = $container->getByType(Assets\File::class);

Assert::true($file instanceof Assets\File);

Assert::same('/config/php-unix.ini?536284800', $file->createUrl('config/php-unix.ini'));

Assert::same('//www.example.com/config/test.neon', preg_replace('~\?.*~', '', $file->createUrl('//config/test.neon')));
