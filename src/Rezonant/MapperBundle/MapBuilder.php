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
	public function field($sourceFieldName, $destinationFieldName, $destinationType = NULL, Map $map = NULL)
	{
		if (is_null($sourceFieldName))
			throw new \InvalidArgumentException('$sourceFieldName cannot be null');
		
		if (is_null($destinationFieldName))
			throw new \InvalidArgumentException('$destinationFieldName cannot be null');
		
		$mapField = new MapField($sourceFieldName);
		$mapField->setDestinationField($destinationFieldName);
		$mapField->setDestinationType($destinationType);
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