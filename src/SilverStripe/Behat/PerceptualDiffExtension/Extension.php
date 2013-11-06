<?php

namespace SilverStripe\Behat\PerceptualDiffExtension;

use Zodyac\Behat\PerceptualDiffExtension\Extension as BaseExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class Extension extends BaseExtension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources'));
        $loader->load('services.yml');
    }

    public function getCompilerPasses()
    {
        return array();
    }
}