<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Functional;

use Doctrine\ORM\Tools\SchemaTool;
use Modera\FoundationBundle\Testing\FunctionalTestCase;
use Sli\AuxBundle\Util\Toolkit;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Group;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Insurance;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Portfolio;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Project;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\User;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class AbstractFunctionalTestCase extends FunctionalTestCase
{
    /* @var SchemaTool $st */
    static private $schemaTool;
    static private $entityClasses = array();

    static protected function createTables()
    {
        $ns = 'Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures';

        Toolkit::addAnnotationMetadataDriverForEntityManager(
            self::$em, $ns, realpath(__DIR__ . '/../../Fixtures/')
        );

        self::$entityClasses = array(
            self::$em->getClassMetadata(User::clazz()),
            self::$em->getClassMetadata(Group::clazz()),
            self::$em->getClassMetadata(Insurance::clazz()),
            self::$em->getClassMetadata(Portfolio::clazz()),
            self::$em->getClassMetadata(Project::clazz()),
        );

        self::$schemaTool = new SchemaTool(self::$em);
        self::$schemaTool->dropSchema(self::$entityClasses);
        self::$schemaTool->createSchema(self::$entityClasses);
    }

    static protected function dropTables()
    {
        self::$schemaTool->dropSchema(self::$entityClasses);
    }
} 