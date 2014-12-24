<?php

namespace Sli\DoctrineEntityDataMapperBundle\Mapping\MethodInvocation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Params extends Annotation
{
    static public function clazz()
    {
        return get_called_class();
    }
}