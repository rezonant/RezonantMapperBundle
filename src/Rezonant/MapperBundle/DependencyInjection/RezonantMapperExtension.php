<?php

namespace Rezonant\MapperBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Rezonant\MapperBundle\DependencyInjection\Configuration;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Rezonant\MapperBundle\MapBuilder;
use Rezonant\MapperBundle\Map;
use Rezonant\MapperBundle\Providers\MapProviderModerator;
use Rezonant\MapperBundle\Cache\CacheProvider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Rezonant\MapperBundle\Utilities\Reflector;

/**
 * Implements the configuration mechanisms for MapperBundle
 */
class RezonantMapperExtension extends ConfigurableExtension {
	
	/**
	 * Load the final, validated config data 
	 * 
	 * @param array $mergedConfig
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	protected function loadInternal(array $mergedConfig, \Symfony\Component\DependencyInjection\ContainerBuilder $container) {
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
		
		$annotationsEnabled = 
			isset($mergedConfig['providers']['annotations']['enabled']) ?
				$mergedConfig['providers']['annotations']['enabled']
				: true;
		
		$configEnabled = 
			isset($mergedConfig['providers']['annotations']['enabled']) ?
				$mergedConfig['providers']['annotations']['enabled']
				: true;
		
		$cacheEnabled = 
			isset($mergedConfig['caching']['enabled']) ?
				$mergedConfig['caching']['enabled']
				: true;
		
		$cacheStrategy =
			isset($mergedConfig['cache']['strategy']) ?
				$mergedConfig['cache']['strategy']
				: 'Rezonant\MapperBundle\Cache\Strategies\MemoryCacheStrategy';
		
		$container->setParameter('rezonant.mapper.providers.config.maps', 
				isset($mergedConfig['maps']) ? $mergedConfig['maps'] : array());
		$container->setParameter('rezonant.mapper.cache.strategy', $cacheStrategy);
		$container->setParameter('rezonant.mapper.providers.annotations.enabled', $annotationsEnabled);
		$container->setParameter('rezonant.mapper.providers.config.enabled', $configEnabled);
		$container->setParameter('rezonant.mapper.cache.enabled', $cacheEnabled);
		
	}
}