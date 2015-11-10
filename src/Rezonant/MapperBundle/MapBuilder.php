<?php

namespace Rezonant\MapperBundle;

class MapBuilder {
	private $fields = array();
	
	/**
	 * @param string $sourceFieldName
	 * @param string $destinationFieldName
	 * @param Map $map
	 * @return MapBuilder
	 */
	public function field($sourceFieldName, $destinationFieldName, Map $map = NULL)
	{
		$mapField = new MapField($sourceFieldName);
		$mapField->setDestinationField($destinationFieldName);
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