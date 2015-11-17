<?php

namespace Rezonant\MapperBundle\Tests\Fixtures\FixtureA;
use Rezonant\MapperBundle\Annotations as Mapper;

class FixtureA { }
	class Source {
		/**
		 * @Mapper\MapTo("name123")
		 * @var string
		 */
		public $name;
		
		/**
		 * @Mapper\MapTo("dest.rank")
		 * @var int
		 */
		public $rank;
		
		/**
		 * @Mapper\Type("Rezonant\MapperBundle\Tests\Fixtures\FixtureA\SourceDetails")
		 * @Mapper\MapTo("detailsABC")
		 * @var SourceDetails
		 */
		public $details;
		
		/**
		 */
		public $detailsSummary;
		
		/**
		 */
		public $detailsDescription;
	}

	class SourceDetails {
		/**
		 */
		private $description;
		
		/**
		 */
		private $summary;
		
		private $rank;
		
		function getRank() {
			return $this->rank;
		}

		function setRank($rank) {
			$this->rank = $rank;
		}

				
		function getDescription() {
			return $this->description;
		}

		function getSummary() {
			return $this->summary;
		}

		function setDescription($description) {
			$this->description = $description;
		}

		function setSummary($summary) {
			$this->summary = $summary;
		}


		
	}

	class Destination {
		public $name123;
		public $detailsABC;
		
		/**
		 * @Mapper\Type("Rezonant\MapperBundle\Tests\Fixtures\FixtureA\DestinationBits")
		 */
		public $dest;
	}
	
	class DestinationBits {
		public $rank;
	}