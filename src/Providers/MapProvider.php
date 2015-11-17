<?php

namespace Rezonant\MapperBundle\Providers;
use Rezonant\MapperBundle\Map;

/**
 * Provides maps to the Mapper service
 */
abstract class MapProvider implements MapProviderInterface {
	/**
	 * @return Map
	 */
	public abstract function getMap($source, $destination);
}