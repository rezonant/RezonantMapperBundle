<?php

namespace Rezonant\MapperBundle\Map;

class Map {
	public function __construct()
	{
	}
	
	private $fields = array();
	
	public function invert()
	{
		$map = new Map;
		foreach ($this->fields as $field) {
			$newField = new MapField();
			$newField->setSource($field->getDestination());
			$newField->setDestination($field->getSource());
			
			if ($field->getSubmap()) {
				$newField->setMap($field->getSubmap()->invert());
			}
			
			$map->fields[] = $newField;
		}
		
		return $map;
	}
	
	/**
	 * @return MapField[]
	 */
	function getFields() {
		return $this->fields;
	}

	function setFields($fields) {
		$this->fields = $fields;
	}
	
	public function getField($fieldName) {
		foreach ($this->fields as $field) {
			if ($field->field == $fieldName)
				return $field;
		}
		
		return null;
	}
}