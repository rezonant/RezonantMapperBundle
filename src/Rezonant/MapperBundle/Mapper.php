<?php

namespace Rezonant\MapperBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Rezonant\MapperBundle\Exceptions\InvalidTypeException;
use Rezonant\MapperBundle\Exceptions\FabricationFailedException;

/**
 * Maps between two objects
 */
class Mapper {
	
	/**
	 * Construct a new instance of Mapper
	 * @param AnnotationReader $reader
	 */
	public function __construct(AnnotationReader $reader) {
		$this->annotationReader = $reader;
	}
	
	/**
	 * @var AnnotationReader
	 */
	private $annotationReader;
	
	/**
	 * Return class name string if there is type information available for the given object/field
	 * 
	 * @param type $object
	 * @param type $fieldName
	 */
	private function getType($object, $fieldName)
	{
		$class = new \ReflectionClass($object);
		
		if (!$class->hasProperty($fieldName))
			return false;
		
		$prop = $class->getProperty($fieldName);
		return $this->getTypeFromProperty($prop);
	}
	
	/**
	 * Maps the source object data into the destination object, using the given map.
	 */
	private function mapExplicitly($source, $destination, Map $map) {
		
		foreach ($map->getFields() as $field) {
			$name = $field->getName();
			$destinationField = $this->parsePath($field->getDestinationField());
			$value = $this->get($source, $name);
			
			$destination = $this->deepSet($destination, $destinationField, $value, 
					$field->getDestinationType(), $field->getSubmap());
		}
		
		return $destination;
		
	}
	
	public function map($source, $destination, $map = NULL)
	{
		if (is_string($source))
			$source = new $source;
		
		if (is_string($destination))
			$destination = new $destination;
		
		return $this->mapExplicitly(
				$source, $destination, 
				$map? $map : $this->generateMap($source, $destination)
		);
	}
	
	private function isStandard($object)
	{
		return is_array($object) || (is_object($object) && get_class($object) == 'stdclass');
	}
	
	private function generateMap($source, $destination)
	{
		// [x] Use case 1: Source is array/stdclass, destination is model
		// [x] Use case 2: Source is model which knows about entity, destination is entity with no knowledge of model
		// [x] Use case 3: Source/dest are both array/stdclass. Direct mapping only.
		
		// Property on source class can have @Mapper\MapTo("fieldName") annotations to map into destination field
		// Property on source class can have @Mapper\Type("Type") to allow Mapper to automatically create instances
		
		// Standard classes/arrays
		if ($this->isStandard($source) && $this->isStandard($destination)) {
			$map = new MapBuilder();
			foreach ($source as $k => $v) {
				$map->field($k, $k);
			}
			
			return $map->build();
		}
		
		// ie: Request -> Model
		if ($this->isStandard($source)) {
			return $this->mapToModel($destination);
		}
		
		// ie: Model -> Entity
		return $this->mapFromModel($source, $destination);
	}
	
	/**
	 * Map using the @MapTo() annotations found in the object
	 * @param object $modelOrClass A class or object to map from
	 * @param object $entityOrClass A class or object to map to
	 */
	public function mapFromModel($modelOrClass, $entityOrClass) {
		$class = new \ReflectionClass($modelOrClass);
		$annotationName = 'Rezonant\\MapperBundle\\Annotations\\MapTo';
		$map = new MapBuilder();
		$destinationClass = new \ReflectionClass($entityOrClass);
		
		foreach ($class->getProperties() as $property) {
			$annotation = $this->annotationReader->getPropertyAnnotation($property, $annotationName);
			
			// Resolve the type of this property for submapping later.
			
			$subSourceType = $this->getTypeFromProperty($property);
			$subDestType = null;
			$fieldValue = null;
			$submap = null;
			$destinationField = null;
			
			// Use the annoted destination field, or assume that the mapping
			// is 1-to-1.
			
			if ($annotation)
				$destinationField = $annotation->value;
			else if ($destinationClass->hasProperty($property->name))
				$destinationField = $property->name;
			
			// Resolve the destination field's type for generating a submap.
			
			if ($destinationField) {
				$subDeepProperty = $this->getDeepProperty($destinationClass, $destinationField);
				$subDestType = $this->getTypeFromProperty($subDeepProperty);
			}
			
			if ($subSourceType && $subDestType)
				$submap = $this->mapFromModel ($subSourceType, $subDestType);
			
			if ($destinationField)
				$map->field($property->name, $destinationField, $subDestType, $submap);
		}
		
		return $map->build();
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
		$originalClass = $class;
		$fields = $this->parsePath($dottedReference);
		$fieldCount = count($fields);
		$property = null;
		$className = $originalClass->getName();
		
		foreach ($fields as $i => $field) {
		
			$isPrimitive = $this->isPrimitiveType($className);
			
			// Cannot traverse into primitive types (it is impossible for this
			// to happen on first iteration)
			
			if ($isPrimitive) {
				throw new \InvalidArgumentException(
						"Cannot traverse into primitive type $className "
						. "to satisfy reference '{$field->field}' "
						. "of full reference '$dottedReference'"
				);
			}
			
			$currentClass = new \ReflectionClass($className);
			$property = $currentClass->getProperty($field->field);
			
			// We're done? We'll return $property at the end.
			
			if ($i + 1 >= $fieldCount)
				break;
			
			$className = $this->getTypeFromProperty($property);
			if (!$className)
				throw new \InvalidArgumentException(
					"Failed to reflect on field reference '$dottedReference' of class {$originalClass->getName()}");
			
			if ($className == '<array>') {
				// Oh, this isn't so bad..
				return "<array>";
			}
			
		}
		
		return $property;
	}
	
