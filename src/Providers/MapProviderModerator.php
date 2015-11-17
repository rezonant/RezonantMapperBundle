<?php

namespace Rezonant\MapperBundle\Providers;

class MapProviderModerator extends MapProvider implements MapProviderInterface {
	public function __construct($providers)
	{
		$this->providers = $providers;
	}

	private $providers;
	
	function getProviders() {
		return $this->providers;
	}
	
	public function getMap($source, $destination) {
		foreach ($this->providers as $provider) {
			$map = $provider->getMap($source, $destination);
			if ($map)
				return $map;
		}
		
		return null;
	}
}