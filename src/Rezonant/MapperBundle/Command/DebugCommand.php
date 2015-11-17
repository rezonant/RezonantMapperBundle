<?php

namespace Rezonant\MapperBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Rezonant\MapperBundle\Cache\CacheProvider;
use Rezonant\MapperBundle\Providers\MapProviderModerator;
use Rezonant\MapperBundle\Providers\ConfigMapProvider;

class DebugCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this
			->setName('debug:maps')
			->setDescription('Show debug information about manually configured RezonantMapperBundle maps')
			->addArgument('source', InputArgument::OPTIONAL, 'Source type')
			->addArgument('destination', InputArgument::OPTIONAL, 'Destination type')
		;
	}
	
	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		
		$provider = $this->getContainer()->get('rezonant.mapper.map_provider');
		
		if ($provider instanceof CacheProvider) {
			
			$output->writeln("Caching: Active");
			$output->writeln(" - Strategy: ".get_class($provider->getStrategy()));
			$provider = $provider->getProvider();
		} else {
			$output->writeln("Caching: Inactive");
		}
		
		if ($provider instanceof MapProviderModerator) {
			$output->writeln('Moderated map providers:');
			
			foreach ($provider->getProviders() as $provider) {
				$output->writeln(' - '.get_class($provider));
				if ($provider instanceof ConfigMapProvider) {
					$output->writeln('   - Maps');
					foreach ($provider->getMaps() as $key => $map) {
						$output->writeln('     - '.$key);
					}
				}
			}
		} else {
			$output->writeln("Provider: ".get_class($provider));
		}
	}
	
}