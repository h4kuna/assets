<?php

namespace h4kuna\Assets;

use Tester\Assert;

require __DIR__ . '/../bootsrap.php';

test(function () {
	$time = filemtime(__DIR__ . '/../config/php-unix.ini');

	/* @var $file Assets\File */
	$file = createFile();
	Assert::same('/config/php-unix.ini?' . $time, $file->createUrl('config/php-unix.ini'));

	Assert::same('//www.example.com/config/test.neon', preg_replace('~\?.*~', '', $file->createUrl('//config/test.neon')));
});
