<?php

namespace Rezonant\MapperBundle\Tests;
use Rezonant\MapperBundle\Tests\Fixtures\FixtureA as FixtureA;
use Rezonant\MapperBundle\Tests\Fixtures\FixtureB as FixtureB;
use Rezonant\MapperBundle\Tests\Fixtures\FixtureC as FixtureC;
use Rezonant\MapperBundle\Mapper;
use Rezonant\MapperBundle\Providers\AnnotationMapProvider;
use Rezonant\MapperBundle\Map\Reference;

class MapperTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * @return \Rezonant\MapperBundle\Mapper
	 */
	private function makeMapper()
	{		
		return new Mapper(new AnnotationMapProvider(new \Doctrine\Common\Annotations\AnnotationReader()));
	}
	
	public function testFromModelBasic()
	{
		$fixture = new FixtureA\FixtureA();
		 
		$source = new FixtureA\Source();
		$source->details = new FixtureA\SourceDetails();
		$source->name = 'foo';
		$source->rank = 123;
		
		$mapper = $this->makeMapper();
		$destination = $mapper->map($source, 'Rezonant\MapperBundle\Tests\Fixtures\FixtureA\Destination');
		
		$this->assertEquals('foo', $destination->name123);
		$this->assertEquals('Rezonant\MapperBundle\Tests\Fixtures\FixtureA\SourceDetails', get_class($destination->detailsABC));
		$this->assertEquals(123, $destination->dest->rank);
		
	}
	
	public function testFromModelInvertedBasic()
	{
		$fixture = new FixtureA\FixtureA();
		 
		$source = new FixtureA\Source();
		$source->details = new FixtureA\SourceDetails();
		$source->name = 'foo';
		$source->rank = 123;
		
		$mapper = $this->makeMapper();
		$intermediate = $mapper->map($source, 'Rezonant\MapperBundle\Tests\Fixtures\FixtureA\Destination');
		$destination = $mapper->mapBack($intermediate, 'Rezonant\MapperBundle\Tests\Fixtures\FixtureA\Source');
		
		$this->assertEquals('foo', $destination->name);
		$this->assertEquals('Rezonant\MapperBundle\Tests\Fixtures\FixtureA\SourceDetails', get_class($destination->details));
		$this->assertEquals(123, $destination->rank);
		
	}
	
	public function testAssociativeAssignment()
	{
		$fixture = new FixtureB\FixtureB();
		
		$source = new FixtureB\Source();
		$source->name = 'foo';
		$source->rank = 123;
		
		$mapper = $this->makeMapper();
		$dest = $mapper->map($source, 'Rezonant\MapperBundle\Tests\Fixtures\FixtureB\Destination');
		
		//print_r($dest); die('shit');
		
		$this->assertEquals('foo', $dest->bits['name123']);
		$this->assertEquals(123, $dest->bits['rank123']);
	}
	
	public function testBasicFreeMapping()
	{
		$fixture = new FixtureB\FixtureB();
		
		$source = array(
			'a' => 123,
			'b' => 321
		);
		
		$mapper = $this->makeMapper();
		$mapBuilder = new \Rezonant\MapperBundle\MapBuilder();
		$mapBuilder->field(new Reference('a'), new Reference('c'));
		$mapBuilder->field(new Reference('b'), new Reference('d'));
		
		$dest = $mapper->map($source, array(), $mapBuilder->build());
		
		$this->assertTrue(is_array($dest));
		$this->assertEquals(123, $dest['c']);
		$this->assertEquals(321, $dest['d']);
	}
	
	public function testBasicObjectFreeMapping()
	{
		$fixture = new FixtureB\FixtureB();
		
		$source = (object)array(
			'a' => 123,
			'b' => 321
		);
		
		$mapper = $this->makeMapper();
		$mapBuilder = new \Rezonant\MapperBundle\MapBuilder();
		$mapBuilder->field(new Reference('a'), new Reference('c'));
		$mapBuilder->field(new Reference('b'), new Reference('d'));
		
		$dest = $mapper->map($source, (object)array(), $mapBuilder->build());
		
		$this->assertTrue(is_object($dest));
		$this->assertEquals(123, $dest->c);
		$this->assertEquals(321, $dest->d);
	}
	
	public function testBasicObjectToArrayFreeMapping()
	{
		$fixture = new FixtureB\FixtureB();
		
		$source = (object)array(
			'a' => 123,
			'b' => 321
		);
		
		$mapper = $this->makeMapper();
		$mapBuilder = new \Rezonant\MapperBundle\MapBuilder();
		$mapBuilder->field(new Reference('a'), new Reference('c'));
		$mapBuilder->field(new Reference('b'), new Reference('d'));
		
		$dest = $mapper->map($source, array(), $mapBuilder->build());
		
		$this->assertTrue(is_array($dest));
		$this->assertEquals(123, $dest['c']);
		$this->assertEquals(321, $dest['d']);
	}
	
	public function testBasicArrayToObjectFreeMapping()
	{
		$fixture = new FixtureB\FixtureB();
		
		$source = array(
			'a' => 123,
			'b' => 321
		);
		
		$mapper = $this->makeMapper();
		$mapBuilder = new \Rezonant\MapperBundle\MapBuilder();
		$mapBuilder->field(new Reference('a'), new Reference('c'));
		$mapBuilder->field(new Reference('b'), new Reference('d'));
		
		$dest = $mapper->map($source, (object)array(), $mapBuilder->build());
		
		$this->assertTrue(is_object($dest));
		$this->assertEquals(123, $dest->c);
		$this->assertEquals(321, $dest->d);
	}
	
	public function testTransformationForwardMapping(){
		
		$fixture = new FixtureC\FixtureC();
		
		
		$source = new FixtureC\Source();
		$source->sPower = 2; //sq
		$source->sRank = 3; //*6
		$source->sLevel = 7; //sq
		$source->sDescription = "things";

//		$destination = new FixtureC\Destination();
//		$destination->dPower;
//		$destination->dRank;
//		$destination->dLevel;
//		$destination->dDescription;
		
		$squareTransformation = new FixtureC\SquaredTransformation();
		$pruductTransformation = new FixtureC\ProductTransformation();
		$pruductTransformation->setMultiply(6);
		
		
		$mapper = $this->makeMapper();
		$mapBuilder = new \Rezonant\MapperBundle\MapBuilder();
		$mapBuilder->field(new Reference('sPower'), new Reference('dPower'), null, $squareTransformation);
		$mapBuilder->field(new Reference('sRank'), new Reference('dRank'), null, $pruductTransformation);
		$mapBuilder->field(new Reference('sLevel'), new Reference('dLevel'), null, $squareTransformation);
		
		$destination = $mapper->map($source, new FixtureC\Destination(), $mapBuilder->build());
		
		$this->assertTrue(is_object($destination));
		$this->assertEquals(4, $destination->getDPower());
		$this->assertEquals(18, $destination->getDRank());
		$this->assertEquals(49, $destination->getDLevel());
	}
	
	
	public function testTransformationReverseMapping(){
		
		$fixture = new FixtureC\FixtureC();

		$destination = new FixtureC\Destination();
		$destination->setDPower(9);//sq
		$destination->setDRank(78);//*6
		$destination->setDLevel(25);//sq
		$destination->setDDescription('things');
		
		$squareTransformation = new FixtureC\SquaredTransformation();
		$pruductTransformation = new FixtureC\ProductTransformation();
		$pruductTransformation->setMultiply(6);
		
		
		$mapper = $this->makeMapper();
		$mapBuilder = new \Rezonant\MapperBundle\MapBuilder();
		$mapBuilder->field(new Reference('sPower'), new Reference('dPower'), null, $squareTransformation);
		$mapBuilder->field(new Reference('sRank'), new Reference('dRank'), null, $pruductTransformation);
		$mapBuilder->field(new Reference('sLevel'), new Reference('dLevel'), null, $squareTransformation);
		
		$map = $mapBuilder->build()->invert();
		
		//var_dump($map->getFields());
		
		$destination = $mapper->map($destination, new FixtureC\Source(), $map);
		
		$this->assertTrue(is_object($destination));
		$this->assertEquals(3, $destination->sPower);
		$this->assertEquals(13, $destination->sRank);
		$this->assertEquals(5, $destination->sLevel);
	}
}