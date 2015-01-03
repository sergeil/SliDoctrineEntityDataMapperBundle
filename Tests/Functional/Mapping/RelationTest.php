<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Functional\Mapping;

use Sli\DoctrineEntityDataMapperBundle\Mapping\Relation;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Insurance;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\User;
use Sli\DoctrineEntityDataMapperBundle\Tests\Functional\AbstractFunctionalTestCase;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class RelationTest extends AbstractFunctionalTestCase
{
    // override
    static public function doSetUpBeforeClass()
    {
        self::createTables();
    }

    // override
    static public function doTearDownAfterClass()
    {
        self::dropTables();
    }

    public function testGetAssociatedFieldRelation()
    {
        $relation = new Relation(User::clazz(), 'insurance', self::$em);

        $this->assertEquals('insurance', $relation->getPropertyName());

        $refRelation = $relation->getAssociatedFieldRelation();

        $this->assertInstanceOf(Relation::clazz(), $refRelation);
        $this->assertEquals('user', $refRelation->getPropertyName());
        $this->assertEquals(Insurance::clazz(), $refRelation->getEntityClassName());
    }

    public function testIsBidirectional()
    {
        $relation1 = new Relation(User::clazz(), 'insurance', self::$em);

        $this->assertTrue($relation1->isBidirectional());

        $relation2 = new Relation(User::clazz(), 'portfolio', self::$em);

        $this->assertFalse($relation2->isBidirectional());
    }

    public function testFindTargetEntity()
    {
        $insurance = new Insurance();

        self::$em->persist($insurance);
        self::$em->flush();

        $relation = new Relation(User::clazz(), 'insurance', self::$em);

        $this->assertInstanceOf(Insurance::clazz(), $relation->findTargetEntity($insurance->id));
    }
}