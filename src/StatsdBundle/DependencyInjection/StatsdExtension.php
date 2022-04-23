<?php

namespace StatsdBundle\DependencyInjection;

use StatsdBundle\Client\StatsdAPIClient;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class StatsdExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $serviceDefinition = $container->getDefinition(StatsdAPIClient::class);
        $serviceDefinition->replaceArgument(0, $config['client']['host']);
        $serviceDefinition->replaceArgument(1, $config['client']['port']);
        $serviceDefinition->replaceArgument(2, $config['client']['namespace']);
    }
}