	private function isPrimitiveType($type)
	{
		return preg_match('#^<.*>$#', $type);
	}
	
	/**
	 * Create a map for an array/stdclass to an object providing @FromRequest annotations
	 * 
	 * @param string $source The root path string of an anonymous object which will source this mapping.
	 * @param object $destination An object with a class providing some @FromRequest annotations (or not whatever)
	 */
	public function mapToModel($destinationOrClass) {
		
		$type = new \ReflectionClass($destinationOrClass);
		$map = new MapBuilder();
		
		foreach ($type->getProperties() as $property) {
			$fromRequest = $this->annotationReader->getPropertyAnnotation(
					$property, 'Rezonant\MapperBundle\Annotations\FromRequest');
			
			if (!$fromRequest)
				continue;
			
			$name = $fromRequest->value;
			if (!$name)
				$name = $property->name;
			
			$subtype = $this->getTypeFromProperty($property);
			$submap = null;
			
			if ($subtype) {
				$submap = $this->mapToModel($subtype);
			}
			
			$map->field($name, $property->name, $subtype, $submap);
		}
		
		return $map->build();
	}
	
	/**
	 * Get the designated class name from the given property
	 * 
	 * @param mixed $property Can be either a \ReflectionProperty or a primitive string type.
	 * @return string If $property was a primitive string type (ie <array>) then that string is returned.
	 *					If $property is a \ReflectionProperty, the type of that property is returned, or null if
	 *					one could not be determined.
	 */
	private function getTypeFromProperty($property)
	{
		if ($this->isPrimitiveType($property))
			return $property;
		
		if (is_string($property)) {
			throw new \InvalidArgumentException(
					'Parameter $property cannot be a string unless the string is a valid primitive type'
			);
		}
		
		$typeAnnotation = $this->annotationReader->getPropertyAnnotation(
				$property, 'Rezonant\\MapperBundle\\Annotations\\Type');
		
		if (!$typeAnnotation)
			return null;
		
		return $typeAnnotation->value;
	}
	
	/**
	 * Parses the given string path into an intermediate representation suitable for use
	 * with the other functions within this service
	 * 
	 * @param string $path A string path like "foo.bar.[baz]"
	 * @return array An array of objects, each object with a 'type' (array or object) 
	 *					  and a 'field' (name of the field)
	 */
	private function parsePath($path)
	{
		if (!is_string($path))
			throw new \InvalidArgumentException('Parameter $path must be a string');
		
		$split = explode('.', $path);
		$retPath = array();
		
		foreach ($split as $part) {
			
			if ($part[0] == '[') {
				$retPath[] = (object)array(
					'type' => 'array',
					'field' => preg_replace('#^\[(.*)\]$#', '\1', $part)
				);
			} else {
				$retPath[] = (object)array(
					'type' => 'object',
					'field' => $part
				);
			}
		}
		
		return $retPath;
	}
	
	/**
	 * Determines what class the given field _should_ be, and then
	 * constructs an instance of that type and returns it.
	 * 
	 * @param object $destination
	 * @param string $fieldName
	 */
	private function fabricateInstance($destination, $fieldName, $destinationType = null)
	{
		if (!$destinationType) {
			$class = new \ReflectionClass($destination);
			$prop = $class->getProperty($fieldName);

			$typeAnnotation = $this->annotationReader->getPropertyAnnotation(
					$prop, 'Rezonant\\MapperBundle\\Annotations\\Type');

			if (!$typeAnnotation) {
				throw new FabricationFailedException(
					"Cannot fabricate instance for field "
					. get_class($destination)."::\$$fieldName: "   
					. "No @Mapper\Type annotation present."
				);
			}

			$destinationType = $typeAnnotation->value;
		}
		
		if ($destinationType == '<array>')
			return array();
		
		if (!class_exists($destinationType, true))
			throw new InvalidTypeException($destinationType);
		
		$instance = new $destinationType();
		
		return $instance;
	}
	
	private function arrayBackSet($visited, $path, $array)
	{
		if (empty($visited))
			return;
		
		$lastVisited = array_pop($visited);
		$lastPath = array_pop($path);
		
		$this->set($lastVisited, $lastPath, $array);
		
		if (is_array($lastVisited))
			return arrayBackSet($visited, $path, $lastVisited);
	}
	
