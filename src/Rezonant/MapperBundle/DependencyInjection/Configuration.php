<?php

namespace Rezonant\MapperBundle\DependencyInjection;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {
	public function getConfigTreeBuilder() 
	{
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rezonant_mapper');

		$rootNode
			->children()
				->arrayNode('maps')
					->children()
						->scalarNode('source')->end()
						->scalarNode('destination')->end()
						->arrayNode('fields')
							->children()
								->scalarNode('from')->end()
								->scalarNode('to')->end()
	}
}