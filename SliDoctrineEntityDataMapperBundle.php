<?php

namespace Sli\DoctrineEntityDataMapperBundle;

use Sli\ExpanderBundle\Ext\ExtensionPoint;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SliDoctrineEntityDataMapperBundle extends Bundle
{
    // override
    public function build(ContainerBuilder $container)
    {
        $valueConverterProviders = new ExtensionPoint('sli_doctrine_entity_data_mapper.complex_field_value_converters');
        $valueConverterProviders->setDescription('Allows to contribute custom value converters');
        $container->addCompilerPass($valueConverterProviders->createCompilerPass());
    }
}
