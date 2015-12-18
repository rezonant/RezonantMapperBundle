<?php

namespace Rezonant\MapperBundle\Exceptions;

class TransformationException extends \Exception {
	public function __construct($type) {
		parent::__construct("Transformation Failed: $type");
	}
}
