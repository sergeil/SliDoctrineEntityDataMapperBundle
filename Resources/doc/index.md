# Contents
 * [Data binding procedure](#data-binding-procedure)
 * [How ManyToOne, OneToOne relations are handled](#many-to-relations)
 * [How OneToMany, ManyToMany relations are handled](#to-many-relations)
 * [Converting complex values before mapping them onto entities](#complex-values)

## <a name="data-binding-procedure"></a>Data binding procedure

To bind a piece of data several steps are taken, to make it easier to explain the point let's say that we have
a User entity which looks like this (ORM mapping is omitted here) and we need to bind a value for its 'username'
field:

```php
class User
{
    private $username;

    public function setUsername($username)
    {
        $this->username = $username;
    }
}
```

At first, the EntityDataMapperService will look if a setter method exists (method name is formatted with respect
to JavaBeans convention, in this case the service will generate `setUsername` method name), if it exists, then it will
be used, but if it is not found, then the service will use reflection to access a possibly non-public class property and
inject a value directly.

Another great feature that the EntityDataMapperService supports out of the box is container services injection when
a setter method is invoked, by default annotation is used to instruct the mapping service what container services
you want to have injected. Let's revise our class a little to leverage the service injection feature:

```php
use Sli\DoctrineEntityDataMapperBundle\Mapping\MethodInvocation\Params as InjectParams;

class User
{
    private $username;

    /**
     * @InjectParams({"my_company.username_validator_service"})
     */
    public function setUsername($username, UsernameValidatorServiceInterface $validator)
    {
        $validator->checkIfUnique($username);

        $this->username = $username;
    }
}
```

With this annotation in place when `setUsername` method is be invoked by the mapping service it will detect that
additional services must be injected. It is worth mentioning that you may also instruct the mapping service that
a given service is optional, for this to happen you need to suffix the service name with `*`:

```php
/**
 * @InjectParams({"my_company.username_validator_service*"})
 */
public function setUsername($username, UsernameValidatorServiceInterface $validator = null)
{
    if ($validator) {
        $validator->checkIfUnique($username);
    }

    $this->username = $username;
}
```

It is apparent that the illustrated approach is not a best way to check for username uniqueness, but it made it easy
to explain the point.

## <a name="many-to-relations"></a>How ManyToOne, OneToOne relations are handled

As it was already mentioned, when binding data/managing relations EntityDataMapperService at first will try
to use mutator methods that you have possible provided for your entities, because oftentimes these methods
aside from traditional fields value updating might also contain some business logic so using them is very important.

At first let's consider mapping of entity `Insurance` which relates to our `User` entity
as OneToOne.

```php
class User
{
    /**
     * @ORM\OneToOne(targetEntity="Insurance")
     */
    private $insurance;
}
```

Now imagine, that for an instance of `User` entity we want to set an `Insurance`:

```php
$params = array(
    'insurance' => 1
);
$dataMapper->mapEntity($user, $params, array_keys($params));
```

From now on two things might happen depending either the relation is bidirectional or not. In our case, the relation
is not bidirectional, so what the mapping service will just find an Insurance record with ID 1 and set it to `User`'s
`insurance` field. Now, for a second let's imagine that this relation is bidirectional and `Insurance` entity has
`user` field which points back to an instance of a `User` this `Insurance` entity belongs to. In this case, the mapping
service would also update the inverse side of relation, **even if you haven't provided smart enough mutator methods for
it**, the mapping service is smart enough to figure this out for you.

Before wrapping up this section is important to mention that if you want to NULL a `ManyToOne`, `OneToOne` side
of relation, then you can use `-` character, for example:

```php
$params = array(
    'insurance' => '-'
);
$dataMapper->mapEntity($user, $params, array_keys($params));
```

## <a name="to-many-relations"></a>How OneToMany, ManyToMany relations are handled

Now let's take a look at more sophisticated relation types, OneToMany and ManyToMany.

Say, that we have a `Project` entity which might have many `Project`s associated with it and they are mapped like
this (primary key field mapping is omitted here):

```php
class Portfolio
{
    /**
     * @ORM\OneToMany(targetEntity="Project", mappedBy="portfolio")
     */
    private $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }
}

class Project
{
    /**
     * @ORM\ManyToOne(targetEntity="Portfolio", inversedBy="projects")
     */
    public $portfolio;
}
```

Also imagine that we have a Portfolio with ID 1 and several Projects with IDs 1, 2, 3:

```php
$portfolio = ne Portfolio();

$project1 = new Project();
$project2 = new Project();
$project3 = new Project();

$portfolio->getProjects()->add($project1);
$project1->portfolio = $portfolio;

foreach ([$portfolio, $project1, $project2, $project3] as $entity) {
    $em->persist($entity);
}
$em->flush();
```

Now let's look at some possible scenarios and what `EntityDataMapperService` would do under the hood.

```php
$params = array(
    'projects' => [$project2->getId(), $project3->getId()]
);

$dataMapper->mapEntity($portfolio, $params, array_keys($params));
```

In this case we are telling the mapping service that we want that our `Portfolio` entity would be associated with
two `Project`s. But as you should remember, our `Portfolio` entity is already associated with a `Project` with ID 1.
In this case the mapping service will remove `Project` with ID 1 from a `Portfolio::$projects` collection and set
`Project::$portfolio` field of removed entity to `NULL` and then add two other entities to that collection.
In other words - in-memory state of your entities will be properly managed even if you haven't provided any mutator
methods. But it is important to mention that if `Portfolio` entity would have had method with name `removeProject`,
`addProject` they would have been used instead (and by the way, you can also use the `Params` annotation which is
described in [Data binding procedure](#data-binding-procedure) section). Not bad, huh ?

Last and the most sophisticated relation type is `ManyToMany`. To illustrate what the mapping service will do when
it encounters such a relation type let's use a classical example of `User`s and `Group`s:

```php
class User
{
    /**
     * @ManyToMany(targetEntity="Group", inversedBy="users")
     */
    private $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}

class Group
{
    /**
     * @ManyToMany(targetEntity="User", mappedBy="groups")
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
}
```

And say that we have some data pre-loaded in database:

```php
$user1 = new User(); // ID 1
$user2 = new User(); // ID 2

$group = new Group(); // ID 1

// manually setting up a relation
$group->getUsers()->add($user1);
$user1->getGroups()->add($group);
```

And we want to use the data mapper service to do something similar to this:

```php
$params = array(
    'users' => [$user2->getId()]
);

$dataMapper->mapEntity($group, $params, array_keys($params));
```

This what the data mapper service will do for you in this case:

* Figure out that `$group` entity already contains a `User` with ID 1, remove it from `Group::$users` collection and
  remove `$group` entity instance from the `User::$groups` collection.
* Take the `$group` and add a `User` with ID 2 to `Group::$users` collection and also add `$group` to `Users::$groups`
  collection.

Everything mentioned about remove/add methods for managing collection also applies when you are dealing with `ManyToMany`
relation type.

## <a name="complex-values></a>Converting complex values before mapping them onto entities

Sometimes you want to apply some pre-processing before a value is mapped onto an entity. For example, say that
we have a `User` entity with a string field `fullname` and from client-side we receive an array similar to this
one containing first name and last name of our `User`:

```php
$params = array(
    'fullname' => array(
        'firstname' => 'John',
        'lastname' => 'Doe'
    )
);

$dataMapper->mapEntity($user, $params, array_keys($params));
```

And what we want to do is that before the entity is mapped to `User::$fullname` field it would be converted to
its string representation, that is - `John Doe`. For this to happen you need to do several things:

 * Create an implementation of \Sli\DoctrineEntityDataMapperBundle\ValueConverting\ComplexFieldValueConverterInterface
   interface
 * Make it possible for the data-mapper to discover it

Creating an implementation of `ComplexFieldValueConverterInterface` is pretty straightforward because it contains
just two methods:

```php
namespace MyCompany\SampleBundle\ComplexValueConverters;

class FullnameConverter implements ComplexFieldValueConverterInterface
{
    public function isResponsible($value, $fieldName, ClassMetadataInfo $meta)
    {
        return is_array($value) && isset($value['firstname']) && isset($value['lastname']);
    }

    public function convert($fieldValue, $fieldName, ClassMetadataInfo $meta)
    {
        return $fieldValue['firstname'] . ' ' . $fieldValue['lastname'];
    }
}
```

Next thing we need to do is to make it possible for the data mapper to discover our converter. For contributions
discovery SliDoctrineEntityDataMapperBundle relies on [SliExpanderBundle](https://github.com/sergeil/ExpanderBundle)
bundle. Please refer to its documentation for more information how you can contribute to extension points, but in
case when you need to contribute many converts at once you may use approach when you register a so called
*batch contributor* by creating an implementation of `\Sli\ExpanderBundle\Ext\ContributorInterface`:

```php
namespace MyCompany\SampleBundle\Contributions;

class ComplexFieldValuesConvertersProvider
{
    public function getItems()
    {
        return [
            new \MyCompany\SampleBundle\ComplexValueConverters\FullnameConverter()
        ];
    }
}
```

and registering it in service container with a tag
 *sli_doctrine_entity_data_mapper.complex_field_value_converters_provider*:

```xml
<service id="mycompany_sample.contributions.complex_values_converters_provider"
         class="MyCompany\SampleBundle\Contributions\ComplexFieldValuesConvertersProvider">

    <tag name="sli_doctrine_entity_data_mapper.complex_field_value_converters_provider" />
</service>
```

That is it, now when a data-mapper detects that an Object or an array is provided for a field to that it
has to map it will iterate all available implementations of ComplexFieldValueConverterInterface, invoke
their `isResponsible` method and if it returns `TRUE` then it will be used to convert the value which will eventually
be mapped.
