<?php

namespace Rezonant\MapperBundle\Utilities;

class Reflector {
	public function describeType($object)
	{
		if (is_null($object))
			return '<null>';
		if (is_string($object))
			return '<string>';
		if (is_array($object))
			return '<array>';
		if (is_object($object))
			return get_class($object);
		
		if (is_int($object))
			return '<int>';
		if (is_float($object))
			return '<float>';
		if (is_bool($object))
			return '<boolean>';
		if (is_long($object))
			return '<long>';
		if (is_resource($object))
			return '<resource>';
		
		return '<unknown>';
	}
}