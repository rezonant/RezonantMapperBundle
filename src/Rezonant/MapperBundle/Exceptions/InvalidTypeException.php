<?php

namespace Rezonant\MapperBundle\Exceptions;

class InvalidTypeException extends \Exception {
	public function __construct($type) {
		parent::__construct("Invalid type: $type");
	}
}
