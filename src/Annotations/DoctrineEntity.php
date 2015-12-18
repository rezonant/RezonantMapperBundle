<?php
namespace Rezonant\MapperBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class DoctrineEntity extends Annotation {
	public $id = 'id';
}