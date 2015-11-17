<?php

namespace Rezonant\MapperBundle\Map;

class MapField {
	
	public function __construct()
	{
	}
	
	private $source;
	private $destination;
	private $submap;
	
	function getSource() {
		return $this->source;
	}

	function getDestination() {
		return $this->destination;
	}

	function getSubmap() {
		return $this->submap;
	}

	function setSource($source) {
		$this->source = $source;
	}

	function setDestination($destination) {
		$this->destination = $destination;
	}

	function setSubmap($submap) {
		$this->submap = $submap;
	}
}