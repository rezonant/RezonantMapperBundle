<?php

namespace Rezonant\MapperBundle\DependencyInjection;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class RezonantMapperExtension extends Extension {
	
	public function load(array $config, \Symfony\Component\DependencyInjection\ContainerBuilder $container) {
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
	}
	
}