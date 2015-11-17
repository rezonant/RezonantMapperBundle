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
				->arrayNode('caching')
					->children()
						->booleanNode('enabled')
							->defaultFalse()
						->end()
						->scalarNode('strategy')
							->defaultValue('\Rezonant\MapperBundle\Cache\Strategies\MemoryCacheStrategy')
						->end()
					->end()
				->end()
				->arrayNode('providers')
					->children()
						->arrayNode('annotations')
							->children()
								->booleanNode('enabled')
									->defaultTrue()
								->end()
							->end()
						->end()
						->arrayNode('config')
							->children()
								->booleanNode('enabled')
									->defaultTrue()
								->end()
							->end()
						->end()
						->arrayNode('custom')
						->end()
					->end()
				->end()
				->arrayNode('maps')
					->prototype('array')
						->children()
							->scalarNode('source')->end()
							->scalarNode('destination')->end()
							->arrayNode('fields')
								->children()
									->scalarNode('from')->end()
									->scalarNode('to')->end()
									->scalarNode('type')->end()
									->scalarNode('types')->end()
									->scalarNode('map')->end()

								->end()
							->end()
						->end()
					->end()
				->end()
			->end();
		
		return $treeBuilder;
	}
}