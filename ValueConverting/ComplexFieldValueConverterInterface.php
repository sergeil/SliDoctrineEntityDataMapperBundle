<?php

namespace Sli\DoctrineEntityDataMapperBundle\ValueConverting;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * If a field value is an object or an array then registered implementations of this interface
 * will have a chance to convert it to some PHP object.
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */ 
interface ComplexFieldValueConverterInterface
{
    /**
     * @param string $value
     * @param string $fieldName
     * @param ClassMetadataInfo $meta
     *
     * @return boolean
     */
    public function isResponsible($value, $fieldName, ClassMetadataInfo $meta);

    /**
     * @param string $fieldValue
     * @param string $fieldName
     * @param ClassMetadataInfo $meta
     *
     * @return mixed
     */
    public function convert($fieldValue, $fieldName, ClassMetadataInfo $meta);
}
