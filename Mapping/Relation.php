<?php

namespace Sli\DoctrineEntityDataMapperBundle\Mapping;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class Relation 
{
    private $entityClassName;
    private $associationPropertyName;
    private $em;

    /* @var ClassMetadataInfo $entityMeta */
    private $entityMeta;
    private $fieldMapping = array();

    public function __construct($entityOrClassName, $associationPropertyName, EntityManager $em)
    {
        $this->entityClassName = is_object($entityOrClassName) ? get_class($entityOrClassName) : $entityOrClassName;
        $this->associationPropertyName = $associationPropertyName;
        $this->em = $em;

        $this->initIfNeeded();
    }

    private function initIfNeeded()
    {
        if (!$this->entityMeta || !$this->fieldMapping) {
            $this->entityMeta = $this->getMetadataFor($this->entityClassName);
            $this->fieldMapping = $this->entityMeta->getAssociationMapping($this->associationPropertyName);;
        }
    }

    public function getPropertyName()
    {
        return $this->associationPropertyName;
    }

    public function getEntityClassName()
    {
        return $this->entityClassName;
    }

    public function getValue($entity)
    {
        return $this->entityMeta->getFieldValue($entity, $this->associationPropertyName);
    }

    public function setValue($entity, $value)
    {
        $this->entityMeta->setFieldValue($entity, $this->associationPropertyName, $value);
    }

    public function isBidirectional()
    {
        try {
            $thisSide = $this;
            $otherSide = $this->getAssociatedFieldRelation();
            $thisSideFromOther = $otherSide->getAssociatedFieldRelation();


            return    $thisSide->getEntityClassName() == $thisSideFromOther->getEntityClassName()
                   && $thisSide->getPropertyName() == $thisSideFromOther->getPropertyName();
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    public function isToOne()
    {
        return $this->isManyToOne() || $this->isOneToOne();
    }

    public function isManyToOne()
    {
        return ClassMetadataInfo::MANY_TO_ONE == $this->fieldMapping['type'];
    }

    public function isOneToMany()
    {
        return ClassMetadataInfo::ONE_TO_MANY == $this->fieldMapping['type'];
    }

    public function isOneToOne()
    {
        return ClassMetadataInfo::ONE_TO_ONE == $this->fieldMapping['type'];
    }

    public function isManyToMany()
    {
        return ClassMetadataInfo::MANY_TO_MANY == $this->fieldMapping['type'];
    }

    /**
     * @param string $className
     *
     * @return ClassMetadataInfo
     */
    private function getMetadataFor($className)
    {
        return $this->em->getClassMetadata($className);
    }

    public function getAssociatedFieldRelation()
    {
        $refEntityPropName = null !== $this->fieldMapping['inversedBy']
                             ? $this->fieldMapping['inversedBy']
                             : $this->fieldMapping['mappedBy'];

        if (!$refEntityPropName) {
            throw new \RuntimeException();
        }

        return new Relation($this->fieldMapping['targetEntity'], $refEntityPropName, $this->em);
    }

    public function findTargetEntity($id)
    {
        return $this->em->getRepository($this->fieldMapping['targetEntity'])->find($id);
    }

    static public function clazz()
    {
        return get_called_class();
    }
} 