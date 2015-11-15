<?php

namespace Rezonant\MapperBundle\Providers;
use Symfony\Component\DependencyInjection\Container;

class ConfiguredMapProviderFactory {
	public function createProvider(Container $container)
	{
		$annotationsEnabled = $container->getParameter('rezonant.mapper.providers.annotations.enabled');
		$configEnabled = $container->getParameter('rezonant.mapper.providers.config.enabled');
		$cacheEnabled = $container->getParameter('rezonant.mapper.cache.enabled');
		$maps = $container->getParameter('rezonant.mapper.maps');
		
		$providers = array();
		if ($annotationsEnabled)
			$providers[] = $container->get('rezonant.mapper.annotation_map_provider');
		if ($configEnabled) {
			$configMapProvider = $container->get('rezonant.mapper.config_map_provider');
			$configMapProvider->setMaps($maps);
			$providers[] = $configMapProvider;
		}
		
		$provider = new MapProviderModerator($providers);
		
		if ($cacheEnabled) {
			$strategy = $mergedConfig['cache']['strategy'];
			if (!is_class($strategy, true))
				throw new InvalidConfigurationException("No such strategy class $strategy");
			$provider = new CacheProvider($provider, new $strategy());
		}
		
		return $provider;
	}
}