<?php

namespace Rezonant\MapperBundle\Providers;
use Doctrine\Common\Annotations\AnnotationReader;
use Rezonant\MapperBundle\Utilities\PathParser;
use Rezonant\MapperBundle\MapBuilder;
use Rezonant\MapperBundle\Utilities\Reflector;

class ConfigMapProvider extends MapProvider {
	
	public function __construct() {
		$this->reflector = new Reflector();		
	}

	private $reflector;
	private $maps;
	
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
