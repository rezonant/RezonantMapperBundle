<?php

namespace Rezonant\MapperBundle\Annotations;
use Rezonant\MapperBundle\Annotations\Transformation as AnnotationTransformation;
use Rezonant\MapperBundle\Transformation\TransformationInterface as MapTransformationInterface;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class Transformation extends Annotation{
	
	public function applyAnnotation(AnnotationTransformation $annotation, MapTransformationInterface $transformation) {
		//this is were you would take information (if you have any) from the annotation and put it into the transformation
	}
	
	/**
	 * Returns the class, class name or service tag of the tranformation that this annotation applies
	 * @return type
	 */
	public function getTransformation(){
		return $this->value;
	}
}