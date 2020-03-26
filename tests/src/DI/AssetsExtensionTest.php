<?php declare(strict_types=1);

namespace h4kuna\Assets\DI;

use h4kuna\Assets;
use	Nette\Bridges;
use	Nette\Utils;
use	Nette\DI AS NDI;
use	Tester\Assert;

require_once __DIR__ . '/../../bootsrap.php';

/**
 * @param array<mixed> $config
 */
function createContainer(array $config): \Container
{
	$compiler = new NDI\Compiler();
	$latteExtension = new Bridges\ApplicationDI\LatteExtension(TEMP_DIR);
	$httpExtension = new Bridges\HttpDI\HttpExtension();
	$compiler->addExtension('latte', $latteExtension);
	$compiler->addExtension('http', $httpExtension);

	Utils\FileSystem::createDir(TEMP_DIR . '/temp');
	$assetsExtension = new AssetsExtension(TEMP_DIR, TEMP_DIR);
	$compiler->addConfig([
		'assets' => $config
	]);

	$compiler->addExtension('assets', $assetsExtension);
//	file_put_contents(__DIR__ . '/container.php', "<?php\n" . $compiler->compile());
	eval($compiler->compile());
	return new \Container();
}

Assert::exception(function () {
	createContainer([
		'externalAssets' => ['http://www.noexists.cl1/js/foo.js']
	]);
}, Assets\Exceptions\DownloadFaildFromExternalUrlException::class);

Assert::exception(function () {
	createContainer([
		'externalAssets' => ['sha256-fljdfkuvzddfdvc' => 'http://example.com/']
	]);
}, Assets\Exceptions\CompareTokensException::class);

Assert::exception(function () {
	createContainer([
		'externalAssets' => [TEMP_DIR . '/_unkown.css']
	]);
}, Assets\Exceptions\FileNotFoundException::class);

Assert::exception(function () {
	createContainer([
		'externalAssets' => [
			'http://example.com/',
			'example.com' => __DIR__ . '/assets/main.js'
		],
	]);
}, Assets\Exceptions\DuplicityAssetNameException::class);

// custom cache builder
class CacheBuilder implements ICacheBuilder
{

	public function create(Assets\CacheAssets $cache, string $wwwDir): void
	{
		$mainJs = $wwwDir . '/temp/main.js';
		touch($mainJs, 123456789);
		/* @var $file \SplFileInfo */
		$cache->load($mainJs);
	}

}

test(function () {
	$mainJs = __DIR__ . '/assets/main.js';
	touch($mainJs, 12345678);
	$container = createContainer([
		'externalAssets' => [
			0 => __DIR__ . '/assets/main.js',
			'app/index.js' => __DIR__ . '/assets/main.js',
			'sha256-DZAnKJ/6XZ9si04Hgrsxu/8s717jcIzLy3oi35EouyE=' => 'https://code.jquery.com/jquery-3.2.1.js',
		],
		'cacheBuilder' => \h4kuna\Assets\DI\CacheBuilder::class
	]);
	$file = $container->getByType(Assets\File::class);
	/* @var $file \h4kuna\Assets\File */
	Assert::same('/temp/main.js?123456789', $file->createUrl('temp/main.js'));
	Assert::same('/temp/app/index.js?12345678', $file->createUrl('temp/app/index.js'));
	Assert::type(Assets\CacheAssets::class, $container->getService('assets.cache'));
});
