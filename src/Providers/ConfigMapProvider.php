<?php

namespace Rezonant\MapperBundle\Providers;
use Doctrine\Common\Annotations\AnnotationReader;
use Rezonant\MapperBundle\Utilities\PathParser;
use Rezonant\MapperBundle\MapBuilder;
use Rezonant\MapperBundle\Utilities\Reflector;

class ConfigMapProvider extends MapProvider {
	
	public function __construct($maps) {
		
		/**
		 * Obtain any maps provided in the configuration and store them for later
		 */
		$finalMap = array();
		foreach ($maps as $map)
			$finalMap["{$map['source']} => {$map['destination']}"] = $this->parseMap($map);
		
		$this->maps = $finalMap;
		$this->reflector = new Reflector();		
	}

	private $reflector;
	private $maps;
	
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
		
		if (!isset($map['fields']))
			return $builder->build();
		
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
	
	function getMaps() {
		return $this->maps;
	}

	function setMaps($maps) {
		$this->maps = $maps;
	}
	
	public function getMap($source, $destination) {
		
		$sourceDesc = $this->reflector->describeType($source);
		$destDesc = $this->reflector->describeType($destination);
		$key = "$sourceDesc => $destDesc";
		
		if (isset($this->maps[$key]))
			return $this->maps[$key];
		
		return null;
	}
}
