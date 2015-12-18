<?php
namespace Rezonant\MapperBundle\Transformation;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Rezonant\MapperBundle\Map\Reference;
use Rezonant\MapperBundle\Exceptions\TransformationException;

/**
 * DoctrineEntityTransformation
 *
 * @author Nathan Erickon
 */
class DoctrineEntityTransformation extends AbstractTransformation {
	
	/**
	 *
	 * @var Registry
	 */
	private $doctrine = null;
	
	
	private $entityClass = null;
	
	private $id = null;
	
	
	public function __construct(Registry $doctrine) {
		$this->doctrine = $doctrine;
	}
	
	public function setEntityClass($entityClass){
		$this->entityClass = $entityClass;
	}
	
	public function getEntityClass(){
		return $this->entityClass;
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	/**
	 * et entity assuming the value is the id and then return it
	 * @param type $sourceFieldValue
	 * @param Reference $source
	 * @param Reference $destination
	 * @return type
	 * @throws \Exception
	 */
	public function forward($sourceFieldValue, $field, $source, $destination) {
		$entityClass = $this->getEntityClass();
		
		if(!$entityClass){
			$entityClass = get_class($destination);
		}
		
		$repository = $this->doctrine->getRepository($entityClass);
		if(!$repository){
			throw new TransformationException('Could not resolve doctrine entity from destination type');
		}
		return $repository->find($sourceFieldValue);
	}
	
	//get the primary key from the entity assuming the value is the entity
	//might want to look up the primary key with the annotation reader
	public function reverse($destinationFieldValue, $field, $source, $destination) {
		if(!$destinationFieldValue){
			return null;
		}
		
		$fieldName = $this->getId();
		
		if(!$fieldName){
			throw new TransformationException("Doctrine Transformation failed because the id was not set correctly.");
		}
		
		$methodName = "get$fieldName";
		
		if (method_exists($destinationFieldValue, $methodName)){
			return $destinationFieldValue->$methodName();
		} else if (property_exists ($destinationFieldValue, $fieldName)){
			return $destinationFieldValue->$fieldName;
		}

		return null;
	}
}

