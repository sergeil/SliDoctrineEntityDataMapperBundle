<?php

namespace Sli\DoctrineEntityDataMapperBundle\Mapping\MethodInvocation;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface MethodInvocationParametersProviderInterface
{
    public function getParameters($fqcn, $methodName);
}
