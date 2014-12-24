<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Functional\Mapping;

use Doctrine\ORM\Tools\SchemaTool;
use Modera\FoundationBundle\Testing\FunctionalTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Sli\AuxBundle\Util\Toolkit;
use Sli\DoctrineEntityDataMapperBundle\Mapping\EntityDataMapperService;

/**
 * @ORM\Entity
 * @ORM\Table("sli_user")
 */
class User
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="boolean")
     */
    public $isActive;

    /**
     * @ORM\Column(type="integer")
     */
    public $accessLevel;

    /**
     * @ORM\Column(type="string")
     */
    public $email;

    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users", cascade={"persist"})
     */
    public $groups;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public $fullname;

    public function __construct($email = null, $accessLevel = 0, $isActive = true)
    {
        $this->email = $email;
        $this->accessLevel = $accessLevel;
        $this->isActive = $isActive;

        $this->groups = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $accessLevel
     */
    public function setAccessLevel($accessLevel)
    {
        $this->accessLevel = $accessLevel;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @param mixed $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * @param mixed $isActive
     */
    public function setActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @param mixed $fullname
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
    }
}

/**
 * @ORM\Entity
 * @ORM\Table("sli_group")
 */
class Group
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groups", cascade={"persist"})
     */
    public $users;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;

        $this->users = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class EntityDataMapperServiceTest extends FunctionalTestCase
{
    /* @var SchemaTool $st */
    static private $schemaTool;
    static private $entityClasses = array();

    /* @var EntityDataMapperService $mapper */
    private $mapper;

    // override
    static public function doSetUpBeforeClass()
    {
        Toolkit::addAnnotationMetadataDriverForEntityManager(
            self::$em, __NAMESPACE__, __DIR__
        );

        self::$entityClasses = array(
            self::$em->getClassMetadata(__NAMESPACE__ . '\User'),
            self::$em->getClassMetadata(__NAMESPACE__ . '\Group'),
        );

        self::$schemaTool = new SchemaTool(self::$em);
        self::$schemaTool->createSchema(self::$entityClasses);
    }

    // override
    static public function doTearDownAfterClass()
    {
        self::$schemaTool->dropSchema(self::$entityClasses);
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
}