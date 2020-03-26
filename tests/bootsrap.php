<?php

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/factory.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

// create temporary directory
define('TEMP_DIR', __DIR__ . '/temp/' . getmypid());
Nette\Utils\FileSystem::createDir(TEMP_DIR);
Tracy\Debugger::enable(false, TEMP_DIR . '/..');

function test(\Closure $closure): void
{
	$closure();
}
