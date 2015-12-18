<?php
namespace Rezonant\MapperBundle\Tests\Fixtures\FixtureC;
use Rezonant\MapperBundle\Annotations as Mapper;
use Rezonant\MapperBundle\Annotations\Transformation as TransformationAnnotation;
use Rezonant\MapperBundle\Transformation\AbstractTransformation;
use Doctrine\Common\Annotations\Annotation;

class FixtureC { }
	class Source {
		/**
		 * @Mapper\MapTo("dPower")
		 * @Mapper\Transformation("Rezonant\MapperBundle\Tests\Fixtures\FixtureC\SquaredTransformation")
		 * @var string
		 */
		public $sPower;
		
		/**
		 * @Mapper\MapTo("dName")
		 * @Rezonant\MapperBundle\Tests\Fixtures\FixtureC\ProductTransformationAnnotaion("3")
		 * @var string
		 */
		public $sRank;
		
		/**
		 * @Mapper\MapTo("dName")
		 * @Mapper\Transformation("Rezonant\MapperBundle\Tests\Fixtures\FixtureC\SquaredTransformation")
		 * @var string
		 */
		public $sLevel;
		
		public $sDescription;
	}

	class Destination {

		private $dPower;
		
		private $dRank;
		
		private $dLevel;
		
		private $dDescription;
		
		public function getDPower() {
			return $this->dPower;
		}

		public function getDRank() {
			return $this->dRank;
		}

		public function getDLevel() {
			return $this->dLevel;
		}

		public function getDDescription() {
			return $this->dDescription;
		}

		public function setDPower($dPower) {
			$this->dPower = $dPower;
		}

		public function setDRank($dRank) {
			$this->dRank = $dRank;
		}

		public function setDLevel($dLevel) {
			$this->dLevel = $dLevel;
		}

		public function setDDescription($dDescription) {
			$this->dDescription = $dDescription;
		}
		
	}
	
	class SquaredTransformation extends AbstractTransformation {
		public function forward($sourceFieldValue, $field, $source, $destination){
			return pow($sourceFieldValue, 2);
		}

		public function reverse($destinationFieldValue, $field, $source, $destination){
			return sqrt($destinationFieldValue);
		}
	}

	/**
	* @Annotation
	*/
	class ProductTransformationAnnotaion extends TransformationAnnotation {
		public $multiply = 0;
	
		public function parseAnnotation(AnnotationTransformation $annotation, MapTransformationInterface $transformation) {
			$transformation->setMultiply($annotation->multiply);
		}
		
		public function getTransformation(){
			return new ProductTransformation();
		}
	}
	
	class ProductTransformation extends AbstractTransformation {
		private $multiply = 0;
		
		public function getMultiply() {
			return $this->multiply;
		}

		public function setMultiply($multiply) {
			$this->multiply = $multiply;
		}

				
		public function forward($sourceFieldValue, $field, $source, $destination){
			return ($sourceFieldValue * $this->getMultiply());
		}

		public function reverse($destinationFieldValue, $field, $source, $destination){
			return ($destinationFieldValue / $this->getMultiply());
		}
	}