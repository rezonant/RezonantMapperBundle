<?php

namespace Rezonant\MapperBundle;
use Rezonant\MapperBundle\Map\Map;
use Rezonant\MapperBundle\Map\MapField;
use Rezonant\MapperBundle\Map\Reference;

class MapBuilder {
	private $fields = array();
	
	/**
	 * @param string $sourceFieldName
	 * @param string $destinationFieldName
	 * @param Map $map
	 * @return MapBuilder
	 */
	public function field(Reference $source, Reference $dest, Map $map = NULL)
	{
		$mapField = new MapField();
		$mapField->setSource($source);
		$mapField->setDestination($dest);
		$mapField->setSubmap($map);
		$this->fields[] = $mapField;
		
		return $this;
	}
	
	public function build()
	{
		$map = new Map();
		$map->setFields($this->fields);
		
		return $map;
	}
}