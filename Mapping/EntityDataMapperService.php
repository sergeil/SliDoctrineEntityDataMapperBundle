<?php

namespace Sli\DoctrineEntityDataMapperBundle\Mapping;

use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Sli\DoctrineEntityDataMapperBundle\Mapping\MethodInvocation\MethodInvocationParametersProviderInterface;
use Sli\DoctrineEntityDataMapperBundle\Preferences\PreferencesProviderInterface;
use Sli\DoctrineEntityDataMapperBundle\ValueConverting\ComplexFieldValueConverterInterface;
use Sli\ExpanderBundle\Ext\ContributorInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadataInfo as CMI;
use Sli\AuxBundle\Util\Toolkit;
use Sli\AuxBundle\Util\JavaBeansObjectFieldsManager;
use Doctrine\Common\Collections\Collection;

/**
 * Service is responsible for inspect the data that usually comes from client-side and update the database. All
 * relation types supported by Doctrine are supported by this service as well - ONE_TO_ONE, ONE_TO_MANY,
 * MANY_TO_ONE, MANY_TO_MANY. Service is capable to properly update all relation types ( owning, inversed-side )
 * even when entity classes do not define them. Also this service is smart enough to properly cast provided
 * values to the types are defined in doctrine mappings, that is - if string "10.2" is provided, but the field
 * it was provided for is mapped as "float", then the conversion to float value will be automatically done - this is
 * especially useful if your setter method have some logic not just assigning a new value to a class field.
 *
 * In order for this class to work, your security principal ( implementation of UserInterface ),
 * must implement {@class PreferencesAwareUserInterface}.
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class EntityDataMapperService implements EntityDataMapperInterface
{
    private $em;
    private $preferencesProvider;
    private $fm;
    private $paramsProvider;
    private $complexFieldValueConvertersProvider;

    public function __construct(
        EntityManager $em, PreferencesProviderInterface $preferencesProvider,
        JavaBeansObjectFieldsManager $fm, MethodInvocationParametersProviderInterface $paramsProvider,
        ContributorInterface $complexFieldValueConvertersProvider
    )
    {
        $this->em = $em;
        $this->preferencesProvider = $preferencesProvider;
        $this->fm = $fm;
        $this->paramsProvider = $paramsProvider;
        $this->complexFieldValueConvertersProvider = $complexFieldValueConvertersProvider;
    }

    /**
     * @param string $keyName
     *
     * @return mixed
     */
    protected function getPreferencesValue($keyName)
    {
        return $this->preferencesProvider->get($keyName);
    }

    /**
     * @throws \RuntimeException
     *
     * @inheritDoc
     */
    public function convertDate($clientValue, $queryCompatibleMode = false)
    {
        if ($clientValue != '') {
            $format = $this->getPreferencesValue(PreferencesProviderInterface::DATE_FORMAT);

            $rawClientValue = $clientValue;
            $clientValue = \DateTime::createFromFormat($format, $clientValue);
            if (!$clientValue) {
                throw new \RuntimeException(
                    "Unable to map a date, unable to transform date-value of '$rawClientValue' to '$format' format."
                );
            }

            if ($queryCompatibleMode) {
                // querying won't work properly if query "date" type field by using instance of \DateTime object
                // because the latter contains information about time which we don't really need for "date" fields
                return $clientValue->format(
                    $this->em->getConnection()->getDatabasePlatform()->getDateFormatString()
                );
            }

            return $clientValue;
        }

        return null;
    }

    /**
     * @throws \RuntimeException
     *
     * @inheritDoc
     */
    public function convertDateTime($clientValue)
    {
        if ($clientValue != '') {
            $format = $this->getPreferencesValue(PreferencesProviderInterface::DATETIME_FORMAT);

            $rawClientValue = $clientValue;
            $clientValue = \DateTime::createFromFormat($format, $clientValue);
            if (!$clientValue) {
                throw new \RuntimeException(
                    "Unable to map a datetime, unable to transform date-value of '$rawClientValue' to '$format' format."
                );
            }
            return $clientValue;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function convertValue($clientValue, $fieldType, $queryCompatibleMode = false)
    {
        switch ($fieldType) {
            case 'boolean':
                return $this->convertBoolean($clientValue);
            case 'date':
                return $this->convertDate($clientValue, $queryCompatibleMode);
            case 'datetime':
                return $this->convertDateTime($clientValue);
        }

        return $clientValue;
    }

    /**
     * @inheritDoc
     */
    public function convertBoolean($clientValue)
    {
        return 'on' === $clientValue || 1 == $clientValue || 'true' === $clientValue;
    }

    /**
     * Be aware, that "id" property will never be mapped to you entities even if it is provided
     * in $params, we presume that it always be generated automatically.
     *
     * @throws \RuntimeException
     *
     * @param Object $entity
     * @param array $params  Data usually received from client-side
     * @param array $allowedFields  Fields names you want to allow have mapped
     */
    public function mapEntity($entity, array $params, array $allowedFields)
    {
        $entityMethods = get_class_methods($entity);
        $metadata = $this->em->getClassMetadata(get_class($entity));

        foreach ($metadata->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $allowedFields) || 'id' == $fieldName) { // ID is always generated dynamically
                continue;
            }

            if (isset($params[$fieldName])) {
                $value = $params[$fieldName];
                $mapping = $metadata->getFieldMapping($fieldName);

                $isNumber = in_array($mapping['type'], array('integer', 'smallint', 'bigint', 'decimal', 'float'));

                // if a field is number and at the same time its value was not provided,
                // then we are not touching it at all, if the model has specified
                // a default value for it - fine, everything's gonna be fine, otherwise
                // Doctrine will look if this field isNullable etc ... and throw
                // an exception if needed
                if (!($isNumber && '' === $value)) {
                    try {
                        $setterMethodName = $this->fm->formatSetterName($fieldName);

                        $methodParams = array();
                        if (in_array($setterMethodName, $entityMethods)) {
                            $methodParams = $this->paramsProvider->getParameters(
                                get_class($entity), $this->fm->formatSetterName($fieldName)
                            );
                        }

                        $convertedValue = null;
                        if (is_object($value) || is_array($value)) {
                            foreach ($this->complexFieldValueConvertersProvider->getItems() as $converter) {
                                /* @var ComplexFieldValueConverterInterface $converter */
                                if ($converter->isResponsible($value, $fieldName, $metadata)) {
                                    $convertedValue = $converter->convert($value, $fieldName, $metadata);
                                }
                            }
                        } else {
                            $convertedValue = $this->convertValue($value, $mapping['type']);
                        }

                        $this->setFieldValue($entity, $fieldName, array_merge(array($convertedValue), $methodParams));
                    } catch (\Exception $e) {
                        throw new \RuntimeException(
                            "Something went wrong during mapping of a scalar field '$fieldName' - ".$e->getMessage(), null, $e
                        );
                    }
                }
            }
        }

        foreach ($metadata->getAssociationMappings() as $mapping) {
            $fieldName = $mapping['fieldName'];

            if (!in_array($fieldName, $allowedFields)) {
                continue;
            }

            if (isset($params[$fieldName])) {
                $rawValue = $params[$fieldName];

                $setterMethodName = $this->fm->formatSetterName($fieldName);
                $isSetterExist = in_array($setterMethodName, $entityMethods);
                $resetRequired = '-' == $rawValue;

                $thisSideRelation = new Relation($entity, $fieldName, $this->em);

                if ($thisSideRelation->isToOne()) {
                    if ($isSetterExist) {
                        $methodParams = $this->paramsProvider->getParameters(
                            get_class($entity), $this->fm->formatSetterName($fieldName)
                        );

                        if ($resetRequired) {
                            $methodParams = array_merge(array(null), $methodParams);
                        }

                        $this->fm->set($entity, $fieldName, $methodParams);
                    } else {
                        if ($thisSideRelation->isBidirectional()) {
                            $otherSideRelation = $thisSideRelation->getAssociatedFieldRelation();
                            $otherSideRelationValue = $thisSideRelation->getValue($entity);

                            if ($resetRequired) {
                                // only if entity exists then
                                if ($otherSideRelationValue) {
                                    if ($thisSideRelation->isOneToOne()) {
                                        // nulling the other side of relation
                                        $otherSideRelation->setValue($otherSideRelationValue, null);
                                    } else { // many_to_one
                                        $otherSideRelationValue = $thisSideRelation->getValue($entity);

                                        /* @var Collection $thisSideCollection */
                                        $thisSideCollection = $otherSideRelation->getValue($otherSideRelationValue);
                                        $thisSideCollection->removeElement($entity);
                                    }
                                }

                                $thisSideRelation->setValue($entity, null);
                            } else {
                                $referencedEntity = $thisSideRelation->findTargetEntity($rawValue, $this->em);

                                if ($thisSideRelation->isOneToOne()) {
                                    $otherSideRelation->setValue($referencedEntity, $entity);
                                } else { // many_to_one
                                    $otherSideRelationValue = $thisSideRelation->getValue($entity);

                                    // removing from old collection:
                                    /* @var Collection $oldCollection */
                                    $oldCollection = $otherSideRelation->getValue($otherSideRelationValue);
                                    $oldCollection->removeElement($entity);

                                    // and adding to a new one:
                                    /* @var Collection $newCollection */
                                    $newCollection = $otherSideRelation->getValue($referencedEntity);
                                    $newCollection->add($entity);
                                }

                                $thisSideRelation->setValue($entity, $referencedEntity);
                            }
                        } else {
                            $newValue = $resetRequired ? null : $thisSideRelation->findTargetEntity($rawValue, $this->em);

                            $thisSideRelation->setValue($entity, $newValue);
                        }
                    }
                } else { // one_to_many, many_to_many
                    $thisSideCollection = $metadata->getFieldValue($entity, $fieldName);

                    // if it is a new entity (you should remember, the entity's constructor is not invoked)
                    // it will have no collection initialized, because this usually happens in the constructor
                    if (!$thisSideCollection) {
                        $thisSideCollection = new ArrayCollection();

                        $metadata->setFieldValue($entity, $fieldName, $thisSideCollection);
                    }

                    $oldIds = Toolkit::extractIds($thisSideCollection);
                    $newIds = is_array($rawValue) ? $rawValue : explode(', ', $rawValue);
                    $idsToDelete = array_diff($oldIds, $newIds);
                    $idsToAdd = array_diff($newIds, $oldIds);

                    $entitiesToDelete = $this->getEntitiesByIds($idsToDelete, $mapping['targetEntity']);
                    $entitiesToAdd = $this->getEntitiesByIds($idsToAdd, $mapping['targetEntity']);

                    $thisSideRelation = new Relation($entity, $fieldName, $this->em);

                    /*
                     * At first it will be checked if removeXXX/addXXX methods exist, if they
                     * do, then they will be used, otherwise we will try to manage
                     * relation manually
                     */
                    $removeMethod = 'remove' . ucfirst(Inflector::singularize($fieldName));
                    if (in_array($removeMethod, $entityMethods) && count($idsToDelete) > 0) {
                        foreach ($entitiesToDelete as $refEntity) {
                            $methodParams = array_merge(
                                array($refEntity),
                                $this->paramsProvider->getParameters(get_class($entity), $removeMethod)
                            );
                            call_user_func_array(array($entity, $removeMethod), $methodParams);
                        }
                    } else {
                        foreach ($entitiesToDelete as $refEntity) {
                            if ($thisSideCollection->contains($refEntity)) {
                                $thisSideCollection->removeElement($refEntity);

                                if (CMI::MANY_TO_MANY == $mapping['type']) {
                                    $otherSideCollection = $thisSideRelation->getAssociatedFieldRelation()->getValue($refEntity);
                                    $otherSideCollection->removeElement($entity);
                                } else { // ONE_TO_MANY
                                    // nulling the OTHER SIDE of relation
                                    $this->setFieldValue($refEntity, $mapping['mappedBy'], array(null));
                                }
                            }
                        }
                    }

                    $addMethod = 'add' . ucfirst(Inflector::singularize($fieldName));
                    if (in_array($addMethod, $entityMethods) && count($idsToAdd) > 0) {
                        foreach ($entitiesToAdd as $refEntity) {
                            $methodParams = array_merge(
                                array($refEntity),
                                $this->paramsProvider->getParameters(get_class($entity), $addMethod)
                            );
                            call_user_func_array(array($entity, $addMethod), $methodParams);
                        }
                    } else {
                        foreach ($entitiesToAdd as $refEntity) {
                            if (!$thisSideCollection->contains($refEntity)) {
                                $thisSideCollection->add($refEntity);

                                if (CMI::MANY_TO_MANY == $mapping['type']) {
                                    $otherSideCollection = $thisSideRelation->getAssociatedFieldRelation()->getValue($refEntity);
                                    $otherSideCollection->add($entity);
                                } else {
                                    $this->setFieldValue($refEntity, $mapping['mappedBy'], array($entity));
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function setFieldValue($object, $fieldName, $value)
    {
        $methodName = $this->fm->formatSetterName($fieldName);

        if (in_array($methodName, get_class_methods(get_class($object)))) {
            $this->fm->set($object, $fieldName, $value);
        } else {
            // when a method exists then we need a value to be array, but when we directly inject
            // a value to a field then it is not needed
            Toolkit::setPropertyValue($object, $fieldName, $value[0]);
        }
    }

    private function getEntitiesByIds(array $ids, $entityClass)
    {
        if (count($ids) == 0) {
            return array();
        }

        return $this->em->getRepository($entityClass)->findBy(array(
            'id' => $ids
        ));
    }

    static public function clazz()
    {
        return get_called_class();
    }
}