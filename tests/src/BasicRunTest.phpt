<?php

use h4kuna\Assets,
	Tester\Assert;

$container = require __DIR__ . '/../bootsrap.php';

/* @var $file Fio\Nette\FioFactory */
$file = $container->getByType(Assets\File::class);

Assert::true($file instanceof Assets\File);

Assert::same('/config/test.neon?2017-04-17', $file->createUrl('config/test.neon'));