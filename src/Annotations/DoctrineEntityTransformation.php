<?php
namespace Rezonant\MapperBundle\Annotations;
use Rezonant\MapperBundle\Annotations\Transformation as AnnotationTransformation;
use Rezonant\MapperBundle\Transformation\TransformationInterface as MapTransformationInterface;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class DoctrineEntityTransformation extends Transformation {
	public $id = 'id';
	
	public function applyAnnotation(AnnotationTransformation $annotation, MapTransformationInterface $transformation) {
		$transformation->setEntityClass($annotation->value);
		$transformation->setId($annotation->id);
	}
	
	/**
	 * Returns the class name, class, or service tag of the tranformation that this annotation applies
	 * @return type
	 */
	public function getTransformation(){
		return 'rezonant.mapper.doctrine_entity_transformation';
	}
}