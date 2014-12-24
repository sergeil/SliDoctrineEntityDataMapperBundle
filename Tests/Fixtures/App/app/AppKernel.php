<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Sli\AuxBundle\SliAuxBundle(),
            new \Sli\ExpanderBundle\SliExpanderBundle($this),

            new \Modera\FoundationBundle\ModeraFoundationBundle(),

            new \Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Bundles\DummyBundle\SliDummyBundle(),

            new \Sli\DoctrineEntityDataMapperBundle\SliDoctrineEntityDataMapperBundle()
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config.yml');
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir() . '/SliDoctrineEntityDataMapperBundle/cache';
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return sys_get_temp_dir() . '/SliDoctrineEntityDataMapperBundle/logs';
    }
}
