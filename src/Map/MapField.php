<?php

namespace Rezonant\MapperBundle\Map;
use Rezonant\MapperBundle\Transformation\TransformationInterface;

class MapField {
	
	public function __construct()
	{
	}
	/**
	 *
	 * @var Reference
	 */
	private $source;
	
	/**
	 *
	 * @var Reference
	 */
	private $destination;
	
	private $submap;
	
	/**
	 *
	 * @var TransformationInterface 
	 */
	private $transformation;
	
	function getSource() {
		return $this->source;
	}

	function getDestination() {
		return $this->destination;
	}

	function getSubmap() {
		return $this->submap;
	}
	
	function getTransformation() {
		return $this->transformation;
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
	
	/**
	 * When you pass in an object it will be cloned to ensure that it will not change
	 * @param type $transformation
	 */
	function setTransformation($transformation){
		if($transformation){
			$transformation = clone $transformation;
		}
		$this->transformation = $transformation;
	}
}