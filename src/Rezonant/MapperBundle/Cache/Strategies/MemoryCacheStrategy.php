<?php

namespace Rezonant\MapperBundle\Cache\Strategies;
use Rezonant\MapperBundle\Cache\CacheStrategy;

class MemoryCacheStrategy extends CacheStrategy {
	private $cache = array();
	
	public function get($key) {
		if (!isset($this->cache[$key]))
			return null;
		
		return $this->cache[$key];
	}
	
	public function set($key, $value) {
		$this->cache[$key] = $value;
	}
}