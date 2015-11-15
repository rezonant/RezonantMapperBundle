<?php

namespace Rezonant\MapperBundle\Providers;
use Doctrine\Common\Annotations\AnnotationReader;
use Rezonant\MapperBundle\Utilities\PathParser;
use Rezonant\MapperBundle\MapBuilder;

class AnnotationMapProvider extends MapProvider {
	
	public function __construct(AnnotationReader $reader) {
		$this->annotationReader = $reader;
		$this->pathParser = new PathParser();
	}

	/**
	 * @var PathParser
	 */
	private $pathParser;
	
	/**
	 * @var AnnotationReader
	 */
	private $annotationReader;
	
	public function getMap($source, $destination) {
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
				$subDeepProperties = $this->getDeepProperty($destinationClass, $destinationField);
				$self = $this;
				$subDestTypes = array_map(
					function($prop) use ($self) { 
						return $self->getTypeFromProperty($prop);
					}, $subDeepProperties);
				
				$subDestType = $subDestTypes[count($subDestTypes) - 1];
			}
			
			if ($subSourceType && $subDestType)
				$submap = $this->mapFromModel ($subSourceType, $subDestType);
			
			if ($destinationField)
				$map->field($property->name, $destinationField, $subDestTypes, $submap);
		}
		
		return $map->build();
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
			
			$map->field($name, $property->name, array($subtype), $submap);
		}
		
		return $map->build();
	}
	
	private function isStandard($object)
	{
		return is_array($object) || (is_object($object) && get_class($object) == 'stdclass');
	}
	
	private function isPrimitiveType($type)
	{
		return preg_match('#^<.*>$#', $type);
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
		$fields = $this->pathParser->parse($dottedReference);
		$fieldCount = count($fields);
		$property = null;
		$className = $originalClass->getName();
		$properties = array();
		
		foreach ($fields as $i => $field) {
		
			if ($className == '<array>') {
				$properties[] = '<array>';
				continue;
			}
			
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
			$properties[] = $property;
			
			// We're done? We'll return $property at the end.
			
			if ($i + 1 >= $fieldCount)
				break;
			
			$className = $this->getTypeFromProperty($property);
			if (!$className)
				throw new \InvalidArgumentException(
					"Failed to reflect on field reference '$dottedReference' of class {$originalClass->getName()}");
			
		}
		
		return $properties;
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
	
}
