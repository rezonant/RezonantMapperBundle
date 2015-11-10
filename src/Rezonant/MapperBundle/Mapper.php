<?php

namespace Rezonant\MapperBundle;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * 
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

		$typeAnnotation = $this->annotationReader->getPropertyAnnotation(
				$prop, 'Rezonant\\MapperBundle\\Annotations\\Type');
		
		if (!$typeAnnotation)
			return false;
		
		return $typeAnnotation->value;
	}
	
	/**
	 * Maps the source object data into the destination object, using the given map.
	 */
	public function mapExplicitly($source, $destination, Map $map) {
		
		foreach ($map->getFields() as $field) {
			$name = $field->getName();
			$destinationField = $this->parsePath($field->getDestinationField());
			$submap = $field->getSubmap();
			$value = $this->get($source, $name);
			
			list($realDestination, $realField) = $this->prepareForSet($destination, $destinationField);
			
			/**
			 * Make sure we have enough information to create the destination object
			 */
			
			$sourceType = "<unknown>";
			if (is_object($value))
				$sourceType = get_class($value);
			else if (is_array($value))
				$sourceType = "<array>";
			else if (is_int($value))
				$sourceType = "<int>";
			else if (is_bool ($value))
				$sourceType = "<bool>";
			else if (is_string ($value))
				$sourceType = "<string>";
			
			$destinationType = $this->getType($realDestination, $realField->field);
			$allowMapping = (is_object($value) || is_array($value)) && $destinationType !== false;
			
			/**
			 * If the value we received from the source does not match the type annotation of the destination,
			 * we will attempt to map it somehow.
			 */
			if ($allowMapping && $sourceType != $destinationType) {
				$subdestination = $this->get($realDestination, $realField);
				
				if (!$subdestination)
					$subdestination = $this->fabricateInstance($realDestination, $realField->field);

				if (!$submap)
					$submap = $this->generateMap($value, $subdestination);

				$this->mapExplicitly($value, $subdestination, $submap);
				$value = $subdestination;
			}
			
			$this->set($realDestination, $realField, $value);
		}
		
		return $destination;
		
	}
	
	public function map($source, $destination)
	{
		if (is_string($source))
			$source = new $source;
		
		if (is_string($destination))
			$destination = new $destination;
		
		return $this->mapExplicitly(
				$source, $destination, 
				$this->generateMap($source, $destination)
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
			return $this->mapToModel($source, $destination);
		}
		
		// ie: Model -> Entity
		$map = $this->mapFromModel($source, $destination);
		
		return $map;
	}
	
	/**
	 * Map using the @MapTo() annotations found in the object
	 * @param object $model
	 * @param object $entity
	 */
	public function mapFromModel($model, $entity) {
		$class = new \ReflectionClass($model);
		$annotationName = 'Rezonant\\MapperBundle\\Annotations\\MapTo';
		$map = new MapBuilder();
		$destinationClass = new \ReflectionClass($entity);
		
		foreach ($class->getProperties() as $property) {
			$annotation = $this->annotationReader->getPropertyAnnotation($property, $annotationName);
			
			if ($annotation) {
				$map->field($property->name, $annotation->value);
				continue;
			}
			
			if ($destinationClass->hasProperty($property->name)) {
				$map->field($property->name, $property->name);
				continue;
			}
		}
		
		return $map->build();
	}
	
	/**
	 * Create a map for an array/stdclass to an object providing @FromRequest annotations
	 * 
	 * @param object $source A dumb (stdclass/array) source object
	 * @param object $destination An object with a class providing some @FromRequest annotations (or not whatever)
	 */
	public function mapToModel($source, $destination) {
		
		if (is_array($source))
			$source = (object)$source;
		
		$type = new \ReflectionClass($destination);
		$map = new MapBuilder();
		
		foreach ($type->getProperties() as $property) {
			$fromRequest = $this->annotationReader->getPropertyAnnotation(
					$property, 'Rezonant\MapperBundle\Annotations\FromRequest');
			
			if (!$fromRequest)
				continue;
			
			$name = $fromRequest->value;
			if (!$name)
				$name = $property->name;
			
			$map->field($name, $property->name);
		}
		
		return $map->build();
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
	private function fabricateInstance($destination, $fieldName)
	{
		
		$class = new \ReflectionClass($destination);
		$prop = $class->getProperty($fieldName);

		$typeAnnotation = $this->annotationReader->getPropertyAnnotation(
				$prop, 'Rezonant\\MapperBundle\\Annotations\\Type');
		
		if (!$typeAnnotation) {
			throw new \Exception(
				"Cannot fabricate instance for field "
				. get_class($destination)."::\$$fieldName: "   
				. "No @Mapper\Type annotation present."
			);
		}
		
		$type = $typeAnnotation->value;
		
		if (!class_exists($type, true)) {
			throw new \Exception("Type '$type' is not a class");
		}
		
		$instance = new $type();
		
		return $instance;
	}
	
	/**
	 * Constructs any objects necessary along the given object path,
	 * starting from $destination.
	 * 
	 */
	private function prepareForSet($destination, $path, $visited = array(), $originalPath = null)
	{
		if (!$originalPath)
			$originalPath = $path;
		
		if (count($path) == 1)
			return array($destination, $path[0], $originalPath);
		
		$field = $path[0];
		$fieldName = $field->field;
		
		$type = new \ReflectionClass($destination);
		$fieldValue = $this->get($destination, $field);
		if (!$fieldValue) {
			// This needs to be an object! Make it so!
			$fieldValue = $this->fabricateInstance($destination, $field->field);			
			$this->set($destination, $field, $fieldValue);
		}
		
		$subset = $path;
		array_shift($subset);
		
		try {
			return $this->prepareForSet($fieldValue, $subset, $visited);
		} catch (\Exception $e) {
			
			if (count($visited) > 0)
				throw $e;
			
			throw new \Exception("Failed to prepare objects before setting $path: ".$e->getMessage());
		}
	}
	
	/**
	 * Sets the property named $fieldName on $instance to $value.
	 * 
	 * @param object $instance
	 * @param string $fieldName
	 * @param mixed $value
	 */
	private function set($instance, $field, $value)
	{
		if (is_string($field))
			$field = (object)array('field' => $field);
		
		$methodName = "set{$field->field}";
		
		if (method_exists($instance, $methodName)) {
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
	 * @param string $field
	 * @return mixed
	 * @throws \Exception Thrown if no such field/property exists
	 */
	private function get($instance, $field)
	{	
		if (is_string($field)) {
			$field = (object)array('type' => is_array($instance)? 'array' : 'object', 'field' => $field);
		}
		
		$path = $this->parsePath($field->field);
		$fieldName = $field->field;
		
		if (count($path) > 1) {
			
			$lastItem = array_pop($path);
			$contextObject = $instance;
			foreach ($path as $item) {
				if (is_array($contextObject))
					$contextObject = $contextObject[$item->field];
				else
					$contextObject = $this->get($contextObject, $item);
			}
			
			$instance = $contextObject;
			$fieldName = $lastItem->field;
		}
		
		if (is_array($instance))
			return $instance[$fieldName];
		
		//if ($field->type == 'array')
		//	throw new \Exception("Field type should be array for array source object, not {$field->type}");
		
	
		$methodName = "get$fieldName";
		
		if (method_exists($instance, $methodName))
			return $instance->$methodName();
		else if (property_exists ($instance, $fieldName))
			return $instance->$fieldName;
		else
			throw new \Exception("No such property $fieldName on object of class ".get_class($instance));
	}
}