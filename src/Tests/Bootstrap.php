<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

if ( ! is_file($autoloadFile = __DIR__.'/../../vendor/autoload.php'))
	throw new \RuntimeException('You must run \'composer install\'');
require_once $autoloadFile;
AnnotationRegistry::registerLoader('class_exists');