<?php

namespace Rezonant\MapperBundle\Exceptions;

/**
 * 
 */
class UnableToMapException extends \Exception {
	public function __construct($provider, $source, $destination) {
		
		if (is_array($source))
			$sourceType = '<array>';
		else if (is_object($source))
			$sourceType = get_class($source);
		else
			$sourceType = $source;
		
		if (is_array($destination))
			$destinationType = '<array>';
		else if (is_object($destination))
			$destinationType = get_class($destination);
		else
			$destinationType = $destination;
		
		$this->provider = $provider;
		$this->source = $source;
		$this->destination = $destination;
		$this->sourceType = $sourceType;
		$this->destinationType = $destinationType;
		
		parent::__construct("Provider ".get_class($provider)." failed to produce a mapping between '$sourceType' and '$destinationType'");
	}
	
	private $provider;
	private $source;
	private $destination;
	private $sourceType;
	private $destinationType;
	
	function getProvider() {
		return $this->provider;
	}

	function getSource() {
		return $this->source;
	}

	function getDestination() {
		return $this->destination;
	}

	function getSourceType() {
		return $this->sourceType;
	}

	function getDestinationType() {
		return $this->destinationType;
	}
}
