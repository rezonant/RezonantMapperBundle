<?php

namespace Rezonant\MapperBundle\Tests\Fixtures\FixtureB;
use Rezonant\MapperBundle\Annotations as Mapper;

class FixtureB { }
	class Source {
		/**
		 * @Mapper\MapTo("bits.name123")
		 * @var string
		 */
		public $name;
		
		/**
		 * @Mapper\MapTo("bits.rank123")
		 * @var string
		 */
		public $rank;
	}

	class Destination {
		/**
		 * @var array
		 * @Mapper\Type("<array>")
		 */
		public $bits = array();
	}