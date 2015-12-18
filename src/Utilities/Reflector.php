<?php

namespace Rezonant\MapperBundle\Utilities;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;

class Reflector {
	public function __construct(Reader $reader = NULL) {
		if (!$reader)
			$reader = new AnnotationReader();
		$this->annotationReader = $reader;
	}
	
	/**
	 * @var Reader
	 */
	private $annotationReader;
	
	
	public function getType($field) {
		if ($this->isPrimitiveType($field)) {
			return $field;
		}
		
		if (is_string($field)) {
			throw new \InvalidArgumentException(
					'Parameter $property cannot be a string unless the string is a valid primitive type'
			);
		}
		
		if($field instanceof \ReflectionMethod){
			return $this->getTypeFromMethod($field);
		}
		
		if($field instanceof \ReflectionProperty){
			return $this->getTypeFromProperty($field);
		}
		
		return null;
		
		/*throw new \InvalidArgumentException(
				'Could not evaluate the type of the field because it was not primitave or a supported reflection class'
		);*/
	}
	
	/**
	 * Get the designated class name from the given property
	 * 
	 * @param mixed $property Can be either a \ReflectionProperty or a primitive string type.
	 * @return string If $property was a primitive string type (ie <array>) then that string is returned.
	 *					If $property is a \ReflectionProperty, the type of that property is returned, or null if
	 *					one could not be determined.
	 */
	public function getTypeFromProperty($property)
	{
		if ($this->isPrimitiveType($property)) {
			return $property;
		}
		
		if (is_string($property)) {
			throw new \InvalidArgumentException(
					'Parameter $property cannot be a string unless the string is a valid primitive type'
			);
		}
		
		$typeAnnotation = $this->annotationReader->getPropertyAnnotation(
				$property, 'Rezonant\\MapperBundle\\Annotations\\Type');
		if ($typeAnnotation)
			return $typeAnnotation->value;
		
		$typeAnnotation = $this->annotationReader->getPropertyAnnotation(
				$property, 'JMS\\Serializer\\Annotation\\Type');
		
		if ($typeAnnotation)
			return $typeAnnotation->name;
		
		return null;
	}

	//IN PROGRESS!!! TODO
	public function getTypeFromMethod($method)
	{
		if ($this->isPrimitiveType($method)) {
			return $method;
		}
		
		if (is_string($method)) {
			throw new \InvalidArgumentException(
				'Parameter $property cannot be a string unless the string is a valid primitive type'
			);
		}
		
		$typeAnnotation = $this->annotationReader->getMethodAnnotation(
				$method, 'Rezonant\\MapperBundle\\Annotations\\Type');
		if ($typeAnnotation)
			return $typeAnnotation->value;
		
		$typeAnnotation = $this->annotationReader->getMethodAnnotation(
				$method, 'JMS\\Serializer\\Annotation\\Type');
		
		if ($typeAnnotation)
			return $typeAnnotation->name;
		
		return null;
	}
	
	public function isStandard($object)
	{
		return is_array($object) || (is_object($object) && get_class($object) == 'stdclass');
	}
	
	public function isPrimitiveType($type)
	{
		return preg_match('#^<.*>$#', $type);
	}
	
	public function describeType($object)
	{
		if (is_null($object))
			return '<null>';
		if (is_string($object))
			return '<string>';
		if (is_array($object))
			return '<array>';
		if (is_object($object))
			return get_class($object);
		
		if (is_int($object))
			return '<int>';
		if (is_float($object))
			return '<float>';
		if (is_bool($object))
			return '<boolean>';
		if (is_long($object))
			return '<long>';
		if (is_resource($object))
			return '<resource>';
		
		return '<unknown>';
	}
}