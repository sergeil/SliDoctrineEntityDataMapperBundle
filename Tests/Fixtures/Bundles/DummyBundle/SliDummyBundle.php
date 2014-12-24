<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Bundles\DummyBundle;

use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Bundles\DummyBundle\DependencyInjection\SliDummyExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class SliDummyBundle extends Bundle
{
    // override
    public function build(ContainerBuilder $container)
    {
        $container->registerExtension(new SliDummyExtension());
    }
}
