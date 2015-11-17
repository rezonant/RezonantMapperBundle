<?php

namespace Rezonant\MapperBundle;

use Rezonant\MapperBundle\Exceptions\InvalidTypeException;
use Rezonant\MapperBundle\Exceptions\FabricationFailedException;
use Rezonant\MapperBundle\Utilities\PathParser;
use Rezonant\MapperBundle\Exceptions\UnableToMapException;
use Rezonant\MapperBundle\Providers\MapProviderInterface;

/**
 * Maps between two objects
 */
class Mapper {
	
	/**
	 * Construct a new instance of Mapper
	 */
	public function __construct(MapProviderInterface $provider) {
		$this->mapProvider = $provider;
		$this->pathParser = new PathParser();
	}
	
	/**
	 * @var MapProviderInterface
	 */
	private $mapProvider;
	
	/**
	 * @var PathParser
	 */
	private $pathParser;
	
	public function mapBack($source, $destination, $forwardMap = NULL)
	{
		// Create a forward map if none is provided
		if (!$forwardMap) {
			$forwardMap = $this->mapProvider->getMap($destination, $source);
			if (!$forwardMap)
				throw new UnableToMapException($this->mapProvider, $destination, $source);
		}
		
		$reverseMap = $forwardMap->invert();
		
		return $this->map($source, $destination, $reverseMap);
	}
	
	/**
	 * Map from the given source to the given destination. This is the primary method of this
	 * service, and is used to kick off all mapping operations. The final result of the mapping
	 * operation is returned for cases where the destination is created while mapping or the destination
	 * is a pass-by-value type like an array, etc. Note that if you pass in an object as the destination,
	 * you do not need to take the return value as they will be identical.
	 * 
	 * @param mixed $source An object or array to map from.
	 * @param mixed $destination May be an existing object, an array, 
	 *							 or a string class name (in which case the object will be
	 *							 created for you and returned).
	 * @param Map $map A map which specifies how to translate between the source and destination.
	 *					If none is provided, the MapProvider specified during construction of this
	 *					Mapper object shall be used to attempt to produce a working map between the 
	 *					two objects. MapProviders can do this in many ways- by consulting caches,
	 *					reading annotations, reading mapping configuration files, etc.
	 * @return mixed The fully mapped $destination. 
	 *				 You must use this value if the destination is an array.
	 *				 
	 * @throws UnableToMapException Thrown if no map is provided and the configured MapProvider
	 *								is unable to produce a valid map to direct the mapping process.
	 */
	public function map($source, $destination, $map = NULL)
	{
		if (is_string($source))
			$source = new $source;
		
		if (is_string($destination))
			$destination = new $destination;
		
		// If the user did not provide an explicit map, get one from the mapping provider.
		
		if (!$map) {
			$map = $this->mapProvider->getMap($source, $destination);
			if (!$map)
				throw new UnableToMapException($this->mapProvider, $source, $destination);
		}
		
		foreach ($map->getFields() as $field) {
			$name = $field->getSource()->toString();
			$destinationField = $field->getDestination()->getFields();
			$value = $this->get($source, $name);
			
			$destination = $this->deepSet($destination, $destinationField, $value, 
					$field->getDestination()->getTypes(), $field->getSubmap());
		}
		
		return $destination;
	}
	
	/**
	 * Determines what class the given field _should_ be, and then
	 * constructs an instance of that type and returns it.
	 * 
	 * @param object $destination
	 * @param string $fieldName
	 */
	private function fabricateInstance($destinationType = null)
	{
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
	private function deepSet(&$destination, $path, $value, $destinationClasses = NULL, $map = NULL)
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
			
			$destinationClass = null;
			if (isset($destinationClasses[$i]))
				$destinationClass = $destinationClasses[$i];
			
			// Are we ready to set?
			if ($i + 1 >= $pathCount) {
				// OK we need to set!
				
				if ($destinationClass && $map) {	
					$nextDestination = new $destinationClass();
					$nextDestination = $this->map($value, $nextDestination, $currentMap);
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
				
				$fieldValue = $this->fabricateInstance(
					$mapField? $mapField->getDestinationType() : $destinationClass
				);
				
				$this->set($current, $field, $fieldValue);
				
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
	}
	
	/**
	 * Sets the property named $fieldName on $instance to $value.
	 * 
	 * @param object $instance The instance for which the field will be set to the value
	 * @param mixed $field A string field name, a field reference object, or an array of field reference objects
	 * @param mixed $value The value to set the field to
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
	 * @param mixed $field A string path (see PathParser), an array of path field reference objects,
	 *						or a single field reference object. 
	 * @return mixed
	 * @throws \Exception Thrown if no such field/property exists
	 */
	private function get($instance, $field)
	{	
		if (is_string($field))
			$field = $this->pathParser->parse($field);
		
		if (is_array($field))
			$path = $field;
		else
			$path = array($field);
		
		// Walk the path to the penultimate object.
		
		$contextObject = &$instance;
		foreach ($path as $i => $item) {
			if ($i + 1 >= count($path))
				continue;
			$contextObject = $this->get($contextObject, $item);
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