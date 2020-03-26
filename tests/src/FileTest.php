<?php declare(strict_types=1);

namespace h4kuna\Assets;

use Tester\Assert;

require_once __DIR__ . '/../bootsrap.php';

test(function () {
	$time = filemtime(__DIR__ . '/../config/php.ini');

	/* @var $file File */
	$file = createFile('//www.example.com');
	Assert::same('/config/php.ini?' . $time, $file->createUrl('config/php.ini'));

	Assert::same('//www.example.com/config/php.ini', preg_replace('~\?.*~', '', $file->createUrl('//config/php.ini')));
});
