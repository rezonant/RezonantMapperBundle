<?php

namespace Rezonant\MapperBundle\Providers;
use Symfony\Component\DependencyInjection\Container;
use Rezonant\MapperBundle\Cache\CacheProvider;

class ConfiguredMapProviderFactory {
	public static function createProvider(Container $container)
	{
		$annotationsEnabled = $container->getParameter('rezonant.mapper.providers.annotations.enabled');
		$configEnabled = $container->getParameter('rezonant.mapper.providers.config.enabled');
		$cacheEnabled = $container->getParameter('rezonant.mapper.cache.enabled');
		$cacheStrategy = $container->getParameter('rezonant.mapper.cache.strategy');
		//$maps = $container->getParameter('rezonant.mapper.maps');
		
		$providers = array();
		if ($annotationsEnabled)
			$providers[] = $container->get('rezonant.mapper.annotation_map_provider');
		if ($configEnabled) {
			$configMapProvider = $container->get('rezonant.mapper.config_map_provider');
			//$configMapProvider->setMaps($maps);
			$providers[] = $configMapProvider;
		}
		
		$provider = new MapProviderModerator($providers);
		
		if ($cacheEnabled) {
			if (!class_exists($cacheStrategy, true))
				throw new InvalidConfigurationException("No such strategy class $cacheStrategy");
			$provider = new CacheProvider($provider, new $cacheStrategy());
		}
		
		return $provider;
	}
}