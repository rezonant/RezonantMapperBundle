<?php
namespace Rezonant\MapperBundle\Transformation;
use Rezonant\MapperBundle\Map\Reference;

/**
 * TransformationInterface.
 *
 * @author Nathan Erickon
 */
interface TransformationInterface {
	
	const FORWARD = 1;
	const REVERSE = 2;
	
	/**
	 * Gets current transformation direction state
	 */
	public function getDirection();
	
	/**
	 * Sets current transformation direction state
	 */
	public function setDirection($direction);
	
	/**
	 * flips the direction
	 * @return this Description
	 */
	public function invert();
	
	/**
	 * Decides what direction the transfermation is going and then returns the new value
	 * @param type $value
	 * @param type $source
	 * @param type $destination
	 */
	public function transform($value, $field, $source, $destination);
	
	/**
	 * When mapping source to destination the source field value is passed in and new value will be expected to be returned
	 * @param type $sourceFieldValue
	 * @param type $source
	 * @param type $destination
	 */
	public function forward($sourceFieldValue, $field, $source, $destination);
	
	/**
	 * When mapping destination to source the destination field value is passed in and new value will be expected to be returned
	 * @param type $destinationFieldValue
	 * @param type $source
	 * @param type $destination
	 */
	public function reverse($destinationFieldValue, $field, $source, $destination);
}