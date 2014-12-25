<?php

namespace Sli\DoctrineEntityDataMapperBundle\Mapping\MethodInvocation;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface MethodInvocationParametersProviderInterface
{
    /**
     * @param string $className
     * @param string $methodName
     *
     * @return array
     */
    public function getParameters($className, $methodName);
}
