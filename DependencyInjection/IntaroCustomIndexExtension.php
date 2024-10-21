<?php

namespace Intaro\CustomIndexBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class IntaroCustomIndexExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__) . '/config'));
        $loader->load('di.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('intaro.custom_index.search_in_all_schemas', $config['search_in_all_schemas']);
        $container->setParameter('intaro.custom.index.allowed_index_types', $config['allowed_index_types']);
    }
}
