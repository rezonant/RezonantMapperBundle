<?php

namespace Rezonant\MapperBundle;

class MapField {
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	private $name;
	private $destinationField;
	private $destinationTypes;
	private $submap;
	
	function getDestinationTypes() {
		return $this->destinationTypes;
	}

	function setDestinationTypes($destinationTypes) {
		$this->destinationTypes = $destinationTypes;
	}
	
	function getSubmap() {
		return $this->submap;
	}

	function setSubmap($submap) {
		$this->submap = $submap;
	}
	
	function getName() {
		return $this->name;
	}

	function getDestinationField() {
		return $this->destinationField;
	}

	function setName($name) {
		$this->name = $name;
	}

	function setDestinationField($destinationField) {
		$this->destinationField = $destinationField;
	}	
}