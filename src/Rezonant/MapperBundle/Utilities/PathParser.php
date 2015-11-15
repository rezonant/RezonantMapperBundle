<?php

namespace Rezonant\MapperBundle\Utilities;

class PathParser {
	
	/**
	 * Parses the given string path into an intermediate representation suitable for use
	 * with the other functions within this service
	 * 
	 * @param string $path A string path like "foo.bar.[baz]"
	 * @return array An array of objects, each object with a 'type' (array or object) 
	 *					  and a 'field' (name of the field)
	 */
	public function parse($path)
	{
		if (!is_string($path))
			throw new \InvalidArgumentException('Parameter $path must be a string');
		
		$split = explode('.', $path);
		$retPath = array();
		
		foreach ($split as $part) {
			
			if ($part[0] == '[') {
				$retPath[] = (object)array(
					'type' => 'array',
					'field' => preg_replace('#^\[(.*)\]$#', '\1', $part)
				);
			} else {
				$retPath[] = (object)array(
					'type' => 'object',
					'field' => $part
				);
			}
		}
		
		return $retPath;
	}
	
}