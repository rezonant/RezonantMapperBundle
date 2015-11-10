<?php

namespace Rezonant\MapperBundle\Tests;
use Rezonant\MapperBundle\Tests\Fixtures\FixtureA\FixtureA;
use Rezonant\MapperBundle\Tests\Fixtures\FixtureA\Source;
use Rezonant\MapperBundle\Tests\Fixtures\FixtureA\SourceDetails;
use Rezonant\MapperBundle\Tests\Fixtures\FixtureA\Destination;

class MapperTest extends \PHPUnit_Framework_TestCase {
	public function testFromModelBasic()
	{
		$fixture = new FixtureA();
		 
		$source = new Source();
		$source->details = new SourceDetails();
		$source->name = 'foo';
		$source->rank = 123;
		
		$mapper = new \Rezonant\MapperBundle\Mapper(new \Doctrine\Common\Annotations\AnnotationReader());
		$destination = $mapper->map($source, 'Rezonant\MapperBundle\Tests\Fixtures\FixtureA\Destination');
		
		$this->assertEquals('foo', $destination->name123);
		$this->assertEquals('Rezonant\MapperBundle\Tests\Fixtures\FixtureA\SourceDetails', get_class($destination->detailsABC));
		$this->assertEquals(123, $destination->dest->rank);
		
	}
	
	public function testToModelBasic()
	{
		$fixture = new FixtureA();
		$request = array(
			'name' => 'bar',
			'rank123' => 321,
			'details' => array(
				'description' => 'descy',
				'summary' => 'summy'
			)
		);
		
		$mapper = new \Rezonant\MapperBundle\Mapper(new \Doctrine\Common\Annotations\AnnotationReader());
		$model = $mapper->map($request, 'Rezonant\MapperBundle\Tests\Fixtures\FixtureA\Source');
		
		$this->assertEquals('bar', $model->name);
		$this->assertEquals(321, $model->rank);
		$this->assertEquals('Rezonant\MapperBundle\Tests\Fixtures\FixtureA\SourceDetails', get_class($model->details));
		$this->assertEquals('descy', $model->details->getDescription());
		$this->assertEquals('summy', $model->details->getSummary());
		$this->assertEquals('descy', $model->detailsDescription);
		$this->assertEquals('summy', $model->detailsSummary);
		
	}
}