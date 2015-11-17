<?php

namespace Rezonant\MapperBundle\Cache;

interface CacheStrategyInterface {
	function get($key);
	function set($key, $value);
}