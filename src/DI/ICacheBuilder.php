<?php

namespace h4kuna\Assets\DI;

use h4kuna\Assets;

interface ICacheBuilder
{

	function create(Assets\CacheAssets $cache, $wwwDir);
}
