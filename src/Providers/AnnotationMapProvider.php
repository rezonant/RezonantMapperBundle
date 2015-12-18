<?php

namespace Rezonant\MapperBundle\Providers;
use Doctrine\Common\Annotations\Reader;
use Rezonant\MapperBundle\Utilities\PathParser;
use Rezonant\MapperBundle\MapBuilder;
use Rezonant\MapperBundle\Map\Reference;
use Rezonant\MapperBundle\Utilities\Reflector;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnnotationMapProvider extends MapProvider {
	
	public function __construct(Reader $reader, ContainerInterface $container) {
		$this->annotationReader = $reader;
		$this->pathParser = new PathParser();
		$this->reflector = new Reflector();
		$this->container = $container;
	}

	/**
	 *
	 * @var ContainerInterface 
	 */
	private $container;
	
	/**
	 * @var Reflector
	 */
	private $reflector;
	
	/**
	 * @var PathParser
	 */
	private $pathParser;
	
	/**
	 * @var AnnotationReader
	 */
	private $annotationReader;
	
	public function getMap($source, $destination) {
		
		// Standard classes/arrays (Deprecated?)
		if ($this->isStandard($source) && $this->isStandard($destination)) {
			$map = new MapBuilder();
			foreach ($source as $k => $v) {
				$map->field(new Reference($k), new Reference($k));
			}
			
			return $map->build();
		}
		
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
		$doctrineEntityAnnotationName = 'Rezonant\\MapperBundle\\Annotations\\DoctrineEntity';
		$map = new MapBuilder();
		$destinationClass = new \ReflectionClass($entityOrClass);
		
		foreach ($class->getProperties() as $property) {
			$annotation = $this->annotationReader->getPropertyAnnotation($property, $annotationName);
			
			$doctrineEntityAnnotation = $this->annotationReader->getPropertyAnnotation($property, $doctrineEntityAnnotationName);
			
			// Resolve the type of this property for submapping later.
			
			$subSourceType = $this->reflector->getTypeFromProperty($property);
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
			
			//figure out if there is a transformation
			$transformation = null;
			if($doctrineEntityAnnotation){
				//get the doctrine entity tranformation rezonant.mapper.doctrine_entity_transformation
				$transformation = $this->container->get('rezonant.mapper.doctrine_entity_transformation');
				$transformation->setEntityClass($doctrineEntityAnnotation->value);
				$transformation->setId($doctrineEntityAnnotation->id);
			}
			
			// Resolve the destination field's type for generating a submap.
			
			if ($destinationField) {
				$destReference = new Reference($destinationField, $destinationClass);
				$subDestTypes = $destReference->getTypes();
				$subDestType = $subDestTypes[count($subDestTypes) - 1];
				
				if ($subSourceType && $subDestType)
					$submap = $this->mapFromModel ($subSourceType, $subDestType);
			
				$map->field(
					new Reference($property->name, $class), 
					$destReference, 
					$submap,
					$transformation);
			}
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
			
			$className = $this->reflector->getTypeFromProperty($property);
			if (!$className)
				throw new \InvalidArgumentException(
					"Failed to reflect on field reference '$dottedReference' of class {$originalClass->getName()}");
			
		}
		
		return $properties;
	}
	
}
