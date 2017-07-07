<?php

namespace h4kuna\Assets\DI;

use h4kuna\Assets,
	Nette\Utils,
	Tester\Assert;

$container = require __DIR__ . '/../../bootsrap.php';

function configuratorFactory($neon, $subDirOff = FALSE)
{
	$configurator = new \Nette\Configurator;
	$tmp = __DIR__ . '/../../temp/test';

	Utils\FileSystem::delete($tmp);
	Utils\FileSystem::createDir($tmp);

	$wwwDir = $tmp . '/../www';
	$subWww = $wwwDir . '/temp';

	@chmod($subWww, 0755);
	Utils\FileSystem::delete($subWww);
	Utils\FileSystem::createDir($subWww);
	if ($subDirOff) {
		chmod($subWww, 0000);
	}

	$configurator->enableDebugger($tmp);
	$configurator->addParameters([
		'wwwDir' => $wwwDir
	]);
	$configurator->setTempDirectory($tmp);
	$configurator->addConfig(__DIR__ . '/assets/assets.neon');
	$configurator->addConfig(__DIR__ . '/assets/' . $neon);
	return $configurator->createContainer();
}

Assert::exception(function() {
	configuratorFactory('external-download-faild.neon');
}, Assets\DownloadFaildFromExternalUrlException::class);


Assert::exception(function() {
	configuratorFactory('permission-denied.neon', TRUE);
}, Assets\DirectoryIsNotWriteableException::class);


Assert::exception(function() {
	configuratorFactory('bad-token.neon');
}, Assets\CompareTokensException::class);


Assert::exception(function() {
	configuratorFactory('file-not-found.neon');
}, Assets\FileNotFoundException::class);


Assert::exception(function() {
	configuratorFactory('fs-main.neon', TRUE);
}, Assets\DirectoryIsNotWriteableException::class);


test(function() {
	touch(__DIR__ . '/assets/main.js', 123456789);
	$container = configuratorFactory('fs-main.neon');
	$file = $container->getByType(Assets\File::class);
	/* @var $file \h4kuna\Assets\File */
	Assert::same('/temp/main.js?123456789', $file->createUrl('temp/main.js'));
});


test(function() {
	touch(__DIR__ . '/assets/main.js', 123456789);
	$container = configuratorFactory('fs-main-alias.neon');
	$file = $container->getByType(Assets\File::class);
	/* @var $file \h4kuna\Assets\File */
	Assert::same('/temp/app/index.js?123456789', $file->createUrl('temp/app/index.js'));
});


Assert::exception(function() {
	configuratorFactory('duplicity.neon');
}, Assets\DuplicityAssetNameException::class);
