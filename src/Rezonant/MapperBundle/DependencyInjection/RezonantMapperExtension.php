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
	 * Parse the given map description obtained from the configuration into
	 * a real Map instance.
	 * 
	 * @param array $map Associative array describing the map, from config
	 * @return Map
	 */
	private function parseMap($map)
	{
		$builder = new MapBuilder();
		
		foreach ($map['fields'] as $field) {
			$map = null;
			if (isset($field['map'])) {
				$map = $this->parseMap($field['map']);
			}
			
			$types = null;
			
			if (isset($field['types']))
				$types = $field['types'];
			else if (isset($field['type']))
				$types = array($field['type']);
			
			$builder->field($field['from'], $field['to'], $types, $map);
		}
		
		return $builder->build();
	}
	
	/**
	 * Load the final, validated config data 
	 * 
	 * @param array $mergedConfig
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	protected function loadInternal(array $mergedConfig, \Symfony\Component\DependencyInjection\ContainerBuilder $container) {
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
		
		/**
		 * Obtain any maps provided in the configuration and store them for later
		 */
		$maps = array();
		if (isset($mergedConfig['maps'])) {
			foreach ($mergedConfig['maps'] as $map) {
				$maps["{$map['source']} => {$map['destination']}"] = $this->parseMap($map);
			}
		}
		
		$annotationsEnabled = 
			isset($mergedConfig['providers']['annotations']['enabled']) ?
				$mergedConfig['providers']['annotations']['enabled']
				: true;
		
		$configEnabled = 
			isset($mergedConfig['providers']['annotations']['enabled']) ?
				$mergedConfig['providers']['annotations']['enabled']
				: true;
		
		$cacheEnabled = 
			isset($mergedConfig['cache']['enabled']) ?
				$mergedConfig['cache']['enabled']
				: true;
		
		$cacheStrategy =
			isset($mergedConfig['cache']['strategy']) ?
				$mergedConfig['cache']['strategy']
				: 'Rezonant\MapperBundle\Cache\Strategies\MemoryCacheStrategy';
		
		$container->setParameter('rezonant.mapper.maps', $maps);
		$container->setParameter('rezonant.mapper.cache.strategy', $cacheStrategy);
		$container->setParameter('rezonant.mapper.providers.annotations.enabled', $annotationsEnabled);
		$container->setParameter('rezonant.mapper.providers.config.enabled', $configEnabled);
		$container->setParameter('rezonant.mapper.cache.enabled', $cacheEnabled);
		
	}
}