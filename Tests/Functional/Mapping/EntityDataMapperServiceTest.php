<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Functional\Mapping;

use Doctrine\ORM\Tools\SchemaTool;
use Modera\FoundationBundle\Testing\FunctionalTestCase;
use Sli\AuxBundle\Util\Toolkit;
use Sli\DoctrineEntityDataMapperBundle\Mapping\EntityDataMapperService;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Group;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Insurance;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Portfolio;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Project;
use Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\User;
use Sli\DoctrineEntityDataMapperBundle\Tests\Functional\AbstractFunctionalTestCase;

require_once __DIR__ . '/../../Fixtures/entities.php';

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class EntityDataMapperServiceTest extends AbstractFunctionalTestCase
{
    /* @var EntityDataMapperService $mapper */
    private $mapper;

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

    public function doSetUp()
    {
        $this->mapper = self::$container->get('sli_doctrine_entity_data_mapper.mapping.entity_data_mapper');
    }

    public function testConvertDate()
    {
        $date = $this->mapper->convertDate('21*12*88');

        $this->assertInstanceOf('DateTime', $date);
        $this->assertEquals('21', $date->format('d'));
        $this->assertEquals('12', $date->format('12'));
        $this->assertEquals('88', $date->format('y'));

        $date = $this->mapper->convertDate('21*12*88', true);

        $this->assertEquals('1988-12-21', $date);
    }

    public function testConvertDateTime()
    {
        // d*m*y H:i
        $date = $this->mapper->convertDateTime('21*12*88 15:14');

        $this->assertInstanceOf('DateTime', $date);
        $this->assertEquals('21', $date->format('d'));
        $this->assertEquals('12', $date->format('12'));
        $this->assertEquals('88', $date->format('y'));
        $this->assertEquals('15', $date->format('G'));
        $this->assertEquals('14', $date->format('i'));
    }

    public function testConvertBoolean()
    {
        $this->assertTrue($this->mapper->convertBoolean(1));
        $this->assertTrue($this->mapper->convertBoolean('on'));
        $this->assertTrue($this->mapper->convertBoolean('true'));
        $this->assertTrue($this->mapper->convertBoolean(true));


        $this->assertFalse($this->mapper->convertBoolean(0));
        $this->assertFalse($this->mapper->convertBoolean('off'));
        $this->assertFalse($this->mapper->convertBoolean('false'));
        $this->assertFalse($this->mapper->convertBoolean(false));
    }

    public function testMapEntity_onlyScalars()
    {
        $user = new User();
        $userParams = array(
            'email' => 'john.doe@example.org',
            'isActive' => 'off',
            'accessLevel' => '5'
        );

        $this->mapper->mapEntity($user, $userParams, array_keys($userParams));

        $this->assertEquals($userParams['email'], $user->email);
        $this->assertFalse($user->isActive);
        $this->assertEquals(5, $user->accessLevel);
    }

    public function testMapEntity_BidirectionalOneToOne()
    {
        // bidirectional
        $insurance = new Insurance();
        $user = new User('jane.doe@example.org');

        self::$em->persist($insurance);
        self::$em->persist($user);
        self::$em->flush();

        $params = array(
            'insurance' => $insurance->id
        );

        $this->mapper->mapEntity($user, $params, array_keys($params));

        $this->assertInstanceOf(Insurance::clazz(), $user->insurance);
        $this->assertInstanceOf(User::clazz(), $insurance->user);

        // nulling:

        $params = array(
            'insurance' => '-'
        );

        $this->mapper->mapEntity($user, $params, array_keys($params));

        $this->assertNull($user->insurance);
        $this->assertNull($insurance->user);

        // when it is already nulled and it is attempted to null it again:

        $this->mapper->mapEntity($user, $params, array_keys($params));

        $this->assertNull($user->insurance);
        $this->assertNull($insurance->user);
    }

    public function testMapEntity_IndirectionalOneToOne()
    {
        $user = new User();
        $portfolio = new Portfolio();

        self::$em->persist($user);
        self::$em->persist($portfolio);
        self::$em->flush();

        $params = array(
            'portfolio' => $portfolio->id
        );

        $this->mapper->mapEntity($user, $params, array_keys($params));

        $this->assertInstanceOf(Portfolio::clazz(), $user->portfolio);
        $this->assertEquals($portfolio->id, $user->portfolio->id);

        // nulling:

        $params = array(
            'portfolio' => '-'
        );

        $this->mapper->mapEntity($user, $params, array_keys($params));

        $this->assertNull($user->portfolio);
    }

    public function testMapEntity_manyToOne()
    {
        $portfolio1 = new Portfolio();
        $portfolio2 = new Portfolio();
        $project1 = new Project();
        $project2 = new Project();

        $portfolio1->projects->add($project1);
        $project1->portfolio = $portfolio1;

        $portfolio1->projects->add($project2);
        $project2->portfolio = $portfolio1;

        self::$em->persist($portfolio1);
        self::$em->persist($portfolio2);
        self::$em->persist($project1);
        self::$em->persist($project2);
        self::$em->flush();

        // portfolio2 <-> project1, project2

        $params = array(
            'portfolio' => $portfolio2->id
        );

        $this->mapper->mapEntity($project1, $params, array_keys($params));

        $this->assertEquals($project1->portfolio->id, $portfolio2->id);
        $this->assertEquals(1, count($portfolio1->projects));
        $this->assertEquals(1, count($portfolio2->projects));

        // nulling:

        $params = array(
            'portfolio' => '-'
        );

        $this->mapper->mapEntity($project1, $params, array_keys($params));

        $this->assertNull($project1->portfolio);
        $this->assertEquals(0, count($portfolio2->projects));

        // nulling something which is already nulled:

        $params = array(
            'portfolio' => '-'
        );

        $this->mapper->mapEntity($project1, $params, array_keys($params));
    }

    public function testMapEntity_oneToMany()
    {
        $portfolio = new Portfolio();

        $project1 = new Project();
        $project2 = new Project();

        self::$em->persist($portfolio);
        self::$em->persist($project1);
        self::$em->persist($project2);
        self::$em->flush();

        $params = array(
            'projects' => array($project1->id)
        );

        $this->mapper->mapEntity($portfolio, $params, array_keys($params));

        $this->assertEquals(1, count($portfolio->projects));
        $this->assertNotNull($project1->portfolio);
        $this->assertEquals($portfolio->id, $project1->portfolio->id);
    }

    public function testMapEntity_manyToMany()
    {
        $adminsGroup = new Group('Admins');
        $moderatorsGroup = new Group('Moderators');
        $usersGroup = new Group('Users');

        self::$em->persist($adminsGroup);
        self::$em->persist($moderatorsGroup);
        self::$em->persist($usersGroup);
        self::$em->flush();

        // --- with 'inversedBy'

        $user = new User('john.doe@example.org');
        $user->groups->add($moderatorsGroup);
        $user->groups->add($usersGroup);
        $moderatorsGroup->users->add($user);
        $usersGroup->users->add($user);

        $usersParams = array(
            'groups' => array($adminsGroup->id)
        );

        $this->mapper->mapEntity($user, $usersParams, array_keys($usersParams));

        $this->assertEquals(1, count($user->groups));
        $userGroups = $user->groups->getValues();
        $this->assertEquals($adminsGroup->getId(), $userGroups[0]->getId());
        $this->assertEquals(0, count($moderatorsGroup->users));
        $this->assertEquals(0, count($usersGroup->users));

        // --- with 'mappedBy'

        $jane = new User('jane.doe@example.org');
        $john = new User('john.doe@example.org');

        self::$em->persist($jane);
        self::$em->persist($john);
        self::$em->flush();

        $adminsGroup->users->clear();
        $moderatorsGroup->users->clear();
        $usersGroup->users->clear();

        $adminsGroup->users->add($jane);
        $adminsGroup->users->add($john);
        $jane->groups->add($adminsGroup);
        $john->groups->add($adminsGroup);

        $groupParams = array(
            'users' => array($john->getId())
        );

        $this->mapper->mapEntity($adminsGroup, $groupParams, array_keys($groupParams));

        $this->assertEquals(1, count($adminsGroup->users));
        $adminUsers = $adminsGroup->users->getValues();
        $this->assertEquals($john->getId(), $adminUsers[0]->getId());
        $this->assertEquals(0, count($jane->groups));
    }

    // see \Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures\Bundles\DummyBundle\Contributions\ConvertersProvider
    // see Tests/Fixtures/Bundles/DummyBundle/Resources/config/services.xml
    public function testMapEntity_complexConverters()
    {
        $user = new User();

        $userParams = array(
            'fullname' => array(
                'firstname' => 'John',
                'lastname' => 'Doe'
            )
        );

        $this->mapper->mapEntity($user, $userParams, array_keys($userParams));

        $this->assertEquals('John Doe', $user->fullname);
    }

    public function testMapEntity_propertyWithNoSetterMethod()
    {
        $group = new Group('Foo');

        $groupParams = array(
            'usersCount' => 5
        );

        $this->mapper->mapEntity($group, $groupParams, array_keys($groupParams));

        $this->assertEquals(5, $group->usersCount);
    }
}