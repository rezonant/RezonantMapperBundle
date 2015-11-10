<?php

namespace Rezonant\MapperBundle\Tests\Fixtures\FixtureA;
use Rezonant\MapperBundle\Annotations as Mapper;

class FixtureA { }
	class Source {
		/**
		 * @Mapper\MapTo("name123")
		 * @Mapper\FromRequest()
		 * @var string
		 */
		public $name;
		
		/**
		 * @Mapper\FromRequest("rank123")
		 * @Mapper\MapTo("dest.rank")
		 * @var int
		 */
		public $rank;
		
		/**
		 * @Mapper\Type("Rezonant\MapperBundle\Tests\Fixtures\FixtureA\SourceDetails")
		 * @Mapper\MapTo("detailsABC")
		 * @Mapper\FromRequest()
		 * @var SourceDetails
		 */
		public $details;
		
		/**
		 * @Mapper\FromRequest("details.summary")
		 */
		public $detailsSummary;
		
		/**
		 * @Mapper\FromRequest("details.description")
		 */
		public $detailsDescription;
	}

	class SourceDetails {
		/**
		 * @Mapper\FromRequest()
		 */
		private $description;
		
		/**
		 * @Mapper\FromRequest()
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