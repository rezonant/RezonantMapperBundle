<?php

namespace Rezonant\MapperBundle\Cache;

/**
 * Base class for cache strategies. Strategies may offer differing levels of 
 * persistence.
 */
abstract class CacheStrategy implements CacheStrategyInterface {
	public abstract function get($key);
	public abstract function set($key, $value);
}