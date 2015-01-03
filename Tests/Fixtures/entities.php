<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\Column(type="string", nullable=true)
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

    /**
     * @ORM\OneToOne(targetEntity="Insurance", inversedBy="user")
     */
    public $insurance;

    /**
     * @ORM\OneToOne(targetEntity="Portfolio")
     */
    public $portfolio;

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

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @ORM\Entity
 * @ORM\Table("sli_portfolio")
 */
class Portfolio
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * Bidirectional
     *
     * @ORM\OneToMany(targetEntity="Project", mappedBy="portfolio")
     */
    public $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @ORM\Entity
 * @ORM\Table("sli_portfolioproject")
 */
class Project
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Portfolio", inversedBy="projects")
     */
    public $portfolio;

    static public function clazz()
    {
        return get_called_class();
    }
}

/**
 * @ORM\Entity
 * @ORM\Table("sli_insurance")
 */
class Insurance
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $type = 'life';

    /**
     * @ORM\OneToOne(targetEntity="User", mappedBy="insurance")
     */
    public $user;

    static public function clazz()
    {
        return get_called_class();
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

    /**
     * Property with no setter method.
     *
     * @ORM\Column(type="integer")
     */
    public $usersCount = 0;

    public function __construct($name)
    {
        $this->name = $name;

        $this->users = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    static public function clazz()
    {
        return get_called_class();
    }
}
 