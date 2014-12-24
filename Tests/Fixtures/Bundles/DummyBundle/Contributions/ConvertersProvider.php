<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Bundles\DummyBundle\Contributions;

use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Bundles\DummyBundle\ValueConverters\FullnameConverter;
use Sli\ExpanderBundle\Ext\ContributorInterface;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ConvertersProvider implements ContributorInterface
{
    /**
     * @inheritDoc
     */
    public function getItems()
    {
        return array(
            new FullnameConverter()
        );
    }
} 