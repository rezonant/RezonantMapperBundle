<?php

namespace Rezonant\MapperBundle\Exceptions;

/**
 * 
 */
class FabricationFailedException extends \Exception {
	public function __construct($message) {
		parent::__construct("Failed to fabricate instance: $message");
	}
}
