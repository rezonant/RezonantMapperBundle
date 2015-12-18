<?php

namespace Rezonant\MapperBundle\Map;
use Rezonant\MapperBundle\Utilities\PathParser;
use Rezonant\MapperBundle\Utilities\Reflector;
use Rezonant\MapperBundle\Exceptions\UnableToMapException;

class Reference {
	public function __construct($ref, $class = null) {
		$pathParser = new PathParser;
		$reflector = new Reflector;
		
		$this->rootClass = $class;
		$this->string = $ref;
		$this->fields = $pathParser->parse($ref);
		
		if ($class) {
			$this->reflectionFields = $this->getDeepProperty($class, $ref);
			$this->types = array_map(
				function($field) use ($reflector) { 
					return $reflector->getType($field);
				}, $this->reflectionFields);
		}
	}
	
	private $rootClass;
	private $string;
	private $fields;
	private $reflectionFields;
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

	function getReflectionFields() {
		return $this->reflectionFields;
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
		$reflectionField = null;
		$className = $originalClass->getName();
		$reflectionFields = array();
		
		foreach ($fields as $i => $field) {
		
			if ($className == '<array>') {
				$reflectionFields[] = '<array>';
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
			
			$fieldName = $field->field;
			$methodName = "get$fieldName";
			
			if($currentClass->hasMethod($methodName)){ //Method
				$reflectionField = $currentClass->getMethod($methodName);
				
			} else if($currentClass->hasProperty($fieldName)){ //Property
				$reflectionField = $currentClass->getProperty($fieldName);
			} else{
				throw new \Exception("Could get property \"{$fieldName}\" in the class \"{$className}\" it either does not exist or does not have a getter.");
			}
			
			$reflectionFields[] = $reflectionField;
			
			// We're done? We'll return $property at the end.
			if ($i + 1 >= $fieldCount)
				break;
			
			
			$className = $reflector->getType($reflectionField);

			
			if (!$className)
				throw new \InvalidArgumentException(
					"Failed to reflect on field reference '$dottedReference' of class {$originalClass->getName()}");
			
		}
		
		return $reflectionFields;
	}
}