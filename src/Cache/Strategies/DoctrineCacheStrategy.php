<?php

namespace Rezonant\MapperBundle\Cache\Strategies;
use Doctrine\Common\Cache\Cache as DoctrineCache;
use Rezonant\MapperBundle\Cache\CacheStrategy;

class DoctrineCacheStrategy extends CacheStrategy {
	public function __construct(DoctrineCache $doctrineCacheProvider, $lifetime) {
		$this->cache = $doctrineCacheProvider;
		$this->lifetime = $lifetime;
	}
	
	/**
	 * @var DoctrineCache
	 */
	private $cache;
	
	/**
	 * @var mixed
	 */
	private $lifetime;

	function getUnderlyingCache() {
		return $this->cache;
	}

	function getKeyLifetime() {
		return $this->lifetime;
	}

		
	public function get($key) {
		return $this->cache->fetch($key);
	}
	
	public function set($key, $value) {
		return $this->cache->save($key, $value, $this->lifetime);
	}
}