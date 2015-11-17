<?php

namespace Rezonant\MapperBundle\Providers;
use Symfony\Component\DependencyInjection\Container;

class ConfiguredCacheStrategyFactory {
	public static function createStrategy(Container $container)
	{
		$strategy = $container->getParameter('rezonant.mapper.cache.strategy');
		return new $strategy;
	}
}