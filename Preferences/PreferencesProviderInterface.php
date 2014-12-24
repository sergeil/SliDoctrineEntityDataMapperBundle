<?php

namespace Sli\DoctrineEntityDataMapperBundle\Preferences;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface PreferencesProviderInterface
{
    const DATE_FORMAT = 'dateFormat';
    const DATETIME_FORMAT = 'datetimeFormat';
    const MONTH_FORMAT = 'monthFormat';

    /**
     * @param string $keyName
     *
     * @return mixed
     */
    public function get($keyName);
} 