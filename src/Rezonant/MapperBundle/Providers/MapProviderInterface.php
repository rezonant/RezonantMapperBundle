<?php

namespace Rezonant\MapperBundle\Providers;
use Rezonant\MapperBundle\Map;

/**
 * Provides maps to the Mapper service
 */
interface MapProviderInterface {
	/**
	 * @return Map
	 */
	function getMap($source, $destination);
}