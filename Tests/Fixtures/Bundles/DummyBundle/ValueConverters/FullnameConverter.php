<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Bundles\DummyBundle\ValueConverters;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sli\DoctrineEntityDataMapperBundle\ValueConverting\ComplexFieldValueConverterInterface;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class FullnameConverter implements ComplexFieldValueConverterInterface
{
    /**
     * @inheritDoc
     */
    public function isResponsible($value, $fieldName, ClassMetadataInfo $meta)
    {
        return is_array($value) && isset($value['firstname']) && isset($value['lastname']);
    }

    /**
     * @inheritDoc
     */
    public function convert($fieldValue, $fieldName, ClassMetadataInfo $meta)
    {
        return $fieldValue['firstname'] . ' ' . $fieldValue['lastname'];
    }
} 