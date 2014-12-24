<?php

namespace Sli\DoctrineEntityDataMapperBundle\DependencyInjection;

use Sli\DoctrineEntityDataMapperBundle\Mapping\EntityDataMapperService;
use Sli\DoctrineEntityDataMapperBundle\Preferences\SemanticConfigPreferencesProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SliDoctrineEntityDataMapperExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setDefinition(
            'sli_doctrine_entity_data_mapper.preferences.semantic_provider',
            new Definition(SemanticConfigPreferencesProvider::clazz(), array($config['preferences_provider_formats']))
        );

        $mapperArguments = array(
            new Reference('doctrine.orm.entity_manager'),
            new Reference($config['preferences_provider']),
            new Reference('sli.aux.javabeans_ofm'),
            new Reference('sli_doctrine_entity_data_mapper.mapping.annotation_method_invocation_parameters_provider'),
            new Reference('sli_doctrine_entity_data_mapper.complex_field_value_converters_provider'),
        );

        $container->setDefinition(
            'sli_doctrine_entity_data_mapper.mapping.entity_data_mapper',
            new Definition(EntityDataMapperService::clazz(), $mapperArguments)
        );

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
