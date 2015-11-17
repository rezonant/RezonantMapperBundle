<?php

namespace Rezonant\MapperBundle\Map;
use Rezonant\MapperBundle\Utilities\PathParser;
use Rezonant\MapperBundle\Utilities\Reflector;

class Reference {
	public function __construct($ref, $class = null) {
		$pathParser = new PathParser;
		$reflector = new Reflector;
		
		$this->rootClass = $class;
		$this->string = $ref;
		$this->fields = $pathParser->parse($ref);
		
		if ($class) {
			$this->properties = $this->getDeepProperty($class, $ref);
			$this->types = array_map(
				function($prop) use ($reflector) { 
					return $reflector->getTypeFromProperty($prop);
				}, $this->properties);
		}
	}
	
	private $rootClass;
	private $string;
	private $fields;
	private $properties;
	private $types;
	
	function getRootClass() {
		return $this->rootClass;
	}
	
	function toString() {
		return $this->string;
	}

	function getFields() {
		return $this->fields;
	}

	function getProperties() {
		return $this->properties;
	}

	function getTypes() {
		return $this->types;
	}
	
	/**
	 * Get a ReflectionProperty deeply.
	 * 
	 * @param \ReflectionClass $class
	 * @param string $dottedReference The given dotted reference
	 * @return mixed Usually a \ReflectionProperty. If the dotted reference falls into
	 *					an associative array property, we will return "<array>"
	 */
	private function getDeepProperty(\ReflectionClass $class, $dottedReference)
	{
		$pathParser = new PathParser;
		$reflector = new Reflector;
		
		$originalClass = $class;
		$fields = $pathParser->parse($dottedReference);
		$fieldCount = count($fields);
		$property = null;
		$className = $originalClass->getName();
		$properties = array();
		
		foreach ($fields as $i => $field) {
		
			if ($className == '<array>') {
				$properties[] = '<array>';
				continue;
			}
			
			// Cannot traverse into primitive types (it is impossible for this
			// to happen on first iteration)
			
			if ($reflector->isPrimitiveType($className)) {
				throw new \InvalidArgumentException(
						"Cannot traverse into primitive type $className "
						. "to satisfy reference '{$field->field}' "
						. "of full reference '$dottedReference'"
				);
			}
			
			$currentClass = new \ReflectionClass($className);
			$property = $currentClass->getProperty($field->field);
			$properties[] = $property;
			
			// We're done? We'll return $property at the end.
			
			if ($i + 1 >= $fieldCount)
				break;
			
			$className = $reflector->getTypeFromProperty($property);
			if (!$className)
				throw new \InvalidArgumentException(
					"Failed to reflect on field reference '$dottedReference' of class {$originalClass->getName()}");
			
		}
		
		return $properties;
	}
}