	/**
	 * Constructs any objects necessary along the given object path,
	 * starting from $destination.
	 * 
	 * @param mixed $destination An object or an array. This parameter is pass by reference in the case of arrays
	 * @param array $path An array of path items
	 * @param Map $map The map
	 * @return type
	 * @throws \Exception
	 */
	private function deepSet(&$destination, $path, $value, $destinationClass = NULL, $map = NULL)
	{
		$originalDestination = $destination;
		$current = $originalDestination;
		$pathCount = count($path);
		$currentMap = $map;
		$visited = array();
		$visitedPath = array();
		
		$vd = function($v) {
			ob_start();
			var_dump($v);
			return ob_get_clean();
		};
		
		foreach ($path as $i => $field) {
			
			if (!is_object($current) && !is_array($current)) {
				throw new \InvalidArgumentException("Cannot traverse into non-object: ".$vd($current));
			}
			
			// Are we ready to set?
			if ($i + 1 >= $pathCount) {
				// OK we need to set!
				
				if ($destinationClass && $map) {	
					$nextDestination = new $destinationClass();
					$this->mapExplicitly($value, $nextDestination, $currentMap);
					$this->set($current, $field, $nextDestination);
				} else {
					$this->set($current, $field, $value);
				}
				
				if (is_array($current)) {
					$this->arrayBackSet($visited, $visitedPath, $current);
				}
				
				break;
			}
			
			$mapField = null;
			if ($currentMap) {
				$mapField = $currentMap->getField($field->field);
			}
			
			$visited[] = $current;
			$visitedPath[] = $field;	
			
			if (!$this->get($current, $field)) {
				// This needs to be an object! Make it so!
				$fieldValue = &$this->fabricateInstance($current, $field->field, 
						$mapField? $mapField->getDestinationType() : null);
				
				$this->set($current, $field, $fieldValue);
				
				//$fieldValue = &$this->get($current, $field);
				$current = &$fieldValue;
			} else {
				$current = &$this->get($current, $field);
			}
			
			if ($currentMap && $mapField) {
				$currentMap = $mapField->getSubmap();
			} else {
				$currentMap = null;
				$mapField = null;
			}
		}
		
		if (count($visited) == 0)
			return $current;
		else
			return $visited[0];
		
		//return $originalDestination;
	}
	
	/**
	 * Sets the property named $fieldName on $instance to $value.
	 * 
	 * @param object $instance
	 * @param string $fieldName
	 * @param mixed $value
	 */
	private function set(&$instance, $field, &$value)
	{
		if (!is_object($instance) && !is_array($instance))
			throw new \InvalidArgumentException("Instance must be an object or array");
		
		if (is_string($field))
			$field = (object)array('field' => $field);
		
		if (is_array($instance)) {
			$instance[$field->field] = &$value;
			return;
		}
		
		if ($instance instanceof \stdclass) {
			$instance->{$field->field} = &$value;
			return;
		}
		
		$methodName = "set{$field->field}";
		
		if (method_exists($instance, $methodName)) {
			
			//var_dump($instance);
			//var_dump($methodName);
			$instance->$methodName($value);
		} else {
			$property = new \ReflectionProperty(get_class($instance), $field->field);
			$property->setAccessible(true);
			$property->setValue($instance, $value);
		}
	}
	
	/**
	 * Gets the value of the property named $field on $instance.
	 * 
	 * @param object $instance
	 * @param mixed $field A string path (see parsePath()), an array of path field reference objects,
	 *						or a single field reference object. 
	 * @return mixed
	 * @throws \Exception Thrown if no such field/property exists
	 */
	private function get(&$instance, $field)
	{	
		if (is_string($field))
			$field = $this->parsePath($field);
		
		if (is_array($field))
			$path = $field;
		else
			$path = array($field);
		
		// Walk the path to the penultimate object.
		
		$contextObject = &$instance;
		foreach ($path as $i => $item) {
			if ($i == 0)
				continue;
			$contextObject = &$this->get($contextObject, $item);
		}
		
		$fieldName = $path[count($path)-1]->field;
		
		// Arrays...
		
		if (is_array($contextObject)) {
			if (!isset($contextObject[$fieldName]))
				return null;
			
			return $contextObject[$fieldName];
		}
		
		// Doesn't exist _yet_!
		
		if (is_null($contextObject))
			return null;
		
		// Various ways of retrieving an object field...
		
		if (is_object($contextObject)) {
			$methodName = "get$fieldName";
			if (method_exists($contextObject, $methodName))
				return $contextObject->$methodName();
			else if (property_exists ($contextObject, $fieldName))
				return $contextObject->$fieldName;
			else
				throw new \InvalidArgumentException("No such property $fieldName on object of class ".get_class($contextObject));
		}
		
		throw new \InvalidArgumentException("Cannot set field $fieldName on a non-object:\nPath trace: ".print_r($path, true)."\nObject trace: ".print_r($contextObject, true));
	}
}