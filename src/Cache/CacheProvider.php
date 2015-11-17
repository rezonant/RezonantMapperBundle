<?php

namespace Rezonant\MapperBundle\Cache;
use Rezonant\MapperBundle\Providers\MapProvider;
use Rezonant\MapperBundle\Utilities\Reflector;
use Rezonant\MapperBundle\Providers\MapProviderInterface;

/**
 * 
 */
class CacheProvider extends MapProvider implements MapProviderInterface { 
	
	public function __construct(MapProviderInterface $provider, CacheStrategyInterface $strategy) {
		$this->provider = $provider;
		$this->strategy = $strategy;
	}
	
	private $provider;
	private $strategy;
	
	function getProvider() {
		return $this->provider;
	}
	
	function getStrategy() {
		return $this->strategy;
	}
	
	private static function describeType($object)
	{
		if (is_string($object))
			return $object;
		
		$reflector = new Reflector();
		return $reflector->describeType($object);
	}
	
	/**
	 * Produce a string description of the mapping so that cached maps
	 * for this mapping may be consistently identified within the cache.
	 * 
	 * @param mixed $source
	 * @param mixed $destination
	 * @return string
	 */
	public static function getCacheKey($source, $destination, $back)
	{
		return self::describeType($source).($back? ' <= ' : ' => ').self::describeType($destination);
	}
	
	public function getMap($source, $destination, $back = false) {
		$key = self::getCacheKey($source, $destination, $back);
		$map = $this->strategy->get($key);
		$miss = !$map;
		
		if ($miss) {
			$map = $this->provider->getMap($source, $destination);
			$this->strategy->set($key, $map);
		}
		
		return $map;
	}
}