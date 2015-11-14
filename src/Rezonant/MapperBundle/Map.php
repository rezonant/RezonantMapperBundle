<?php

namespace Rezonant\MapperBundle;

class Map {
	public function __construct()
	{
	}
	
	private $fields = array();
	
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