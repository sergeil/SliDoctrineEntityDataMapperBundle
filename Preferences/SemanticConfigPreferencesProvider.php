<?php

namespace Sli\DoctrineEntityDataMapperBundle\Preferences;

/**
 * @see \Sli\DoctrineEntityDataMapperBundle\DependencyInjection\Configuration
 * @see \Sli\DoctrineEntityDataMapperBundle\DependencyInjection\SliDoctrineEntityDataMapperExtension
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class SemanticConfigPreferencesProvider implements PreferencesProviderInterface
{
    private $formatsConfig;

    /**
     * @param array $formatsConfig
     */
    public function __construct(array $formatsConfig)
    {
        $this->formatsConfig = $formatsConfig;
    }

    /**
     * @inheritDoc
     */
    public function get($keyName)
    {
        $mapping = array(
           self::DATE_FORMAT => 'date',
           self::DATETIME_FORMAT => 'datetime',
           self::MONTH_FORMAT => 'month'
        );

        return $this->formatsConfig[$mapping[$keyName]];
    }

    /**
     * @return string
     */
    static public function clazz()
    {
        return get_called_class();
    }
} 