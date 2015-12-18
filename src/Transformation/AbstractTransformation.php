<?php
namespace Rezonant\MapperBundle\Transformation;
use Rezonant\MapperBundle\Map\Reference;
/**
 * TransformationInterface.
 *
 * @author Nathan Erickon
 */
abstract class AbstractTransformation implements TransformationInterface {
	
	private $direction = 1;
	
	public function getDirection(){
		return $this->direction;
	}
	
	public function setDirection($direction){
		$this->direction = $direction;
	}
	
	public function invert(){
		if($this->getDirection() == self::FORWARD){
			$this->setDirection(self::REVERSE);
		}
		else if ($this->getDirection() == self::REVERSE){
			$this->setDirection(self::FORWARD);
		}
		return $this;
	}
	
	public function transform($value, $field, $source, $destination){
		if($this->getDirection() == self::FORWARD){
			return $this->forward($value, $field, $source, $destination);
		}
		else if ($this->getDirection() == self::REVERSE){
			return $this->reverse($value, $field, $source, $destination);
		}
	}
	
	/**
	 * When mapping source to destination the source field value is passed in and new value will be expected to be returned
	 * @param type $sourceFieldValue
	 * @return type Transformed $sourceFieldValue
	 */
	public function forward($sourceFieldValue, $field, $source, $destination){
		return $sourceFieldValue;
	}
	
	/**
	 * When mapping destination to source the destination field value is passed in and new value will be expected to be returned
	 * @param type $destinationFieldValue
	 * @return type Transformed $sourceFieldValue
	 */
	public function reverse($destinationFieldValue, $field, $source, $destination){
		return $destinationFieldValue;
	}
}