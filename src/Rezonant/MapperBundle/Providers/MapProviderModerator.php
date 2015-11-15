<?php

namespace Rezonant\MapperBundle\Providers;

class MapProviderModerator extends MapProvider {
	public function __construct($providers)
	{
		$this->providers = $providers;
	}

	private $providers;
	
	public function getMap($source, $destination) {
		foreach ($this->providers as $provider) {
			$map = $provider->getMap($source, $destination);
			if ($map)
				return $map;
		}
		
		return null;
	}
}