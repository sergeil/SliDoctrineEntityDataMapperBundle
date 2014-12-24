<?php

namespace Sli\DoctrineEntityDataMapperBundle\Mapping;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface EntityDataMapperInterface
{
    /**
     * @param string $clientValue
     * @param boolean $queryCompatibleMode  If TRUE then a date will be returned in a format compatible with underlying
     *                                      database so it can be properly queried
     *
     * @return null|string|\DateTime
     */
    public function convertDate($clientValue, $queryCompatibleMode = false);

    /**
     * @param string$clientValue
     *
     * @return null|\DateTime
     */
    public function convertDateTime($clientValue);

    /**
     * @param mixed $clientValue
     * @param string $fieldType
     * @param boolean $queryCompatibleMode  If TRUE then a date will be returned in a format compatible with underlying
     *                                      database so it can be properly queried
     *
     * @return mixed
     */
    public function convertValue($clientValue, $fieldType, $queryCompatibleMode = false);

    /**
     * @param string $clientValue
     *
     * @return bool
     */
    public function convertBoolean($clientValue);

    public function mapEntity($entity, array $params, array $allowedFields);
}